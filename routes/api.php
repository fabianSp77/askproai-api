<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RetellConversationEndedController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Nur stateless Endpunkte (kein CSRF). Retell ruft diesen exakt an.
*/

Route::post(
    '/webhooks/retell-conversation-ended',
    RetellConversationEndedController::class      // Controller wird direkt aufgerufen
)->name('retell.webhook');
