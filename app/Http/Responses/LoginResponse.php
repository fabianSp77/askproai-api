<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        // Log the login success
        \Log::info('Custom LoginResponse called', [
            'user' => auth()->user()?->email,
            'session_id' => session()->getId(),
            'auth_check' => auth()->check(),
        ]);
        
        // Ensure session is saved
        session()->save();
        
        // Redirect to admin dashboard
        return redirect('/admin');
    }
}