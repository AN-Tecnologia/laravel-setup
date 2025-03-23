<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Support\ClientRequest;
use Illuminate\Support\Str;

class LogRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $content = $request->getContent();

        if (!is_string($content)) {
            return response()->json(['error' => 'Invalid payload format'], 400);
        }

        $clientRequest = ClientRequest::create([
            'url' => Str::limit($request->fullUrl(), 255),
            'method' => $request->method(),
            'body' => Str::limit($content, 65535),
            'ip_address' => $request->ip(),
            'headers' => json_encode($request->header()),
        ]);

        session(['client_request_id' => $clientRequest->id]);

        return $next($request);
    }
}
