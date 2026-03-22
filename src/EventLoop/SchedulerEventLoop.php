<?php

declare(strict_types=1);

namespace RVxLab\CronlessScheduler\EventLoop;

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
use RVxLab\CronlessScheduler\Validation\EventDispatchValidator;
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

                if ($event->shouldSkipDueToOverlapping()) {
                    $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                    continue;
                }

                EventLoop::queue(function (Event $event): void {
                    $this->dispatchEvent($event);

                    // Set the run event properly right after execution
                    $this->runEvents[$event] = CarbonImmutable::now()->getTimestampMs();
                }, $event);

                // Set the run event so that it can't be picked up twice
                $this->runEvents[$event] = CarbonImmutable::now()->getTimestampMs();
            }
        });

        EventLoop::onSignal(SIGINT, function (): never {
            EventLoop::cancel($this->scheduleTickerId);
            exit(0);
        });

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

                if (0 !== $event->exitCode && !$event->runInBackground) {
                    throw new Exception(sprintf('Scheduled command [%s] failed with exit code [%s].', $event->command, $event->exitCode));
                }
            } catch (Throwable $throwable) {
                $this->dispatcher->dispatch(new ScheduledTaskFailed($event, $throwable));

                $this->exceptionHandler->report($throwable);
            }

            return 0 === $event->exitCode;
        });

        if (!$event instanceof CallbackEvent) {
            $this->components->bulletList([
                $event->getSummaryForDisplay(),
            ]);
        }
    }
}
