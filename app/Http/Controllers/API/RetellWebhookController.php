<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RetellWebhookController
{
    public function __invoke(Request $request): Response
    {
        // TODO: echte Verarbeitung
        Log::info('Retell‑Webhook OK', $request->all());

        return response()->noContent();   // 204
    }
}
