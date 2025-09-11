<?php

use Illuminate\Support\Facades\Route;

// Flowbite Component Test Page
Route::get('/test/flowbite-all', function () {
    return view('flowbite-test-all');
});

// Test route for Livewire transaction component
Route::get('/test-transaction/{id}', function ($id) {
    return view('test-transaction-view', ['id' => $id]);
});