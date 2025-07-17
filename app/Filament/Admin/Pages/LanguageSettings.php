<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class LanguageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static ?string $navigationLabel = 'Spracheinstellungen';
    protected static ?string $navigationGroup = 'Einstellungen';
    protected static ?int $navigationSort = 50;
    protected static string $view = 'filament.admin.pages.language-settings';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $user = Auth::user();
        
        $this->form->fill([
            'interface_language' => $user->interface_language ?? 'de',
            'content_language' => $user->content_language ?? 'de',
            'auto_translate_content' => $user->auto_translate_content ?? true,
        ]);
    }
    
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Spracheinstellungen')
                ->description('Passen Sie Ihre persönlichen Spracheinstellungen an')
                ->schema([
                    Forms\Components\Select::make('interface_language')
                        ->label('Oberflächensprache')
                        ->helperText('Die Sprache der Benutzeroberfläche (Menüs, Buttons, etc.)')
                        ->options([
                            'de' => '🇩🇪 Deutsch',
                            'en' => '🇬🇧 English',
                            'es' => '🇪🇸 Español',
                            'fr' => '🇫🇷 Français',
                            'it' => '🇮🇹 Italiano',
                            'tr' => '🇹🇷 Türkçe',
                        ])
                        ->required()
                        ->native(false),
                        
                    Forms\Components\Select::make('content_language')
                        ->label('Inhaltssprache')
                        ->helperText('Ihre bevorzugte Sprache für Anrufzusammenfassungen, Transkripte und andere Inhalte')
                        ->options([
                            'de' => '🇩🇪 Deutsch',
                            'en' => '🇬🇧 English',
                            'es' => '🇪🇸 Español',
                            'fr' => '🇫🇷 Français',
                            'it' => '🇮🇹 Italiano',
                            'tr' => '🇹🇷 Türkçe',
                        ])
                        ->required()
                        ->native(false),
                        
                    Forms\Components\Toggle::make('auto_translate_content')
                        ->label('Automatische Übersetzung')
                        ->helperText('Inhalte automatisch in Ihre bevorzugte Sprache übersetzen')
                        ->inline(false),
                ]),
                
            Forms\Components\Section::make('Übersetzungsbeispiel')
                ->description('So funktioniert die automatische Übersetzung')
                ->schema([
                    Forms\Components\ViewField::make('translation_example')
                        ->view('filament.forms.components.translation-example'),
                ])
                ->collapsible(),
        ];
    }
    
    protected function getFormStatePath(): ?string
    {
        return 'data';
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        $user = Auth::user();
        $user->update([
            'interface_language' => $data['interface_language'],
            'content_language' => $data['content_language'],
            'auto_translate_content' => $data['auto_translate_content'],
        ]);
        
        Notification::make()
            ->title('Spracheinstellungen gespeichert')
            ->success()
            ->send();
            
        // If interface language changed, redirect to reload the page
        if ($user->interface_language !== $data['interface_language']) {
            $this->redirect(static::getUrl());
        }
    }
    
    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Speichern')
                ->action('save')
                ->color('primary'),
        ];
    }
}