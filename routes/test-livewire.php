<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-livewire-error', function() {
    try {
        // Test basic Livewire functionality
        $component = app('livewire')->new('filament.admin.auth.login');
        return response()->json([
            'status' => 'success',
            'component' => get_class($component),
            'session_driver' => config('session.driver'),
            'csrf_token' => csrf_token(),
            'livewire_loaded' => class_exists(\Livewire\Livewire::class),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});