# Laravel Metronome

A drop-in replacement for Laravel's built-in scheduler using an event loop instead of Cron.

## Requirements

- PHP 8.2+
- Laravel 12+
- `ext-pcntl`

## Installation

```bash
composer require rvxlab/laravel-metronome
```

## Usage

Replace your `schedule:work` invocation with:

```bash
php artisan schedule:metronome
```

### Tick rate

The tick rate controls how often the scheduler checks for due tasks in seconds. By default, the tick rate is 1 second.
You can change the tick rate by passing the `--tick-rate` or `-t` option:

```bash
# Default, suitable for most workloads
php artisan schedule:metronome --tick-rate=1

# Check twice per second, useful for high-frequency sub-minute tasks
php artisan schedule:metronome --tick-rate=0.5

# Minimum allowed value (100 times per second), rarely needed in practice
php artisan schedule:metronome --tick-rate=0.01
```

Lower tick rates increase the number of times the scheduler checks for due tasks, which can be helpful if your workload
relies on sub-minute scheduling or if you have a large number of small tasks scheduled. It's worth keeping in mind that
lowering the tick rate will increase the CPU usage slightly. In most cases the difference is negligible (< 0.1%).

## Overlap Protection

Metronome makes use of Laravel's overlap protection, exactly how the built-in scheduler works.

Calls to `->withoutOverlapping()` will continue to work as expected.

## Long-running tasks

As with Laravel's built-in scheduler, synchronous tasks that run long will delay later ticks. For tasks
expected to take more than a second or two, use `->runInBackground()` to shell out a child process instead.

```php
$schedule->command('orders:process')->everyMinute()->runInBackground();
```

## Running with Supervisor

Add the following to your Supervisor configuration, adjusting `command`, `user`, and `stdout_logfile` to match your
setup.

```ini
[program:laravel-metronome]
process_name = %(program_name)s_%(process_num)02d
command = php /var/www/html/artisan schedule:metronome
autostart = true
autorestart = true
stopasgroup = true
killasgroup = true
user = www-data
numprocs = 1
redirect_stderr = true
stdout_logfile = /var/www/html/storage/logs/metronome.log
stopwaitsecs = 60
```

`numprocs` must be `1`. Running multiple instances will cause tasks to fire multiple times. `stopwaitsecs` should be
set
high enough to allow any currently running tasks to finish before Supervisor force-kills the process.

## Running with Docker

Because Metronome doesn't rely on Cron, it's really easy to have it run in a Docker container, either directly or
through Supervisor.

Without Supervisor, you can run the scheduler directly as the container's entrypoint:

```dockerfile
FROM php:8.2-cli

RUN docker-php-ext-install pcntl

WORKDIR /var/www/html

COPY . .

CMD ["php", "artisan", "schedule:metronome"]
```

Or with Supervisor, if you need it running alongside other processes:

```dockerfile
FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
        supervisor \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pcntl

WORKDIR /var/www/html

COPY . .

COPY supervisord.conf /etc/supervisor/conf.d/laravel-metronome.conf

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
```

## Stopping the Scheduler

Send `SIGINT` (Ctrl+C) or `SIGTERM` to stop the process cleanly. Laravel's shutdown logic runs before the process
exits, so process managers like Supervisor and systemd work out of the box.

## Differences from `schedule:work`

|                            | `schedule:work` | `schedule:metronome` |
|----------------------------|-----------------|----------------------|
| Tick mechanism             | Cron            | Event loop           |
| Default tick rate          | 60 seconds      | 1 second             |
| Configurable tick rate     | No              | Yes                  |
| Sub-minute precision       | Best effort     | High precision       |
| Overlap protection         | Yes             | Yes                  |
| Laravel events             | Yes             | Yes                  |
| Filters (`->when()`, etc.) | Yes             | Yes                  |

## Known limitations

- Last-run state is held in memory and not persisted across process restarts. A task that ran shortly before a crash may
  re-run immediately on startup.

## License

This package is licensed under [MIT](./LICENSE).
