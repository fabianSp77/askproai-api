<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RetellV1Service          // Realtime-API – wartet auf TLS-Fix
{
    private string $url;
    private string $token;

    public function __construct()
    {
        $baseUrl = rtrim(config('services.retellai.base_url'), '/');
        $this->url = $baseUrl . '/v1';
        $this->token = config('services.retellai.api_key');
    }

    public function calls(int $limit = 20): ?array
    {
        try {
            return Http::withToken($this->token)
                       ->get($this->url . '/calls', ['limit' => $limit])
                       ->throw()
                       ->json();
        } catch (\Throwable $e) {
            // Wird solange Cloudflare blockiert, sauber abgefangen
            report($e);
            return null;
        }
    }
}
