<?php

namespace App\Filament\Admin\Resources\StaffResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\BelongsToManyMultiSelect;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $title = 'Leistungen';

    /* -------------------------------------------------------------------- */
    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            BelongsToManyMultiSelect::make('services')
                ->relationship('services', 'name')
                ->required(),
        ]);
    }

    /* -------------------------------------------------------------------- */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('price')->money('eur', locale: 'de_DE'),
        ]);
    }
}
