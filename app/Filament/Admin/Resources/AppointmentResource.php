<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AppointmentResource\Pages;
use App\Filament\Admin\Resources\Concerns\MultiTenantResource;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\DatePicker;

class AppointmentResource extends Resource
{

    public static function canViewAny(): bool
    {
        return true;
    }

    use MultiTenantResource;
    use HasConsistentNavigation;
    
    protected static ?string $model = Appointment::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Termine';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Termindetails')
                    ->description('Grundlegende Informationen zum Termin')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('Kunde')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required(),
                                        Forms\Components\TextInput::make('email')
                                            ->email(),
                                        Forms\Components\TextInput::make('phone'),
                                    ]),
                                    
                                Forms\Components\Select::make('staff_id')
                                    ->label('Mitarbeiter')
                                    ->relationship('staff', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('service_id')
                                    ->label('Leistung')
                                    ->relationship('service', 'name')
                                    ->searchable()
                                    ->preload(),
                                    
                                Forms\Components\Select::make('branch_id')
                                    ->label('Filiale')
                                    ->relationship('branch', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Zeitplanung')
                    ->description('Datum, Uhrzeit und Status')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Beginn')
                                    ->required()
                                    ->native(false),
                                    
                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('Ende')
                                    ->native(false),
                                    
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Ausstehend',
                                        'confirmed' => 'Bestätigt',
                                        'completed' => 'Abgeschlossen',
                                        'cancelled' => 'Abgesagt',
                                        'no_show' => 'Nicht erschienen',
                                    ])
                                    ->required(),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Notizen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Interne Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(array_merge(
                ['customer', 'staff', 'service'],
                static::getMultiTenantRelations()
            )))
            ->poll('30s')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->sortable()
                    ->searchable()
                    ->default('—')
                    ->description(fn ($record) => $record->customer?->phone),
                    
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Leistung')
                    ->searchable()
                    ->badge()
                    ->default('—'),
                    
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->sortable()
                    ->searchable()
                    ->default('—'),
                    
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Datum & Zeit')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon(fn ($record) => $record->starts_at && Carbon::parse($record->starts_at)->isPast() ? 'heroicon-o-clock' : null)
                    ->iconColor('warning')
                    ->description(function ($record) {
                        if (!$record->starts_at) return null;
                        $startsAt = Carbon::parse($record->starts_at);
                        if ($startsAt->isToday()) return '🔵 Heute';
                        if ($startsAt->isTomorrow()) return '🟢 Morgen';
                        if ($startsAt->isPast()) return '⚠️ Vergangen';
                        return '📅 ' . $startsAt->diffForHumans();
                    })
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'no_show' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ausstehend',
                        'confirmed' => 'Bestätigt',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no_show' => 'Nicht erschienen',
                        default => $state,
                    }),
                    
                static::getCompanyColumn(),
                static::getBranchColumn(),
                    
                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer')
                    ->getStateUsing(fn ($record) => $record->service?->duration ? $record->service->duration . ' Min.' : '—')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->getStateUsing(fn ($record) => $record->service?->price ? number_format($record->service->price / 100, 2, ',', '.') . ' €' : '—')
                    ->badge()
                    ->color('success')
                    ->toggleable(),
            ])
            ->filters(array_merge(
                static::getMultiTenantFilters(),
                [
                    Tables\Filters\SelectFilter::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Ausstehend',
                            'confirmed' => 'Bestätigt',
                            'completed' => 'Abgeschlossen',
                            'cancelled' => 'Abgesagt',
                            'no_show' => 'Nicht erschienen',
                        ])
                        ->multiple(),
                        
                    Tables\Filters\SelectFilter::make('staff_id')
                        ->label('Mitarbeiter')
                        ->relationship('staff', 'name')
                        ->searchable()
                        ->preload(),
                        
                    Tables\Filters\SelectFilter::make('service_id')
                        ->label('Leistung')
                        ->relationship('service', 'name')
                        ->searchable()
                        ->preload(),
                        
                    Tables\Filters\TernaryFilter::make('calcom_sync')
                        ->label('Cal.com Sync')
                        ->placeholder('Alle')
                        ->trueLabel('Mit Cal.com')
                        ->falseLabel('Ohne Cal.com')
                        ->queries(
                            true: fn (Builder $query) => $query->where(function($q) {
                                $q->whereNotNull('calcom_booking_id')
                                  ->orWhereNotNull('calcom_v2_booking_id');
                            }),
                            false: fn (Builder $query) => $query->where(function($q) {
                                $q->whereNull('calcom_booking_id')
                                  ->whereNull('calcom_v2_booking_id');
                            }),
                        ),
                        
                    Tables\Filters\TernaryFilter::make('has_call')
                        ->label('Mit Anruf')
                        ->placeholder('Alle')
                        ->trueLabel('Aus Anruf')
                        ->falseLabel('Ohne Anruf')
                        ->queries(
                            true: fn (Builder $query) => $query->whereNotNull('call_id'),
                            false: fn (Builder $query) => $query->whereNull('call_id'),
                        ),
                    
                    Tables\Filters\Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('Von')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                        DatePicker::make('until')
                            ->label('Bis')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Von: ' . Carbon::parse($data['from'])->format('d.m.Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Bis: ' . Carbon::parse($data['until'])->format('d.m.Y');
                        }
                        return $indicators;
                    }),
                ]
            ), layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye'),
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil'),
                    Action::make('complete')
                        ->label('Abschließen')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->action(fn ($record) => $record->update(['status' => 'completed'])),
                    Action::make('cancel')
                        ->label('Absagen')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->action(fn ($record) => $record->update(['status' => 'cancelled'])),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('updateStatus')
                        ->label('Status ändern')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Neuer Status')
                                ->options([
                                    'confirmed' => 'Bestätigt',
                                    'completed' => 'Abgeschlossen',
                                    'cancelled' => 'Abgesagt',
                                    'no_show' => 'Nicht erschienen',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn ($record) => $record->update(['status' => $data['status']]));
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('starts_at', 'desc')
            ->emptyStateHeading('Keine Termine vorhanden')
            ->emptyStateDescription('Erstellen Sie einen neuen Termin über den Button oben.')
            ->emptyStateIcon('heroicon-o-calendar');
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
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'view' => Pages\ViewAppointment::route('/{record}'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'customer.name', 'staff.name', 'service.name', 'notes'];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('starts_at', today())->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereDate('starts_at', today())->count() > 0 ? 'primary' : 'gray';
    }
}