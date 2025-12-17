<?php

namespace App\Observers;

use App\Models\CallbackRequest;
use App\Services\Notifications\CallbackNotificationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class CallbackRequestObserver
{
    protected CallbackNotificationService $notificationService;

    public function __construct(CallbackNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the CallbackRequest "creating" event.
     */
    public function creating(CallbackRequest $callbackRequest): void
    {
        $this->sanitizeUserInput($callbackRequest);
        $this->validatePhoneNumber($callbackRequest);
    }

    /**
     * 🔧 FIX 2025-11-18: Send multi-channel notifications when callback request is created
     *
     * Root Cause: Team was not being notified about callback requests
     * Solution: Trigger notifications through all configured channels (Filament, Email, Slack)
     */
    public function created(CallbackRequest $callbackRequest): void
    {
        try {
            Log::info('📧 CallbackRequest created, triggering team notifications', [
                'callback_id' => $callbackRequest->id,
                'company_id' => $callbackRequest->company_id,
                'customer' => $callbackRequest->customer_name,
                'action' => $callbackRequest->metadata['action_requested'] ?? 'unknown'
            ]);

            // Send notifications through all configured channels
            $results = $this->notificationService->notifyTeam($callbackRequest);

            Log::info('✅ Team notifications completed', [
                'callback_id' => $callbackRequest->id,
                'channels_sent' => count(array_filter($results)),
                'results' => $results
            ]);
        } catch (\Exception $e) {
            // Never fail the callback creation due to notification errors
            Log::error('❌ Failed to send team notifications', [
                'callback_id' => $callbackRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the CallbackRequest "updating" event.
     */
    public function updating(CallbackRequest $callbackRequest): void
    {
        if ($callbackRequest->isDirty(['customer_name', 'notes', 'phone_number'])) {
            $this->sanitizeUserInput($callbackRequest);

            if ($callbackRequest->isDirty('phone_number')) {
                $this->validatePhoneNumber($callbackRequest);
            }
        }
    }

    /**
     * Sanitize user input to prevent XSS attacks.
     */
    protected function sanitizeUserInput(CallbackRequest $callbackRequest): void
    {
        // Sanitize customer_name
        if ($callbackRequest->customer_name) {
            $callbackRequest->customer_name = strip_tags($callbackRequest->customer_name);
            $callbackRequest->customer_name = htmlspecialchars(
                $callbackRequest->customer_name,
                ENT_QUOTES,
                'UTF-8'
            );
        }

        // Sanitize notes
        if ($callbackRequest->notes) {
            $callbackRequest->notes = strip_tags($callbackRequest->notes);
            $callbackRequest->notes = htmlspecialchars(
                $callbackRequest->notes,
                ENT_QUOTES,
                'UTF-8'
            );
        }
    }

    /**
     * Validate phone number format (E.164).
     * ✅ FIX 2025-11-18: Allow anonymous placeholders for callback requests from anonymous callers
     */
    protected function validatePhoneNumber(CallbackRequest $callbackRequest): void
    {
        $phoneNumber = $callbackRequest->phone_number;

        if (!$phoneNumber) {
            throw ValidationException::withMessages([
                'phone_number' => 'Phone number is required.',
            ]);
        }

        // ✅ FIX: Allow anonymous placeholder phone numbers (for reschedule/cancel callback requests)
        // Pattern: anonymous_[timestamp]_[hash] or anonymous_[timestamp]
        if (preg_match('/^anonymous_\d+(_[a-z0-9]+)?$/', $phoneNumber)) {
            return; // Valid anonymous placeholder - skip E.164 validation
        }

        // E.164 format: +[country code][number] (max 15 digits)
        // Example: +491234567890
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber)) {
            throw ValidationException::withMessages([
                'phone_number' => 'Phone number must be in E.164 format (e.g., +491234567890).',
            ]);
        }

        // Additional length validation
        $digitCount = strlen(preg_replace('/[^0-9]/', '', $phoneNumber));
        if ($digitCount < 7 || $digitCount > 15) {
            throw ValidationException::withMessages([
                'phone_number' => 'Phone number must contain between 7 and 15 digits.',
            ]);
        }
    }

    /**
     * Handle the CallbackRequest "saving" event.
     */
    public function saving(CallbackRequest $callbackRequest): void
    {
        // Auto-set expires_at based on priority if not already set
        if (!$callbackRequest->expires_at) {
            $hoursToAdd = match ($callbackRequest->priority) {
                'urgent' => 1,
                'high' => 4,
                'normal' => 24,
                default => 24,
            };

            $callbackRequest->expires_at = now()->addHours($hoursToAdd);
        }

        // Auto-set assigned_at when assigned_to is set
        if ($callbackRequest->isDirty('assigned_to') && $callbackRequest->assigned_to && !$callbackRequest->assigned_at) {
            $callbackRequest->assigned_at = now();
        }

        // Auto-set status to 'assigned' when assigned_to is set
        if ($callbackRequest->isDirty('assigned_to') && $callbackRequest->assigned_to && $callbackRequest->status === 'pending') {
            $callbackRequest->status = 'assigned';
        }
    }
}
