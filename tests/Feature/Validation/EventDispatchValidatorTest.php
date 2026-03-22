<?php

declare(strict_types=1);

use Carbon\{CarbonImmutable, CarbonInterface};
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Schedule;
use RVxLab\Metronome\Validation\EventDispatchValidator;

it('allows dispatching an event that was not run recently with a matching cron expression', function (CarbonImmutable $now, Closure $using): void {
    $validator = app(EventDispatchValidator::class);

    $event = $using(Schedule::call(fn (): null => null));

    expect($validator->canDispatch($event, null, $now))->toBeTrue();
})->with(static fn (): array => [
    'every minute' => [
        CarbonImmutable::create(2025, 12, 28, 12),
        fn (Event $event) => $event->everyMinute(),
    ],
    'every 5 minutes' => [
        CarbonImmutable::create(2025, 12, 28, 12, 5),
        fn (Event $event) => $event->everyFiveMinutes(),
    ],
    'every 15 minutes' => [
        CarbonImmutable::create(2025, 12, 28, 12, 30),
        fn (Event $event) => $event->everyFifteenMinutes(),
    ],
    'every tuesday at midnight' => [
        CarbonImmutable::create(2025, 12, 28)->previous(CarbonInterface::TUESDAY),
        fn (Event $event) => $event->tuesdays()->at('00:00'),
    ],
]);

it('does not dispatch is the event was dispatched recently', function (): void {
    $now = CarbonImmutable::create(2025, 12, 28, 14, 0, 23);

    $validator = app(EventDispatchValidator::class);

    $event = Schedule::call(fn (): null => null)->wednesdays()->at('14:00');

    expect($validator->canDispatch($event, null, $now))->toBeTrue()
        ->and($validator->canDispatch($event, $now, $now->addSeconds(10)))->toBeFalse();
});

it('dispatches if the event fires every minute and a minute has elapsed', function (): void {
    $now = CarbonImmutable::create(2025, 12, 28, 14, 0, 23);

    $validator = app(EventDispatchValidator::class);

    $event = Schedule::call(fn (): null => null)->everyMinute();

    expect($validator->canDispatch($event, null, $now))->toBeTrue()
        ->and($validator->canDispatch($event, $now, $now->addSeconds(10)))->toBeFalse()
        ->and($validator->canDispatch($event, $now, $now->addSeconds(60)))->toBeTrue();
});

it('dispatches if the event fires every 5 seconds and that time has elapsed', function (): void {
    $now = CarbonImmutable::create(2025, 12, 28, 14, 0, 23);

    $validator = app(EventDispatchValidator::class);

    $event = Schedule::call(fn (): null => null)->everyFiveSeconds();

    expect($validator->canDispatch($event, null, $now))->toBeTrue()
        ->and($validator->canDispatch($event, $now, $now->addSeconds(3)))->toBeFalse()
        ->and($validator->canDispatch($event, $now, $now->addSeconds(5)))->toBeTrue();
});

it('does not dispatch a repeatable event that was run recently', function (): void {
    $now = CarbonImmutable::create(2025, 12, 28, 14, 0, 0);

    $validator = app(EventDispatchValidator::class);

    $event = Schedule::call(fn (): null => null)->everyFiveSeconds();

    // First dispatch should pass
    expect($validator->canDispatch($event, null, $now))->toBeTrue();

    // 3 seconds later — within the 5-second repeat window — should NOT dispatch.
    // This is the exact scenario where the old fallthrough bug would have
    // incorrectly returned true via the 60-second check.
    expect($validator->canDispatch($event, $now, $now->addSeconds(3)))->toBeFalse();
});

it('dispatches if the event fires every second', function (): void {
    $now = CarbonImmutable::create(2025, 12, 28, 14, 0, 23);

    $validator = app(EventDispatchValidator::class);

    $event = Schedule::call(fn (): null => null)->everySecond();

    expect($validator->canDispatch($event, null, $now))->toBeTrue()
        ->and($validator->canDispatch($event, $now, $now->addSecond()))->toBeTrue()
        ->and($validator->canDispatch($event, $now, $now->addSeconds(2)))->toBeTrue()
        ->and($validator->canDispatch($event, $now, $now->addSeconds(3)))->toBeTrue();
});
