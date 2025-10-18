<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PhoneNumbersRelationManager extends RelationManager
{
    protected static string $relationship = 'phoneNumbers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Telefonnummer Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('phone_number')
                                    ->required()
                                    ->tel()
                                    ->maxLength(20),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'main' => 'Main',
                                        'support' => 'Support',
                                        'sales' => 'Sales',
                                        'mobile' => 'Mobile',
                                        'fax' => 'Fax',
                                    ])
                                    ->default('main')
                                    ->required(),
                            ]),
                        Forms\Components\TextInput::make('extension')
                            ->maxLength(10),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->rows(3),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_primary')
                                    ->default(false)
                                    ->helperText('Markiert als Haupttelefon'),
                                Forms\Components\Toggle::make('is_active')
                                    ->default(true)
                                    ->helperText('Ist diese Nummer aktiv?'),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone_number')
            ->columns([
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'main' => 'primary',
                        'support' => 'info',
                        'sales' => 'success',
                        'mobile' => 'warning',
                        'fax' => 'gray',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('extension')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\IconColumn::make('is_primary')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'main' => 'Main',
                        'support' => 'Support',
                        'sales' => 'Sales',
                        'mobile' => 'Mobile',
                        'fax' => 'Fax',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueLabel('Active numbers')
                    ->falseLabel('Inactive numbers')
                    ->native(false),
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->trueLabel('Primary numbers')
                    ->falseLabel('Secondary numbers')
                    ->native(false),
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