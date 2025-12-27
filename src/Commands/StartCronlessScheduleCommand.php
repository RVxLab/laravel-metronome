<?php

declare(strict_types=1);

namespace RVxLab\CronlessScheduler\Commands;

use Illuminate\Console\Command;

final class StartCronlessScheduleCommand extends Command
{
    protected $signature = 'schedule:cronless-start';

    protected $description = 'Start the scheduler';

    public function handle(): void
    {
        $this->info('Starting!');
    }
}
