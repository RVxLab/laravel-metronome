<?php

declare(strict_types=1);

use Illuminate\Support\Facades\{Log, Schedule};

Schedule::call(function (): void {
    Log::info('This line is logged every minute');
})->everyMinute()->description('every minute');

Schedule::call(function (): void {
    Log::info('This line is logged every 10 seconds');
})->everyTenSeconds()->description('every ten seconds');

Schedule::call(function (): void {
    Log::info('This line is logged every five seconds');
})->everyFiveSeconds()->description('every five seconds');
