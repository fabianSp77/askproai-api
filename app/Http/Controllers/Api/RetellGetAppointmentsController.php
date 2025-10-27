<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Carbon\Carbon;

/**
 * Retell AI - Get Customer Appointments
 * Returns all upcoming appointments for a customer
 */
class RetellGetAppointmentsController extends Controller
{
    /**
     * Get all appointments for a customer
     * POST /api/retell/get-customer-appointments
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointments(Request $request)
    {
        try {
            $args = $request->input('args', []);
            $callId = $args['call_id'] ?? $request->input('call_id');
            $customerName = $args['customer_name'] ?? null;

            Log::info('📅 Getting customer appointments', [
                'call_id' => $callId,
                'customer_name' => $customerName
            ]);

            // Get customer from call
            $customer = null;
            $companyId = null;

            if ($callId) {
                $call = Call::with(['customer', 'company'])
                    ->where('retell_call_id', $callId)
                    ->first();

                if ($call) {
                    $customer = $call->customer;
                    $companyId = $call->company_id;

                    // If no customer linked, try to find by phone
                    if (!$customer && $call->from_number && $call->from_number !== 'anonymous') {
                        $normalizedPhone = preg_replace('/[^0-9+]/', '', $call->from_number);

                        $customer = Customer::where('company_id', $companyId)
                            ->where(function($q) use ($normalizedPhone) {
                                $q->where('phone', $normalizedPhone)
                                  ->orWhere('phone', 'LIKE', '%' . substr($normalizedPhone, -8) . '%');
                            })
                            ->first();
                    }

                    // For anonymous callers, try exact name match
                    if (!$customer && $customerName && $call->from_number === 'anonymous') {
                        $customer = Customer::where('company_id', $companyId)
                            ->where('name', $customerName)
                            ->first();
                    }
                }
            }

            if (!$customer) {
                Log::info('❌ No customer found', [
                    'call_id' => $callId,
                    'customer_name' => $customerName
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'customer_not_found',
                    'message' => 'Kunde nicht gefunden',
                    'appointments' => []
                ], 200);
            }

            // Get all upcoming appointments
            $appointments = Appointment::where('customer_id', $customer->id)
                ->where('company_id', $companyId)
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at', 'asc')
                ->get()
                ->map(function($appointment) {
                    return [
                        'id' => $appointment->id,
                        'date' => $appointment->starts_at->format('Y-m-d'),
                        'time' => $appointment->starts_at->format('H:i'),
                        'weekday' => $appointment->starts_at->locale('de')->dayName,
                        'service' => $appointment->service?->name ?? 'Nicht angegeben',
                        'staff' => $appointment->staff?->name ?? null,
                        'branch' => $appointment->branch?->name ?? null,
                        'status' => $appointment->status,
                        'duration_minutes' => $appointment->duration_minutes,
                        'human_date' => $appointment->starts_at->locale('de')->isoFormat('dddd, D. MMMM YYYY'),
                        'human_time' => $appointment->starts_at->format('H:i') . ' Uhr'
                    ];
                });

            Log::info('✅ Found appointments', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'count' => $appointments->count()
            ]);

            return response()->json([
                'success' => true,
                'status' => 'found',
                'customer_name' => $customer->name,
                'appointment_count' => $appointments->count(),
                'appointments' => $appointments,
                'message' => $appointments->isEmpty()
                    ? 'Sie haben aktuell keine bevorstehenden Termine.'
                    : 'Ich habe ' . $appointments->count() . ' Termin' . ($appointments->count() > 1 ? 'e' : '') . ' gefunden.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error getting appointments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Fehler beim Abrufen der Termine: ' . $e->getMessage(),
                'appointments' => []
            ], 200);
        }
    }
}
