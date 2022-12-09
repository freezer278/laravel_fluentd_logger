<?php

namespace Vmorozov\LaravelRichLogs\Middleware;

use Closure;
use Illuminate\Http\Request;
use Vmorozov\LaravelRichLogs\Tracing\ParsedTraceParentHeaderValue;
use Vmorozov\LaravelRichLogs\Tracing\TraceIdStorage;

class ContinueTraceMiddleware
{
    private TraceIdStorage $traceIdStorage;

    public function __construct(TraceIdStorage $traceIdStorage)
    {
        $this->traceIdStorage = $traceIdStorage;
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

        $this->traceIdStorage->setTraceId($traceId);

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
