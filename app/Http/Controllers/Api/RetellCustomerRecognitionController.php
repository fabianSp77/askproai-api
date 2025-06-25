<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Customer\EnhancedCustomerService;
use App\Services\PhoneNumberResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RetellCustomerRecognitionController extends Controller
{
    protected EnhancedCustomerService $customerService;
    protected PhoneNumberResolver $phoneResolver;
    
    public function __construct(
        EnhancedCustomerService $customerService,
        PhoneNumberResolver $phoneResolver
    ) {
        $this->customerService = $customerService;
        $this->phoneResolver = $phoneResolver;
    }
    
    /**
     * Retell Custom Function: identify_customer
     * Identifiziert Kunde anhand Telefonnummer
     */
    public function identifyCustomer(Request $request)
    {
        try {
            $data = $request->input('args', $request->all());
            
            // Maskiere sensitive Daten in Logs
            $maskedData = $data;
            if (isset($maskedData['phone_number'])) {
                $maskedData['phone_number'] = substr($maskedData['phone_number'], 0, 3) . '****' . substr($maskedData['phone_number'], -2);
            }
            if (isset($maskedData['telefonnummer'])) {
                $maskedData['telefonnummer'] = substr($maskedData['telefonnummer'], 0, 3) . '****' . substr($maskedData['telefonnummer'], -2);
            }
            
            Log::info('Retell customer identification request', [
                'data' => $maskedData,  // Verwende maskierte Daten
                'call_id' => $data['call_id'] ?? null
            ]);
            
            // Extrahiere Telefonnummer
            $phoneNumber = $data['phone_number'] ?? $data['telefonnummer'] ?? null;
            
            if (!$phoneNumber) {
                return response()->json([
                    'customer_found' => false,
                    'message' => 'Keine Telefonnummer angegeben'
                ]);
            }
            
            // Resolve Company Context
            $context = $this->phoneResolver->resolveFromWebhook([
                'to' => $data['to_number'] ?? null,
                'from' => $phoneNumber
            ]);
            
            if (!$context['company_id']) {
                return response()->json([
                    'customer_found' => false,
                    'message' => 'Firma nicht gefunden'
                ]);
            }
            
            // Identifiziere Kunde
            $customerData = $this->customerService->identifyByPhone(
                $phoneNumber,
                $context['company_id']
            );
            
            if (!$customerData) {
                return response()->json([
                    'customer_found' => false,
                    'is_new_customer' => true,
                    'phone_number' => $phoneNumber,
                    'company_id' => $context['company_id']
                ]);
            }
            
            // Generiere personalisierte Begrüßung
            $greeting = $this->customerService->generatePersonalizedGreeting(
                $customerData['customer']['id']
            );
            
            // Bereite Antwort für Retell auf
            $response = [
                'customer_found' => true,
                'customer_id' => $customerData['customer']['id'],
                'customer_name' => $customerData['customer']['name'],
                'vip_status' => $customerData['vip_status']['status'],
                'vip_benefits' => $customerData['vip_status']['benefits'],
                'personalized_greeting' => $greeting['greeting'],
                'greeting_elements' => $greeting['elements'],
                
                // Präferenzen
                'time_preferences' => $customerData['preferences']['time_preference'] ?? [],
                'weekday_preferences' => $customerData['preferences']['weekday_preference'] ?? [],
                'staff_preferences' => $customerData['preferences']['staff_preference'] ?? [],
                
                // Historie
                'total_appointments' => $customerData['history']['total_appointments'],
                'last_appointment' => $customerData['last_interaction']['appointment']['start_time'] ?? null,
                'member_since' => $customerData['history']['member_since'],
                
                // Statistiken
                'no_show_count' => $customerData['history']['no_shows'],
                'favorite_services' => $customerData['statistics']['top_services'] ?? [],
                
                // Personalisierung
                'preferred_language' => $customerData['personalization']['preferred_language'],
                'notes' => $customerData['personalization']['notes']
            ];
            
            // Cache Kundendaten für schnelleren Zugriff während des Anrufs
            if (isset($data['call_id'])) {
                // Verschlüssele sensitive Daten vor Cache-Speicherung
                $encryptedResponse = $response;
                $encryptedResponse['customer_name'] = encrypt($response['customer_name']);
                $encryptedResponse['notes'] = isset($response['notes']) ? encrypt($response['notes']) : null;
                
                Cache::put(
                    "retell:customer:{$data['call_id']}",
                    $encryptedResponse,
                    3600 // 1 Stunde
                );
            }
            
            Log::info('Customer identified successfully', [
                'customer_id' => $customerData['customer']['id'],
                'vip_status' => $customerData['vip_status']['status'],
                'has_notes' => !empty($customerData['personalization']['notes'])  // Keine echten Notizen loggen
            ]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Customer identification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'customer_found' => false,
                'error' => 'Fehler bei der Kundenidentifikation'
            ], 500);
        }
    }
    
    /**
     * Retell Custom Function: save_customer_preference
     * Speichert Kundenpräferenz während des Anrufs
     */
    public function savePreference(Request $request)
    {
        try {
            $data = $request->input('args', $request->all());
            
            $customerId = $data['customer_id'] ?? null;
            $preferenceType = $data['preference_type'] ?? null;
            $preferenceKey = $data['preference_key'] ?? null;
            $preferenceValue = $data['preference_value'] ?? null;
            
            if (!$customerId || !$preferenceType || !$preferenceKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fehlende Pflichtfelder'
                ], 400);
            }
            
            // Speichere Präferenz
            $this->customerService->savePreference(
                $customerId,
                $preferenceType,
                $preferenceKey,
                $preferenceValue
            );
            
            Log::info('Customer preference saved', [
                'customer_id' => $customerId,
                'type' => $preferenceType,
                'key' => $preferenceKey
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Präferenz gespeichert'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to save customer preference', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Speichern der Präferenz'
            ], 500);
        }
    }
    
    /**
     * Retell Custom Function: apply_vip_benefits
     * Wendet VIP-Vorteile auf Buchung an
     */
    public function applyVipBenefits(Request $request)
    {
        try {
            $data = $request->input('args', $request->all());
            
            $customerId = $data['customer_id'] ?? null;
            $bookingData = $data['booking_data'] ?? [];
            
            if (!$customerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kunde nicht angegeben'
                ], 400);
            }
            
            // Hole VIP-Status
            $customer = \App\Models\Customer::find($customerId);
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kunde nicht gefunden'
                ], 404);
            }
            
            $vipStatus = $this->customerService->calculateVipStatus($customer);
            $benefits = $vipStatus['benefits'];
            
            $appliedBenefits = [];
            
            // Wende Vorteile an
            if ($benefits['priority_booking'] ?? false) {
                $appliedBenefits[] = 'priority_booking';
                // TODO: Implementiere Prioritäts-Slot-Suche
            }
            
            if ($benefits['discount_percentage'] ?? 0 > 0) {
                $appliedBenefits[] = 'discount';
                $bookingData['discount_percentage'] = $benefits['discount_percentage'];
                $bookingData['discount_reason'] = 'VIP ' . ucfirst($vipStatus['status']);
            }
            
            if ($benefits['exclusive_slots'] ?? false) {
                $appliedBenefits[] = 'exclusive_slots';
                // TODO: Freischaltung exklusiver Zeitslots
            }
            
            if ($benefits['flexible_cancellation'] ?? false) {
                $appliedBenefits[] = 'flexible_cancellation';
                $bookingData['cancellation_policy'] = 'flexible';
            }
            
            return response()->json([
                'success' => true,
                'vip_status' => $vipStatus['status'],
                'applied_benefits' => $appliedBenefits,
                'booking_data' => $bookingData,
                'message' => count($appliedBenefits) . ' VIP-Vorteile angewendet'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to apply VIP benefits', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Anwenden der VIP-Vorteile'
            ], 500);
        }
    }
}