<?php

declare(strict_types=1);

namespace RVxLab\CronlessScheduler\EventLoop;

use Carbon\CarbonImmutable;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Scheduling\{Event, Schedule};
use Illuminate\Console\View\Components\Factory as Output;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Revolt\EventLoop;
use WeakMap;

final class SchedulerEventLoop
{
    /** @var WeakMap<Event, CarbonImmutable> */
    private WeakMap $seenEvents;

    private string $scheduleTickerId;

    public function __construct(
        private readonly Application $app,
        private readonly Schedule $schedule,
        private readonly Dispatcher $dispatcher,
        private readonly Output $components,
    ) {
        $this->seenEvents = new WeakMap();
    }

    public function run(): never
    {
        $this->scheduleTickerId = EventLoop::repeat(1, function (): void {
            foreach ($this->getDueEvents() as $event) {
                if ($this->shouldDispatchEvent($event)) {
                    $this->components->info($event->getSummaryForDisplay());

                    $this->seenEvents[$event] = CarbonImmutable::now();

                    continue;
                }

                $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));
            }
        });

        EventLoop::onSignal(SIGINT, function (): never {
            EventLoop::cancel($this->scheduleTickerId);
            $this->components->error('Boom!');
            exit(0);
        });

        EventLoop::run();

        exit(0);
    }

    /**
     * @return Collection<int, Event>
     */
    private function getDueEvents(): Collection
    {
        return $this->schedule->dueEvents($this->app);
    }

    private function shouldDispatchEvent(Event $event): bool
    {
        // If the event is not passing the cron expression, don't run
        if (!$event->filtersPass($this->app)) {
            return false;
        }

        // If the event is repeatable and has been run within the last n seconds, run
        if ($event->isRepeatable() && !$this->wasEventRunRecently($event, (int) $event->repeatSeconds)) {
            return true;
        }

        // If the event has already been run within the last 60 seconds, don't run
        return !$this->wasEventRunRecently($event, 60);
    }

    private function wasEventRunRecently(Event $event, int $seconds): bool
    {
        if (1 === $seconds) {
            return false;
        }

        $lastSeen = $this->seenEvents[$event] ?? null;

        if (null === $lastSeen) {
            return false;
        }

        $secondsSinceLastRun = CarbonImmutable::now()->diffInSeconds($lastSeen, true);

        return $secondsSinceLastRun < $seconds;
    }
}
