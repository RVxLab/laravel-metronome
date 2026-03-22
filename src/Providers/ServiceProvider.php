<?php

declare(strict_types=1);

namespace RVxLab\CronlessScheduler\Providers;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Foundation\Application;
use RVxLab\CronlessScheduler\Commands\StartCronlessScheduleCommand;
use RVxLab\CronlessScheduler\Validation\EventDispatchValidator;

final class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            EventDispatchValidator::class,
            fn (Application $app): EventDispatchValidator => new EventDispatchValidator(
                app: $app,
                cache: $app->make(Cache::class),
            ),
        );
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
