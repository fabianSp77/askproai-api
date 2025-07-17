<?php

namespace App\Filament\Admin\Resources\ErrorCatalogResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PreventionTipsRelationManager extends RelationManager
{
    protected static string $relationship = 'preventionTips';

    protected static ?string $recordTitleAttribute = 'tip';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->minValue(1),
                Forms\Components\Textarea::make('tip')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('category')
                    ->required()
                    ->options([
                        'configuration' => 'Configuration',
                        'monitoring' => 'Monitoring',
                        'testing' => 'Testing',
                        'deployment' => 'Deployment',
                    ])
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tip')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable()
                    ->label('#'),
                Tables\Columns\TextColumn::make('tip')
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'configuration' => 'info',
                        'monitoring' => 'success',
                        'testing' => 'warning',
                        'deployment' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'configuration' => 'Configuration',
                        'monitoring' => 'Monitoring',
                        'testing' => 'Testing',
                        'deployment' => 'Deployment',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}