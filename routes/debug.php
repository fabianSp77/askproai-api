<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/_debug/headers', function (Request $request) {
    return response()->json($request->headers->all());
});
