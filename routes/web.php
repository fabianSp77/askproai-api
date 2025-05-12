<?php

use Illuminate\Support\Facades\Route;

Route::get('/session-test', function (\Illuminate\Http\Request $r) {
    $r->session()->put('ping', 'pong');
    return 'session ok';
});
