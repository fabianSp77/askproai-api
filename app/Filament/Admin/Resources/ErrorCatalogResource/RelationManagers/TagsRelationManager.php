<?php

namespace App\Filament\Admin\Resources\ErrorCatalogResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TagsRelationManager extends RelationManager
{
    protected static string $relationship = 'tags';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(50),
                Forms\Components\ColorPicker::make('color')
                    ->required()
                    ->default('#6B7280'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => new \Illuminate\Support\HtmlString(
                        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: ' . $record->color . '20; color: ' . $record->color . '">' . $state . '</span>'
                    )),
                Tables\Columns\TextColumn::make('errors_count')
                    ->counts('errors')
                    ->label('Used in'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}