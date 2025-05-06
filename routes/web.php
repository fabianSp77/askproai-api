<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProfileController,
    BillingController,
    TestController    // ← kannst du später entfernen
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| – Health-Check
| – Breeze-/Jetstream-Routen
| – Billing-Webhook
| – evtl. temporäre Test-Routen
| Alle Filament-Routen werden NICHT hier, sondern
|   von deinem AdminPanelProvider registriert.
*/

/*--- Health-Check --------------------------------------------------------*/
Route::get('/', fn () => response()->json(['status' => 'API online']));

/*--- Breeze / Dashboard --------------------------------------------------*/
Route::middleware(['auth', 'verified'])
    ->get('/dashboard', fn () => view('dashboard'))
    ->name('dashboard');

/*--- Profil --------------------------------------------------------------*/
Route::middleware('auth')->group(static function () {
    Route::get   ('/profile', [ProfileController::class,'edit'  ])->name('profile.edit');
    Route::patch ('/profile', [ProfileController::class,'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class,'destroy'])->name('profile.destroy');
});

/*--- Billing -------------------------------------------------------------*/
Route::middleware(['auth', 'verified'])
    ->get('/billing/checkout', [BillingController::class,'checkout'])
    ->name('billing.checkout');

Route::post('/billing/webhook', [BillingController::class,'webhook'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

/*--- TEMP-Test-Routen (kannst du löschen) --------------------------------*/
Route::get('/test',         [TestController::class,'index']);
Route::get('/session-test', fn () => 'OK');

/*--- Breeze-/Jetstream Auth ---------------------------------------------*/
require __DIR__.'/auth.php';
