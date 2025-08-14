<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RetellV2Service          //  Telefon- & Agent-API (AWS)
{
    private string $url;   // z. B. https://api.retellai.com

    private string $token;

    public function __construct()
    {
        $this->url = rtrim(config('services.retell.base_url'), '/');
        $this->token = config('services.retell.api_key');
    }

    /**
     *  Einen Anruf starten.
     *  Erforderlich   : from_number  (+E.164)
     *  Entweder ODER  : to_number    (+E.164)  **oder**  agent_id
     */
    public function createPhoneCall(array $payload): array
    {
        return Http::withToken($this->token)
            ->post($this->url.'/v2/create-phone-call', $payload)
            ->throw()               // wirft Exception bei HTTP-Fehler
            ->json();
    }
}
