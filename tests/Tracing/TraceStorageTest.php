<?php

namespace Vmorozov\LaravelFluentdLogger\Tests\Tracing;

use LogicException;
use Vmorozov\LaravelFluentdLogger\Tests\TestCase;
use Vmorozov\LaravelFluentdLogger\Tracing\RandomIdGenerator;
use Vmorozov\LaravelFluentdLogger\Tracing\TraceStorage;

class TraceStorageTest extends TestCase
{
    public function testShouldStartNewTraceAndSpanOnNewObjectCreation(): void
    {
        $traceId = 'sometraceid123';
        $spanId = 'somespanid123';
        $idGeneratorMock = \Mockery::mock(RandomIdGenerator::class);
        $idGeneratorMock->shouldReceive('generateTraceId')
            ->once()
            ->andReturn($traceId);
        $idGeneratorMock->shouldReceive('generateSpanId')
            ->once()
            ->andReturn($spanId);
        $storage = new TraceStorage($idGeneratorMock);

        $this->assertEquals($traceId, $storage->getTraceId());
        $this->assertEquals($spanId, $storage->getSpanId());
    }

    public function testStartNewTrace(): void
    {
        $initialTraceId = 'sometraceidinitial';
        $traceId = 'somenewtraceid123';
        $idGeneratorMock = \Mockery::mock(RandomIdGenerator::class);
        $idGeneratorMock->shouldReceive('generateTraceId')
            ->twice()
            ->andReturn($initialTraceId, $traceId);
        $idGeneratorMock->shouldReceive('generateSpanId')
            ->once()
            ->andReturn('somespanid123');
        $storage = new TraceStorage($idGeneratorMock);

        $newTraceId = $storage->startNewTrace();

        $this->assertEquals($traceId, $newTraceId);
        $this->assertEquals($traceId, $storage->getTraceId());
    }

    public function testSetTraceId(): void
    {
        $traceId = 'somenewtraceid123';
        $idGeneratorMock = \Mockery::mock(RandomIdGenerator::class)->makePartial();
        $storage = new TraceStorage($idGeneratorMock);

        $storage->setTraceId($traceId);

        $this->assertEquals($traceId, $storage->getTraceId());
    }

    public function testStartNewSpan(): void
    {
        $spanId = 'somenewspanid123';
        $idGeneratorMock = \Mockery::mock(RandomIdGenerator::class)->makePartial();
        $idGeneratorMock->shouldReceive('generateSpanId')
            ->twice()
            ->andReturn('initialspanid', $spanId);
        $storage = new TraceStorage($idGeneratorMock);

        $newSpanId = $storage->startNewSpan();

        $this->assertEquals($spanId, $newSpanId);
        $this->assertEquals($spanId, $storage->getSpanId());
    }

    public function testSetSpanId(): void
    {
        $spanId = 'somenewspanid123';
        $idGeneratorMock = \Mockery::mock(RandomIdGenerator::class)->makePartial();
        $storage = new TraceStorage($idGeneratorMock);

        $storage->setSpanId($spanId);

        $this->assertEquals($spanId, $storage->getSpanId());
    }
}
