<?php

use App\Http\Controllers\CalcomWebhookController;
use App\Http\Controllers\RetellWebhookController;
use Illuminate\Support\Facades\Route;   // ← HIERHER!

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ---- Cal.com Webhook -------------------------------------------

// 1) Ping-Route (GET)  ➜ ohne Signaturprüfung
Route::get('calcom/webhook', [CalcomWebhookController::class, 'ping']);

// 2) Produktiver Webhook (POST) ➜ mit Signaturprüfung
Route::post('calcom/webhook', [CalcomWebhookController::class, 'handle'])
    ->middleware('calcom.signature');

// Retell Webhook (POST) - Multiple endpoint aliases for compatibility
Route::post('retell/webhook', [RetellWebhookController::class, '__invoke']);
Route::post('webhooks/retell', [RetellWebhookController::class, '__invoke']);
