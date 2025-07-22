<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RetellAgentResource\Pages;
use App\Filament\Admin\Resources\RetellAgentResource\RelationManagers;
use App\Models\RetellAgent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RetellAgentResource extends Resource
{
    protected static ?string $model = RetellAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'AI Configuration';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('retell_agent_id')
                            ->label('Retell Agent ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('The agent ID from Retell.ai'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->options(RetellAgent::getTypes())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(
                                fn ($state, Forms\Set $set) => $set('capabilities', self::getDefaultCapabilities($state))
                            ),

                        Forms\Components\Select::make('language')
                            ->options(RetellAgent::getSupportedLanguages())
                            ->required()
                            ->searchable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive agents will not be used for calls'),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Agent')
                            ->helperText('Used when no other agent matches the criteria'),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->helperText('Higher priority agents are preferred'),

                        Forms\Components\Toggle::make('is_test_agent')
                            ->label('Test Agent')
                            ->reactive()
                            ->helperText('Test agents are used for A/B testing'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Capabilities')
                    ->schema([
                        Forms\Components\CheckboxList::make('capabilities')
                            ->options([
                                'appointment_booking' => 'Appointment Booking',
                                'appointment_rescheduling' => 'Appointment Rescheduling',
                                'appointment_cancellation' => 'Appointment Cancellation',
                                'customer_data_collection' => 'Customer Data Collection',
                                'service_information' => 'Service Information',
                                'pricing_information' => 'Pricing Information',
                                'business_hours' => 'Business Hours Information',
                                'location_directions' => 'Location & Directions',
                                'faq_handling' => 'FAQ Handling',
                                'complaint_handling' => 'Complaint Handling',
                                'feedback_collection' => 'Feedback Collection',
                                'upselling' => 'Upselling & Cross-selling',
                                'lead_qualification' => 'Lead Qualification',
                                'survey_completion' => 'Survey Completion',
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Voice Settings')
                    ->schema([
                        Forms\Components\KeyValue::make('voice_settings')
                            ->addButtonLabel('Add Voice Setting')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->helperText('Voice configuration from Retell.ai'),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Advanced Settings')
                    ->schema([
                        Forms\Components\KeyValue::make('prompt_settings')
                            ->addButtonLabel('Add Prompt Setting')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->helperText('Custom prompts and instructions'),

                        Forms\Components\KeyValue::make('integration_settings')
                            ->addButtonLabel('Add Integration Setting')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->helperText('Integration-specific configurations'),

                        Forms\Components\KeyValue::make('test_config')
                            ->addButtonLabel('Add Test Setting')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->visible(fn (Forms\Get $get) => $get('is_test_agent'))
                            ->helperText('Test configuration for A/B testing'),
                    ])
                    ->collapsed()
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('retell_agent_id')
                    ->label('Agent ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Agent ID copied'),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'appointments',
                        'warning' => 'sales',
                        'info' => 'support',
                        'secondary' => 'custom',
                    ]),

                Tables\Columns\TextColumn::make('language')
                    ->formatStateUsing(fn ($state) => RetellAgent::getSupportedLanguages()[$state] ?? $state),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                Tables\Columns\TextColumn::make('total_calls')
                    ->label('Total Calls')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->formatStateUsing(fn ($record) => $record->success_rate . '%')
                    ->color(fn ($record) => match (true) {
                        $record->success_rate >= 80 => 'success',
                        $record->success_rate >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('average_duration')
                    ->label('Avg Duration')
                    ->formatStateUsing(fn ($state) => gmdate('i:s', $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(RetellAgent::getTypes()),

                Tables\Filters\SelectFilter::make('language')
                    ->options(RetellAgent::getSupportedLanguages()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default'),

                Tables\Filters\TernaryFilter::make('is_test_agent')
                    ->label('Test Agent'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(fn ($record) => redirect()->route('filament.admin.pages.ai-call-center', [
                        'test_agent_id' => $record->retell_agent_id,
                    ])),
                Tables\Actions\Action::make('updateMetrics')
                    ->label('Update Metrics')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->updateMetrics()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('priority', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRetellAgents::route('/'),
            'create' => Pages\CreateRetellAgent::route('/create'),
            'view' => Pages\ViewRetellAgent::route('/{record}'),
            'edit' => Pages\EditRetellAgent::route('/{record}/edit'),
        ];
    }

    protected static function getDefaultCapabilities(string $type): array
    {
        $capabilities = match ($type) {
            RetellAgent::TYPE_APPOINTMENTS => [
                'appointment_booking',
                'appointment_rescheduling',
                'appointment_cancellation',
                'customer_data_collection',
                'service_information',
                'business_hours',
            ],
            RetellAgent::TYPE_SALES => [
                'lead_qualification',
                'service_information',
                'pricing_information',
                'upselling',
                'customer_data_collection',
            ],
            RetellAgent::TYPE_SUPPORT => [
                'complaint_handling',
                'faq_handling',
                'feedback_collection',
                'service_information',
                'business_hours',
            ],
            default => [
                'service_information',
                'business_hours',
                'customer_data_collection',
            ],
        };

        return $capabilities;
    }
}
