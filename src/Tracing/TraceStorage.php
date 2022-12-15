<?php

namespace Vmorozov\LaravelFluentdLogger\Tracing;

class TraceStorage
{
    private RandomIdGenerator $randomIdGenerator;

    private string $traceId;
    private string $spanId;

    public function __construct(RandomIdGenerator $randomIdGenerator)
    {
        $this->randomIdGenerator = $randomIdGenerator;
        $this->startNewTrace();
        $this->startNewSpan();
    }

    public function startNewTrace(): string
    {
        $this->setTraceId($this->randomIdGenerator->generateTraceId());
        return $this->getTraceId();
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function setTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    public function startNewSpan(): string
    {
        $this->setSpanId($this->randomIdGenerator->generateSpanId());
        return $this->getSpanId();
    }

    public function getSpanId(): string
    {
        return $this->spanId;
    }

    public function setSpanId(string $spanId): void
    {
        $this->spanId = $spanId;
    }
}
