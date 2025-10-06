<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Models\Call;
use App\Models\Service;
use App\Services\CalcomService;
use Carbon\Carbon;

class TestChecklist extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = '📞 Test Checklist';

    protected static ?string $title = 'Test Checklist - Retell & Cal.com Integration';

    protected static ?int $navigationSort = 100;

    protected static ?string $navigationGroup = 'System';

    protected static string $view = 'filament.pages.test-checklist';

    public $systemStatus = [];
    public $phoneNumbers = [];
    public $recentCalls = [];
    public $testScenarios = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->systemStatus = $this->getSystemStatus();
        $this->phoneNumbers = $this->getPhoneNumbers();
        $this->recentCalls = $this->getRecentCalls();
        $this->testScenarios = $this->getTestScenarios();
    }

    private function getSystemStatus(): array
    {
        $status = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'components' => []
        ];

        // Database Status
        try {
            DB::connection()->getPdo();
            $status['components']['database'] = [
                'status' => 'operational',
                'message' => 'Database verbunden',
                'icon' => '✅'
            ];
        } catch (\Exception $e) {
            $status['components']['database'] = [
                'status' => 'error',
                'message' => 'Database nicht erreichbar',
                'icon' => '❌'
            ];
        }

        // Redis/Cache Status
        try {
            Cache::put('health_check', true, 10);
            if (Cache::get('health_check')) {
                $status['components']['cache'] = [
                    'status' => 'operational',
                    'message' => 'Cache funktioniert',
                    'icon' => '✅'
                ];
            }
        } catch (\Exception $e) {
            $status['components']['cache'] = [
                'status' => 'error',
                'message' => 'Cache nicht verfügbar',
                'icon' => '❌'
            ];
        }

        // Retell API Status
        try {
            $activeAgents = RetellAgent::count();
            $status['components']['retell'] = [
                'status' => $activeAgents > 0 ? 'operational' : 'warning',
                'message' => $activeAgents . ' Agenten insgesamt',
                'icon' => $activeAgents > 0 ? '✅' : '⚠️'
            ];
        } catch (\Exception $e) {
            $status['components']['retell'] = [
                'status' => 'warning',
                'message' => 'Agenten Status unbekannt',
                'icon' => '⚠️'
            ];
        }

        // Cal.com API Status
        try {
            $service = Service::whereNotNull('calcom_event_type_id')->first();
            if ($service) {
                $status['components']['calcom'] = [
                    'status' => 'operational',
                    'message' => 'Cal.com konfiguriert',
                    'icon' => '✅'
                ];
            } else {
                $status['components']['calcom'] = [
                    'status' => 'warning',
                    'message' => 'Kein Service konfiguriert',
                    'icon' => '⚠️'
                ];
            }
        } catch (\Exception $e) {
            $status['components']['calcom'] = [
                'status' => 'error',
                'message' => 'Cal.com Fehler',
                'icon' => '❌'
            ];
        }

        // Phone Numbers Status
        $phoneCount = PhoneNumber::where('is_active', true)->count();
        $phoneWithAgent = PhoneNumber::whereNotNull('retell_agent_id')->count();

        $status['components']['phone_numbers'] = [
            'status' => $phoneWithAgent > 0 ? 'operational' : 'warning',
            'message' => $phoneCount . ' aktiv, ' . $phoneWithAgent . ' mit Agent',
            'icon' => $phoneWithAgent > 0 ? '✅' : '⚠️'
        ];

        // Active Calls
        $activeCalls = Call::whereIn('status', ['ongoing', 'in-progress'])
            ->orWhere('call_status', 'ongoing')
            ->count();

        $status['components']['active_calls'] = [
            'status' => 'info',
            'message' => $activeCalls . ' aktive Anrufe',
            'icon' => $activeCalls > 0 ? '📞' : '☎️'
        ];

        // Overall Status
        $hasError = collect($status['components'])->contains('status', 'error');
        $hasWarning = collect($status['components'])->contains('status', 'warning');

        $status['overall'] = [
            'status' => $hasError ? 'error' : ($hasWarning ? 'warning' : 'operational'),
            'message' => $hasError ? 'System hat Fehler' : ($hasWarning ? 'System läuft mit Warnungen' : 'Alle Systeme betriebsbereit'),
            'ready_for_test' => !$hasError
        ];

        return $status;
    }

    private function getPhoneNumbers(): array
    {
        return PhoneNumber::with(['company', 'branch'])
            ->where('is_active', true)
            ->whereNotNull('retell_agent_id')
            ->get()
            ->map(function ($phone) {
                $agent = RetellAgent::where('retell_agent_id', $phone->retell_agent_id)->first();
                return [
                    'number' => $phone->number,
                    'formatted' => $phone->formatted_number,
                    'company' => $phone->company?->name ?? 'Unbekannt',
                    'branch' => $phone->branch?->name ?? '-',
                    'agent' => $agent?->name ?? 'Kein Agent',
                    'is_primary' => $phone->is_primary
                ];
            })
            ->toArray();
    }

    private function getRecentCalls(): array
    {
        return Call::with(['customer', 'phoneNumber'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'from' => $call->from_number,
                    'to' => $call->to_number,
                    'status' => $call->status,
                    'duration' => $call->duration_sec ? $call->duration_sec . ' Sek.' : '-',
                    'customer' => $call->customer?->name ?? 'Anonym',
                    'created' => $call->created_at?->format('d.m.Y H:i') ?? '-',
                    'appointment_made' => $call->converted_appointment_id ? true : false
                ];
            })
            ->toArray();
    }

    private function getTestScenarios(): array
    {
        return [
            [
                'id' => 1,
                'title' => '🎯 Basis Terminbuchung',
                'description' => 'Einfache Terminanfrage für morgen',
                'test_phrases' => [
                    'Hallo, ich möchte einen Termin vereinbaren',
                    'Morgen um 14 Uhr',
                    'Max Mustermann'
                ]
            ],
            [
                'id' => 2,
                'title' => '🔄 Alternative Termine',
                'description' => 'Test für Alternativvorschläge',
                'test_phrases' => [
                    'Guten Tag, ich brauche einen Termin',
                    'Was haben Sie diese Woche noch frei?',
                    'Der erste Vorschlag passt gut'
                ]
            ],
            [
                'id' => 3,
                'title' => '📅 Verfügbarkeit prüfen',
                'description' => 'Nur Verfügbarkeit abfragen',
                'test_phrases' => [
                    'Haben Sie am Freitag noch was frei?',
                    'Wie sieht es nächste Woche aus?',
                    'Danke, ich melde mich nochmal'
                ]
            ]
        ];
    }

    public function refreshData(): void
    {
        $this->loadData();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Daten aktualisiert'
        ]);
    }
}