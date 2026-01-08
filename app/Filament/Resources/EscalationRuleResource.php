<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EscalationRuleResource\Pages;
use App\Models\AssignmentGroup;
use App\Models\EscalationRule;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * EscalationRuleResource
 *
 * Manage automated escalation rules for ServiceNow-style SLA monitoring.
 * Only visible for companies with escalation_rules_enabled.
 */
class EscalationRuleResource extends Resource
{
    protected static ?string $model = EscalationRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Eskalationsregeln';
    protected static ?string $modelLabel = 'Eskalationsregel';
    protected static ?string $pluralModelLabel = 'Eskalationsregeln';
    protected static ?int $navigationSort = 15;

    /**
     * Only show if company has escalation rules enabled.
     */
    public static function shouldRegisterNavigation(): bool
    {
        if (!config('gateway.mode_enabled', false)) {
            return false;
        }

        $company = Auth::user()?->company;
        return $company?->escalation_rules_enabled ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grundeinstellungen')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Regelname')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. SLA Resolution Warnung - Kritisch'),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(2)
                            ->placeholder('Optionale Beschreibung der Regel'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Inaktive Regeln werden nicht ausgeführt'),

                        Forms\Components\TextInput::make('execution_order')
                            ->label('Ausführungsreihenfolge')
                            ->numeric()
                            ->default(0)
                            ->helperText('Niedrigere Werte werden zuerst ausgeführt'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Auslöser')
                    ->schema([
                        Forms\Components\Select::make('trigger_type')
                            ->label('Trigger-Typ')
                            ->options(EscalationRule::TRIGGER_TYPES)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('trigger_minutes', null)),

                        Forms\Components\TextInput::make('trigger_minutes')
                            ->label('Minuten')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10080) // Max 1 week
                            ->visible(fn (Forms\Get $get) => in_array($get('trigger_type'), [
                                EscalationRule::TRIGGER_SLA_RESPONSE_WARNING,
                                EscalationRule::TRIGGER_SLA_RESOLUTION_WARNING,
                                EscalationRule::TRIGGER_IDLE_TIME,
                            ]))
                            ->helperText(fn (Forms\Get $get) => match ($get('trigger_type')) {
                                EscalationRule::TRIGGER_SLA_RESPONSE_WARNING,
                                EscalationRule::TRIGGER_SLA_RESOLUTION_WARNING => 'Minuten VOR SLA-Ablauf',
                                EscalationRule::TRIGGER_IDLE_TIME => 'Minuten ohne Aktivität',
                                default => '',
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Bedingungen (Optional)')
                    ->description('Regel nur für Cases anwenden, die diese Kriterien erfüllen')
                    ->schema([
                        Forms\Components\Select::make('conditions.priorities')
                            ->label('Nur für Prioritäten')
                            ->multiple()
                            ->options([
                                ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                                ServiceCase::PRIORITY_HIGH => 'Hoch',
                                ServiceCase::PRIORITY_NORMAL => 'Normal',
                                ServiceCase::PRIORITY_LOW => 'Niedrig',
                            ]),

                        Forms\Components\Select::make('conditions.case_types')
                            ->label('Nur für Case-Typen')
                            ->multiple()
                            ->options([
                                ServiceCase::TYPE_INCIDENT => 'Incident',
                                ServiceCase::TYPE_REQUEST => 'Request',
                                ServiceCase::TYPE_INQUIRY => 'Inquiry',
                            ]),

                        Forms\Components\Select::make('conditions.category_ids')
                            ->label('Nur für Kategorien')
                            ->multiple()
                            ->options(fn () => ServiceCaseCategory::where('company_id', Auth::user()?->company_id)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->preload(),

                        Forms\Components\Select::make('conditions.assigned_group_ids')
                            ->label('Nur für Zuweisungsgruppen')
                            ->multiple()
                            ->options(fn () => AssignmentGroup::where('company_id', Auth::user()?->company_id)
                                ->active()
                                ->pluck('name', 'id'))
                            ->preload(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Aktion')
                    ->schema([
                        Forms\Components\Select::make('action_type')
                            ->label('Aktionstyp')
                            ->options(EscalationRule::ACTION_TYPES)
                            ->required()
                            ->live(),

                        // Email notification config
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('action_config.email_recipients')
                                ->label('E-Mail Empfänger')
                                ->placeholder('email1@example.com, email2@example.com')
                                ->helperText('Komma-getrennte E-Mail-Adressen'),

                            Forms\Components\TextInput::make('action_config.email_subject')
                                ->label('E-Mail Betreff')
                                ->placeholder('SLA Warnung: {case_number}')
                                ->helperText('Verfügbar: {case_number}, {priority}, {category}'),
                        ])
                            ->visible(fn (Forms\Get $get) => $get('action_type') === EscalationRule::ACTION_NOTIFY_EMAIL)
                            ->columns(1),

                        // Reassign group config
                        Forms\Components\Select::make('action_config.target_group_id')
                            ->label('Zielgruppe')
                            ->options(fn () => AssignmentGroup::where('company_id', Auth::user()?->company_id)
                                ->active()
                                ->pluck('name', 'id'))
                            ->visible(fn (Forms\Get $get) => $get('action_type') === EscalationRule::ACTION_REASSIGN_GROUP)
                            ->required(fn (Forms\Get $get) => $get('action_type') === EscalationRule::ACTION_REASSIGN_GROUP),

                        // Priority escalation config
                        Forms\Components\Select::make('action_config.target_priority')
                            ->label('Neue Priorität')
                            ->options([
                                ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                                ServiceCase::PRIORITY_HIGH => 'Hoch',
                            ])
                            ->visible(fn (Forms\Get $get) => $get('action_type') === EscalationRule::ACTION_ESCALATE_PRIORITY)
                            ->required(fn (Forms\Get $get) => $get('action_type') === EscalationRule::ACTION_ESCALATE_PRIORITY),

                        // Webhook config
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('action_config.webhook_url')
                                ->label('Webhook URL')
                                ->url()
                                ->placeholder('https://example.com/webhook'),

                            Forms\Components\TextInput::make('action_config.webhook_secret')
                                ->label('Webhook Secret (Optional)')
                                ->password()
                                ->revealable(),
                        ])
                            ->visible(fn (Forms\Get $get) => $get('action_type') === EscalationRule::ACTION_NOTIFY_WEBHOOK)
                            ->columns(1),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Regelname')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trigger_type')
                    ->label('Trigger')
                    ->formatStateUsing(fn (string $state) => EscalationRule::TRIGGER_TYPES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        EscalationRule::TRIGGER_SLA_RESPONSE_BREACH,
                        EscalationRule::TRIGGER_SLA_RESOLUTION_BREACH => 'danger',
                        EscalationRule::TRIGGER_SLA_RESPONSE_WARNING,
                        EscalationRule::TRIGGER_SLA_RESOLUTION_WARNING => 'warning',
                        EscalationRule::TRIGGER_IDLE_TIME => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('action_type')
                    ->label('Aktion')
                    ->formatStateUsing(fn (string $state) => EscalationRule::ACTION_TYPES[$state] ?? $state)
                    ->badge()
                    ->color('primary'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                Tables\Columns\TextColumn::make('execution_order')
                    ->label('Reihenfolge')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('executions_count')
                    ->label('Ausführungen')
                    ->counts('executions')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('last_executed_at')
                    ->label('Letzte Ausführung')
                    ->since()
                    ->placeholder('Noch nie'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_type')
                    ->label('Trigger')
                    ->options(EscalationRule::TRIGGER_TYPES),

                Tables\Filters\SelectFilter::make('action_type')
                    ->label('Aktion')
                    ->options(EscalationRule::ACTION_TYPES),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktiv')
                    ->falseLabel('Inaktiv'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (EscalationRule $record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn (EscalationRule $record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (EscalationRule $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (EscalationRule $record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('execution_order', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEscalationRules::route('/'),
            'create' => Pages\CreateEscalationRule::route('/create'),
            'edit' => Pages\EditEscalationRule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Auth::user()?->company_id);
    }
}
