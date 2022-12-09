<?php

namespace Vmorozov\LaravelRichLogs\Tracing;

use Throwable;

class TraceIdGenerator
{
    private const TRACE_ID_HEX_LENGTH = 32;

    public const INVALID_TRACE = '00000000000000000000000000000000';

    public function generateTraceId(): string
    {
        do {
            $traceId = $this->randomHex(self::TRACE_ID_HEX_LENGTH);
        } while (! $this->isValidTraceId($traceId));

        return $traceId.'.'.time();
    }

    private function randomHex(int $hexLength): string
    {
        try {
            return bin2hex(random_bytes(intdiv($hexLength, 2)));
        } catch (Throwable $e) {
            return $this->fallbackAlgorithm($hexLength);
        }
    }

    private function fallbackAlgorithm(int $hexLength): string
    {
        return substr(str_shuffle(str_repeat('0123456789abcdef', $hexLength)), 1, $hexLength);
    }

    private function isValidTraceId($traceId): bool
    {
        return ctype_xdigit($traceId) && strlen($traceId) === self::TRACE_ID_HEX_LENGTH && $traceId !== self::INVALID_TRACE && $traceId === strtolower($traceId);
    }
}
