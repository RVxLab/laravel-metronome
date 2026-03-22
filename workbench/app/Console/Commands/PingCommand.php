<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class PingCommand extends Command
{
    protected $signature = 'workbench:ping';

    protected $description = 'Simple command for E2E testing';

    public function handle(): int
    {
        Log::info('Pong');

        return self::SUCCESS;
    }
}
