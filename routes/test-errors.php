<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::get('/test-error-logging', function () {
    Log::info('Test error logging - INFO level');
    Log::error('Test error logging - ERROR level');
    Log::critical('Test error logging - CRITICAL level', [
        'test_data' => [
            'timestamp' => now()->toDateTimeString(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
        ]
    ]);
    
    return response()->json([
        'message' => 'Error logging test completed',
        'log_path' => storage_path('logs/laravel.log'),
        'writable' => is_writable(storage_path('logs/laravel.log')),
    ]);
});

Route::get('/test-500-error', function () {
    throw new \Exception('Test 500 error for logging');
});