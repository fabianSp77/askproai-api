<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class UserLanguageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static ?string $navigationLabel = 'Spracheinstellungen';
    protected static ?string $navigationGroup = 'Einstellungen';
    protected static ?int $navigationSort = 50;
    protected static string $view = 'filament.admin.pages.user-language-settings';
    
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
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sprachpräferenzen')
                    ->description('Passen Sie Ihre persönlichen Spracheinstellungen an')
                    ->schema([
                        Forms\Components\Select::make('interface_language')
                            ->label('Oberflächensprache')
                            ->helperText('Die Sprache der Benutzeroberfläche')
                            ->options([
                                'de' => '🇩🇪 Deutsch',
                                'en' => '🇬🇧 English',
                                'es' => '🇪🇸 Español',
                                'fr' => '🇫🇷 Français',
                                'it' => '🇮🇹 Italiano',
                                'tr' => '🇹🇷 Türkçe',
                                'pl' => '🇵🇱 Polski',
                                'ru' => '🇷🇺 Русский',
                            ])
                            ->required()
                            ->native(false),
                            
                        Forms\Components\Select::make('content_language')
                            ->label('Bevorzugte Inhaltssprache')
                            ->helperText('Anrufinhalte werden automatisch in diese Sprache übersetzt')
                            ->options([
                                'de' => '🇩🇪 Deutsch',
                                'en' => '🇬🇧 English',
                                'es' => '🇪🇸 Español',
                                'fr' => '🇫🇷 Français',
                                'it' => '🇮🇹 Italiano',
                                'tr' => '🇹🇷 Türkçe',
                                'pl' => '🇵🇱 Polski',
                                'ru' => '🇷🇺 Русский',
                            ])
                            ->required()
                            ->native(false),
                            
                        Forms\Components\Toggle::make('auto_translate_content')
                            ->label('Automatische Übersetzung')
                            ->helperText('Anrufinhalte automatisch in Ihre bevorzugte Sprache übersetzen')
                            ->inline(false),
                    ]),
                    
                Forms\Components\Section::make('Hinweise')
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->content('Wenn die automatische Übersetzung aktiviert ist, werden alle Anruftexte (Zusammenfassungen, Transkripte, Anrufgründe) automatisch in Ihre bevorzugte Sprache übersetzt. Sie können jederzeit zwischen Original und Übersetzung wechseln.'),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
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
    }
}