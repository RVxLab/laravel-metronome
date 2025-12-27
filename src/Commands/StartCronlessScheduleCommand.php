<?php

declare(strict_types=1);

namespace RVxLab\CronlessScheduler\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use RVxLab\CronlessScheduler\EventLoop\SchedulerEventLoop;

//use Illuminate\Contracts\Cache\Repository as Cache;
//use Illuminate\Contracts\Debug\ExceptionHandler;

final class StartCronlessScheduleCommand extends Command
{
    protected $signature = 'schedule:cronless-start';

    protected $description = 'Start the scheduler';

    public function handle(): never
    {
        $this->info('Starting!');

        $eventLoop = new SchedulerEventLoop(
            $this->laravel,
            $this->laravel->get(Schedule::class),
            $this->laravel->get(Dispatcher::class),
            //            $this->laravel->get(Cache::class),
            //            $this->laravel->get(ExceptionHandler::class),
            $this->outputComponents(),
        );

        $eventLoop->run();
    }
}
