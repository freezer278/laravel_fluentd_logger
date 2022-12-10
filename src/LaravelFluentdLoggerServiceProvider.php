<?php

namespace Vmorozov\LaravelFluentdLogger;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vmorozov\LaravelFluentdLogger\Logs\FluentLogManager;
use Vmorozov\LaravelFluentdLogger\Queue\MakeQueueTraceAwareAction;
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
    }

    private function initTracing(): void
    {
        /** @var TraceStorage $traceStorage */
        $traceStorage = $this->app->make(TraceStorage::class);
        $traceStorage->startNewTrace();
        $traceStorage->startNewSpan();

        /** @var MakeQueueTraceAwareAction $action */
        $action = $this->app->make(MakeQueueTraceAwareAction::class);
        $action->execute();

        $this->initQueueJobsLog();
        $this->initDbQueryLog();
    }

    private function initQueueJobsLog(): void
    {
        Queue::failing(function (JobFailed $event) {
            $context = $this->getLogContextForJob($event->job, $event->connectionName);
            $context['exception'] = $event->exception;
            $context['payload'] = $event->job->payload();
            Log::error('Failed Job | ' . $event->job->resolveName(), $context);
        });

        Queue::before(function (JobProcessing $event) {
            Log::info(
                'Job Started | ' . $event->job->getName(),
                $this->getLogContextForJob($event->job, $event->connectionName)
            );
        });

        Queue::after(function (JobProcessed $event) {
            if ($event->job->hasFailed()) {
                return;
            }
            Log::info(
                'Job Finished | ' . $event->job->getName(),
                $this->getLogContextForJob($event->job, $event->connectionName)
            );
        });
    }

    /**
     * @param Job $job
     * @param string $connectionName
     * @return array
     */
    private function getLogContextForJob($job, string $connectionName): array
    {
        return [
            'connection_name' => $connectionName,
            'queue' => $job->getQueue(),
            'job_name' => $job->resolveName(),
            'attempt' => $job->attempts(),
            'job_id' => $job->getJobId(),
        ];
    }

    private function initDbQueryLog(): void
    {
        DB::listen(function ($query) {
            Log::info(
                'DB Query',
                [
                    'query' => $query->sql,
// commented because there are elasticsearch problems with mapping some kinds of content inside bindings
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
