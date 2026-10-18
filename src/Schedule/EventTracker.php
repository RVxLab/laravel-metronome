<?php

declare(strict_types=1);

namespace RVxLab\Metronome\Schedule;

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use WeakMap;

final class EventTracker
{
    /** @var WeakMap<Event, int> */
    private WeakMap $runEvents;

    public function __construct()
    {
        $this->runEvents = new WeakMap();
    }

    public function getLastRunForEvent(Event $event): ?CarbonImmutable
    {
        $lastRunTimestampMs = $this->runEvents[$event] ?? null;

        if (null === $lastRunTimestampMs) {
            return null;
        }

        return CarbonImmutable::createFromTimestampMs($lastRunTimestampMs);
    }

    public function markEventRun(Event $event): void
    {
        $this->runEvents[$event] = CarbonImmutable::now()->getTimestampMs();
    }
}
