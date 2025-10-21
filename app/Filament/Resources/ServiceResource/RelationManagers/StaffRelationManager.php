<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StaffRelationManager extends RelationManager
{
    protected static string $relationship = 'staff';
    protected static ?string $title = 'Mitarbeiter';
    protected static ?string $icon = 'heroicon-o-users';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->disabled(),

            Forms\Components\Toggle::make('is_primary')
                ->label('Primärer Mitarbeiter'),

            Forms\Components\Toggle::make('can_book')
                ->label('Kann Termine buchen'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Name'),

            Tables\Columns\TextColumn::make('position')
                ->label('Position'),

            Tables\Columns\TextColumn::make('email')
                ->label('Email'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DetachAction::make(),
        ]);
    }
}
