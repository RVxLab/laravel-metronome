<?php

declare(strict_types=1);

namespace RVxLab\Metronome\EventLoop;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Console\Events\{ScheduledTaskFailed, ScheduledTaskFinished, ScheduledTaskSkipped, ScheduledTaskStarting};
use Illuminate\Console\Scheduling\{CallbackEvent, Event, Schedule};
use Illuminate\Console\View\Components\Factory as Output;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\{Carbon, Collection};
use Revolt\EventLoop;
use RVxLab\Metronome\Validation\EventDispatchValidator;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use WeakMap;

final class SchedulerEventLoop
{
    /** @var WeakMap<Event, int> */
    private WeakMap $runEvents;

    private string $scheduleTickerId;

    public function __construct(
        private readonly Application $app,
        private readonly string $phpBinary,
        private readonly Schedule $schedule,
        private readonly Dispatcher $dispatcher,
        private readonly ExceptionHandler $exceptionHandler,
        private readonly Output $components,
        private readonly EventDispatchValidator $validator,
    ) {
        $this->runEvents = new WeakMap();
    }

    public function run(float $tickRate): never
    {
        $this->scheduleTickerId = EventLoop::repeat($tickRate, function (): void {
            foreach ($this->getDueEvents() as $event) {
                $lastRun = $this->getLastRunForEvent($event);
                $now = CarbonImmutable::now();

                if (!$this->validator->canDispatch($event, $lastRun, $now)) {
                    $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                    continue;
                }

                // Use mutex->exists() rather than shouldSkipDueToOverlapping() here.
                // shouldSkipDueToOverlapping() calls mutex->create(), which acquires the lock,
                // and when event->run() is later called it also calls shouldSkipDueToOverlapping()
                // internally. Since the lock is already held, it returns true and bails out early
                // without setting exitCode, causing a spurious failure exception.
                if ($event->withoutOverlapping && $event->mutex->exists($event)) {
                    $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                    continue;
                }

                // Queue up the event for dispatching. After queueing, we set that the event was "run" so
                // it cannot be dispatched twice on accident. Then after it was dispatched, we set the
                // run time again properly so that jobs don't re-run before they should
                EventLoop::queue(function (Event $event): void {
                    $this->dispatchEvent($event);

                    $this->runEvents[$event] = CarbonImmutable::now()->getTimestampMs();
                }, $event);

                $this->runEvents[$event] = CarbonImmutable::now()->getTimestampMs();
            }
        });

        $stop = function (): never {
            EventLoop::cancel($this->scheduleTickerId);
            $this->app->terminate();
            $this->components->warn('Stopping scheduler...', OutputInterface::VERBOSITY_VERBOSE);
            exit(0);
        };

        EventLoop::onSignal(SIGINT, $stop);
        EventLoop::onSignal(SIGTERM, $stop);

        EventLoop::run();

        exit(0);
    }

    /**
     * Type-safe wrapper around Schedule::dueEvents
     *
     * @return Collection<int, Event>
     *
     * @see Schedule::dueEvents()
     */
    private function getDueEvents(): Collection
    {
        return $this->schedule->dueEvents($this->app);
    }

    /**
     * Get the last run timestamp for the given event, if it was run before
     */
    private function getLastRunForEvent(Event $event): ?CarbonImmutable
    {
        $lastRunTimestampMs = $this->runEvents[$event] ?? null;

        if (null === $lastRunTimestampMs) {
            return null;
        }

        return CarbonImmutable::createFromTimestampMs($lastRunTimestampMs);
    }

    private function dispatchEvent(Event $event): void
    {
        $summary = $event->getSummaryForDisplay();

        $command = $event instanceof CallbackEvent
            ? $summary
            : trim(str_replace($this->phpBinary, '', (string) $event->command));

        $description = sprintf(
            '<fg=gray>%s</> Running [%s]%s',
            Carbon::now()->format('Y-m-d H:i:s'),
            $command,
            $event->runInBackground ? ' in background' : '',
        );

        $this->components->task($description, function () use ($event): bool {
            $this->dispatcher->dispatch(new ScheduledTaskStarting($event));

            $start = microtime(true);

            try {
                $event->run($this->app);

                $this->dispatcher->dispatch(new ScheduledTaskFinished(
                    $event,
                    round(microtime(true) - $start, 2),
                ));

                // Non-background tasks that exit non-zero are treated as failures.
                // Background tasks manage their own exit codes independently.
                if (null !== $event->exitCode && 0 !== $event->exitCode && !$event->runInBackground) {
                    throw new Exception(sprintf('Scheduled command [%s] failed with exit code [%s].', $event->command, $event->exitCode));
                }
            } catch (Throwable $throwable) {
                $this->dispatcher->dispatch(new ScheduledTaskFailed($event, $throwable));

                $this->exceptionHandler->report($throwable);
            }

            return null === $event->exitCode || 0 === $event->exitCode;
        });

        if (!$event instanceof CallbackEvent) {
            $this->components->bulletList([
                $event->getSummaryForDisplay(),
            ]);
        }
    }
}
