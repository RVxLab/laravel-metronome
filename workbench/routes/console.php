<?php

declare(strict_types=1);

use Illuminate\Support\Facades\{Log, Schedule};
use Illuminate\Support\Sleep;

Schedule::call(function (): void {
    Log::info('This line is logged every minute');
})->everyMinute()->description('every minute');

Schedule::call(function (): void {
    Log::info('This line is logged every 10 seconds');
})->everyTenSeconds()->description('every ten seconds');

Schedule::call(function (): void {
    Log::info('This line is logged every five seconds');
})->everyFiveSeconds()->description('every five seconds');

Schedule::call(function (): void {
    Log::info('This line is logged every second and a half');
    Sleep::for(1.5)->seconds();
    Log::info('After sleep to test overlap');
})->everySecond()->name('1.5-seconds-with-sleep')->withoutOverlapping(2);
