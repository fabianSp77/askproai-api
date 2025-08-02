<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KnowledgeBaseController;

// Knowledge base routes - requires customer authentication
// These routes are loaded within the portal prefix, so they'll be /portal/knowledge/*
Route::prefix('knowledge')
    ->name('portal.knowledge.')
    ->middleware(['auth:customer', 'verified:customer'])
    ->group(function () {
        Route::get('/', [KnowledgeBaseController::class, 'index'])->name('index');
        Route::get('/search', [KnowledgeBaseController::class, 'search'])->name('search');
        Route::get('/category/{slug}', [KnowledgeBaseController::class, 'category'])->name('category');
        Route::get('/tag/{slug}', [KnowledgeBaseController::class, 'tag'])->name('tag');
        Route::get('/{slug}', [KnowledgeBaseController::class, 'show'])->name('show');
        Route::post('/{slug}/feedback', [KnowledgeBaseController::class, 'feedback'])->name('feedback');
    });