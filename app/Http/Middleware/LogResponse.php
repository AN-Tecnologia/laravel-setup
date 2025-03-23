<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Support\ServerResponse;
use Illuminate\Support\Str;

class LogResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response)
    {
        $method = $request->method();
        $url = Str::limit($request->fullUrl(), 255);
        $statusCode = $response->getStatusCode();
        $responseBody =  Str::limit($response->getContent(), 65535);
        $clientRequestId = session('client_request_id');

        ServerResponse::create([
            'method' => $method,
            'url' => $url,
            'status_code' => $statusCode,
            'body' => $responseBody,
            'client_request_id' => $clientRequestId,
        ]);
    }
}
