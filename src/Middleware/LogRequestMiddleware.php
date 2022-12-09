<?php

namespace Vmorozov\LaravelFluentdLogger\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $this->logRequest($request, $response);
    }

    private function logRequest($request, $response): void
    {
        Log::info('Request log', $this->createLogContext($request, $response));
    }

    private function createLogContext($request, $response)
    {
        return [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $this->getHeadersToLog($request),
            'request' => $request->all(),
            'duration_seconds' => microtime(true) - LARAVEL_START,
        ];
    }

    private function getHeadersToLog($request): array
    {
        $headers = $request->headers->all();

        unset($headers['cookie'], $headers['authorization']);

        return $headers;
    }
}
