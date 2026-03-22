<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

it('skips overlapping events', function (): void {
    $event = Schedule::call(fn (): null => null)
        ->name('overlap-test')
        ->everySecond()
        ->withoutOverlapping();

    // First call acquires the mutex
    expect($event->shouldSkipDueToOverlapping())->toBeFalse();

    // Second call should skip because the mutex is held
    expect($event->shouldSkipDueToOverlapping())->toBeTrue();
});
