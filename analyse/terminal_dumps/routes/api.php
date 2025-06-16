<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalcomWebhookController;
use App\Http\Controllers\RetellWebhookController;

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

// ---- Retell Webhook (POST) --------------------------------------

// Unterstützt beide Pfade für maximale Kompatibilität:
Route::post('retell/webhook', [RetellWebhookController::class, '__invoke']);
Route::post('webhooks/retell', [RetellWebhookController::class, '__invoke']);
