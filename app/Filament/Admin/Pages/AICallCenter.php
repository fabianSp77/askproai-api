<?php

namespace App\Filament\Admin\Pages;

use App\Models\Company;
use App\Models\RetellAICallCampaign;
use App\Services\MCP\RetellAIBridgeMCPServer;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\On;

class AICallCenter extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';

    protected static ?string $navigationGroup = 'AI Tools';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.ai-call-center';

    public ?array $quickCallData = [];

    public ?array $campaignData = [];

    public bool $showCampaignForm = false;

    public ?string $selectedCampaignId = null;

    public static function getNavigationLabel(): string
    {
        return 'AI Call Center';
    }

    public function getTitle(): string
    {
        return 'AI Call Center';
    }

    public function getSubheading(): ?string
    {
        return 'Manage outbound AI-powered calls and campaigns';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\AICallStatsWidget::class,
            \App\Filament\Admin\Widgets\ActiveCampaignsWidget::class,
            \App\Filament\Admin\Widgets\RealTimeCallMonitorWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\OutboundCallMetricsWidget::class,
            \App\Filament\Admin\Widgets\CampaignPerformanceInsightsWidget::class,
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Quick Call')
                ->description('Initiate a single AI-powered call')
                ->schema([
                    TextInput::make('quickCallData.phone_number')
                        ->label('Phone Number')
                        ->tel()
                        ->required()
                        ->placeholder('+49 123 456789'),

                    Select::make('quickCallData.agent_id')
                        ->label('AI Agent')
                        ->options(fn () => $this->getAvailableAgents())
                        ->required()
                        ->searchable(),

                    Select::make('quickCallData.purpose')
                        ->label('Call Purpose')
                        ->options([
                            'follow_up' => 'Follow-up Call',
                            'appointment_reminder' => 'Appointment Reminder',
                            'feedback_collection' => 'Feedback Collection',
                            'custom' => 'Custom Message',
                        ])
                        ->reactive()
                        ->required(),

                    Textarea::make('quickCallData.custom_message')
                        ->label('Custom Message')
                        ->visible(fn ($get) => $get('quickCallData.purpose') === 'custom')
                        ->rows(3),

                    KeyValue::make('quickCallData.variables')
                        ->label('Dynamic Variables')
                        ->keyLabel('Variable')
                        ->valueLabel('Value')
                        ->addButtonLabel('Add Variable')
                        ->deletable()
                        ->reorderable(),
                ])
                ->columnSpan(1),
        ];
    }

    protected function getCampaignFormSchema(): array
    {
        return [
            Section::make('Campaign Details')
                ->schema([
                    TextInput::make('campaignData.name')
                        ->label('Campaign Name')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('campaignData.description')
                        ->label('Description')
                        ->rows(2),

                    Select::make('campaignData.agent_id')
                        ->label('AI Agent')
                        ->options(fn () => $this->getAvailableAgents())
                        ->required()
                        ->searchable(),

                    Select::make('campaignData.target_type')
                        ->label('Target Audience')
                        ->options([
                            'all_customers' => 'All Customers',
                            'inactive_customers' => 'Inactive Customers',
                            'custom_list' => 'Custom List',
                        ])
                        ->reactive()
                        ->required(),

                    TextInput::make('campaignData.inactive_days')
                        ->label('Inactive for (days)')
                        ->numeric()
                        ->default(90)
                        ->visible(fn ($get) => $get('campaignData.target_type') === 'inactive_customers'),

                    Select::make('campaignData.schedule_type')
                        ->label('Schedule')
                        ->options([
                            'immediate' => 'Start Immediately',
                            'scheduled' => 'Schedule for Later',
                        ])
                        ->reactive()
                        ->default('immediate'),

                    DateTimePicker::make('campaignData.scheduled_at')
                        ->label('Start Date & Time')
                        ->visible(fn ($get) => $get('campaignData.schedule_type') === 'scheduled')
                        ->minDate(now())
                        ->required(fn ($get) => $get('campaignData.schedule_type') === 'scheduled'),

                    KeyValue::make('campaignData.variables')
                        ->label('Campaign Variables')
                        ->keyLabel('Variable')
                        ->valueLabel('Value')
                        ->addButtonLabel('Add Variable'),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(RetellAICallCampaign::query())
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'scheduled',
                        'primary' => 'running',
                        'info' => 'paused',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),

                TextColumn::make('target_type')
                    ->label('Target')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'all_customers' => 'All Customers',
                        'inactive_customers' => 'Inactive Customers',
                        'custom_list' => 'Custom List',
                        default => $state,
                    }),

                TextColumn::make('total_targets')
                    ->label('Targets')
                    ->numeric(),

                TextColumn::make('completion_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($record) => $record->completion_percentage . '%')
                    ->color(fn ($record) => match (true) {
                        $record->completion_percentage >= 100 => 'success',
                        $record->completion_percentage >= 50 => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->formatStateUsing(fn ($record) => $record->success_rate . '%')
                    ->color(fn ($record) => match (true) {
                        $record->success_rate >= 80 => 'success',
                        $record->success_rate >= 60 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Add filters as needed
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->icon('heroicon-o-eye')
                        ->action(fn ($record) => $this->viewCampaign($record)),

                    Action::make('start')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn ($record) => $record->canStart())
                        ->requiresConfirmation()
                        ->action(fn ($record) => $this->startCampaign($record)),

                    Action::make('pause')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->visible(fn ($record) => $record->canPause())
                        ->action(fn ($record) => $this->pauseCampaign($record)),

                    Action::make('resume')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->visible(fn ($record) => $record->canResume())
                        ->action(fn ($record) => $this->resumeCampaign($record)),
                ]),
            ])
            ->bulkActions([
                // Add bulk actions as needed
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function makeQuickCall(): void
    {
        $this->validate([
            'quickCallData.phone_number' => 'required',
            'quickCallData.agent_id' => 'required',
            'quickCallData.purpose' => 'required',
        ]);

        try {
            $bridgeServer = app(RetellAIBridgeMCPServer::class);

            $params = [
                'company_id' => auth()->user()->company_id,
                'to_number' => $this->quickCallData['phone_number'],
                'agent_id' => $this->quickCallData['agent_id'],
                'purpose' => $this->quickCallData['purpose'],
                'dynamic_variables' => $this->quickCallData['variables'] ?? [],
            ];

            if ($this->quickCallData['purpose'] === 'custom' && ! empty($this->quickCallData['custom_message'])) {
                $params['dynamic_variables']['custom_message'] = $this->quickCallData['custom_message'];
            }

            $result = $bridgeServer->createOutboundCall($params);

            Notification::make()
                ->title('Call Initiated')
                ->body('AI call to ' . $this->quickCallData['phone_number'] . ' has been initiated.')
                ->success()
                ->send();

            // Reset form
            $this->quickCallData = [];

            // Refresh stats
            $this->dispatch('refresh-widgets');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Call Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createCampaign(): void
    {
        if (! $this->showCampaignForm) {
            $this->showCampaignForm = true;

            return;
        }

        $this->validate([
            'campaignData.name' => 'required|string|max:255',
            'campaignData.agent_id' => 'required',
            'campaignData.target_type' => 'required',
        ]);

        try {
            $bridgeServer = app(RetellAIBridgeMCPServer::class);

            $params = array_merge($this->campaignData, [
                'company_id' => auth()->user()->company_id,
                'dynamic_variables' => $this->campaignData['variables'] ?? [],
                'target_criteria' => [
                    'inactive_days' => $this->campaignData['inactive_days'] ?? 90,
                    'has_phone' => true,
                ],
            ]);

            $result = $bridgeServer->createCallCampaign($params);

            Notification::make()
                ->title('Campaign Created')
                ->body("Campaign '{$this->campaignData['name']}' has been created with {$result['total_targets']} targets.")
                ->success()
                ->send();

            // Reset form
            $this->campaignData = [];
            $this->showCampaignForm = false;

            // Refresh table
            $this->resetTable();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Campaign Creation Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function startCampaign(RetellAICallCampaign $campaign): void
    {
        try {
            $bridgeServer = app(RetellAIBridgeMCPServer::class);
            $bridgeServer->startCampaign(['campaign_id' => $campaign->id]);

            Notification::make()
                ->title('Campaign Started')
                ->body("Campaign '{$campaign->name}' is now running.")
                ->success()
                ->send();

            $this->resetTable();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to Start Campaign')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function pauseCampaign(RetellAICallCampaign $campaign): void
    {
        $campaign->update(['status' => 'paused']);

        Notification::make()
            ->title('Campaign Paused')
            ->body("Campaign '{$campaign->name}' has been paused.")
            ->warning()
            ->send();

        $this->resetTable();
    }

    protected function resumeCampaign(RetellAICallCampaign $campaign): void
    {
        $campaign->update(['status' => 'running']);

        // Re-dispatch the job
        \App\Jobs\ProcessRetellAICampaignJob::dispatch($campaign)->onQueue('campaigns');

        Notification::make()
            ->title('Campaign Resumed')
            ->body("Campaign '{$campaign->name}' has been resumed.")
            ->success()
            ->send();

        $this->resetTable();
    }

    protected function viewCampaign(RetellAICallCampaign $campaign): void
    {
        $this->selectedCampaignId = $campaign->id;
        $this->dispatch('open-modal', id: 'campaign-details');
    }

    protected function getAvailableAgents(): array
    {
        // Get all active agents for the company
        $agents = \App\Models\RetellAgent::where('company_id', auth()->user()->company_id)
            ->active()
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get();

        if ($agents->isEmpty()) {
            // Fallback to company's default agent if no agents configured
            $company = Company::find(auth()->user()->company_id);
            if ($company && $company->retell_agent_id) {
                return [
                    $company->retell_agent_id => 'Default Agent',
                ];
            }

            return [];
        }

        // Build options array
        $options = [];
        foreach ($agents as $agent) {
            $label = $agent->name;
            if ($agent->is_default) {
                $label .= ' (Default)';
            }
            if ($agent->type !== 'general') {
                $label .= ' - ' . \App\Models\RetellAgent::getTypes()[$agent->type];
            }
            $options[$agent->retell_agent_id] = $label;
        }

        return $options;
    }

    #[On('refresh-widgets')]
    public function refreshWidgets(): void
    {
        // This will trigger widget refresh
    }
}
