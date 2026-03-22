<?php

declare(strict_types=1);

namespace RVxLab\Metronome\Commands;

use Illuminate\Console\{Application as ConsoleApplication, Command};
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ValidatedInput;
use RVxLab\Metronome\EventLoop\SchedulerEventLoop;
use RVxLab\Metronome\Validation\EventDispatchValidator;

final class MetronomeCommand extends Command
{
    protected $signature = <<<CMD
        schedule:metronome
            {--t|tick-rate=1 : The time between each scheduler call. This must be a non-zero positive number and should not be higher than 1. Lower tick rates may cause higher CPU usage.}
    CMD;

    protected $description = 'Start the scheduler using an event loop';

    public function handle(): int
    {
        $validator = Validator::make($this->options(), [
            'tick-rate' => 'required|numeric|min:0.01',
        ], [
            'tick-rate.numeric' => 'The tick rate option must be a number.',
        ]);

        if ($validator->fails()) {
            $this->error('Failed to validate options:');
            $this->components->bulletList($validator->errors()->all());

            return self::INVALID;
        }

        /** @var ValidatedInput $options */
        $options = $validator->safe();
        $tickRate = $options->float('tick-rate');

        if ($tickRate > 1) {
            $this->warn('The tick rate is higher than 1, which is not recommended. This may cause timing issues with your scheduled tasks.');
        }

        $eventLoop = new SchedulerEventLoop(
            app: $this->laravel,
            phpBinary: ConsoleApplication::phpBinary(),
            schedule: $this->laravel->make(Schedule::class),
            dispatcher: $this->laravel->make(Dispatcher::class),
            exceptionHandler: $this->laravel->make(ExceptionHandler::class),
            components: $this->outputComponents(),
            validator: $this->laravel->make(EventDispatchValidator::class),
        );

        $eventLoop->run(tickRate: $tickRate);
    }
}
