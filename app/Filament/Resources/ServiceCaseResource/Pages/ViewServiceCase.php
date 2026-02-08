<?php

namespace App\Filament\Resources\ServiceCaseResource\Pages;

use App\Filament\Resources\ServiceCaseResource;
use App\Filament\Resources\ServiceCaseResource\Widgets\ServiceCaseActivityTimelineWidget;
use App\Jobs\ServiceGateway\DeliverCaseOutputJob;
use App\Models\ServiceCase;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

/**
 * ServiceNow-Style ServiceCase View Page
 *
 * Features:
 * - Enhanced header with large case ID, status/priority badges
 * - Split layout: Main content (tabs) + Sidebar (quick stats, SLA, related)
 * - Activity timeline widget in footer
 * - Audio player for call recordings
 */
class ViewServiceCase extends ViewRecord
{
    protected static string $resource = ServiceCaseResource::class;

    /**
     * Custom Blade view for ServiceNow-like layout
     */
    protected static string $view = 'filament.resources.service-case-resource.pages.view-service-case';

    /**
     * Eager load all relations needed for the ServiceNow-style view.
     * This ensures sidebar widgets (related-records, quick-stats) have access to related data.
     */
    protected function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return ServiceCase::with([
            'category',
            'call',
            'customer',
            'assignedTo',
            'company',
            'topLevelNotes.user',
            'topLevelNotes.replies.user',
        ])->findOrFail($key);
    }

    /**
     * Get dynamic page title
     */
    public function getTitle(): string
    {
        return $this->record->formatted_id;
    }

    /**
     * Get subheading with category
     */
    public function getSubheading(): ?string
    {
        return $this->record->subject;
    }

    /**
     * Footer widgets for activity timeline
     */
    protected function getFooterWidgets(): array
    {
        return [
            ServiceCaseActivityTimelineWidget::class,
        ];
    }

    /**
     * Single column for footer widgets
     */
    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    /**
     * Override infolist with ServiceNow-style Split layout
     * Note: We completely replace the parent schema to avoid duplication
     */
    public function infolist(Infolist $infolist): Infolist
    {
        // Clear any existing schema from parent Resource to prevent duplication
        return $infolist
            ->record($this->record)
            ->schema([
                // Use Grid instead of Split for better width control
                Components\Grid::make([
                    'default' => 1,
                    'lg' => 12, // 12-column grid on large screens
                ])
                    ->schema([
                        // ========================================
                        // SIDEBAR LEFT (ServiceNow-Style) - 3 columns
                        // ========================================
                        Components\Group::make([
                            $this->getQuickStatsSection(),
                            $this->getSlaSection(),
                            $this->getRelatedRecordsSection(),
                        ])
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 3,
                            ])
                            ->extraAttributes(['class' => 'service-case-sidebar']),

                        // ========================================
                        // MAIN CONTENT AREA (Tabs) - 9 columns
                        // ========================================
                        Components\Tabs::make('CaseTabs')
                            ->tabs([
                                $this->getDetailsTab(),
                                $this->getCallerTab(),
                                $this->getRelatedTab(),
                                $this->getNotesTab(),
                                $this->getAiMetadataTab(),
                            ])
                            ->persistTabInQueryString('tab')
                            ->contained(false)
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 9,
                            ])
                            ->extraAttributes(['class' => 'service-case-main-content']),
                    ]),
            ]);
    }

    /**
     * Details Tab - Case classification, description
     */
    protected function getDetailsTab(): Components\Tabs\Tab
    {
        return Components\Tabs\Tab::make('Details')
            ->icon('heroicon-o-document-text')
            ->schema([
                // Classification Card
                Components\Section::make('Klassifizierung')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('case_type')
                                    ->label('Typ')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        ServiceCase::TYPE_INCIDENT => 'danger',
                                        ServiceCase::TYPE_REQUEST => 'warning',
                                        ServiceCase::TYPE_INQUIRY => 'info',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        ServiceCase::TYPE_INCIDENT => 'Störung',
                                        ServiceCase::TYPE_REQUEST => 'Anfrage',
                                        ServiceCase::TYPE_INQUIRY => 'Anliegen',
                                        default => $state,
                                    }),
                                Components\TextEntry::make('priority')
                                    ->label('Priorität')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        ServiceCase::PRIORITY_CRITICAL => 'danger',
                                        ServiceCase::PRIORITY_HIGH => 'warning',
                                        ServiceCase::PRIORITY_NORMAL => 'primary',
                                        ServiceCase::PRIORITY_LOW => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                                        ServiceCase::PRIORITY_HIGH => 'Hoch',
                                        ServiceCase::PRIORITY_NORMAL => 'Normal',
                                        ServiceCase::PRIORITY_LOW => 'Niedrig',
                                        default => $state,
                                    }),
                                Components\TextEntry::make('urgency')
                                    ->label('Dringlichkeit')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                                        ServiceCase::PRIORITY_HIGH => 'Hoch',
                                        ServiceCase::PRIORITY_NORMAL => 'Normal',
                                        ServiceCase::PRIORITY_LOW => 'Niedrig',
                                        default => $state,
                                    }),
                                Components\TextEntry::make('impact')
                                    ->label('Auswirkung')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        ServiceCase::PRIORITY_CRITICAL => 'Kritisch',
                                        ServiceCase::PRIORITY_HIGH => 'Hoch',
                                        ServiceCase::PRIORITY_NORMAL => 'Normal',
                                        ServiceCase::PRIORITY_LOW => 'Niedrig',
                                        default => $state,
                                    }),
                            ]),
                        Components\TextEntry::make('category.name')
                            ->label('Kategorie')
                            ->icon('heroicon-o-folder')
                            ->placeholder('Keine Kategorie'),
                        Components\TextEntry::make('external_reference')
                            ->label('Externe Referenz')
                            ->icon('heroicon-o-link')
                            ->placeholder('—')
                            ->copyable(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Description Card
                Components\Section::make('Problembeschreibung')
                    ->icon('heroicon-o-document')
                    ->schema([
                        Components\TextEntry::make('description')
                            ->label('')
                            ->markdown()
                            ->prose()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Caller Tab - Voice AI captured data
     */
    protected function getCallerTab(): Components\Tabs\Tab
    {
        return Components\Tabs\Tab::make('Anrufer')
            ->icon('heroicon-o-phone')
            ->badge(fn () => $this->record->ai_metadata ? '✓' : null)
            ->badgeColor('success')
            ->schema([
                Components\Section::make('Vom Voice-AI erfasste Daten')
                    ->icon('heroicon-o-cpu-chip')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('ai_metadata.customer_name')
                                    ->label('Name')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('Nicht angegeben')
                                    ->size(Components\TextEntry\TextEntrySize::Large),
                                Components\TextEntry::make('ai_metadata.customer_phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-o-phone')
                                    ->placeholder('Nicht angegeben')
                                    ->copyable()
                                    ->copyMessage('Telefonnummer kopiert')
                                    ->helperText(fn ($record) => match($record->ai_metadata['customer_phone_source'] ?? null) {
                                        'agent' => 'Vom Anrufer genannt',
                                        'call_record' => 'Anrufer-ID (automatisch)',
                                        'call_record_backfill' => 'Anrufer-ID (nachträglich)',
                                        default => null,
                                    }),
                                Components\TextEntry::make('ai_metadata.customer_location')
                                    ->label('Standort/Büro')
                                    ->icon('heroicon-o-map-pin')
                                    ->placeholder('Nicht angegeben'),
                                Components\IconEntry::make('ai_metadata.others_affected')
                                    ->label('Mehrere Personen betroffen')
                                    ->icon(fn ($state) => $this->parseOthersAffected($state)
                                        ? 'heroicon-o-user-group'
                                        : 'heroicon-o-user')
                                    ->color(fn ($state) => $this->parseOthersAffected($state)
                                        ? 'danger'
                                        : 'success'),
                            ]),
                    ]),

                Components\Section::make('Technische Details')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('ai_metadata.retell_call_id')
                                    ->label('Retell Call ID')
                                    ->placeholder('—')
                                    ->badge()
                                    ->color('gray')
                                    ->copyable(),
                                Components\TextEntry::make('ai_metadata.finalized_at')
                                    ->label('Erfasst am')
                                    ->placeholder('—')
                                    ->dateTime('d.m.Y H:i:s'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Related Tab - Call, Customer, Audio
     */
    protected function getRelatedTab(): Components\Tabs\Tab
    {
        return Components\Tabs\Tab::make('Verknüpfungen')
            ->icon('heroicon-o-link')
            ->badge(fn () => ($this->record->call_id ? 1 : 0) + ($this->record->customer_id ? 1 : 0))
            ->schema([
                // Customer Section
                Components\Section::make('CRM Kunde')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('customer.name')
                                    ->label('Name')
                                    ->url(fn ($record) => $record->customer_id
                                        ? route('filament.admin.resources.customers.edit', $record->customer_id)
                                        : null)
                                    ->weight('bold'),
                                Components\TextEntry::make('customer.email')
                                    ->label('E-Mail')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                Components\TextEntry::make('customer.phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->customer_id),

                // Enrichment Section
                Components\Section::make('Anreicherung')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('enrichment_status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        ServiceCase::ENRICHMENT_ENRICHED => 'success',
                                        ServiceCase::ENRICHMENT_PENDING => 'warning',
                                        ServiceCase::ENRICHMENT_TIMEOUT => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        ServiceCase::ENRICHMENT_ENRICHED => 'Angereichert',
                                        ServiceCase::ENRICHMENT_PENDING => 'Ausstehend',
                                        ServiceCase::ENRICHMENT_TIMEOUT => 'Timeout',
                                        ServiceCase::ENRICHMENT_SKIPPED => 'Übersprungen',
                                        default => $state,
                                    }),
                                Components\TextEntry::make('enriched_at')
                                    ->label('Angereichert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('—'),
                                Components\TextEntry::make('transcript_segment_count')
                                    ->label('Transcript Segmente')
                                    ->placeholder('—'),
                            ]),
                    ])
                    ->collapsible(),

                // Call Section with Audio Player
                Components\Section::make('Verknüpfter Anruf')
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('call.id')
                                    ->label('Anruf ID')
                                    ->url(fn ($record) => $record->call_id
                                        ? route('filament.admin.resources.calls.view', $record->call_id)
                                        : null)
                                    ->badge()
                                    ->color('primary'),
                                Components\TextEntry::make('call.duration_sec')
                                    ->label('Dauer')
                                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '—'),
                                Components\TextEntry::make('call.status')
                                    ->label('Status')
                                    ->badge(),
                            ]),
                        // Audio Player & Transcript (same component as Call detail page)
                        Components\ViewEntry::make('recording_transcript_tab')
                            ->label('')
                            ->view('filament.components.recording-transcript-tab.index')
                            ->viewData(fn () => [
                                'recordingUrl' => $this->record->call?->recording_url,
                                'durationSec' => $this->record->call?->duration_sec ?? 0,
                                'callId' => $this->record->call?->id ?? 0,
                                'transcript' => is_array($this->record->call?->transcript)
                                    ? ($this->record->call->transcript['text'] ?? $this->record->call->transcript['transcript'] ?? json_encode($this->record->call->transcript))
                                    : ($this->record->call?->transcript ?? ''),
                                'transcriptObject' => is_array($this->record->call?->raw)
                                    ? ($this->record->call->raw['transcript_object'] ?? [])
                                    : (json_decode($this->record->call?->raw ?? '{}', true)['transcript_object'] ?? []),
                            ])
                            ->visible(fn ($record) => $record->call?->recording_url)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->call_id),
            ]);
    }

    /**
     * Notes Tab - Livewire-powered threaded notes
     */
    protected function getNotesTab(): Components\Tabs\Tab
    {
        return Components\Tabs\Tab::make('Notizen')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->badge(fn () => $this->record->notes()->count() ?: null)
            ->schema([
                Components\ViewEntry::make('notes_section')
                    ->label('')
                    ->view('filament.resources.service-case-resource.widgets.notes-wrapper')
                    ->viewData([
                        'serviceCase' => $this->record, // Pass directly - maintains tenant scope
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * AI Metadata Tab - Structured data, raw metadata
     */
    protected function getAiMetadataTab(): Components\Tabs\Tab
    {
        return Components\Tabs\Tab::make('KI Daten')
            ->icon('heroicon-o-cpu-chip')
            ->schema([
                Components\Section::make('Strukturierte Daten')
                    ->icon('heroicon-o-table-cells')
                    ->schema([
                        Components\KeyValueEntry::make('structured_data')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->structured_data))
                    ->collapsible(),

                Components\Section::make('AI Metadaten (Roh)')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        Components\KeyValueEntry::make('ai_metadata')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->ai_metadata))
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Sidebar: Quick Stats Section
     */
    protected function getQuickStatsSection(): Components\Section
    {
        return Components\Section::make('Kurzinfo')
            ->icon('heroicon-o-chart-bar')
            ->compact(false)
            ->schema([
                Components\ViewEntry::make('quick_stats')
                    ->label('')
                    ->view('filament.resources.service-case-resource.widgets.quick-stats'),
            ])
            ->extraAttributes(['class' => 'service-case-sidebar-section']);
    }

    /**
     * Sidebar: SLA Section with countdown
     */
    protected function getSlaSection(): Components\Section
    {
        return Components\Section::make('SLA Status')
            ->icon('heroicon-o-shield-check')
            ->compact(false)
            ->schema([
                Components\ViewEntry::make('sla_countdown')
                    ->label('')
                    ->view('filament.resources.service-case-resource.widgets.sla-countdown'),
            ])
            ->extraAttributes(['class' => 'service-case-sidebar-section']);
    }

    /**
     * Sidebar: Related Records Section
     */
    protected function getRelatedRecordsSection(): Components\Section
    {
        return Components\Section::make('Verknüpfungen')
            ->icon('heroicon-o-link')
            ->compact(false)
            ->schema([
                Components\ViewEntry::make('related_records')
                    ->label('')
                    ->view('filament.resources.service-case-resource.widgets.related-records'),
            ])
            ->extraAttributes(['class' => 'service-case-sidebar-section'])
            ->collapsible();
    }

    /**
     * Header Actions - ServiceNow-Style Quick Actions Bar
     *
     * Primary actions are directly visible, secondary actions grouped in dropdown.
     * Quick Actions allow single-click operations for common tasks.
     */
    protected function getHeaderActions(): array
    {
        return [
            // ========================================
            // QUICK ACTIONS GROUP (ServiceNow-Style)
            // ========================================
            Actions\ActionGroup::make([
                // Mir zuweisen (1-Click Self-Assign)
                Actions\Action::make('assign_to_me')
                    ->label('Mir zuweisen')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->visible(fn (ServiceCase $record) =>
                        $record->assigned_to !== \Illuminate\Support\Facades\Auth::user()->staff?->id
                        && \Illuminate\Support\Facades\Auth::user()->staff !== null
                    )
                    ->action(function (ServiceCase $record) {
                        $staffId = \Illuminate\Support\Facades\Auth::user()->staff?->id;
                        if ($staffId) {
                            $record->update(['assigned_to' => $staffId]);
                            Notification::make()
                                ->title('Case zugewiesen')
                                ->body('Der Case wurde Ihnen zugewiesen.')
                                ->success()
                                ->send();
                        }
                    }),

                // Priorität ändern
                Actions\Action::make('change_priority')
                    ->label('Priorität ändern')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Select::make('priority')
                            ->label('Neue Priorität')
                            ->options([
                                ServiceCase::PRIORITY_CRITICAL => '🔴 Kritisch',
                                ServiceCase::PRIORITY_HIGH => '🟠 Hoch',
                                ServiceCase::PRIORITY_NORMAL => '🔵 Normal',
                                ServiceCase::PRIORITY_LOW => '⚪ Niedrig',
                            ])
                            ->default(fn (ServiceCase $record) => $record->priority)
                            ->required(),
                    ])
                    ->action(function (ServiceCase $record, array $data) {
                        $oldPriority = $record->priority;
                        $record->update(['priority' => $data['priority']]);
                        Notification::make()
                            ->title('Priorität geändert')
                            ->body("Priorität von {$oldPriority} auf {$data['priority']} geändert.")
                            ->success()
                            ->send();
                    }),

                // Status ändern
                Actions\Action::make('change_status')
                    ->label('Status ändern')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('info')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Neuer Status')
                            ->options([
                                ServiceCase::STATUS_NEW => 'Neu',
                                ServiceCase::STATUS_OPEN => 'Offen',
                                ServiceCase::STATUS_PENDING => 'Wartend',
                                ServiceCase::STATUS_RESOLVED => 'Gelöst',
                                ServiceCase::STATUS_CLOSED => 'Geschlossen',
                            ])
                            ->default(fn (ServiceCase $record) => $record->status)
                            ->required(),
                    ])
                    ->action(function (ServiceCase $record, array $data) {
                        $record->update(['status' => $data['status']]);
                        Notification::make()
                            ->title('Status geändert')
                            ->success()
                            ->send();
                    }),

                // Kategorie ändern
                Actions\Action::make('change_category')
                    ->label('Kategorie ändern')
                    ->icon('heroicon-o-folder')
                    ->color('gray')
                    ->form([
                        \Filament\Forms\Components\Select::make('category_id')
                            ->label('Neue Kategorie')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn (ServiceCase $record) => $record->category_id)
                            ->required(),
                    ])
                    ->action(function (ServiceCase $record, array $data) {
                        $record->update(['category_id' => $data['category_id']]);
                        Notification::make()
                            ->title('Kategorie geändert')
                            ->success()
                            ->send();
                    }),

                // Externe Referenz hinzufügen
                Actions\Action::make('add_external_reference')
                    ->label('Externe Referenz')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('external_reference')
                            ->label('Externe Ticket-ID')
                            ->placeholder('z.B. JIRA-1234, INC0001234')
                            ->default(fn (ServiceCase $record) => $record->external_reference)
                            ->maxLength(100),
                    ])
                    ->action(function (ServiceCase $record, array $data) {
                        $record->update(['external_reference' => $data['external_reference']]);
                        Notification::make()
                            ->title('Externe Referenz gespeichert')
                            ->success()
                            ->send();
                    }),
            ])
                ->label('Schnellaktionen')
                ->icon('heroicon-o-bolt')
                ->color('gray')
                ->button(),

            // ========================================
            // PRIMARY ACTIONS (Visible)
            // ========================================
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),

            Actions\Action::make('mark_resolved')
                ->label('Als gelöst markieren')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (ServiceCase $record) => $record->isOpen())
                ->action(function (ServiceCase $record) {
                    $record->update(['status' => ServiceCase::STATUS_RESOLVED]);
                    Notification::make()
                        ->title('Case als gelöst markiert')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reopen')
                ->label('Wieder öffnen')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (ServiceCase $record) => $record->isClosed())
                ->action(function (ServiceCase $record) {
                    $record->update(['status' => ServiceCase::STATUS_OPEN]);
                    Notification::make()
                        ->title('Case wieder geöffnet')
                        ->warning()
                        ->send();
                }),

            Actions\Action::make('resend_output')
                ->label('Output erneut senden')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (ServiceCase $record) => in_array($record->output_status, [
                    ServiceCase::OUTPUT_FAILED,
                    ServiceCase::OUTPUT_SENT,
                ]))
                ->action(function (ServiceCase $record) {
                    $record->update([
                        'output_status' => ServiceCase::OUTPUT_PENDING,
                        'output_sent_at' => null,
                        'output_error' => null,
                    ]);

                    DeliverCaseOutputJob::dispatch($record->id);

                    Notification::make()
                        ->title('Output wird erneut gesendet')
                        ->body('Der DeliverCaseOutputJob wurde gestartet.')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    /**
     * Parse others_affected value to boolean.
     *
     * Handles both:
     * - Boolean values (new format)
     * - German string values like "ja"/"nein" (legacy format from Retell agent)
     *
     * @param mixed $value The value from ai_metadata.others_affected
     * @return bool
     */
    protected function parseOthersAffected(mixed $value): bool
    {
        // Already boolean
        if (is_bool($value)) {
            return $value;
        }

        // Handle string values (German and English)
        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            // "Yes" variants
            if (in_array($normalized, ['ja', 'yes', 'true', '1', 'wahr'])) {
                return true;
            }

            // "No" variants (explicit false)
            if (in_array($normalized, ['nein', 'no', 'false', '0', 'falsch', ''])) {
                return false;
            }
        }

        // Fallback for null or other values
        return (bool) $value;
    }
}
