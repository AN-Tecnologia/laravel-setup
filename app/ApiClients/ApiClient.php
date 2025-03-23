<?php

namespace App\ApiClients;

use App\Models\Support\ApiRequest;
use App\Services\Support\ApiRequestService;
use App\Services\Support\ApiResponseService;
use Illuminate\Http\Client\Response;

class ApiClient
{
    /**
     * Log the request details.
     *
     * @param Request $request
     * @return ApiRequest
     */
    public function logRequest(string $method, array $body, array $headers, string $url): ApiRequest
    {
        return app(ApiRequestService::class)->log($url, $body, $method, get_called_class(), $headers);
    }

    /**
     * Log the response details.
     *
     * @param Response $response
     * @return void
     */
    public function logResponse(Response $response, ?ApiRequest $request = null): void
    {
        app(ApiResponseService::class)->log($response, $request?->id, $request?->method, $request?->url, get_called_class());
    }
}
