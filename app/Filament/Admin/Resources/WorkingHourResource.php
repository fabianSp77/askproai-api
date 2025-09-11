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
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Working Hours';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = true;

    // Temporarily disable authorization for testing
    public static function canViewAny(): bool
    {
        return true; // Allow all access for testing
    }
    
    public static function canView($record): bool
    {
        return true; // Allow view access for testing
    }
    
    public static function canCreate(): bool
    {
        return true; // Allow create access for testing
    }
    
    public static function canEdit($record): bool
    {
        return true; // Allow edit access for testing
    }
    
    public static function canDelete($record): bool
    {
        return true; // Allow delete access for testing
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('staff_id')
                    ->relationship('staff', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('weekday')
                    ->required()
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                    ]),
                Forms\Components\TimePicker::make('start')
                    ->required(),
                Forms\Components\TimePicker::make('end')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff Member')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('weekday')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('start')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end')
                    ->time('H:i'),
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
                Tables\Actions\ViewAction::make(),
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
            'view' => Pages\ViewWorkingHourFixed::route('/{record}'),
            'edit' => Pages\EditWorkingHour::route('/{record}/edit'),
        ];
    }
}
