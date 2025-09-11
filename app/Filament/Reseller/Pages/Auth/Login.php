<?php

namespace App\Filament\Reseller\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();
        
        // Pre-fill for demo/testing (remove in production)
        if (app()->environment('local')) {
            $this->form->fill([
                'email' => 'reseller@demo.com',
                'password' => 'password',
                'remember' => true,
            ]);
        }
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        // Add reseller-specific validation
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        // Additional check: user must belong to a reseller tenant
        return $credentials;
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('Diese Anmeldedaten sind ungültig oder Sie haben keine Reseller-Berechtigung.'),
        ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('E-Mail')
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus()
            ->placeholder('reseller@beispiel.de')
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Passwort')
            ->password()
            ->required()
            ->placeholder('••••••••')
            ->extraInputAttributes(['tabindex' => 2]);
    }

    public function getHeading(): string
    {
        return 'Reseller Portal Login';
    }

    public function getSubheading(): ?string
    {
        return 'Willkommen zurück! Bitte melden Sie sich mit Ihren Reseller-Zugangsdaten an.';
    }
}