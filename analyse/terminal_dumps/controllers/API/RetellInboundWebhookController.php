<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Call;
use App\Services\RetellService;

class RetellInboundWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $p = $request->json()->all();

        $call = Call::create([
            'retell_call_id' => $p['call_id'] ?? Str::uuid(),
            'from_number'    => data_get($p,'call_inbound.from_number'),
            'to_number'      => data_get($p,'call_inbound.to_number'),
            'raw'            => $p,
        ]);

        return response()->json(
            RetellService::buildInboundResponse(
                'agent_9a8202a740cd3120d96fcfda1e',
                $call->from_number
            )
        );
    }
}
