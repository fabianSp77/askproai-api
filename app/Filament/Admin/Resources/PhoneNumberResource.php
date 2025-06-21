<?php

namespace App\Filament\Admin\Resources;

use App\Models\PhoneNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Admin\Resources\PhoneNumberResource\Pages;

class PhoneNumberResource extends Resource
{

    public static function canViewAny(): bool
    {
        return true;
    }

    protected static ?string $model = PhoneNumber::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'Unternehmensstruktur';
    protected static ?string $navigationLabel = 'Telefonnummern';
    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('number')
                ->label('Telefonnummer')
                ->required(),
            Forms\Components\Toggle::make('active')
                ->label('Aktiv')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('Telefonnummer'),
                Tables\Columns\IconColumn::make('active')->boolean()->label('Aktiv'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhoneNumbers::route('/'),
            'create' => Pages\CreatePhoneNumber::route('/create'),
            'edit' => Pages\EditPhoneNumber::route('/{record}/edit'),
        ];
    }
}
