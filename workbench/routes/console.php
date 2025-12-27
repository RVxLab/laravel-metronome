<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {
    //
})->everyMinute()->description('every minute');

Schedule::call(function (): void {
    //
})->everyTenSeconds()->description('every ten seconds');

Schedule::call(function (): void {
    //
})->everyFiveSeconds()->description('every five seconds');
