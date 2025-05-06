<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessRetellCallJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RetellWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // 👉 TODO: Wenn Retell einen Signatur-Header liefert, hier prüfen.
        ProcessRetellCallJob::dispatch($request->all());

        // Retell braucht immer 200, sonst versucht es re-delivery.
        return response('ok', 200);
    }
}
