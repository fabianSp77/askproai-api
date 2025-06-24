<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-simple-livewire', function() {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Livewire Test</title>
        @livewireStyles
    </head>
    <body>
        <h1>Simple Livewire Test</h1>
        <div>
            Session Driver: ' . config('session.driver') . '<br>
            Session ID: ' . session()->getId() . '<br>
            CSRF Token: ' . csrf_token() . '<br>
        </div>
        
        @livewire("filament.admin.auth.login")
        
        @livewireScripts
    </body>
    </html>
    ';
});

Route::post('/debug-livewire-error', function() {
    \Log::info('Debug endpoint hit');
    
    try {
        $request = request();
        
        \Log::info('Livewire Update Request', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'session' => session()->all(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Debug info logged'
        ]);
    } catch (\Exception $e) {
        \Log::error('Debug endpoint error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});