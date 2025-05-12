<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RetellWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $signature = $request->header('retell-signature');

        $computed = hash_hmac(
            'sha256',
            $request->getContent(),
            config('app.retell_webhook_secret')
        );

        if (! hash_equals($computed, $signature)) {
            Log::warning('Retell-Webhook: ungültige Signatur', [
                'expected' => $computed,
                'given'    => $signature,
            ]);
            return response('invalid signature', 400);
        }

        // 👉 Hier echte Payload-Verarbeitung
        Log::info('Retell-Webhook OK', $request->all());

        return response()->noContent();   // 204
    }
}
