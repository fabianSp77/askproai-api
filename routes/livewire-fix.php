<?php

use Illuminate\Support\Facades\Route;

// Add fallback for missing Livewire routes
Route::any('/livewire/{method}', function ($method) {
    \Log::warning('Livewire route not found', [
        'method' => $method,
        'request_method' => request()->method(),
        'component' => request()->input('components.0.snapshot')
    ]);
    
    // Return empty response to prevent popup
    return response()->json([
        'effects' => [],
        'serverMemo' => []
    ]);
})->where('method', '.*');