<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformCostResource\Pages;
use App\Models\PlatformCost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformCostResource extends Resource
{
    protected static ?string $model = PlatformCost::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';

    protected static ?string $navigationLabel = 'Plattform-Kosten';

    protected static ?string $modelLabel = 'Plattform-Kosten';

    protected static ?string $pluralModelLabel = 'Plattform-Kosten';

    protected static ?string $navigationGroup = 'Finanzen';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kostendetails')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Unternehmen'),

                        Forms\Components\Select::make('platform')
                            ->options([
                                'retell' => 'Retell.ai',
                                'twilio' => 'Twilio',
                                'calcom' => 'Cal.com',
                                'openai' => 'OpenAI',
                                'other' => 'Andere'
                            ])
                            ->required()
                            ->label('Plattform'),

                        Forms\Components\Select::make('service_type')
                            ->options([
                                'api_call' => 'API-Anruf',
                                'telephony' => 'Telefonie',
                                'subscription' => 'Abonnement',
                                'usage' => 'Nutzung',
                                'other' => 'Andere'
                            ])
                            ->required()
                            ->label('Service-Typ'),

                        Forms\Components\Select::make('cost_type')
                            ->options([
                                'usage' => 'Nutzungsbasiert',
                                'fixed' => 'Fest',
                                'subscription' => 'Abonnement',
                                'one_time' => 'Einmalig'
                            ])
                            ->required()
                            ->label('Kostenart'),

                        Forms\Components\TextInput::make('amount_cents')
                            ->numeric()
                            ->required()
                            ->label('Betrag (Cent)')
                            ->suffix('¢')
                            ->helperText('Betrag in Cent eingeben'),

                        Forms\Components\Select::make('currency')
                            ->options([
                                'EUR' => 'EUR',
                                'USD' => 'USD',
                                'GBP' => 'GBP'
                            ])
                            ->default('EUR')
                            ->required()
                            ->label('Währung'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Zeitraum & Nutzung')
                    ->schema([
                        Forms\Components\DateTimePicker::make('period_start')
                            ->required()
                            ->label('Zeitraum Start'),

                        Forms\Components\DateTimePicker::make('period_end')
                            ->label('Zeitraum Ende'),

                        Forms\Components\TextInput::make('usage_quantity')
                            ->numeric()
                            ->label('Nutzungsmenge'),

                        Forms\Components\TextInput::make('usage_unit')
                            ->label('Nutzungseinheit')
                            ->placeholder('z.B. Minuten, API-Calls, Nutzer'),

                        Forms\Components\TextInput::make('external_reference_id')
                            ->label('Externe Referenz-ID')
                            ->helperText('z.B. Retell Call ID, Twilio SID'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Metadaten')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Zusätzliche Daten')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('platform')
                    ->label('Plattform')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'retell' => 'Retell.ai',
                        'twilio' => 'Twilio',
                        'calcom' => 'Cal.com',
                        'openai' => 'OpenAI',
                        default => ucfirst($state)
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'retell' => 'info',
                        'twilio' => 'warning',
                        'calcom' => 'success',
                        'openai' => 'danger',
                        default => 'gray'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_type')
                    ->label('Service')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'api_call' => 'API-Anruf',
                        'telephony' => 'Telefonie',
                        'subscription' => 'Abonnement',
                        'usage' => 'Nutzung',
                        default => ucfirst($state)
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Betrag')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', '.') . ' €')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()
                        ->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', '.') . ' €')),

                Tables\Columns\TextColumn::make('usage_quantity')
                    ->label('Nutzung')
                    ->formatStateUsing(function ($record) {
                        if (!$record->usage_quantity) return '-';
                        return number_format($record->usage_quantity, 2, ',', '.') . ' ' . ($record->usage_unit ?: '');
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Zeitraum')
                    ->formatStateUsing(function ($record) {
                        $start = $record->period_start->format('d.m.Y H:i');
                        if ($record->period_end) {
                            return $start . ' - ' . $record->period_end->format('H:i');
                        }
                        return $start;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('external_reference_id')
                    ->label('Referenz')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(20),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'retell' => 'Retell.ai',
                        'twilio' => 'Twilio',
                        'calcom' => 'Cal.com',
                        'openai' => 'OpenAI',
                        'other' => 'Andere'
                    ])
                    ->label('Plattform'),

                Tables\Filters\SelectFilter::make('service_type')
                    ->options([
                        'api_call' => 'API-Anruf',
                        'telephony' => 'Telefonie',
                        'subscription' => 'Abonnement',
                        'usage' => 'Nutzung',
                        'other' => 'Andere'
                    ])
                    ->label('Service-Typ'),

                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Unternehmen'),

                Tables\Filters\Filter::make('period')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('period_start', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('period_start', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Von: ' . \Carbon\Carbon::parse($data['from'])->format('d.m.Y');
                        }
                        if ($data['to'] ?? null) {
                            $indicators['to'] = 'Bis: ' . \Carbon\Carbon::parse($data['to'])->format('d.m.Y');
                        }
                        return $indicators;
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPlatformCosts::route('/'),
            'create' => Pages\CreatePlatformCost::route('/create'),
            'view' => Pages\ViewPlatformCost::route('/{record}'),
            'edit' => Pages\EditPlatformCost::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            PlatformCostResource\Widgets\PlatformCostOverview::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }
}