<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalcomWebhookController;
use App\Http\Controllers\RetellWebhookController;   // ← HIERHER!

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

// Retell Webhook (POST)
Route::post('retell/webhook', [RetellWebhookController::class, '__invoke']);
Route::get('/health', [\App\Http\Controllers\HealthCheckController::class, '__invoke']);
