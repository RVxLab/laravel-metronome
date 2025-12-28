<?php

declare(strict_types=1);

namespace RVxLab\CronlessScheduler\Validation;

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Foundation\Application;

final readonly class EventDispatchValidator
{
    public function __construct(private Application $app) {}

    public function canDispatch(Event $event, ?CarbonImmutable $lastRun, CarbonImmutable $now): bool
    {
        if (!$event->filtersPass($this->app)) {
            return false;
        }

        if ($event->isRepeatable() && !$this->wasRunRecently($lastRun, $now, (int) $event->repeatSeconds)) {
            return true;
        }

        return !$this->wasRunRecently($lastRun, $now, 60);
    }

    private function wasRunRecently(?CarbonImmutable $lastRun, CarbonImmutable $now, int $secondsBetweenDispatches): bool
    {
        if (1 === $secondsBetweenDispatches || !$lastRun instanceof CarbonImmutable) {
            return false;
        }

        return $now->diffInSeconds($lastRun, true) < $secondsBetweenDispatches;
    }
}
