<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    public function getTitle(): string|Htmlable
    {
        return __('Willkommen');
    }

    public function getHeading(): string|Htmlable
    {
        return __('Bei AskProAI anmelden');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Ihr intelligentes Gateway fuer Terminverwaltung');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus()
            ->placeholder('name@unternehmen.de')
            ->extraInputAttributes([
                'class' => 'login-input',
                'aria-describedby' => 'email-hint',
                'x-ref' => 'emailInput',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        $baseUrl = filament()->getRequestPasswordResetUrl();

        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->hint(
                filament()->hasPasswordReset()
                    ? new \Illuminate\Support\HtmlString(
                        '<a href="' . $baseUrl . '" ' .
                        'x-on:click.prevent="window.location.href=\'' . $baseUrl . '?email=\' + encodeURIComponent($refs.emailInput?.value || \'\')" ' .
                        'class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded">' .
                        __('filament-panels::pages/auth/login.actions.request_password_reset.label') .
                        '</a>'
                    )
                    : null
            )
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes([
                'class' => 'login-input',
                'aria-describedby' => 'password-hint',
            ]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label(__('filament-panels::pages/auth/login.form.remember.label'))
            ->extraAttributes([
                'class' => 'login-checkbox',
            ]);
    }
}
