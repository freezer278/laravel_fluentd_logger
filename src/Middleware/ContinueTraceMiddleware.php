<?php

namespace Vmorozov\LaravelFluentdLogger\Middleware;

use Closure;
use Illuminate\Http\Request;
use Vmorozov\LaravelFluentdLogger\Tracing\ParsedTraceParentHeaderValue;
use Vmorozov\LaravelFluentdLogger\Tracing\TraceStorage;

class ContinueTraceMiddleware
{
    private TraceStorage $traceStorage;

    public function __construct(TraceStorage $traceIdStorage)
    {
        $this->traceStorage = $traceIdStorage;
    }

    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $traceId = $this->getTraceIdFromGetParams($request) ?? $this->getTraceIdFromTraceparentHeader($request);

        if (! $traceId) {
            return $next($request);
        }

        $this->traceStorage->setTraceId($traceId);

        return $next($request);
    }

    /**
     * @param  Request  $request
     */
    private function getTraceIdFromGetParams($request): ?string
    {
        return $request->get('trace_id');
    }

    /**
     * @param  Request  $request
     */
    private function getTraceIdFromTraceparentHeader($request): ?string
    {
        if (! $request->hasHeader('traceparent')) {
            return null;
        }

        if (! $parsedHeader = ParsedTraceParentHeaderValue::make($request->header('traceparent'))) {
            return null;
        }

        return $parsedHeader->getTraceId();
    }
}
