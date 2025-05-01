<?php // routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DirectCalcomController;
use App\Http\Controllers\RetellWebhookController;
use App\Http\Controllers\KundeController;
use App\Http\Controllers\ZeitinfoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Hier definierst du sämtliche API-Routen deiner Anwendung.
| Diese Datei wurde um drei Stellen ergänzt:
|   1. Root-Route   GET /                  → JSON-Status
|   2. Minimal-Test GET /minimal-test      → schnelle Debug-Antwort
|   3. Zeitinfo     GET|POST /zeitinfo     → bestehender Controller
|
*/

/* -------------------------------------------------
 | 1. Root-Route  (https://api.askproai.de/)
 * -------------------------------------------------
 | Gibt einfachen JSON-Ping zurück – praktisch für Health-Checks.
 */
Route::get('/', fn () => response()->json(['status' => 'API online']));

/* -------------------------------------------------
 | 2. Minimale Test-Route
 * -------------------------------------------------
 | Liefert ebenfalls JSON und schreibt einen Debug-Log-Eintrag.
 */
Route::get('/minimal-test', function () {
    try {
        \Illuminate\Support\Facades\Log::channel('single')->debug('Minimal test route reached!');
    } catch (\Throwable $e) {
        // Ignoriere etwaige Log-Fehler – Hauptsache Response kommt
    }
    return response()->json(['message' => 'Minimal test OK']);
});

/* -------------------------------------------------
 | 3. Öffentliche Routen – ohne Authentifizierung
 * -------------------------------------------------
*/
Route::post('/calcom/check-availability', [DirectCalcomController::class, 'checkAvailability']);
Route::post('/calcom/create-booking',        [DirectCalcomController::class, 'createBooking']);
Route::post('/direct-calcom/check-availability', [DirectCalcomController::class, 'checkAvailability']);
Route::post('/direct-calcom/create-booking',      [DirectCalcomController::class, 'createBooking']);
Route::post('/webhooks/retell', [RetellWebhookController::class, 'processWebhook']);

/* Öffentlicher Ping-Endpoint */
Route::get('/ping', fn () => ['message' => 'API aktiv!', 'status' => 'online']);

/* -------------------------------------------------
 | 4. Geschützte Routen – erfordern OAuth2 / auth:api
 * -------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    /* Benutzer-Infos */
    Route::get('/user', fn (Request $request) => $request->user());

    /* Authentifizierter Test-Endpoint */
    Route::get('/test', function (Request $request) {
        return [
            'message' => 'API funktioniert!',
            'user'    => $request->user()->name,
            'user_id' => $request->user()->id,
        ];
    });

    /* Kunden-CRUD */
    Route::prefix('kunden')->group(function () {
        Route::get('/',          [KundeController::class, 'index']);
        Route::post('/',         [KundeController::class, 'store']);
        Route::get('/{kunde}',   [KundeController::class, 'show']);
        Route::put('/{kunde}',   [KundeController::class, 'update']);
        Route::delete('/{kunde}',[KundeController::class, 'destroy']);
    });
});

/* -------------------------------------------------
 | 5. Zeitinfo-Route (GET oder POST)
 * -------------------------------------------------
*/
Route::match(['get', 'post'], '/zeitinfo', [ZeitinfoController::class, 'jetzt']);
