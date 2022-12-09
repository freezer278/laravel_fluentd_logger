<?php

namespace Vmorozov\LaravelRichLogs;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vmorozov\LaravelRichLogs\Logs\FluentLogManager;
use Vmorozov\LaravelRichLogs\Queue\MakeQueueTraceAwareAction;
use Vmorozov\LaravelRichLogs\Tracing\TraceIdGenerator;
use Vmorozov\LaravelRichLogs\Tracing\TraceIdStorage;
use Psr\Log\LoggerInterface;

class LaravelRichLogsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel_rich_logs')
            ->hasConfigFile();
    }

    public function bootingPackage()
    {
        $traceId = (new TraceIdGenerator())->generateTraceId();

        /** @var TraceIdStorage $traceIdStorage */
        $traceIdStorage = $this->app->make(TraceIdStorage::class);
        $traceIdStorage->setTraceId($traceId);

        /** @var MakeQueueTraceAwareAction $action */
        $action = $this->app->make(MakeQueueTraceAwareAction::class);
        $action->execute();

        Queue::failing(function (JobFailed $event) {
            Log::error($event->exception->getMessage(), [
                'connection_name' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'job_name' => $event->job->getName(),
                //                'job_payload' => $event->job->getRawBody(),
                'file' => $event->exception->getFile().':'.$event->exception->getLine(),
                'exception_trace' => $event->exception->getTraceAsString(),
            ]);
        });

        DB::listen(function ($query) {
            Log::info(
                'DB Query',
                [
                    'query' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ]
            );
        });

        $this->registerLogDriver();
    }

    private function registerLogDriver()
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
}
