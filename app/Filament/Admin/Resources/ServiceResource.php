<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $navigationGroup = 'Unternehmensstruktur';

    protected static ?int $navigationSort = 40;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        // No user logged in
        if (! $user) {
            return false;
        }

        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check specific permission or if user belongs to a company
        return $user->can('view_any_service') || $user->company_id !== null;
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        // No user logged in
        if (! $user) {
            return false;
        }

        // Super admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check specific permission
        if ($user->can('view_service')) {
            return true;
        }

        // Users can view services from their own company
        return $user->company_id === $record->company_id;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        // No user logged in
        if (! $user) {
            return false;
        }

        // Super admin can edit all
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check specific permission
        if ($user->can('update_service')) {
            return true;
        }

        // Company admins and branch managers can edit services
        return $user->company_id === $record->company_id &&
               ($user->hasRole('company_admin') || $user->hasRole('branch_manager'));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        // No user logged in
        if (! $user) {
            return false;
        }

        // Super admin can create
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check specific permission
        if ($user->can('create_service')) {
            return true;
        }

        // Company admins and branch managers can create services
        return $user->company_id !== null &&
               ($user->hasRole('company_admin') || $user->hasRole('branch_manager'));
    }

    use HasConsistentNavigation;

    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Leistungen';

    public static function form(Form $form): Form
    {
        $companies = \App\Models\Company::all()->pluck('name', 'id')->toArray();
        // Für das Beispiel: Wir holen nur EventTypes von der ersten Company!
        $apiKey = env('CALCOM_API_KEY');
        $eventTypeOptions = [];
        $companyEventTypeIds = [];
        if (! empty($companies)) {
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
            ->modifyQueryUsing(fn ($query) => $query->with(['company']))
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->sortable()
                    ->searchable()
                    ->getStateUsing(fn ($record) => $record?->company?->name ?? '-'),
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
                    ->getStateUsing(fn ($record) => $record->calcom_event_type_id ?: 'Von Unternehmen'),
                Tables\Columns\TextColumn::make('company.calcom_event_type_id')
                    ->label('Company-Default EventType')
                    ->sortable(),
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
            'index' => ServiceResource\Pages\ListServices::route('/'),
            'create' => ServiceResource\Pages\CreateService::route('/create'),
            'edit' => ServiceResource\Pages\EditService::route('/{record}/edit'),
        ];
    }
}
