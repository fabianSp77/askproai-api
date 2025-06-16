<?php

namespace App\Filament\Admin\Resources\BranchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\MasterService;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'masterServices';
    
    protected static ?string $title = 'Zugewiesene Services';
    
    protected static ?string $modelLabel = 'Service';
    
    protected static ?string $pluralModelLabel = 'Services';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('master_service_id')
                    ->label('Master Service')
                    ->options(function () {
                        $branch = $this->ownerRecord;
                        return MasterService::where('company_id', $branch->company_id)
                            ->where('active', true)
                            ->pluck('name', 'id');
                    })
                    ->required(),
                    
                Forms\Components\TextInput::make('custom_duration')
                    ->label('Individuelle Dauer (Min)')
                    ->numeric()
                    ->placeholder('Leer = Basis-Dauer verwenden'),
                    
                Forms\Components\TextInput::make('custom_price')
                    ->label('Individueller Preis (€)')
                    ->numeric()
                    ->placeholder('Leer = Basis-Preis verwenden'),
                    
                Forms\Components\TextInput::make('custom_calcom_event_type_id')
                    ->label('Cal.com Event Type ID')
                    ->placeholder('Filialspezifische Event Type ID'),
                    
                Forms\Components\Toggle::make('active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('masterService.name')
                    ->label('Service')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('effectiveDuration')
                    ->label('Dauer (Min)')
                    ->getStateUsing(function ($record) {
                        return $record->custom_duration ?? $record->masterService->base_duration;
                    }),
                    
                Tables\Columns\TextColumn::make('effectivePrice')
                    ->label('Preis (€)')
                    ->getStateUsing(function ($record) {
                        $price = $record->custom_price ?? $record->masterService->base_price;
                        return number_format($price, 2, ',', '.');
                    }),
                    
                Tables\Columns\IconColumn::make('active')
                    ->label('Aktiv')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Service zuweisen')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function ($query) {
                        $branch = $this->ownerRecord;
                        return $query->where('company_id', $branch->company_id)
                                    ->where('active', true);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
