<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Support\Responsable;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract, Responsable
{
    protected string $redirectUrl;
    
    public function __construct()
    {
        $this->redirectUrl = Filament::getUrl();
    }
    
    public function toResponse($request): RedirectResponse
    {
        // Log the login success
        \Log::info('Custom LoginResponse called', [
            'user' => auth()->user()?->email,
            'session_id' => session()->getId(),
            'auth_check' => auth()->check(),
            'is_livewire' => $request->hasHeader('X-Livewire'),
            'redirect_to' => $this->redirectUrl,
        ]);
        
        // Ensure session is saved
        session()->save();
        
        // ALWAYS return a RedirectResponse, never a Livewire Redirector
        // This ensures compatibility with all middleware
        $response = redirect()->intended($this->redirectUrl);
        
        // Ensure it's the right type
        if (!($response instanceof RedirectResponse)) {
            $response = new RedirectResponse($this->redirectUrl);
        }
        
        return $response;
    }
}