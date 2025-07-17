<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HelpCenterController;
use App\Http\Controllers\HelpCenterSitemapController;

/*
|--------------------------------------------------------------------------
| Help Center Routes
|--------------------------------------------------------------------------
|
| These routes handle the help center functionality including articles,
| search, analytics, and feedback.
|
*/

Route::prefix('help')->name('help.')->group(function () {
    // Main help center page
    Route::get('/', [HelpCenterController::class, 'index'])->name('index');
    
    // Search functionality
    Route::get('/search', [HelpCenterController::class, 'search'])->name('search');
    Route::post('/search/track-click', [HelpCenterController::class, 'trackSearchClick'])->name('track-click');
    
    // Article feedback
    Route::post('/feedback', [HelpCenterController::class, 'submitFeedback'])->name('feedback');
    
    // Analytics dashboard (admin only)
    Route::get('/dashboard', [HelpCenterController::class, 'dashboard'])
        ->middleware('auth:portal')
        ->name('dashboard');
    
    // Article pages (must be last to avoid conflicts)
    Route::get('/{category}/{topic}', [HelpCenterController::class, 'article'])->name('article');
});

// Sitemap for SEO
Route::get('/help-sitemap.xml', [HelpCenterSitemapController::class, 'index'])->name('help.sitemap');