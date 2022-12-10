<?php

namespace Vmorozov\LaravelFluentdLogger\Tracing;

class TraceStorage
{
    private RandomIdGenerator $randomIdGenerator;

    private static string $traceId;
    private static string $spanId;

    public function __construct(RandomIdGenerator $randomIdGenerator)
    {
        $this->randomIdGenerator = $randomIdGenerator;
    }

    public function startNewTrace(): string
    {
        $this->setTraceId($this->randomIdGenerator->generateTraceId());
        return $this->getTraceId();
    }

    public function getTraceId(): string
    {
        return static::$traceId;
    }

    public function setTraceId(string $traceId): void
    {
        static::$traceId = $traceId;
    }

    public function startNewSpan(): string
    {
        if (!$this->getTraceId()) {
            throw new \LogicException('Span can only be started after trace start.');
        }

        $this->setSpanId($this->randomIdGenerator->generateSpanId());
        return $this->getSpanId();
    }

    public function getSpanId(): string
    {
        return static::$spanId;
    }

    public function setSpanId(string $spanId): void
    {
        static::$spanId = $spanId;
    }
}
