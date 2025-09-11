<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;

class WorkingLogin extends BaseLogin
{
    protected static string $view = 'auth.filament-login';
    
    public function mount(): void
    {
        parent::mount();
        
        // Force initialize the form with schema
        if (method_exists($this, 'form')) {
            $this->form(
                $this->makeForm()
                    ->schema([
                        TextInput::make('email')
                            ->label('E-Mail-Adresse')
                            ->email()
                            ->required()
                            ->autocomplete('email')
                            ->autofocus(),
                        TextInput::make('password')
                            ->label('Passwort')
                            ->password()
                            ->required()
                            ->autocomplete('current-password'),
                        Checkbox::make('remember')
                            ->label('Angemeldet bleiben'),
                    ])
                    ->statePath('data')
            );
        }
    }
    
    /**
     * Ensure the form is properly initialized
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('E-Mail-Adresse')
                    ->email()
                    ->required()
                    ->autocomplete('email')
                    ->autofocus(),
                TextInput::make('password')
                    ->label('Passwort')
                    ->password()
                    ->required()
                    ->autocomplete('current-password'),
                Checkbox::make('remember')
                    ->label('Angemeldet bleiben'),
            ])
            ->statePath('data');
    }
}