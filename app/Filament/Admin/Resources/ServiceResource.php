<?php

namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Service;
use Filament\Tables\Filters\SelectFilter;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'Unternehmensstruktur';
    protected static ?string $navigationLabel = 'Leistungen';
    protected static ?int $navigationSort = 40;
    protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $form): Form
    {
        $companies = \App\Models\Company::all()->pluck('name', 'id')->toArray();
        // Für das Beispiel: Wir holen nur EventTypes von der ersten Company!
        $apiKey = env('CALCOM_API_KEY');
        $eventTypeOptions = [];
        $companyEventTypeIds = [];
        if (!empty($companies)) {
            $firstCompany = \App\Models\Company::first();
            if ($firstCompany && $firstCompany->calcom_api_key) {
                $apiKey = $firstCompany->calcom_api_key;
            }
            $eventTypes = \App\Services\CalcomEventTypeSyncService::fetchEventTypes($apiKey);
            foreach ($eventTypes as $ev) {
                $eventTypeOptions[$ev['id']] = $ev['title'] . ' (' . ($ev['length'] ?? '-') . ' min)';
            }
            $companyEventTypeIds[$firstCompany->id] = $firstCompany->calcom_event_type_id;
        }

        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Name')->required(),
            Forms\Components\TextInput::make('duration')->label('Dauer (Minuten)')->numeric()->required(),
            Forms\Components\Select::make('company_id')
                ->label('Unternehmen')
                ->options($companies)
                ->required(),
            Forms\Components\Select::make('calcom_event_type_id')
                ->label('Cal.com EventType')
                ->options(['' => 'Von Unternehmen übernehmen'] + $eventTypeOptions)
                ->helperText('Leerlassen = Wert wird vom Unternehmen übernommen'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer (Min)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('calcom_event_type_id')
                    ->label('EventType (Override)')
                    ->default('Von Unternehmen')
                    ->getStateUsing(fn($record) => $record->calcom_event_type_id ?: 'Von Unternehmen'),
                Tables\Columns\TextColumn::make('company.calcom_event_type_id')
                    ->label('Company-Default EventType')
                    ->sortable()
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('company_id')
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\ServiceResource\Pages\ListServices::route('/'),
            'create' => \App\Filament\Admin\Resources\ServiceResource\Pages\CreateService::route('/create'),
            'edit' => \App\Filament\Admin\Resources\ServiceResource\Pages\EditService::route('/{record}/edit'),
        ];
    }
}
