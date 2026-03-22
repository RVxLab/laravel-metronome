<?php

declare(strict_types=1);

use Illuminate\Support\Facades\{Log, Schedule};
use Illuminate\Support\Sleep;

// Basic closure jobs at various intervals to verify the tick rate and dispatch timing.
Schedule::call(function (): void {
    Log::info('This line is logged every minute');
})->everyMinute()->description('every minute');

Schedule::call(function (): void {
    Log::info('This line is logged every 10 seconds');
})->everyTenSeconds()->description('every ten seconds');

Schedule::call(function (): void {
    Log::info('This line is logged every five seconds');
})->everyFiveSeconds()->description('every five seconds');

// withoutOverlapping where the job always exceeds its interval, verifying the mutex
// skip path and that a held lock prevents re-entry.
Schedule::call(function (): void {
    Log::info('This line is logged every second and a half');
    Sleep::for(1.5)->seconds();
    Log::info('After sleep to test overlap');
})->everySecond()->name('1.5-seconds-with-sleep')->withoutOverlapping(2);

// Non-CallbackEvent path, exercises command stripping and bullet list output.
Schedule::command('workbench:ping')
    ->everyFiveSeconds()
    ->description('artisan command every 5 seconds');

// runInBackground (exit code check is bypassed entirely).
Schedule::command('workbench:ping')
    ->everyTenSeconds()
    ->runInBackground()
    ->description('artisan command in background');

// withoutOverlapping where the job finishes well within the interval, verifying the
// non-skip path works correctly with the mutex after the double-acquire fix.
Schedule::call(function (): void {
    Log::info('Quick job with overlap protection');
})->everyFiveSeconds()->name('quick-without-overlapping')->withoutOverlapping()->description('quick withoutOverlapping');

// Intentional failure, exercises ScheduledTaskFailed and exceptionHandler->report().
Schedule::call(function (): void {
    throw new RuntimeException('Intentional E2E failure');
})->everyTenSeconds()->description('intentional failure');
