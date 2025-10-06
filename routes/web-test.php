<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->get('/test-admin', function () {
    return "Hello from authenticated route. User: " . auth()->user()->email . " (company_id: " . auth()->user()->company_id . ")";
});
