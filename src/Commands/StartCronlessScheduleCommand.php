<?php

declare(strict_types=1);

namespace RVxLab\CronlessScheduler\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ValidatedInput;
use RVxLab\CronlessScheduler\EventLoop\SchedulerEventLoop;
use RVxLab\CronlessScheduler\Validation\EventDispatchValidator;

//use Illuminate\Contracts\Cache\Repository as Cache;
//use Illuminate\Contracts\Debug\ExceptionHandler;

final class StartCronlessScheduleCommand extends Command
{
    protected $signature = <<<CMD
        schedule:cronless-start
            {--t|tick-rate=1 : The time between each scheduler call. This must be a non-zero positive number and should not be higher than 1. Lower tick rates may cause higher CPU usage.}
    CMD;

    protected $description = 'Start the scheduler';

    public function handle(): int
    {
        $inputData = $this->options();

        $validator = Validator::make($inputData, [
            'tick-rate' => 'required|numeric|min:0.01',
        ], [
            'tick-rate.numeric' => 'The tick rate option must be a number.',
        ]);

        if ($validator->fails()) {
            $this->error('Failed to validate options:');

            $errors = $validator->errors()->all();

            $this->components->bulletList($errors);

            return self::INVALID;
        }

        /** @var ValidatedInput $options */
        $options = $validator->safe();
        $tickRate = $options->float('tick-rate');

        if ($tickRate > 1) {
            $this->warn('The tick rate is higher than 1, which is not recommended. This may cause timing issues with your scheduled tasks.');
        }

        $this->info('Starting the scheduler.');

        $eventLoop = new SchedulerEventLoop(
            app: $this->laravel,
            schedule: $this->laravel->get(Schedule::class),
            dispatcher: $this->laravel->get(Dispatcher::class),
            //            $this->laravel->get(Cache::class),
            //            $this->laravel->get(ExceptionHandler::class),
            components: $this->outputComponents(),
            validator: new EventDispatchValidator($this->laravel),
        );

        $eventLoop->run(
            tickRate: $tickRate,
        );
    }
}
