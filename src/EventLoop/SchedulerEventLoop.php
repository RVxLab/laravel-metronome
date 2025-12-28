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
use RVxLab\CronlessScheduler\Validation\EventDispatchValidator;
use WeakMap;

final class SchedulerEventLoop
{
    /** @var WeakMap<Event, int> */
    private WeakMap $runEvents;

    private string $scheduleTickerId;

    public function __construct(
        private readonly Application $app,
        private readonly Schedule $schedule,
        private readonly Dispatcher $dispatcher,
        private readonly Output $components,
        private readonly EventDispatchValidator $validator,
    ) {
        $this->runEvents = new WeakMap();
    }

    public function run(): never
    {
        $this->scheduleTickerId = EventLoop::repeat(1, function (): void {
            foreach ($this->getDueEvents() as $event) {
                $lastRun = $this->getLastRunForEvent($event);
                $now = CarbonImmutable::now();

                if ($this->validator->canDispatch($event, $lastRun, $now)) {
                    $this->components->info($event->getSummaryForDisplay());

                    $this->runEvents[$event] = CarbonImmutable::now()->getTimestampMs();

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
}
