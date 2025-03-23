<?php

namespace App\Services\Support;

use App\Models\Support\ApiRequest;

class ApiRequestService
{
    /**
     * Registra a requisição na tabela `api_requests`.
     *
     * @param string $url
     * @param array $body
     * @param string $method
     * @param string $api
     * @return \App\Models\Support\ApiRequest
     */
    public function log(string $url, array $body, string $method, string $api, array $headers): ApiRequest
    {
        return ApiRequest::create([
            'body' => json_encode($body),
            'method' => $method,
            'headers' => json_encode($headers),
            'url' => $url,
            'api' => $api,
        ]);
    }
}
