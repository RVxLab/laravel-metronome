<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use RVxLab\Metronome\Schedule\EventTracker;
use Tests\Fixtures\FakeEventMutex;

it('can track events', function (): void {
    $event = new Event(new FakeEventMutex(), '');

    $tracker = new EventTracker();

    expect($tracker->getLastRunForEvent($event))
        ->toBeNull();

    $tracker->markEventRun($event);

    expect($tracker->getLastRunForEvent($event))
        ->not
        ->toBeNull();
});
