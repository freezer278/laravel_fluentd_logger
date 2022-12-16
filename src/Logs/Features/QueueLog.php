<?php

namespace Vmorozov\LaravelFluentdLogger\Logs\Features;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Vmorozov\LaravelFluentdLogger\Queue\MakeQueueTraceAwareAction;

class QueueLog
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function init(): void
    {
        Queue::failing(function (JobFailed $event) {
            $context = $this->getLogContextForJob($event->job, $event->connectionName);
            $context['exception'] = $event->exception;
            $context['payload'] = $event->job->payload();
            Log::error('Failed Job | ' . $event->job->resolveName(), $context);
        });

        Queue::before(function (JobProcessing $event) {
            Log::info(
                'Job Started | ' . $event->job->resolveName(),
                $this->getLogContextForJob($event->job, $event->connectionName)
            );
        });

        Queue::after(function (JobProcessed $event) {
            if ($event->job->hasFailed()) {
                return;
            }
            Log::info(
                'Job Finished | ' . $event->job->resolveName(),
                $this->getLogContextForJob($event->job, $event->connectionName)
            );
        });

        /** @var MakeQueueTraceAwareAction $action */
        $action = $this->app->make(MakeQueueTraceAwareAction::class);
        $action->execute();
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
}
