<?php

declare(strict_types=1);

return [
    // The time between each scheduler call in seconds.
    // Must be a non-zero positive integer and should not be higher than 1.
    // Lower tick rates may cause higher CPU usage.
    'tick_rate' => env('METRONOME_TICK_RATE', 1.0),
];
