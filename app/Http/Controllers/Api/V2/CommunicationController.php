<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Communication\NotificationService;
use App\Services\Communication\IcsGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CommunicationController extends Controller
{
    private NotificationService $notificationService;
    private IcsGeneratorService $icsGenerator;

    public function __construct(
        NotificationService $notificationService,
        IcsGeneratorService $icsGenerator
    ) {
        $this->notificationService = $notificationService;
        $this->icsGenerator = $icsGenerator;
    }

    /**
     * Send confirmation for appointment
     */
    public function sendConfirmation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'resend' => 'nullable|boolean'
        ]);

        try {
            $appointment = Appointment::with(['customer', 'service', 'branch', 'staff'])
                ->findOrFail($validated['appointment_id']);

            // Check if already sent (unless resend is requested)
            if (!($validated['resend'] ?? false)) {
                if ($appointment->metadata['confirmation_sent'] ?? false) {
                    return response()->json([
                        'message' => 'Confirmation already sent',
                        'data' => [
                            'sent_at' => $appointment->metadata['confirmation_sent_at'] ?? null
                        ]
                    ]);
                }
            }

            // Send appropriate confirmation
            if ($appointment->isComposite()) {
                $success = $this->notificationService->sendCompositeConfirmation($appointment);
            } else {
                $success = $this->notificationService->sendSimpleConfirmation($appointment);
            }

            if ($success) {
                // Update metadata
                $appointment->update([
                    'metadata' => array_merge($appointment->metadata ?? [], [
                        'confirmation_sent' => true,
                        'confirmation_sent_at' => now()->toIso8601String(),
                        'confirmation_resent' => $validated['resend'] ?? false
                    ])
                ]);

                return response()->json([
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'sent_to' => $appointment->customer->email,
                        'is_composite' => $appointment->is_composite
                    ],
                    'message' => 'Confirmation sent successfully'
                ]);
            }

            throw new \Exception('Failed to send confirmation');

        } catch (\Exception $e) {
            Log::error('Failed to send confirmation', [
                'appointment_id' => $validated['appointment_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to send confirmation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send reminder for appointment
     */
    public function sendReminder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'hours_before' => 'nullable|integer|in:1,2,4,8,12,24,48'
        ]);

        try {
            $appointment = Appointment::with(['customer', 'service', 'branch'])
                ->findOrFail($validated['appointment_id']);

            $hoursBeforeStart = $validated['hours_before'] ?? 24;

            $success = $this->notificationService->sendReminder($appointment, $hoursBeforeStart);

            if ($success) {
                return response()->json([
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'sent_to' => $appointment->customer->email,
                        'reminder_type' => "{$hoursBeforeStart}h"
                    ],
                    'message' => 'Reminder sent successfully'
                ]);
            }

            return response()->json([
                'message' => 'Reminder already sent or not needed'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send reminder', [
                'appointment_id' => $validated['appointment_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to send reminder',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send cancellation notification
     */
    public function sendCancellation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id'
        ]);

        try {
            $appointment = Appointment::with(['customer', 'service', 'branch'])
                ->findOrFail($validated['appointment_id']);

            // Check if appointment is actually cancelled
            if ($appointment->status !== 'cancelled') {
                return response()->json([
                    'error' => 'Appointment is not cancelled'
                ], 400);
            }

            $success = $this->notificationService->sendCancellationNotification($appointment);

            if ($success) {
                // Update metadata
                $appointment->update([
                    'metadata' => array_merge($appointment->metadata ?? [], [
                        'cancellation_sent' => true,
                        'cancellation_sent_at' => now()->toIso8601String()
                    ])
                ]);

                return response()->json([
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'sent_to' => $appointment->customer->email
                    ],
                    'message' => 'Cancellation notification sent successfully'
                ]);
            }

            throw new \Exception('Failed to send cancellation notification');

        } catch (\Exception $e) {
            Log::error('Failed to send cancellation', [
                'appointment_id' => $validated['appointment_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to send cancellation notification',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate ICS file for appointment
     */
    public function generateIcs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id'
        ]);

        try {
            $appointment = Appointment::with(['customer', 'service', 'branch', 'company'])
                ->findOrFail($validated['appointment_id']);

            if ($appointment->isComposite()) {
                $icsContent = $this->icsGenerator->generateCompositeIcs($appointment);
            } else {
                $icsContent = $this->icsGenerator->generateSimpleIcs($appointment);
            }

            // Return as base64 encoded for easy transmission
            return response()->json([
                'data' => [
                    'appointment_id' => $appointment->id,
                    'filename' => 'termin_' . $appointment->id . '.ics',
                    'content_base64' => base64_encode($icsContent),
                    'content_type' => 'text/calendar'
                ],
                'message' => 'ICS generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate ICS', [
                'appointment_id' => $validated['appointment_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to generate ICS',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send bulk reminders
     */
    public function sendBulkReminders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hours_before' => 'nullable|integer|in:1,2,4,8,12,24,48'
        ]);

        try {
            $hoursBeforeStart = $validated['hours_before'] ?? 24;
            $sent = $this->notificationService->sendBulkReminders($hoursBeforeStart);

            return response()->json([
                'data' => [
                    'reminders_sent' => $sent,
                    'hours_before' => $hoursBeforeStart
                ],
                'message' => "Sent {$sent} reminders successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send bulk reminders', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to send bulk reminders',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}