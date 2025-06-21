<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HelpCenterController;

/*
|--------------------------------------------------------------------------
| Help Center Routes
|--------------------------------------------------------------------------
|
| Routes for the customer help center documentation
|
*/

// German routes
Route::prefix('hilfe')->name('help.')->group(function () {
    
    // Main help center page
    Route::get('/', [HelpCenterController::class, 'index'])->name('index');
    
    // Search functionality
    Route::get('/suche', [HelpCenterController::class, 'search'])->name('search');
    
    // Dynamic markdown file viewer - must be last to avoid conflicts
    Route::get('/{category}/{topic}', [HelpCenterController::class, 'article'])->name('article');
    
});

// English alias routes
Route::prefix('help')->group(function () {
    
    // Main help center page
    Route::get('/', [HelpCenterController::class, 'index']);
    
    // Search functionality
    Route::get('/search', [HelpCenterController::class, 'search']);
    
    // Dynamic markdown file viewer - must be last to avoid conflicts
    Route::get('/{category}/{topic}', [HelpCenterController::class, 'article']);
    
});