<?php

namespace App\Services\Support;

use App\Models\Support\ApiResponse;

class ApiResponseService
{
    /**
     * Registra a resposta da API na tabela `api_responses`.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param string $apiRequestId
     * @param string $url
     * @param string $api
     * @return void
     */
    public function log($response, string $apiRequestId, string $method, string $url, string $api): void
    {
        ApiResponse::create([
            'method' => $method,
            'url' => $url,
            'body' => $response->body(),
            'status' => $response->status(),
            'api_request_id' => $apiRequestId,
            'api' => $api,
        ]);
    }
}
