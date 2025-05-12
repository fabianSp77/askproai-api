<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RetellConversationEndedController
{
    public function __invoke(Request $request): Response
    {
        // TODO: Conversation‑Ended Event verarbeiten
        Log::info('Retell Conversation Ended', $request->all());

        return response()->noContent();   // 204
    }
}
