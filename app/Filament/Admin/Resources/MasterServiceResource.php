<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MasterServiceResource\Pages;
use App\Models\MasterService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class MasterServiceResource extends Resource
{
    protected static ?string $model = MasterService::class;
    protected static ?string $navigationGroup = 'Unternehmensstruktur';
    protected static ?int $navigationSort = 40;
    protected static ?string $navigationLabel = 'Leistungen';
    protected static ?string $modelLabel = 'Master Service';
    protected static ?string $pluralModelLabel = 'Master Services';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service-Informationen')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Unternehmen'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Service-Name'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Beschreibung'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Service-Details')
                    ->schema([
                        Forms\Components\TextInput::make('duration_minutes')
                            ->required()
                            ->numeric()
                            ->default(30)
                            ->label('Dauer (Minuten)'),

                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('€')
                            ->label('Preis'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Aktiv'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Integrationen')
                    ->schema([
                        Forms\Components\TextInput::make('cal_event_type_id')
                            ->label('Cal.com Event Type ID')
                            ->numeric()
                            ->helperText('Die ID des Event Types in Cal.com'),

                        Forms\Components\Textarea::make('retell_prompt_template')
                            ->label('Retell.ai Prompt Template')
                            ->rows(5)
                            ->columnSpanFull()
                            ->helperText('Template für die KI-Anrufannahme'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record?->company?->name ?? '-'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Service-Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Dauer')
                    ->suffix(' Min.')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->label('Unternehmen'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMasterServices::route('/'),
            'create' => Pages\CreateMasterService::route('/create'),
            'edit' => Pages\EditMasterService::route('/{record}/edit'),
        ];
    }
}
