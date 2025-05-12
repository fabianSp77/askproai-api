<?php

use Illuminate\Support\Facades\Route;

/**  GET /api/retell-debug  – zeigt einfach „pong“ */
Route::get('/retell-debug', function () {
    return response()->json(['pong' => now()]);
});
