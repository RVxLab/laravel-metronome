<?php

declare(strict_types=1);

namespace RVxLab\Metronome\Providers;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Foundation\Application;
use RVxLab\Metronome\Commands\MetronomeCommand;
use RVxLab\Metronome\Schedule\{EventDispatchValidator, EventTracker};

final class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    private const PUBLISH_GROUP_PREFIX = 'laravel-metronome:';

    public function register(): void
    {
        $this->app->bind(
            EventDispatchValidator::class,
            fn (Application $app): EventDispatchValidator => new EventDispatchValidator(
                app: $app,
                cache: $app->make(Cache::class),
            ),
        );

        $this->app->singleton(EventTracker::class);
    }

    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            MetronomeCommand::class,
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/metronome.php',
            'metronome',
        );

        $this->publishes([
            __DIR__ . '/../../config/metronome.php' => config_path('metronome.php'),
        ], $this->publishGroup('config'));
    }

    private function publishGroup(string $suffix): string
    {
        return self::PUBLISH_GROUP_PREFIX . $suffix;
    }
}
