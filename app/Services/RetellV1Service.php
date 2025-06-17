<?php
// MARKED_FOR_DELETION - 2025-06-17


namespace App\Services;

use Illuminate\Support\Facades\Http;

class RetellV1Service          // Realtime-API – wartet auf TLS-Fix
{
    private string $url   = 'https://api.retellai.com/v1';
    private string $token;

    public function __construct()
    {
        $this->token = config('services.retell.api_key');
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
