<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Models\Call;
use App\Models\Service;
use App\Services\CalcomService;
use Carbon\Carbon;

class TestChecklistController extends Controller
{
    /**
     * Display the test checklist page
     */
    public function index()
    {
        $systemStatus = $this->getSystemStatus();
        $phoneNumbers = $this->getPhoneNumbers();
        $recentCalls = $this->getRecentCalls();
        $testScenarios = $this->getTestScenarios();

        return view('test-checklist', compact(
            'systemStatus',
            'phoneNumbers',
            'recentCalls',
            'testScenarios'
        ));
    }

    /**
     * Get real-time system status
     */
    public function status()
    {
        return response()->json($this->getSystemStatus());
    }

    /**
     * Get system status for all components
     */
    private function getSystemStatus()
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
            $retellApiKey = config('services.retellai.api_key');
            if ($retellApiKey) {
                // Check if we have active agents
                $activeAgents = RetellAgent::where('status', 'active')->count();
                $status['components']['retell'] = [
                    'status' => $activeAgents > 0 ? 'operational' : 'warning',
                    'message' => $activeAgents . ' aktive Agenten',
                    'icon' => $activeAgents > 0 ? '✅' : '⚠️',
                    'details' => [
                        'total_agents' => RetellAgent::count(),
                        'active_agents' => $activeAgents
                    ]
                ];
            } else {
                $status['components']['retell'] = [
                    'status' => 'warning',
                    'message' => 'Retell API Key nicht konfiguriert',
                    'icon' => '⚠️'
                ];
            }
        } catch (\Exception $e) {
            $status['components']['retell'] = [
                'status' => 'error',
                'message' => 'Retell API Fehler: ' . $e->getMessage(),
                'icon' => '❌'
            ];
        }

        // Cal.com API Status
        try {
            $service = Service::whereNotNull('calcom_event_type_id')->first();
            if ($service) {
                $calcomService = new CalcomService();
                $testDate = Carbon::tomorrow();

                // Try to get slots to verify connection
                $response = $calcomService->getAvailableSlots(
                    $service->calcom_event_type_id,
                    $testDate->format('Y-m-d'),
                    $testDate->format('Y-m-d')
                );

                if ($response->successful()) {
                    $status['components']['calcom'] = [
                        'status' => 'operational',
                        'message' => 'Cal.com API verbunden',
                        'icon' => '✅',
                        'details' => [
                            'event_type_id' => $service->calcom_event_type_id,
                            'service' => $service->name
                        ]
                    ];
                } else {
                    $status['components']['calcom'] = [
                        'status' => 'error',
                        'message' => 'Cal.com API Fehler: ' . $response->status(),
                        'icon' => '❌'
                    ];
                }
            } else {
                $status['components']['calcom'] = [
                    'status' => 'warning',
                    'message' => 'Kein Service mit Cal.com Event Type konfiguriert',
                    'icon' => '⚠️'
                ];
            }
        } catch (\Exception $e) {
            $status['components']['calcom'] = [
                'status' => 'error',
                'message' => 'Cal.com API nicht erreichbar',
                'icon' => '❌'
            ];
        }

        // Webhook Status
        $status['components']['webhooks'] = [
            'status' => 'operational',
            'message' => 'Webhooks konfiguriert',
            'icon' => '✅',
            'details' => [
                'retell' => url('/webhooks/retell'),
                'calcom' => url('/webhooks/calcom'),
                'function' => url('/webhooks/retell/function')
            ]
        ];

        // Phone Numbers Status
        $phoneCount = PhoneNumber::where('is_active', true)->count();
        $phoneWithAgent = PhoneNumber::whereNotNull('retell_agent_id')->count();

        $status['components']['phone_numbers'] = [
            'status' => $phoneWithAgent > 0 ? 'operational' : 'warning',
            'message' => $phoneCount . ' aktive Nummern, ' . $phoneWithAgent . ' mit Agent',
            'icon' => $phoneWithAgent > 0 ? '✅' : '⚠️'
        ];

        // Active Calls Status
        $activeCalls = Call::whereIn('status', ['ongoing', 'in-progress', 'active'])
            ->orWhere('call_status', 'ongoing')
            ->count();

        $status['components']['active_calls'] = [
            'status' => 'info',
            'message' => $activeCalls . ' aktive Anrufe',
            'icon' => $activeCalls > 0 ? '📞' : '☎️',
            'count' => $activeCalls
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

    /**
     * Get configured phone numbers
     */
    private function getPhoneNumbers()
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
                    'agent_id' => $phone->retell_agent_id,
                    'is_primary' => $phone->is_primary
                ];
            });
    }

    /**
     * Get recent calls
     */
    private function getRecentCalls()
    {
        return Call::with(['customer', 'phoneNumber'])
            ->orderBy('created_at', 'desc')
            ->take(5)
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
            });
    }

    /**
     * Get test scenarios
     */
    private function getTestScenarios()
    {
        return [
            [
                'id' => 1,
                'title' => '🎯 Basis Terminbuchung',
                'description' => 'Einfache Terminanfrage für morgen',
                'steps' => [
                    'Anrufen und warten bis der Agent antwortet',
                    'Sagen: "Hallo, ich möchte gerne einen Termin vereinbaren"',
                    'Auf Frage nach Zeit antworten: "Morgen um 14 Uhr wäre gut"',
                    'Namen angeben wenn gefragt',
                    'Termin bestätigen'
                ],
                'test_phrases' => [
                    'greeting' => 'Hallo, ich möchte einen Termin vereinbaren',
                    'time' => 'Morgen um 14 Uhr',
                    'name' => 'Max Mustermann',
                    'confirm' => 'Ja, das passt mir'
                ],
                'expected_result' => 'Termin sollte in Cal.com und Datenbank erstellt werden'
            ],
            [
                'id' => 2,
                'title' => '🔄 Alternative Termine',
                'description' => 'Test für Alternativvorschläge wenn gewünschte Zeit nicht verfügbar',
                'steps' => [
                    'Anrufen und Termin anfragen',
                    'Bewusst eine vergangene oder unmögliche Zeit nennen',
                    'Auf Alternativvorschläge warten',
                    'Einen alternativen Termin auswählen'
                ],
                'test_phrases' => [
                    'greeting' => 'Guten Tag, ich brauche einen Termin',
                    'impossible_time' => 'Heute um 22 Uhr',
                    'ask_alternatives' => 'Was haben Sie denn noch frei diese Woche?',
                    'accept' => 'Der erste Vorschlag passt gut'
                ],
                'expected_result' => 'System sollte Alternativen anbieten und buchen können'
            ],
            [
                'id' => 3,
                'title' => '📅 Verfügbarkeit prüfen',
                'description' => 'Nur Verfügbarkeit abfragen ohne Buchung',
                'steps' => [
                    'Anrufen und nach freien Terminen fragen',
                    'Verschiedene Tage/Zeiten erfragen',
                    'Keine Buchung vornehmen, nur informieren'
                ],
                'test_phrases' => [
                    'greeting' => 'Hallo, ich wollte nur mal fragen',
                    'check_availability' => 'Haben Sie am Freitag noch was frei?',
                    'another_check' => 'Und wie sieht es nächste Woche aus?',
                    'thank_you' => 'Danke, ich melde mich nochmal'
                ],
                'expected_result' => 'Verfügbarkeit sollte korrekt mitgeteilt werden'
            ],
            [
                'id' => 4,
                'title' => '🎨 Composite Booking (Friseur)',
                'description' => 'Mehrteiliger Termin mit Pausen',
                'steps' => [
                    'Anrufen und nach Friseurtermin fragen',
                    'Service mit mehreren Teilen nennen (z.B. Färben und Schneiden)',
                    'Zeit bestätigen',
                    'Auf Info über Pausen achten'
                ],
                'test_phrases' => [
                    'greeting' => 'Hallo, ich möchte einen Termin zum Färben',
                    'service' => 'Ich möchte färben und schneiden lassen',
                    'time' => 'Am Samstag Vormittag wäre gut',
                    'confirm' => 'Ja, mit der Pause dazwischen ist ok'
                ],
                'expected_result' => 'Composite Booking mit mehreren Segmenten sollte erstellt werden'
            ],
            [
                'id' => 5,
                'title' => '❌ Fehlerbehandlung',
                'description' => 'Test der Fehlerbehandlung',
                'steps' => [
                    'Anrufen und unklare Anfragen stellen',
                    'Ungültige Daten angeben',
                    'Verbindung unterbrechen und wieder aufnehmen',
                    'Prüfen ob System stabil bleibt'
                ],
                'test_phrases' => [
                    'unclear' => 'Ähm, also, vielleicht irgendwann',
                    'invalid' => 'Am 35. Februar',
                    'interrupt' => '[Pause für 5 Sekunden]',
                    'resume' => 'Hallo, sind Sie noch da?'
                ],
                'expected_result' => 'System sollte robust reagieren und nicht abstürzen'
            ]
        ];
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(Request $request)
    {
        try {
            $response = Http::post(url('/webhooks/retell'), [
                'event' => 'test',
                'timestamp' => now()->toIso8601String(),
                'source' => 'test_checklist'
            ]);

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Webhook erfolgreich getestet' : 'Webhook Test fehlgeschlagen'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        try {
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Cache erfolgreich geleert'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check availability for testing
     */
    public function checkAvailability()
    {
        try {
            $service = Service::whereNotNull('calcom_event_type_id')->first();

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'error' => 'Kein Service mit Cal.com Event Type gefunden'
                ], 404);
            }

            $calcomService = new CalcomService();
            $tomorrow = Carbon::tomorrow();

            $response = $calcomService->getAvailableSlots(
                $service->calcom_event_type_id,
                $tomorrow->format('Y-m-d'),
                $tomorrow->format('Y-m-d')
            );

            if ($response->successful()) {
                $data = $response->json();
                $slots = $data['data']['slots'] ?? [];

                return response()->json([
                    'success' => true,
                    'date' => $tomorrow->format('d.m.Y'),
                    'slots_count' => count($slots),
                    'slots' => array_slice($slots, 0, 5), // First 5 slots
                    'message' => count($slots) . ' freie Termine für morgen gefunden'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Cal.com API Fehler: ' . $response->status()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}