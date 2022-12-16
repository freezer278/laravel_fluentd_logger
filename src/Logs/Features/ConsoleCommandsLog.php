<?php

namespace Vmorozov\LaravelFluentdLogger\Logs\Features;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class ConsoleCommandsLog
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function init(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $excludedCommands = config('laravel_fluentd_logger.console_commands_log.excluded', []);

        Event::listen(CommandFinished::class, function (CommandFinished $event) use ($excludedCommands) {
            $signature = $event->command;

            if (!$signature || in_array($signature, $excludedCommands)) {
                return;
            }

            $timeFinished = microtime(true);

            $executionTime = defined('LARAVEL_START') ?
                $timeFinished - LARAVEL_START :
                0;
            $executionTime = round($executionTime * 1000);

            $memoryPeak = memory_get_peak_usage(true) / 1048576;

            Log::info('Console command executed: ' . $signature, [
                'signature' => $signature,
                'execution_time_ms' => $executionTime,
                'peak_memory_usage' => $memoryPeak,
                'input' => $event->input->getArguments(),
                'exit_code' => $event->exitCode,
            ]);
        });
    }
}
