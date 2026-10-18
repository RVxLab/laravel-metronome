<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Console\Scheduling\{Event, EventMutex};

final class FakeEventMutex implements EventMutex
{
    public function create(Event $event): void
    {
        //
    }

    public function exists(Event $event): void
    {
        //
    }

    public function forget(Event $event): void
    {
        //
    }
}
