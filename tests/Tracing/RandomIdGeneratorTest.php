<?php

namespace Vmorozov\LaravelFluentdLogger\Tests\Tracing;

use Vmorozov\LaravelFluentdLogger\Tests\TestCase;
use Vmorozov\LaravelFluentdLogger\Tracing\RandomIdGenerator;

class RandomIdGeneratorTest extends TestCase
{
    private RandomIdGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new RandomIdGenerator();
    }

    public function testGenerateTraceId(): void
    {
        $result = $this->generator->generateTraceId();

        $this->assertEquals(RandomIdGenerator::TRACE_ID_LENGTH, strlen($result));
        $this->assertEquals(strtolower($result), $result);
        $this->assertNotEquals(str_repeat('0', RandomIdGenerator::TRACE_ID_LENGTH), $result);
    }

    public function testGenerateSpanId(): void
    {
        $result = $this->generator->generateSpanId();

        $this->assertEquals(RandomIdGenerator::SPAN_ID_LENGTH, strlen($result));
        $this->assertEquals(strtolower($result), $result);
        $this->assertNotEquals(str_repeat('0', RandomIdGenerator::SPAN_ID_LENGTH), $result);
    }
}
