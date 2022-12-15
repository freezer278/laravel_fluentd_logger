<?php

namespace Vmorozov\LaravelFluentdLogger\Tracing;

class ParsedTraceParentHeaderValue
{
    public static function make(string $headerValue): ?self
    {
        if (substr_count($headerValue, '-') !== 3) {
            return null;
        }

        [$version, $traceId, $spanId, $flags] = explode('-', $headerValue);
        if ($version !== '00') {
            return null;
        }

        return new self($traceId, $spanId);
    }

    public function __construct(
        private string $traceId,
        private string $spanId,
    ) {
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function getSpanId(): string
    {
        return $this->spanId;
    }
}
