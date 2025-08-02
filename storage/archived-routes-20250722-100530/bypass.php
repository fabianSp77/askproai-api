<?php
use Illuminate\Support\Facades\Route;

// Direct access routes without any middleware
Route::get("/business-direct", function() {
    return view("portal.direct-access");
})->withoutMiddleware(["web", "auth", "csrf"]);

Route::get("/api/business-direct/data", function() {
    return response()->json([
        "success" => true,
        "stats" => [
            "calls_today" => 12,
            "appointments_today" => 5,
            "new_customers" => 3,
            "revenue_today" => 245.50
        ]
    ]);
})->withoutMiddleware(["web", "auth", "csrf"]);
