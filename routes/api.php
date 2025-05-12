<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CalcomBookingController;
use App\Http\Controllers\RetellConversationEndedController;
use App\Http\Controllers\RetellWebhookController;
use App\Http\Middleware\VerifyRetellSignature;
use App\Http\Middleware\LogCalcom;

/*
|--------------------------------------------------------------------------
| API Routes  (Prefix „api/“, Middleware-Gruppe „api“)
|--------------------------------------------------------------------------
*/

/* --------------------------------------------------------------------- */
/*  Cal.com – Buchungs-API                                               */
/* --------------------------------------------------------------------- */
Route::post(
    'calcom/bookings',
    [CalcomBookingController::class, 'createBooking']
)
    ->middleware(LogCalcom::class)          // Request/Response-Logging
    ->name('calcom.bookings.create');

/* --------------------------------------------------------------------- */
/*  Retell Webhooks                                                      */
/* --------------------------------------------------------------------- */
Route::post(
    'webhooks/retell-conversation-ended',
    RetellConversationEndedController::class
)->name('retell.webhook.ended');

Route::post(
    'webhooks/retell',
    RetellWebhookController::class
)->middleware(VerifyRetellSignature::class)
  ->name('retell.webhook.main');

Route::post(
    'webhooks/retell-inbound',
    RetellWebhookController::class
)->middleware(VerifyRetellSignature::class)
  ->name('retell.webhook.inbound');
