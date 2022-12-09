<?php

namespace Vmorozov\LaravelRichLogs\Queue;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobRetryRequested;
use Vmorozov\LaravelRichLogs\Tracing\TraceIdStorage;

class MakeQueueTraceAwareAction
{
    private TraceIdStorage $traceIdStorage;

    public function __construct(TraceIdStorage $traceIdStorage)
    {
        $this->traceIdStorage = $traceIdStorage;
    }

    public function execute(): void
    {
        $this
            ->listenForJobsBeingQueued()
            ->listenForJobsBeingProcessed()
            ->listenForJobsRetryRequested();
    }

    protected function listenForJobsBeingQueued(): self
    {
        app('queue')->createPayloadUsing(function ($connectionName, $queue, $payload) {
            $currentTraceId = $this->traceIdStorage->getTraceId();
            if ($currentTraceId) {
                return ['traceId' => $currentTraceId];
            }
        });

        return $this;
    }

    protected function listenForJobsBeingProcessed(): self
    {
        app('events')->listen(JobProcessing::class, function (JobProcessing $event) {
            if (! array_key_exists('traceId', $event->job->payload())) {
                return;
            }

            $this->traceIdStorage->setTraceId($event->job->payload()['traceId']);
        });

        return $this;
    }

    protected function listenForJobsRetryRequested(): self
    {
        app('events')->listen(JobRetryRequested::class, function (JobRetryRequested $event) {
            if (! array_key_exists('traceId', $event->payload())) {
                return;
            }

            $this->traceIdStorage->setTraceId($event->payload()['traceId']);
        });

        return $this;
    }

    protected function jobName(object $job): string
    {
        return $job->payload()['displayName'];
    }
}