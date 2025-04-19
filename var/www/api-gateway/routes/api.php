<?php
use App\Http\Controllers\DirectCalcomController;
use App\Http\Controllers\RetellWebhookController;
use App\Http\Controllers\KundeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
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
