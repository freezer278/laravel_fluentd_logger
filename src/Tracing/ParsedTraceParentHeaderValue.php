<?php

namespace Vmorozov\LaravelRichLogs\Tracing;

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

        return new self($version, $traceId, $spanId, $flags);
    }

    public function __construct(
        private string $version,
        private string $traceId,
        private string $spanId,
        private string $flags,
    ) {
    }

    /**
     * @return string
     */
    public function getTraceId(): string
    {
        return $this->traceId;
    }
}
