<?php

namespace App\Services\Retell\CustomFunctions;

use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Retell.ai Custom Function zur Identifikation existierender Kunden
 * Findet Kunden anhand von Telefonnummer oder anderen Merkmalen
 */
class IdentifyCustomerFunction
{
    /**
     * Function Definition für Retell.ai
     */
    public static function getDefinition(): array
    {
        return [
            'name' => 'identify_customer',
            'description' => 'Identifiziert einen existierenden Kunden anhand von Telefonnummer oder anderen Informationen',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'phone_number' => [
                        'type' => 'string',
                        'description' => 'Telefonnummer des Anrufers'
                    ],
                    'customer_name' => [
                        'type' => 'string',
                        'description' => 'Name des Kunden (falls erwähnt)'
                    ],
                    'company_id' => [
                        'type' => 'string',
                        'description' => 'ID des Unternehmens'
                    ],
                    'branch_id' => [
                        'type' => 'string',
                        'description' => 'ID der Filiale (optional)'
                    ]
                ],
                'required' => ['phone_number', 'company_id']
            ]
        ];
    }

    /**
     * Führt die Kundenidentifikation aus
     */
    public function execute(array $parameters): array
    {
        Log::info('IdentifyCustomerFunction::execute', [
            'parameters' => $parameters
        ]);

        try {
            $phoneNumber = $this->normalizePhoneNumber($parameters['phone_number']);
            $companyId = $parameters['company_id'];
            $customerName = $parameters['customer_name'] ?? null;
            $branchId = $parameters['branch_id'] ?? null;

            // 1. Suche nach Telefonnummer
            $customer = Customer::where('company_id', $companyId)
                ->where('phone', $phoneNumber)
                ->first();

            if ($customer) {
                return $this->buildCustomerResponse($customer, 'phone_match');
            }

            // 2. Suche nach alternativen Telefonnummern (ohne Ländervorwahl etc.)
            $alternativeNumbers = $this->generateAlternativeNumbers($phoneNumber);
            
            $customer = Customer::where('company_id', $companyId)
                ->where(function($query) use ($alternativeNumbers) {
                    foreach ($alternativeNumbers as $number) {
                        $query->orWhere('phone', $number);
                    }
                })
                ->first();

            if ($customer) {
                return $this->buildCustomerResponse($customer, 'phone_variant_match');
            }

            // 3. Falls Name angegeben, suche nach ähnlichen Namen
            if ($customerName) {
                $customer = $this->findByNameSimilarity($customerName, $companyId);
                
                if ($customer) {
                    // Prüfe ob es der richtige Kunde ist anhand Historie
                    $confidence = $this->calculateNameMatchConfidence($customer, $customerName, $phoneNumber);
                    
                    if ($confidence > 0.7) {
                        return $this->buildCustomerResponse($customer, 'name_match', $confidence);
                    }
                }
            }

            // 4. Kein Kunde gefunden
            return [
                'success' => true,
                'found' => false,
                'message' => 'Kein existierender Kunde gefunden',
                'suggestions' => [
                    'action' => 'create_new_customer',
                    'phone' => $phoneNumber,
                    'name' => $customerName
                ]
            ];

        } catch (\Exception $e) {
            Log::error('IdentifyCustomerFunction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Fehler bei der Kundenidentifikation',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Baut die Kunden-Response auf
     */
    protected function buildCustomerResponse(Customer $customer, string $matchType, float $confidence = 1.0): array
    {
        // Lade letzte Termine
        $lastAppointments = Appointment::where('customer_id', $customer->id)
            ->orderBy('starts_at', 'desc')
            ->limit(3)
            ->get();

        // Berechne Kunden-Statistiken
        $stats = $this->calculateCustomerStats($customer);

        // Bevorzugte Services
        $preferredServices = $this->getPreferredServices($customer);

        // Bevorzugte Mitarbeiter
        $preferredStaff = $this->getPreferredStaff($customer);

        return [
            'success' => true,
            'found' => true,
            'match_type' => $matchType,
            'confidence' => $confidence,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'tags' => $customer->tags ?? [],
                'notes' => $customer->notes,
                'created_at' => $customer->created_at->format('Y-m-d'),
                'is_vip' => $customer->is_vip ?? false,
                'loyalty_points' => $customer->loyalty_points ?? 0
            ],
            'history' => [
                'total_appointments' => $stats['total_appointments'],
                'completed_appointments' => $stats['completed_appointments'],
                'no_shows' => $stats['no_shows'],
                'cancellations' => $stats['cancellations'],
                'total_spent' => $stats['total_spent'],
                'average_spend' => $stats['average_spend'],
                'last_visit' => $stats['last_visit'],
                'customer_since' => $customer->created_at->diffForHumans()
            ],
            'preferences' => [
                'preferred_services' => $preferredServices,
                'preferred_staff' => $preferredStaff,
                'preferred_time' => $stats['preferred_time'],
                'preferred_day' => $stats['preferred_day']
            ],
            'last_appointments' => $lastAppointments->map(function($apt) {
                return [
                    'date' => $apt->starts_at->format('d.m.Y'),
                    'time' => $apt->starts_at->format('H:i'),
                    'service' => $apt->service?->name,
                    'staff' => $apt->staff?->name,
                    'status' => $apt->status,
                    'price' => $apt->price
                ];
            })->toArray(),
            'ai_insights' => $this->generateAIInsights($customer, $stats)
        ];
    }

    /**
     * Normalisiert Telefonnummern
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Entferne alle nicht-numerischen Zeichen außer +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Verschiedene Normalisierungen für deutsche Nummern
        if (str_starts_with($phone, '00')) {
            // 00491234567890 -> +491234567890
            $phone = '+' . substr($phone, 2);
        } elseif (str_starts_with($phone, '0') && !str_starts_with($phone, '00')) {
            // 01234567890 -> +491234567890
            $phone = '+49' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '+')) {
            // 1234567890 -> +491234567890
            $phone = '+49' . $phone;
        }
        
        return $phone;
    }

    /**
     * Generiert alternative Telefonnummern-Formate
     */
    protected function generateAlternativeNumbers(string $phone): array
    {
        $alternatives = [$phone];
        
        // Original ohne +
        if (str_starts_with($phone, '+')) {
            $alternatives[] = substr($phone, 1);
        }
        
        // Mit führender 0 statt +49
        if (str_starts_with($phone, '+49')) {
            $alternatives[] = '0' . substr($phone, 3);
        }
        
        // Ohne Ländervorwahl
        if (str_starts_with($phone, '+49')) {
            $alternatives[] = substr($phone, 3);
        }
        
        // Mit Leerzeichen/Bindestrichen an verschiedenen Stellen
        $withoutPlus = str_replace('+', '', $phone);
        $alternatives[] = substr($withoutPlus, 0, 2) . ' ' . substr($withoutPlus, 2);
        
        return array_unique($alternatives);
    }

    /**
     * Sucht Kunden nach Namensähnlichkeit
     */
    protected function findByNameSimilarity(string $name, string $companyId): ?Customer
    {
        $nameParts = explode(' ', mb_strtolower($name));
        
        $query = Customer::where('company_id', $companyId);
        
        // Suche nach jedem Namensteil
        foreach ($nameParts as $part) {
            if (strlen($part) > 2) {
                $query->where('name', 'LIKE', '%' . $part . '%');
            }
        }
        
        return $query->first();
    }

    /**
     * Berechnet Konfidenz für Namens-Match
     */
    protected function calculateNameMatchConfidence(Customer $customer, string $searchName, string $phoneNumber): float
    {
        $confidence = 0.0;
        
        // Basis-Namensähnlichkeit
        similar_text(mb_strtolower($customer->name), mb_strtolower($searchName), $percent);
        $confidence += ($percent / 100) * 0.5;
        
        // Bonus wenn Teile der Telefonnummer übereinstimmen
        if ($customer->phone) {
            $customerDigits = preg_replace('/[^0-9]/', '', $customer->phone);
            $searchDigits = preg_replace('/[^0-9]/', '', $phoneNumber);
            
            // Prüfe letzte 4 Ziffern
            if (strlen($customerDigits) >= 4 && strlen($searchDigits) >= 4) {
                if (substr($customerDigits, -4) === substr($searchDigits, -4)) {
                    $confidence += 0.3;
                }
            }
        }
        
        // Bonus für aktive Kunden
        $lastAppointment = Appointment::where('customer_id', $customer->id)
            ->orderBy('starts_at', 'desc')
            ->first();
            
        if ($lastAppointment && $lastAppointment->starts_at->diffInDays(now()) < 90) {
            $confidence += 0.2;
        }
        
        return min(1.0, $confidence);
    }

    /**
     * Berechnet Kunden-Statistiken
     */
    protected function calculateCustomerStats(Customer $customer): array
    {
        $appointments = Appointment::where('customer_id', $customer->id)
            ->with(['service', 'staff'])
            ->get();

        $stats = [
            'total_appointments' => $appointments->count(),
            'completed_appointments' => $appointments->where('status', 'completed')->count(),
            'no_shows' => $appointments->where('status', 'no_show')->count(),
            'cancellations' => $appointments->where('status', 'cancelled')->count(),
            'total_spent' => $appointments->where('status', 'completed')->sum('price'),
            'average_spend' => 0,
            'last_visit' => null,
            'preferred_time' => null,
            'preferred_day' => null
        ];

        if ($stats['completed_appointments'] > 0) {
            $stats['average_spend'] = round($stats['total_spent'] / $stats['completed_appointments'], 2);
        }

        $lastCompleted = $appointments->where('status', 'completed')
            ->sortByDesc('starts_at')
            ->first();
            
        if ($lastCompleted) {
            $stats['last_visit'] = $lastCompleted->starts_at->format('d.m.Y');
        }

        // Bevorzugte Zeit analysieren
        $times = $appointments->where('status', 'completed')
            ->groupBy(function($apt) {
                $hour = $apt->starts_at->hour;
                if ($hour < 12) return 'morning';
                if ($hour < 15) return 'noon';
                if ($hour < 18) return 'afternoon';
                return 'evening';
            })
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();
            
        $stats['preferred_time'] = $times;

        // Bevorzugter Wochentag
        $days = $appointments->where('status', 'completed')
            ->groupBy(function($apt) {
                return $apt->starts_at->dayOfWeek;
            })
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();
            
        if ($days !== null) {
            $dayNames = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
            $stats['preferred_day'] = $dayNames[$days];
        }

        return $stats;
    }

    /**
     * Ermittelt bevorzugte Services
     */
    protected function getPreferredServices(Customer $customer): array
    {
        return Appointment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->with('service')
            ->get()
            ->groupBy('service_id')
            ->map(function($group) {
                $service = $group->first()->service;
                return [
                    'id' => $service?->id,
                    'name' => $service?->name,
                    'count' => $group->count(),
                    'last_booked' => $group->sortByDesc('starts_at')->first()->starts_at->format('d.m.Y')
                ];
            })
            ->sortByDesc('count')
            ->take(3)
            ->values()
            ->toArray();
    }

    /**
     * Ermittelt bevorzugte Mitarbeiter
     */
    protected function getPreferredStaff(Customer $customer): array
    {
        return Appointment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->whereNotNull('staff_id')
            ->with('staff')
            ->get()
            ->groupBy('staff_id')
            ->map(function($group) {
                $staff = $group->first()->staff;
                return [
                    'id' => $staff?->id,
                    'name' => $staff?->name,
                    'count' => $group->count(),
                    'last_appointment' => $group->sortByDesc('starts_at')->first()->starts_at->format('d.m.Y')
                ];
            })
            ->sortByDesc('count')
            ->take(2)
            ->values()
            ->toArray();
    }

    /**
     * Generiert AI-Insights über den Kunden
     */
    protected function generateAIInsights(Customer $customer, array $stats): array
    {
        $insights = [];

        // Treuekunde
        if ($stats['total_appointments'] >= 10) {
            $insights[] = [
                'type' => 'loyalty',
                'message' => 'Treuekunde mit über 10 Terminen',
                'importance' => 'high'
            ];
        }

        // No-Show Risiko
        if ($stats['total_appointments'] > 0) {
            $noShowRate = $stats['no_shows'] / $stats['total_appointments'];
            if ($noShowRate > 0.2) {
                $insights[] = [
                    'type' => 'risk',
                    'message' => 'Erhöhtes No-Show Risiko (' . round($noShowRate * 100) . '%)',
                    'importance' => 'medium'
                ];
            }
        }

        // Inaktiver Kunde
        if ($stats['last_visit']) {
            $daysSinceLastVisit = now()->diffInDays($stats['last_visit']);
            if ($daysSinceLastVisit > 180) {
                $insights[] = [
                    'type' => 'reactivation',
                    'message' => 'Lange nicht mehr da gewesen (' . round($daysSinceLastVisit / 30) . ' Monate)',
                    'importance' => 'medium'
                ];
            }
        }

        // High-Value Kunde
        if ($stats['average_spend'] > 100) {
            $insights[] = [
                'type' => 'value',
                'message' => 'High-Value Kunde (Ø ' . number_format($stats['average_spend'], 2, ',', '.') . ' €)',
                'importance' => 'high'
            ];
        }

        // Neue Kunde
        if ($customer->created_at->diffInDays(now()) < 30 && $stats['total_appointments'] < 2) {
            $insights[] = [
                'type' => 'new',
                'message' => 'Neukunde - besondere Aufmerksamkeit empfohlen',
                'importance' => 'medium'
            ];
        }

        return $insights;
    }
}