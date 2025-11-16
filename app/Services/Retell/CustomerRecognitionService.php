<?php

namespace App\Services\Retell;

use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Customer Recognition Service
 *
 * Analyzes customer appointment history to predict:
 * - Most likely service (predicted_service)
 * - Confidence score (service_confidence)
 * - Preferred staff member (preferred_staff)
 *
 * Used by check_customer to enable smart booking suggestions
 */
class CustomerRecognitionService
{
    /**
     * Analyze customer appointment history and predict preferences
     *
     * @param Customer $customer
     * @return array {
     *   predicted_service: string|null - Most booked service name
     *   service_confidence: float - Confidence score 0.0-1.0
     *   preferred_staff: string|null - Most booked staff member name
     *   preferred_staff_id: int|null - Staff ID
     *   appointment_history: array - Summary of past appointments
     * }
     */
    public function analyzeCustomerPreferences(Customer $customer): array
    {
        try {
            Log::info('🔍 Analyzing customer preferences', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name
            ]);

            // Get all completed appointments for this customer
            // SECURITY: Filter by company_id to prevent multi-tenant data leak
            $appointments = Appointment::where('customer_id', $customer->id)
                ->where('company_id', $customer->company_id)
                ->whereNotNull('service_id')
                ->whereNotNull('staff_id')
                ->where('status', '!=', 'cancelled')
                ->with(['service', 'staff'])
                ->orderBy('starts_at', 'desc')
                ->get();

            $totalAppointments = $appointments->count();

            if ($totalAppointments === 0) {
                Log::info('📊 No appointment history found', [
                    'customer_id' => $customer->id
                ]);

                return [
                    'predicted_service' => null,
                    'service_confidence' => 0.0,
                    'preferred_staff' => null,
                    'preferred_staff_id' => null,
                    'appointment_history' => [
                        'total_appointments' => 0,
                        'services' => [],
                        'staff_members' => []
                    ]
                ];
            }

            // SERVICE ANALYSIS: Count frequency of each service
            $serviceFrequency = [];
            foreach ($appointments as $appointment) {
                if ($appointment->service) {
                    $serviceName = $appointment->service->name;
                    if (!isset($serviceFrequency[$serviceName])) {
                        $serviceFrequency[$serviceName] = [
                            'count' => 0,
                            'service_id' => $appointment->service->id,
                            'name' => $serviceName
                        ];
                    }
                    $serviceFrequency[$serviceName]['count']++;
                }
            }

            // Find most frequent service
            $predictedService = null;
            $serviceConfidence = 0.0;
            $maxCount = 0;

            foreach ($serviceFrequency as $service) {
                if ($service['count'] > $maxCount) {
                    $maxCount = $service['count'];
                    $predictedService = $service['name'];
                }
            }

            // Calculate confidence:
            // - 1.0 = Always booked same service (100%)
            // - 0.5 = Half of appointments
            // - Minimum 0.7 required for "confident prediction"
            if ($predictedService && $totalAppointments > 0) {
                $serviceConfidence = round($maxCount / $totalAppointments, 2);
            }

            // STAFF ANALYSIS: Count frequency of each staff member
            $staffFrequency = [];
            foreach ($appointments as $appointment) {
                if ($appointment->staff) {
                    $staffId = $appointment->staff->id;
                    if (!isset($staffFrequency[$staffId])) {
                        $staffFrequency[$staffId] = [
                            'count' => 0,
                            'staff_id' => $staffId,
                            'name' => $appointment->staff->name
                        ];
                    }
                    $staffFrequency[$staffId]['count']++;
                }
            }

            // Find preferred staff
            $preferredStaff = null;
            $preferredStaffId = null;
            $maxStaffCount = 0;

            foreach ($staffFrequency as $staff) {
                if ($staff['count'] > $maxStaffCount) {
                    $maxStaffCount = $staff['count'];
                    $preferredStaff = $staff['name'];
                    $preferredStaffId = $staff['staff_id'];
                }
            }

            Log::info('✅ Customer preferences analyzed', [
                'customer_id' => $customer->id,
                'total_appointments' => $totalAppointments,
                'predicted_service' => $predictedService,
                'service_confidence' => $serviceConfidence,
                'preferred_staff' => $preferredStaff,
                'service_frequency' => array_values($serviceFrequency),
                'staff_frequency' => array_values($staffFrequency)
            ]);

            return [
                'predicted_service' => $predictedService,
                'service_confidence' => $serviceConfidence,
                'preferred_staff' => $preferredStaff,
                'preferred_staff_id' => $preferredStaffId,
                'appointment_history' => [
                    'total_appointments' => $totalAppointments,
                    'services' => array_values($serviceFrequency),
                    'staff_members' => array_values($staffFrequency)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error analyzing customer preferences', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'predicted_service' => null,
                'service_confidence' => 0.0,
                'preferred_staff' => null,
                'preferred_staff_id' => null,
                'appointment_history' => [
                    'total_appointments' => 0,
                    'services' => [],
                    'staff_members' => []
                ]
            ];
        }
    }

    /**
     * Generate smart greeting based on customer preferences
     *
     * @param Customer $customer
     * @param array $preferences - From analyzeCustomerPreferences()
     * @return string - Personalized greeting message
     */
    public function generateSmartGreeting(Customer $customer, array $preferences): string
    {
        $firstName = explode(' ', $customer->name)[0] ?? $customer->name;

        // High confidence service prediction (≥80%)
        if ($preferences['service_confidence'] >= 0.8 && $preferences['predicted_service']) {
            return sprintf(
                "Willkommen zurück, %s! Ich sehe Sie hatten zuletzt meist %s. Möchten Sie wieder %s buchen?",
                $firstName,
                $preferences['predicted_service'],
                $preferences['predicted_service']
            );
        }

        // Medium confidence (50-79%) or preferred staff
        if ($preferences['service_confidence'] >= 0.5 && $preferences['predicted_service']) {
            return sprintf(
                "Willkommen zurück, %s! Möchten Sie wieder einen %s?",
                $firstName,
                $preferences['predicted_service']
            );
        }

        // Low confidence or new customer with few appointments
        if ($preferences['appointment_history']['total_appointments'] > 0) {
            return sprintf(
                "Willkommen zurück, %s! Wie kann ich Ihnen heute helfen?",
                $firstName
            );
        }

        // Fallback (should not happen in this method)
        return sprintf("Willkommen zurück, %s!", $firstName);
    }
}
