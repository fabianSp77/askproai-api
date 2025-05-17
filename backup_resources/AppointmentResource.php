<?php

namespace App\Filament\Admin\Resources;

use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    /* ───── Basis ───── */
    protected static ?string $model           = Appointment::class;
    protected static ?string $navigationIcon  = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Buchungen';

    /** Navigation immer sichtbar (später per Policy einschränken). */
    public static function canViewAny(): bool
    {
        return true;
    }

    /* ───── Formular (Create / Edit) ───── */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'name')
                ->label('Kunde')
                ->required(),

            Forms\Components\TextInput::make('external_id')
                ->maxLength(255),

            Forms\Components\DateTimePicker::make('starts_at')
                ->label('Start'),

            Forms\Components\DateTimePicker::make('ends_at')
                ->label('Ende'),

            Forms\Components\Textarea::make('payload')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('status')
                ->default('pending')
                ->required(),
        ]);
    }

    /* ───── Tabelle (List) ───── */
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),

            Tables\Columns\TextColumn::make('customer.name')
                ->label('Kunde')
                ->searchable(),

            Tables\Columns\TextColumn::make('external_id')
                ->label('Externe ID')
                ->searchable(),

            Tables\Columns\TextColumn::make('starts_at')
                ->dateTime('d.m.Y H:i')
                ->sortable(),

            Tables\Columns\TextColumn::make('ends_at')
                ->dateTime('d.m.Y H:i')
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->badge(),
        ]);
    }
}
