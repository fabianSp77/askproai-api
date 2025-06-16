<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;
    protected static ?string $navigationGroup = 'Buchungen';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Termine';
    protected static bool $shouldRegisterNavigation = true;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('super_admin') && !$user->hasRole('reseller')) {
            return parent::getEloquentQuery()->where('tenant_id', $user->tenant_id);
        }
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')->label('ID')->disabled(),
                Forms\Components\TextInput::make('customer_id')->label('Kunden-ID'),
                Forms\Components\TextInput::make('external_id')->label('External-ID'),
                Forms\Components\DateTimePicker::make('starts_at')->label('Startzeit'),
                Forms\Components\DateTimePicker::make('ends_at')->label('Endzeit'),
                Forms\Components\TextInput::make('status')->label('Status'),
                Forms\Components\Textarea::make('payload')->label('Payload')->rows(4),
                Forms\Components\DateTimePicker::make('created_at')->label('Angelegt')->disabled(),
                Forms\Components\DateTimePicker::make('updated_at')->label('Geändert')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('customer_id')->label('Kunde')->sortable(),
                Tables\Columns\TextColumn::make('external_id')->label('External-ID')->sortable(),
                Tables\Columns\TextColumn::make('starts_at')->label('Startzeit')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->label('Endzeit')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Erstellt am')->dateTime()->sortable(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}

