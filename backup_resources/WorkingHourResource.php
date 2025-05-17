<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WorkingHourResource\Pages;
use App\Filament\Admin\Resources\WorkingHourResource\RelationManagers;
use App\Models\WorkingHour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkingHourResource extends Resource
{
    protected static ?string $model = WorkingHour::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Stammdaten';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('staff_id')
                    ->relationship('staff', 'name')
                    ->required(),
                Forms\Components\TextInput::make('weekday')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('start')
                    ->required(),
                Forms\Components\TextInput::make('end')
                    ->required(),
            ]);
    /** Navigation immer sichtbar */
    public static function canViewAny(): bool
    {
        return true;   // TODO: Policy/Shield nutzen
    }

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('weekday')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start'),
                Tables\Columns\TextColumn::make('end'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkingHours::route('/'),
            'create' => Pages\CreateWorkingHour::route('/create'),
            'edit' => Pages\EditWorkingHour::route('/{record}/edit'),
        ];
    }
}
