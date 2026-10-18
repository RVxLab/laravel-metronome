<?php

declare(strict_types=1);

namespace RVxLab\Metronome\EventLoop;

use Exception;
use Illuminate\Console\Events\{ScheduledTaskFailed, ScheduledTaskFinished, ScheduledTaskStarting};
use Illuminate\Console\Scheduling\{CallbackEvent, Event};
use Illuminate\Console\View\Components\Factory as Output;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\{Carbon};
use Revolt\EventLoop;
use RVxLab\Metronome\Schedule\{EventFetcher, EventTracker};
use Throwable;

final class SchedulerEventLoop
{
    private string $scheduleTickerId;

    public function __construct(
        private readonly Application $app,
        private readonly string $phpBinary,
        private readonly Dispatcher $dispatcher,
        private readonly ExceptionHandler $exceptionHandler,
        private readonly Output $components,
        private readonly EventFetcher $eventFetcher,
        private readonly EventTracker $eventTracker,
    ) {}

    public function run(float $tickRate): never
    {
        $this->scheduleTickerId = EventLoop::repeat($tickRate, function (): void {
            foreach ($this->eventFetcher->getDispatchableEvents() as $event) {
                // Queue up the event for dispatching. After queueing, we set that the event was "run" so
                // it cannot be dispatched twice on accident. Then after it was dispatched, we set the
                // run time again properly so that jobs don't re-run before they should
                EventLoop::queue(function (Event $event): void {
                    $this->dispatchEvent($event);

                    $this->eventTracker->markEventRun($event);
                }, $event);

                $this->eventTracker->markEventRun($event);
            }
        });

        $stop = function (): never {
            EventLoop::cancel($this->scheduleTickerId);
            $this->app->terminate();
            exit(0);
        };

        EventLoop::onSignal(SIGINT, $stop);
        EventLoop::onSignal(SIGTERM, $stop);

        EventLoop::run();

        exit(0);
    }

    private function dispatchEvent(Event $event): void
    {
        $summary = $event->getSummaryForDisplay();

        $command = $event instanceof CallbackEvent
            ? $summary
            : mb_trim(str_replace($this->phpBinary, '', (string) $event->command));

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
