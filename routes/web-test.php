<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->get('/test-admin', function () {
    return "Hello from authenticated route. User: " . auth()->user()->email . " (company_id: " . auth()->user()->company_id . ")";
});

// Test endpoint: Render booking form directly for screenshot testing
Route::get('/test/booking-form', function () {
    // Authenticate as test user
    $user = \App\Models\User::where('email', 'admin@askproai.de')->first();
    if ($user) {
        auth()->login($user);
    }

    // Render the booking component directly
    return view('livewire.appointment-booking-flow', [
        'companyId' => $user->company_id ?? 1,
        'preselectedServiceId' => null,
        'preselectedSlot' => null,
    ]);
});
