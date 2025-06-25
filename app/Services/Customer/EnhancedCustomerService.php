<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Services\PhoneNumberResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EnhancedCustomerService
{
    protected PhoneNumberResolver $phoneResolver;
    
    public function __construct(PhoneNumberResolver $phoneResolver = null)
    {
        $this->phoneResolver = $phoneResolver ?? app(PhoneNumberResolver::class);
    }
    
    /**
     * Identifiziere Kunde anhand Telefonnummer mit Fuzzy-Matching
     */
    public function identifyByPhone(string $phoneNumber, int $companyId): ?array
    {
        // Normalisiere Telefonnummer
        $normalized = $this->phoneResolver->normalize($phoneNumber);
        $variants = $this->generatePhoneVariants($normalized);
        
        // Suche in Cache
        $cacheKey = "customer:phone:{$companyId}:{$normalized}";
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Suche Kunde mit verschiedenen Varianten
        $customer = Customer::where('company_id', $companyId)
            ->where(function ($query) use ($variants) {
                foreach ($variants as $variant) {
                    $query->orWhere('phone', $variant);
                }
            })
            ->first();
        
        if (!$customer) {
            // Fuzzy-Suche für ähnliche Nummern
            $customer = $this->fuzzyPhoneSearch($phoneNumber, $companyId);
        }
        
        if ($customer) {
            // Lade zusätzliche Informationen
            $customerData = $this->enrichCustomerData($customer);
            
            // Cache für 1 Stunde
            Cache::put($cacheKey, $customerData, 3600);
            
            // Update last_seen
            $customer->update(['last_seen_at' => now()]);
            
            // Log Interaktion
            $this->logInteraction($customer->id, 'call', [
                'phone_number' => $phoneNumber,
                'identification_method' => 'phone'
            ]);
            
            return $customerData;
        }
        
        // Cache negative Ergebnisse für 5 Minuten
        Cache::put($cacheKey, null, 300);
        
        return null;
    }
    
    /**
     * Reichere Kundendaten mit Historie und Präferenzen an
     */
    protected function enrichCustomerData(Customer $customer): array
    {
        // Basis-Kundendaten
        $data = [
            'customer' => $customer->toArray(),
            'vip_status' => $this->calculateVipStatus($customer),
            'preferences' => $this->getCustomerPreferences($customer->id),
            'history' => $this->getCustomerHistory($customer->id),
            'statistics' => $this->getCustomerStatistics($customer->id),
            'last_interaction' => $this->getLastInteraction($customer->id),
            'personalization' => $this->getPersonalizationData($customer)
        ];
        
        return $data;
    }
    
    /**
     * Berechne VIP-Status basierend auf verschiedenen Faktoren
     */
    public function calculateVipStatus(Customer $customer): array
    {
        $score = 0;
        $factors = [];
        
        // Anzahl Termine
        if ($customer->total_appointments >= 50) {
            $score += 30;
            $factors[] = 'high_appointment_count';
        } elseif ($customer->total_appointments >= 20) {
            $score += 20;
            $factors[] = 'medium_appointment_count';
        } elseif ($customer->total_appointments >= 10) {
            $score += 10;
            $factors[] = 'regular_customer';
        }
        
        // Treue (Zeit seit erster Buchung)
        $firstAppointment = Appointment::where('customer_id', $customer->id)
            ->orderBy('created_at', 'asc')
            ->first();
            
        if ($firstAppointment) {
            $monthsSinceFirst = $firstAppointment->created_at->diffInMonths(now());
            if ($monthsSinceFirst >= 24) {
                $score += 25;
                $factors[] = 'long_term_customer';
            } elseif ($monthsSinceFirst >= 12) {
                $score += 15;
                $factors[] = 'loyal_customer';
            } elseif ($monthsSinceFirst >= 6) {
                $score += 5;
                $factors[] = 'established_customer';
            }
        }
        
        // No-Show Rate (negativ)
        $noShowRate = $customer->total_appointments > 0 
            ? ($customer->no_show_count / $customer->total_appointments) 
            : 0;
            
        if ($noShowRate > 0.2) {
            $score -= 20;
            $factors[] = 'high_no_show_rate';
        } elseif ($noShowRate > 0.1) {
            $score -= 10;
            $factors[] = 'moderate_no_show_rate';
        }
        
        // Umsatz (falls verfügbar)
        $totalRevenue = Appointment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->sum('total_price');
            
        if ($totalRevenue >= 5000) {
            $score += 25;
            $factors[] = 'high_revenue';
        } elseif ($totalRevenue >= 2000) {
            $score += 15;
            $factors[] = 'good_revenue';
        } elseif ($totalRevenue >= 1000) {
            $score += 5;
            $factors[] = 'moderate_revenue';
        }
        
        // Bestimme Status basierend auf Score
        $status = 'none';
        if ($score >= 80) {
            $status = 'platinum';
        } elseif ($score >= 60) {
            $status = 'gold';
        } elseif ($score >= 40) {
            $status = 'silver';
        } elseif ($score >= 20) {
            $status = 'bronze';
        }
        
        // Update Customer VIP Status
        if ($customer->vip_status !== $status) {
            $customer->update(['vip_status' => $status]);
        }
        
        return [
            'status' => $status,
            'score' => $score,
            'factors' => $factors,
            'benefits' => $this->getVipBenefits($status)
        ];
    }
    
    /**
     * Hole Kundenpräferenzen
     */
    public function getCustomerPreferences(int $customerId): array
    {
        $preferences = DB::table('customer_preferences')
            ->where('customer_id', $customerId)
            ->orderBy('confidence_score', 'desc')
            ->orderBy('usage_count', 'desc')
            ->get();
        
        $grouped = [];
        
        foreach ($preferences as $pref) {
            $type = $pref->preference_type;
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            
            $grouped[$type][] = [
                'key' => $pref->preference_key,
                'value' => json_decode($pref->preference_value, true),
                'confidence' => $pref->confidence_score,
                'usage_count' => $pref->usage_count,
                'last_used' => $pref->last_used_at
            ];
        }
        
        // Analysiere implizite Präferenzen aus Historie
        $implicitPrefs = $this->analyzeImplicitPreferences($customerId);
        
        return array_merge($grouped, $implicitPrefs);
    }
    
    /**
     * Analysiere implizite Präferenzen aus Buchungshistorie
     */
    protected function analyzeImplicitPreferences(int $customerId): array
    {
        $preferences = [];
        
        // Bevorzugte Buchungszeiten
        $timePrefs = Appointment::where('customer_id', $customerId)
            ->where('status', 'completed')
            ->selectRaw('HOUR(start_time) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->get();
        
        if ($timePrefs->isNotEmpty()) {
            $preferences['time_preference'] = $timePrefs->map(function ($pref) {
                $timeRange = $this->hourToTimeRange($pref->hour);
                return [
                    'range' => $timeRange,
                    'frequency' => $pref->count,
                    'confidence' => min($pref->count * 0.1, 0.9)
                ];
            })->toArray();
        }
        
        // Bevorzugte Wochentage
        $dayPrefs = Appointment::where('customer_id', $customerId)
            ->where('status', 'completed')
            ->selectRaw('DAYOFWEEK(start_time) as weekday, COUNT(*) as count')
            ->groupBy('weekday')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->get();
        
        if ($dayPrefs->isNotEmpty()) {
            $preferences['weekday_preference'] = $dayPrefs->map(function ($pref) {
                return [
                    'weekday' => $this->numberToWeekday($pref->weekday),
                    'frequency' => $pref->count,
                    'confidence' => min($pref->count * 0.1, 0.9)
                ];
            })->toArray();
        }
        
        // Bevorzugte Mitarbeiter
        $staffPrefs = Appointment::where('customer_id', $customerId)
            ->where('status', 'completed')
            ->whereNotNull('staff_id')
            ->selectRaw('staff_id, COUNT(*) as count')
            ->groupBy('staff_id')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->get();
        
        if ($staffPrefs->isNotEmpty()) {
            $preferences['staff_preference'] = $staffPrefs->map(function ($pref) {
                $staff = \App\Models\Staff::find($pref->staff_id);
                return [
                    'staff_id' => $pref->staff_id,
                    'staff_name' => $staff ? $staff->name : 'Unknown',
                    'frequency' => $pref->count,
                    'confidence' => min($pref->count * 0.15, 0.95)
                ];
            })->toArray();
        }
        
        return $preferences;
    }
    
    /**
     * Generiere personalisierte Begrüßung
     */
    public function generatePersonalizedGreeting(int $customerId): array
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return [
                'greeting' => 'Guten Tag! Willkommen bei {{company_name}}.',
                'type' => 'default'
            ];
        }
        
        $data = $this->enrichCustomerData($customer);
        $greeting = '';
        $type = 'personalized';
        $elements = [];
        
        // VIP-Begrüßung
        if ($data['vip_status']['status'] !== 'none') {
            $statusNames = [
                'platinum' => 'Platin',
                'gold' => 'Gold',
                'silver' => 'Silber',
                'bronze' => 'Bronze'
            ];
            $elements[] = 'vip_status';
            $greeting = "Guten Tag, {$customer->name}! Schön, von unserem {$statusNames[$data['vip_status']['status']]}-Kunden zu hören. ";
        } else {
            // Standard personalisierte Begrüßung
            $greeting = "Guten Tag, {$customer->name}! Schön, wieder von Ihnen zu hören. ";
        }
        
        // Füge kontextbezogene Informationen hinzu
        if (isset($data['last_interaction']['appointment'])) {
            $lastAppointment = $data['last_interaction']['appointment'];
            $daysAgo = Carbon::parse($lastAppointment['start_time'])->diffInDays(now());
            
            if ($daysAgo < 7) {
                $greeting .= "Ich hoffe, Ihr letzter Termin war zufriedenstellend. ";
                $elements[] = 'recent_appointment';
            } elseif ($daysAgo > 60) {
                $greeting .= "Es ist schon eine Weile her seit Ihrem letzten Besuch. ";
                $elements[] = 'long_absence';
            }
        }
        
        // Füge bevorzugte Mitarbeiter-Info hinzu
        if (isset($data['preferences']['staff_preference'][0])) {
            $preferredStaff = $data['preferences']['staff_preference'][0];
            if ($preferredStaff['confidence'] > 0.7) {
                $greeting .= "Möchten Sie wieder einen Termin bei {$preferredStaff['staff_name']}? ";
                $elements[] = 'preferred_staff';
            }
        }
        
        return [
            'greeting' => $greeting,
            'type' => $type,
            'elements' => $elements,
            'customer_name' => $customer->name,
            'vip_status' => $data['vip_status']['status'],
            'personalization_score' => $this->calculatePersonalizationScore($elements)
        ];
    }
    
    /**
     * Speichere/Update Kundenpräferenz
     */
    public function savePreference(
        int $customerId,
        string $type,
        string $key,
        $value,
        float $confidence = 0.5
    ): void {
        DB::table('customer_preferences')->updateOrInsert(
            [
                'customer_id' => $customerId,
                'preference_type' => $type,
                'preference_key' => $key
            ],
            [
                'company_id' => Customer::find($customerId)->company_id,
                'preference_value' => json_encode($value),
                'confidence_score' => $confidence,
                'usage_count' => DB::raw('IFNULL(usage_count, 0) + 1'),
                'last_used_at' => now(),
                'updated_at' => now()
            ]
        );
    }
    
    /**
     * Log Kundeninteraktion
     */
    public function logInteraction(
        int $customerId,
        string $type,
        array $data,
        ?float $sentiment = null
    ): void {
        DB::table('customer_interactions')->insert([
            'customer_id' => $customerId,
            'company_id' => Customer::find($customerId)->company_id,
            'interaction_type' => $type,
            'interaction_data' => json_encode($data),
            'sentiment_score' => $sentiment,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    /**
     * Hilfsmethoden
     */
    
    protected function generatePhoneVariants(string $phone): array
    {
        $variants = [$phone];
        
        // Mit/ohne Ländercode
        if (str_starts_with($phone, '+49')) {
            $variants[] = '0' . substr($phone, 3);
        } elseif (str_starts_with($phone, '0')) {
            $variants[] = '+49' . substr($phone, 1);
        }
        
        // Mit/ohne Leerzeichen
        $variants[] = str_replace(' ', '', $phone);
        $variants[] = preg_replace('/(\d{3})(\d{3})(\d+)/', '$1 $2 $3', $phone);
        
        return array_unique($variants);
    }
    
    protected function fuzzyPhoneSearch(string $phone, int $companyId): ?Customer
    {
        // Entferne alle nicht-numerischen Zeichen (sicher)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Keine Suche bei zu kurzen Nummern
        if (strlen($cleanPhone) < 7) {
            return null;
        }
        
        // Suche nach ähnlichen Nummern (letzte 7 Ziffern) - parameterisiert
        $lastDigits = substr($cleanPhone, -7);
        
        // Verwende Query Builder mit Bindings statt whereRaw
        return Customer::where('company_id', $companyId)
            ->where(function ($query) use ($lastDigits) {
                // Sichere LIKE-Suche mit Escaping
                $query->where('phone', 'LIKE', '%' . $lastDigits)
                      ->orWhere('phone', 'LIKE', '%' . chunk_split($lastDigits, 3, ' ') . '%')
                      ->orWhere('phone', 'LIKE', '%' . chunk_split($lastDigits, 3, '-') . '%');
            })
            ->first();
    }
    
    protected function getCustomerHistory(int $customerId): array
    {
        return [
            'total_appointments' => Appointment::where('customer_id', $customerId)->count(),
            'completed_appointments' => Appointment::where('customer_id', $customerId)
                ->where('status', 'completed')->count(),
            'cancelled_appointments' => Appointment::where('customer_id', $customerId)
                ->where('status', 'cancelled')->count(),
            'no_shows' => Appointment::where('customer_id', $customerId)
                ->where('status', 'no_show')->count(),
            'total_calls' => Call::where('customer_id', $customerId)->count(),
            'member_since' => Customer::find($customerId)->created_at->format('Y-m-d')
        ];
    }
    
    protected function getCustomerStatistics(int $customerId): array
    {
        $stats = [];
        
        // Durchschnittliche Termindauer
        $avgDuration = Appointment::where('customer_id', $customerId)
            ->where('status', 'completed')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg_duration')
            ->first();
            
        $stats['avg_appointment_duration'] = $avgDuration->avg_duration ?? 60;
        
        // Lieblings-Services
        $topServices = Appointment::where('customer_id', $customerId)
            ->where('status', 'completed')
            ->whereNotNull('service_id')
            ->selectRaw('service_id, COUNT(*) as count')
            ->groupBy('service_id')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->get();
            
        $stats['top_services'] = $topServices->map(function ($item) {
            $service = \App\Models\Service::find($item->service_id);
            return [
                'service_id' => $item->service_id,
                'service_name' => $service ? $service->name : 'Unknown',
                'count' => $item->count
            ];
        })->toArray();
        
        return $stats;
    }
    
    protected function getLastInteraction(int $customerId): array
    {
        $lastAppointment = Appointment::where('customer_id', $customerId)
            ->orderBy('start_time', 'desc')
            ->first();
            
        $lastCall = Call::where('customer_id', $customerId)
            ->orderBy('started_at', 'desc')
            ->first();
            
        $lastInteraction = DB::table('customer_interactions')
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->first();
        
        return [
            'appointment' => $lastAppointment ? $lastAppointment->toArray() : null,
            'call' => $lastCall ? $lastCall->toArray() : null,
            'interaction' => $lastInteraction ? (array) $lastInteraction : null,
            'most_recent' => $this->getMostRecentActivity($lastAppointment, $lastCall, $lastInteraction)
        ];
    }
    
    protected function getMostRecentActivity($appointment, $call, $interaction): ?array
    {
        $activities = [];
        
        if ($appointment) {
            $activities[] = [
                'type' => 'appointment',
                'date' => $appointment->start_time,
                'data' => $appointment->toArray()
            ];
        }
        
        if ($call) {
            $activities[] = [
                'type' => 'call',
                'date' => $call->started_at,
                'data' => $call->toArray()
            ];
        }
        
        if ($interaction) {
            $activities[] = [
                'type' => 'interaction',
                'date' => $interaction->created_at,
                'data' => (array) $interaction
            ];
        }
        
        if (empty($activities)) {
            return null;
        }
        
        // Sortiere nach Datum
        usort($activities, function ($a, $b) {
            return Carbon::parse($b['date'])->timestamp - Carbon::parse($a['date'])->timestamp;
        });
        
        return $activities[0];
    }
    
    protected function getPersonalizationData(Customer $customer): array
    {
        return [
            'preferred_language' => $customer->custom_attributes['language'] ?? 'de',
            'communication_preferences' => $customer->custom_attributes['communication'] ?? ['phone'],
            'special_requirements' => $customer->custom_attributes['requirements'] ?? [],
            'notes' => $customer->notes
        ];
    }
    
    protected function getVipBenefits(string $status): array
    {
        $benefits = [
            'bronze' => [
                'priority_booking' => false,
                'flexible_cancellation' => true,
                'discount_percentage' => 0,
                'exclusive_slots' => false
            ],
            'silver' => [
                'priority_booking' => true,
                'flexible_cancellation' => true,
                'discount_percentage' => 5,
                'exclusive_slots' => false
            ],
            'gold' => [
                'priority_booking' => true,
                'flexible_cancellation' => true,
                'discount_percentage' => 10,
                'exclusive_slots' => true
            ],
            'platinum' => [
                'priority_booking' => true,
                'flexible_cancellation' => true,
                'discount_percentage' => 15,
                'exclusive_slots' => true,
                'personal_account_manager' => true
            ]
        ];
        
        return $benefits[$status] ?? [];
    }
    
    protected function hourToTimeRange(int $hour): string
    {
        if ($hour < 6) return 'early_morning';
        if ($hour < 9) return 'morning';
        if ($hour < 12) return 'late_morning';
        if ($hour < 14) return 'noon';
        if ($hour < 17) return 'afternoon';
        if ($hour < 20) return 'evening';
        return 'night';
    }
    
    protected function numberToWeekday(int $number): string
    {
        $days = [
            1 => 'Sonntag',
            2 => 'Montag',
            3 => 'Dienstag',
            4 => 'Mittwoch',
            5 => 'Donnerstag',
            6 => 'Freitag',
            7 => 'Samstag'
        ];
        
        return $days[$number] ?? 'Unknown';
    }
    
    protected function calculatePersonalizationScore(array $elements): float
    {
        $weights = [
            'vip_status' => 0.3,
            'recent_appointment' => 0.2,
            'long_absence' => 0.15,
            'preferred_staff' => 0.25,
            'custom_preferences' => 0.1
        ];
        
        $score = 0;
        foreach ($elements as $element) {
            $score += $weights[$element] ?? 0.05;
        }
        
        return min($score, 1.0);
    }
}