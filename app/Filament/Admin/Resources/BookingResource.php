<?php

namespace App\Filament\Admin\Resources;

use App\Models\Booking;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class BookingResource extends Resource
{
    protected static ?string $model            = Booking::class;
    protected static ?string $navigationGroup  = 'Buchungen';
    protected static ?string $navigationIcon   = 'heroicon-o-calendar-days';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('service_id')
                ->relationship('service', 'name')
                ->required(),

            Forms\Components\Select::make('staff_id')
                ->relationship('staff', 'name')
                ->required(),

            Forms\Components\DateTimePicker::make('starts_at')->required(),
            Forms\Components\DateTimePicker::make('ends_at')->required(),

            Forms\Components\TextInput::make('status')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('starts_at')->dateTime(),
            Tables\Columns\TextColumn::make('ends_at')->dateTime(),
            Tables\Columns\TextColumn::make('staff.name')->label('Mitarbeiter'),
            Tables\Columns\TextColumn::make('service.name')->label('Leistung'),
            Tables\Columns\TextColumn::make('status'),
        ]);
    }
}
