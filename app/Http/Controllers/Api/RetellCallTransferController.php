<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallbackRequest;
use App\Models\Call;
use App\Services\CallbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RetellCallTransferController extends Controller
{
    protected CallbackService $callbackService;
    
    // Fabian's direkte Nummer für Weiterleitungen
    const FABIAN_DIRECT_NUMBER = '+491604366218';
    
    public function __construct(CallbackService $callbackService = null)
    {
        $this->callbackService = $callbackService ?? app(CallbackService::class);
    }
    
    /**
     * Retell Custom Function: transfer_to_fabian
     * Direkte Weiterleitung zu Fabian Spitzer
     */
    public function transferToFabian(Request $request)
    {
        try {
            $data = $request->input('args', $request->all());
            
            Log::info('Transfer to Fabian requested', [
                'call_id' => $data['call_id'] ?? null,
                'reason' => $data['reason'] ?? 'direct_request'
            ]);
            
            // Retell Transfer Response
            return response()->json([
                'action' => 'transfer',
                'transfer_type' => 'blind', // Blind transfer (sofort)
                'transfer_to' => self::FABIAN_DIRECT_NUMBER,
                'transfer_message' => 'Ich verbinde Sie jetzt direkt mit Herrn Spitzer. Einen Moment bitte.',
                'hold_music' => true,
                'metadata' => [
                    'transfer_reason' => $data['reason'] ?? 'direct_request',
                    'customer_name' => $data['customer_name'] ?? null,
                    'topic' => $data['topic'] ?? null,
                    'timestamp' => now()->toIso8601String()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Transfer to Fabian failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'action' => 'continue',
                'message' => 'Entschuldigung, die Weiterleitung ist momentan nicht möglich. Kann ich Ihnen anderweitig helfen?',
                'offer_callback' => true
            ]);
        }
    }
    
    /**
     * Retell Custom Function: check_availability_for_transfer
     * Prüft Verfügbarkeit für Weiterleitung
     */
    public function checkAvailabilityForTransfer(Request $request)
    {
        try {
            $now = Carbon::now('Europe/Berlin');
            $dayOfWeek = $now->dayOfWeek;
            $hour = $now->hour;
            
            // Geschäftszeiten definieren (Mo-Fr 9-18 Uhr)
            $isBusinessHours = $dayOfWeek >= 1 && $dayOfWeek <= 5 && $hour >= 9 && $hour < 18;
            
            // Zusätzliche Verfügbarkeitsprüfung (könnte erweitert werden)
            $additionalChecks = $this->performAdditionalAvailabilityChecks();
            
            $available = $isBusinessHours && $additionalChecks['available'];
            
            return response()->json([
                'available' => $available,
                'current_time' => $now->format('H:i'),
                'is_business_hours' => $isBusinessHours,
                'reason' => !$available ? ($isBusinessHours ? 'person_unavailable' : 'outside_business_hours') : null,
                'alternative_options' => [
                    'callback' => true,
                    'email' => 'fabian@askproai.de',
                    'next_available' => $this->getNextAvailableTime()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Availability check failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'available' => false,
                'reason' => 'system_error',
                'alternative_options' => ['callback' => true]
            ]);
        }
    }
    
    /**
     * Retell Custom Function: schedule_callback
     * Plant einen Rückruf
     */
    public function scheduleCallback(Request $request)
    {
        try {
            $data = $request->input('args', $request->all());
            
            // Validierung
            $required = ['customer_name', 'phone_number', 'preferred_time'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Pflichtfeld '$field' fehlt"
                    ], 400);
                }
            }
            
            // Parse preferred time
            $preferredTime = $this->parsePreferredCallbackTime($data['preferred_time']);
            
            // Erstelle Callback Request
            $callbackRequest = CallbackRequest::create([
                'company_id' => $data['company_id'] ?? 1,
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'phone_number' => $data['phone_number'],
                'preferred_time' => $preferredTime,
                'reason' => $data['reason'] ?? 'Weiterleitung nicht möglich',
                'notes' => $data['notes'] ?? null,
                'priority' => $this->determinePriority($data),
                'assigned_to' => 'Fabian Spitzer',
                'metadata' => [
                    'original_call_id' => $data['call_id'] ?? null,
                    'requested_at' => now()->toIso8601String(),
                    'language' => $data['language'] ?? 'de'
                ]
            ]);
            
            // Sende Benachrichtigung
            $this->callbackService->notifyAboutNewCallback($callbackRequest);
            
            // Formatiere Antwort für Retell
            $formattedTime = Carbon::parse($preferredTime)->locale('de')->isoFormat('dddd, D. MMMM [um] HH:mm [Uhr]');
            
            return response()->json([
                'success' => true,
                'callback_id' => $callbackRequest->id,
                'scheduled_time' => $preferredTime,
                'formatted_time' => $formattedTime,
                'message' => "Perfekt! Ich habe einen Rückruf für Sie am {$formattedTime} eingetragen. Herr Spitzer wird Sie dann persönlich kontaktieren.",
                'confirmation_sent' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to schedule callback', [
                'error' => $e->getMessage(),
                'data' => $data ?? []
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Entschuldigung, beim Planen des Rückrufs ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.'
            ], 500);
        }
    }
    
    /**
     * Retell Custom Function: handle_urgent_transfer
     * Behandelt dringende Weiterleitungen
     */
    public function handleUrgentTransfer(Request $request)
    {
        try {
            $data = $request->input('args', $request->all());
            
            // Prüfe Dringlichkeit
            $urgencyLevel = $this->assessUrgency($data);
            
            if ($urgencyLevel === 'emergency') {
                // Bei Notfällen: Sofortige Weiterleitung oder Notfall-Nummer
                return response()->json([
                    'action' => 'transfer',
                    'transfer_type' => 'immediate',
                    'transfer_to' => self::FABIAN_DIRECT_NUMBER,
                    'transfer_message' => 'Dies scheint dringend zu sein. Ich verbinde Sie sofort.',
                    'fallback_action' => 'emergency_protocol'
                ]);
            }
            
            if ($urgencyLevel === 'high') {
                // Bei hoher Dringlichkeit: Prioritäts-Callback
                return response()->json([
                    'action' => 'priority_callback',
                    'message' => 'Ich verstehe die Dringlichkeit. Lassen Sie mich einen prioritären Rückruf für Sie einrichten.',
                    'callback_window' => '30_minutes',
                    'alternative_contact' => true
                ]);
            }
            
            // Normale Dringlichkeit
            return response()->json([
                'action' => 'standard_handling',
                'message' => 'Ich kümmere mich darum. Wie kann ich Ihnen am besten helfen?',
                'options' => ['callback', 'email', 'appointment']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Urgent transfer handling failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'action' => 'fallback',
                'message' => 'Ich leite Ihre Anfrage mit hoher Priorität weiter.'
            ]);
        }
    }
    
    /**
     * Hilfsmethoden
     */
    
    private function performAdditionalAvailabilityChecks(): array
    {
        // Hier könnten zusätzliche Checks implementiert werden:
        // - Kalender-Integration
        // - Anwesenheitsstatus
        // - Aktuelle Anruflast
        
        return [
            'available' => true, // Simplified for now
            'reason' => null
        ];
    }
    
    private function getNextAvailableTime(): string
    {
        $now = Carbon::now('Europe/Berlin');
        
        // Wenn außerhalb der Geschäftszeiten
        if ($now->hour >= 18 || $now->hour < 9) {
            $next = $now->copy()->addDay()->setTime(9, 0);
            if ($next->isWeekend()) {
                $next->nextWeekday();
            }
        } else {
            // Nächste volle Stunde
            $next = $now->copy()->addHour()->setMinute(0);
        }
        
        return $next->locale('de')->isoFormat('dddd, D. MMMM [um] HH:mm [Uhr]');
    }
    
    private function parsePreferredCallbackTime(string $timeString): Carbon
    {
        // Versuche verschiedene Formate zu parsen
        $patterns = [
            '/morgen\s+(\d{1,2})\s*uhr/i' => function($matches) {
                return Carbon::tomorrow('Europe/Berlin')->setTime((int)$matches[1], 0);
            },
            '/heute\s+(\d{1,2})\s*uhr/i' => function($matches) {
                return Carbon::today('Europe/Berlin')->setTime((int)$matches[1], 0);
            },
            '/in\s+(\d+)\s+stunden?/i' => function($matches) {
                return Carbon::now('Europe/Berlin')->addHours((int)$matches[1]);
            },
            '/(\d{1,2}):(\d{2})/' => function($matches) {
                $time = Carbon::today('Europe/Berlin')->setTime((int)$matches[1], (int)$matches[2]);
                if ($time->isPast()) {
                    $time->addDay();
                }
                return $time;
            }
        ];
        
        foreach ($patterns as $pattern => $parser) {
            if (preg_match($pattern, $timeString, $matches)) {
                return $parser($matches);
            }
        }
        
        // Fallback: Nächste Geschäftsstunde
        return $this->getNextBusinessHour();
    }
    
    private function getNextBusinessHour(): Carbon
    {
        $next = Carbon::now('Europe/Berlin');
        
        // Wenn außerhalb der Geschäftszeiten
        if ($next->hour >= 18) {
            $next->addDay()->setTime(9, 0);
        } elseif ($next->hour < 9) {
            $next->setTime(9, 0);
        } else {
            $next->addHour()->setMinute(0);
        }
        
        // Überspringe Wochenenden
        while ($next->isWeekend()) {
            $next->addDay();
        }
        
        return $next;
    }
    
    private function determinePriority(array $data): string
    {
        $keywords = [
            'dringend' => 'high',
            'notfall' => 'urgent',
            'wichtig' => 'high',
            'eilig' => 'high',
            'sofort' => 'urgent'
        ];
        
        $text = strtolower($data['reason'] ?? '') . ' ' . strtolower($data['notes'] ?? '');
        
        foreach ($keywords as $keyword => $priority) {
            if (str_contains($text, $keyword)) {
                return $priority;
            }
        }
        
        // VIP-Kunden erhalten automatisch höhere Priorität
        if (isset($data['vip_status']) && in_array($data['vip_status'], ['gold', 'platinum'])) {
            return 'high';
        }
        
        return 'normal';
    }
    
    private function assessUrgency(array $data): string
    {
        $urgencyKeywords = [
            'emergency' => ['notfall', 'dringender notfall', 'lebensbedrohlich'],
            'high' => ['sehr dringend', 'sofort', 'eilig', 'wichtig und dringend'],
            'normal' => ['normal', 'standard', 'wenn möglich']
        ];
        
        $text = strtolower($data['reason'] ?? '') . ' ' . strtolower($data['urgency'] ?? '');
        
        foreach ($urgencyKeywords as $level => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $level;
                }
            }
        }
        
        return 'normal';
    }
}