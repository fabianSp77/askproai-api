<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Stammdaten';

    /* Form -------------------------------------------------------------- */
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email(),
            TextInput::make('phone')->label('Telefon'),
            Textarea::make('notes')->label('Notizen')
                     ->rows(3)
                     ->columnSpan('full'),
        ]);
    }

    /* Table ------------------------------------------------------------- */
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('email'),
            Tables\Columns\TextColumn::make('phone'),
        ]);
    }

    /* Pages ------------------------------------------------------------- */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
