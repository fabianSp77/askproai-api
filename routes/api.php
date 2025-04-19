<?php // routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DirectCalcomController;
use App\Http\Controllers\RetellWebhookController;
use App\Http\Controllers\KundeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// +++ NEUE MINIMALE TEST-ROUTE (ganz oben) +++
Route::get('/minimal-test', function () {
    // Logge, dass die Route erreicht wurde (falls Logging funktioniert)
    try {
         \Illuminate\Support\Facades\Log::channel('single')->debug('Minimal test route reached!');
    } catch (\Throwable $e) {
         // Ignoriere Fehler beim Loggen hier, Hauptsache die Antwort kommt
    }
    return response()->json(['message' => 'Minimal test OK']);
});
// +++ ENDE NEUE ROUTE +++


// --- Bestehende Routen bleiben unverändert ---

// Öffentliche Routen - ohne Authentifizierung
Route::post('/calcom/check-availability', [DirectCalcomController::class, 'checkAvailability']);
Route::post('/calcom/create-booking', [DirectCalcomController::class, 'createBooking']);
Route::post('/direct-calcom/check-availability', [DirectCalcomController::class, 'checkAvailability']);
Route::post('/direct-calcom/create-booking', [DirectCalcomController::class, 'createBooking']);
Route::post('/webhooks/retell', [RetellWebhookController::class, 'processWebhook']);

// Öffentliche Test-Route
Route::get('/ping', function () {
    return ['message' => 'API aktiv!', 'status' => 'online'];
});

// Geschützte Routen - erfordern Authentifizierung
Route::middleware('auth:api')->group(function () {
    // Benutzer-Informationen
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    // Authentifizierter Test-Endpunkt
    Route::get('/test', function (Request $request) {
        return [
            'message' => 'API funktioniert!',
            'user' => $request->user()->name,
            'user_id' => $request->user()->id
        ];
    });
    // Kunden-Routen
    Route::prefix('kunden')->group(function () {
        Route::get('/', [KundeController::class, 'index']);
        Route::post('/', [KundeController::class, 'store']);
        Route::get('/{kunde}', [KundeController::class, 'show']);
        Route::put('/{kunde}', [KundeController::class, 'update']);
        Route::delete('/{kunde}', [KundeController::class, 'destroy']);
    });
});
use App\Http\Controllers\ZeitinfoController;
Route::match(['get','post'], '/zeitinfo', [\App\Http\Controllers\ZeitinfoController::class, 'jetzt']);
