<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Booking\EnhancedBookingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EnhancedBookingController extends Controller
{
    private EnhancedBookingService $bookingService;

    public function __construct(EnhancedBookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Create a new appointment
     */
    public function createAppointment(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'service_id' => 'required|exists:services,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'nullable|date|after:start_time',
            'customer.name' => 'required|string|max:255',
            'customer.phone' => 'required|string|max:50',
            'customer.email' => 'nullable|email|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string|max:1000',
            'source' => 'nullable|string|in:phone,web,app,walk-in',
        ]);

        $result = $this->bookingService->createAppointment($validatedData);

        return response()->json(
            $result->toArray(),
            $result->getStatusCode()
        );
    }

    /**
     * Create appointment from phone call data
     */
    public function createFromPhoneCall(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'datum' => 'required|string',
            'uhrzeit' => 'required|string',
            'name' => 'required|string|max:255',
            'telefonnummer' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'dienstleistung' => 'required|string',
            'mitarbeiter_wunsch' => 'nullable|string',
            'kundenpraeferenzen' => 'nullable|string',
            'call_id' => 'nullable|exists:calls,id',
        ]);

        $result = $this->bookingService->bookFromPhoneCall($validatedData);

        return response()->json(
            $result->toArray(),
            $result->getStatusCode()
        );
    }
}