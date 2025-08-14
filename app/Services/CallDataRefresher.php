<?php

namespace App\Services;

use App\Models\Call;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallDataRefresher
{
    public function refresh(Call $call): bool
    {
        if (! $call->call_id) {
            Log::warning('Refresh aborted – call_id empty', ['db_id' => $call->id]);

            return false;
        }

        $base = rtrim(config('services.retell.base'), '/');   // z. B. https://api.retellai.com
        $token = config('services.retell.token');              // Bearer-Key

        // --- Retell v2-Endpoint ------------------------------------------------
        $url = "{$base}/v2/get-call/{$call->call_id}";

        $res = Http::withToken($token)->get($url);

        Log::info('Retell-status', ['code' => $res->status()]);
        Log::info('Retell-RAW', ['body' => $res->body()]);

        if ($res->failed()) {
            Log::warning('Retell API error', [
                'db_id' => $call->id,
                'status' => $res->status(),
            ]);

            return false;
        }

        $data = $res->json()['call'] ?? null;
        if (! $data) {
            Log::warning('Retell response missing call object', ['db_id' => $call->id]);

            return false;
        }

        // --- speichern ---------------------------------------------------------
        $call->update([
            'analysis' => $data['call_analysis'] ?? [],
            'transcript' => $data['transcript'] ?? null,
        ]);

        return true;
    }
}
