<?php

namespace Vmorozov\LaravelFluentdLogger;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vmorozov\LaravelFluentdLogger\Logs\Features\ConsoleCommandsLog;
use Vmorozov\LaravelFluentdLogger\Logs\Features\DbQueryLog;
use Vmorozov\LaravelFluentdLogger\Logs\FluentLogManager;
use Vmorozov\LaravelFluentdLogger\Logs\Features\QueueLog;
use Vmorozov\LaravelFluentdLogger\Tracing\RandomIdGenerator;
use Vmorozov\LaravelFluentdLogger\Tracing\TraceStorage;
use Psr\Log\LoggerInterface;

class LaravelFluentdLoggerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel_fluentd_logger')
            ->hasConfigFile();
    }

    public function bootingPackage()
    {
        $this->initTracing();
        $this->registerLogDriver();
        $this->initLogFeatures();
    }

    private function initTracing(): void
    {
        $this->app->singleton(TraceStorage::class, function () {
            return new TraceStorage($this->app->make(RandomIdGenerator::class));
        });
        $this->app->make(TraceStorage::class);
    }

    private function registerLogDriver(): void
    {
        $log = $this->app->make(LoggerInterface::class);
        $log->extend('fluentd', function ($app, array $config) {
            $manager = $app->make(FluentLogManager::class);

            return $manager($config);
        });

        $this->app->singleton(FluentLogManager::class, function ($app) {
            return new FluentLogManager($app);
        });
    }

    private function initLogFeatures(): void
    {
        $config = config('laravel_fluentd_logger');

        if ($config['features_enabled']['db_query_log'] ?? true) {
            $this->initDbQueryLog();
        }

        if ($config['features_enabled']['queue_log'] ?? true) {
            $this->initQueueJobsLog();
        }

        if ($config['features_enabled']['console_commands_log'] ?? true) {
            $this->initConsoleCommandsLog();
        }
    }

    private function initQueueJobsLog(): void
    {
        /** @var QueueLog $log */
        $log = $this->app->make(QueueLog::class);
        $log->init();
    }

    private function initDbQueryLog(): void
    {
        /** @var DbQueryLog $log */
        $log = $this->app->make(DbQueryLog::class);
        $log->init();
    }

    private function initConsoleCommandsLog(): void
    {
        /** @var ConsoleCommandsLog $log */
        $log = $this->app->make(ConsoleCommandsLog::class);
        $log->init();
    }
}
