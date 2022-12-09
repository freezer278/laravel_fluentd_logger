<?php

namespace Vmorozov\LaravelRichLogs\Tracing;

class TraceIdStorage
{
    /** @var string */
    private static $traceId;

    public function getTraceId(): string
    {
        return static::$traceId;
    }

    public function setTraceId(string $traceId): void
    {
        static::$traceId = $traceId;
    }
}
