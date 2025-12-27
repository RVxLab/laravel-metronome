<?php

declare(strict_types=1);

namespace RVxLab\CronlessScheduler\Providers;

use RVxLab\CronlessScheduler\Commands\StartCronlessScheduleCommand;

final class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                StartCronlessScheduleCommand::class,
            ]);
        }
    }
}
