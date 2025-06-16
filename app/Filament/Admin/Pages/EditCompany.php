<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\CompanyResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Actions\Action;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Grunddaten')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        TextInput::make('email')
                            ->label('E-Mail')
                            ->email(),
                        TextInput::make('phone')
                            ->label('Telefon'),
                        TextInput::make('address')
                            ->label('Adresse'),
                        TextInput::make('contact_person')
                            ->label('Ansprechpartner'),
                        Toggle::make('active')
                            ->label('Aktiv')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('api-credentials')
                ->label('API Zugangsdaten')
                ->url(fn () => CompanyResource::getUrl('api-credentials', ['record' => $this->record]))
                ->icon('heroicon-o-key'),
        ];
    }
}
