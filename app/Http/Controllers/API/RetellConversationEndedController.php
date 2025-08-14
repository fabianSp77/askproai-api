<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RetellConversationEndedRequest;
use App\Models\Call;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RetellConversationEndedController extends Controller
{
    public function __invoke(RetellConversationEndedRequest $request): JsonResponse
    {
        $data = $request->validated(); // call_id, tmp_call_id?, duration

        /** @var Call $call */
        $call = Call::firstOrCreate(
            ['retell_call_id' => $data['call_id']],
            ['tmp_call_id' => $data['tmp_call_id'] ?? null]
        );

        $call->duration_sec = $data['duration'];
        $call->cost_cents = $call->duration_sec * config('billing.price_per_second_cents', 3);
        $call->save();

        Log::info('Retell conversation ended', $data);

        return response()->json(['status' => 'ok']);
    }
}
