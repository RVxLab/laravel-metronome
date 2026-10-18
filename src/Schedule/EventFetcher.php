<?php

declare(strict_types=1);

namespace RVxLab\Metronome\Schedule;

use Carbon\CarbonImmutable;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Console\Scheduling\{Event, Schedule};

final readonly class EventFetcher
{
    public function __construct(
        private Application $app,
        private Schedule $schedule,
        private Dispatcher $dispatcher,
        private EventTracker $tracker,
        private EventDispatchValidator $dispatchValidator,
    ) {}

    /** @return iterable<int, Event> */
    public function getDispatchableEvents(): iterable
    {
        foreach ($this->getDueEvents() as $event) {
            $lastRun = $this->tracker->getLastRunForEvent($event);
            $now = CarbonImmutable::now();

            $canDispatch = $this->dispatchValidator->canDispatch($event, $lastRun, $now);

            /**
             * Normally you'd check if the event should be skipped due to overlap using
             * `Event::shouldSkipDueToOverlapping`, but that method also creates the
             * mutex under the hood. Because `shouldSkipDueToOverlapping` is called again later
             * it causes a false positive on the skip check
             */
            $isLocked = $event->withoutOverlapping && $event->mutex->exists($event);

            $shouldSkip = !$canDispatch || $isLocked;

            if ($shouldSkip) {
                $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                continue;
            }

            yield $event;
        }
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
}
