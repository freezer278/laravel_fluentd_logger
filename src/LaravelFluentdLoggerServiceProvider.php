<?php

namespace Vmorozov\LaravelFluentdLogger;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vmorozov\LaravelFluentdLogger\Logs\FluentLogManager;
use Vmorozov\LaravelFluentdLogger\Queue\MakeQueueTraceAwareAction;
use Vmorozov\LaravelFluentdLogger\Tracing\RandomIdGenerator;
use Vmorozov\LaravelFluentdLogger\Tracing\TraceIdStorage;
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
    }

    private function initTracing(): void
    {
//        $this->app->singleton(TraceIdStorage::class, function () {
//            return new TraceIdStorage($this->app->make(RandomIdGenerator::class));
//        });
        /** @var TraceIdStorage $traceIdStorage */
        $traceIdStorage = $this->app->make(TraceIdStorage::class);
        $traceIdStorage->startNewTrace();
        $traceIdStorage->startNewSpan();

        /** @var MakeQueueTraceAwareAction $action */
        $action = $this->app->make(MakeQueueTraceAwareAction::class);
        $action->execute();

        $this->initQueueJobsFailsLog();
        $this->initDbQueryLog();
    }

    private function initQueueJobsFailsLog(): void
    {
        Queue::failing(function (JobFailed $event) {
            Log::error('Failed Job | ' . $event->job->resolveName(), [
                'exception' => $event->exception,
                'connection_name' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'job_name' => $event->job->resolveName(),
                'attempts' => $event->job->attempts(),
                // 'job_payload' => $event->job->getRawBody(),
            ]);
        });
    }

    private function initDbQueryLog()
    {
        DB::listen(function ($query) {
            Log::info(
                'DB Query',
                [
                    'query' => $query->sql,
//                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ]
            );
        });
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
}
