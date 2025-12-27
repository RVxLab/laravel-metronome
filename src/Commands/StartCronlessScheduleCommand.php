<?php

declare(strict_types=1);

namespace RVxLab\CronlessScheduler\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Revolt\EventLoop;

final class StartCronlessScheduleCommand extends Command
{
    protected $signature = 'schedule:cronless-start';

    protected $description = 'Start the scheduler';

    public function handle(
        Schedule $schedule,
        Dispatcher $dispatcher,
        Cache $cache,
        ExceptionHandler $exceptionHandler,
    ): int {
        $this->info('Starting!');

        $ticker = EventLoop::repeat(1, function (): void {
            $this->info(sprintf("Tick: %s\n", date("Y-m-d H:i:s")));
        });

        EventLoop::onSignal(SIGINT, function () use ($ticker): never {
            $this->newLine();
            $this->warn('Received interrupt signal, preparing to exit');
            EventLoop::cancel($ticker);
            exit(self::SUCCESS);
        });

        EventLoop::run();

        return self::SUCCESS;
    }
}
