<?php

declare(strict_types=1);

namespace RVxLab\Metronome\Commands;

use Illuminate\Console\{Application as ConsoleApplication, Command};
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ValidatedInput;
use RVxLab\Metronome\EventLoop\SchedulerEventLoop;
use RVxLab\Metronome\Schedule\{EventFetcher, EventTracker};
use RVxLab\Metronome\Validation\OptionValidator;
use Illuminate\Validation\ValidationException;

final class MetronomeStartCommand extends Command
{
    protected $signature = <<<CMD
        metronome:start
    CMD;

    protected $description = 'Start the scheduler using an event loop';

    public function handle(
        OptionValidator $validator,
        #[Config('metronome.tick_rate')]
        int|float $tickRate,
    ): int {
        try {
            /** @var ValidatedInput $options */
            $options = $validator->validate([
                'tick-rate' => $tickRate,
            ])->safe();
        } catch (ValidationException $validationException) {
            $this->error('Failed to validate options:');
            $this->components->bulletList($validationException->errors());

            return self::INVALID;
        }

        $tickRate = $options->float('tick-rate');

        if ($tickRate > 1) {
            $this->warn('The tick rate is higher than 1, which is not recommended. This may cause timing issues with your scheduled tasks.');
        }

        $eventLoop = new SchedulerEventLoop(
            app: $this->laravel,
            phpBinary: ConsoleApplication::phpBinary(),
            dispatcher: $this->laravel->make(Dispatcher::class),
            exceptionHandler: $this->laravel->make(ExceptionHandler::class),
            components: $this->outputComponents(),
            eventFetcher: $this->laravel->make(EventFetcher::class),
            eventTracker: $this->laravel->make(EventTracker::class),
        );

        $eventLoop->run(tickRate: $tickRate);
    }
}
