<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProfileController,
    BillingController
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| – Health-Check
| – Breeze- / Jetstream-Routen
| – Billing-Webhook
| Alle Filament-Routen werden automatisch
|   über den AdminPanelProvider registriert.
*/

/* --- Health-Check ------------------------------------------------------ */
Route::get('/', fn () => response()->json(['status' => 'API online']));

/* --- Breeze / Dashboard ------------------------------------------------ */
Route::middleware(['auth', 'verified'])
    ->get('/dashboard', fn () => view('dashboard'))
    ->name('dashboard');

/* --- Profil ------------------------------------------------------------ */
Route::middleware('auth')->group(static function () {
    Route::get   ('/profile',  [ProfileController::class, 'edit'  ])->name('profile.edit');
    Route::patch ('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',  [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/* --- Billing ----------------------------------------------------------- */
Route::middleware(['auth', 'verified'])
    ->get('/billing/checkout', [BillingController::class, 'checkout'])
    ->name('billing.checkout');

Route::post('/billing/webhook', [BillingController::class, 'webhook'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

/* --- Breeze- / Jetstream Auth ----------------------------------------- */
require __DIR__.'/auth.php';

//  ----  manueller POST-Handler für Filament-Login  --------------------------
use Filament\Http\Controllers\Auth\LoginController;

Route::post('admin/login', [LoginController::class, 'store'])
    ->middleware(['web'])
    ->name('filament.admin.auth.login');
