<?php

namespace App\Services;

use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\Company;
use App\Notifications\CallbackNotification;
use App\Notifications\CallbackSummaryNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CallbackService
{
    /**
     * Create callback request from failed booking
     */
    public function createFromFailedBooking(
        Call $call,
        string $reason,
        array $bookingData,
        ?string $errorDetails = null
    ): CallbackRequest {
        $company = $call->company;
        
        // Generate call summary
        $callSummary = $this->generateCallSummary($call);
        
        // Determine priority
        $priority = $this->determinePriority($call, $bookingData);
        
        // Extract requested appointment details
        $requestedDate = null;
        $requestedTime = null;
        
        if (isset($bookingData['date'])) {
            try {
                $requestedDate = Carbon::parse($bookingData['date'])->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('Could not parse requested date', ['date' => $bookingData['date']]);
            }
        }
        
        if (isset($bookingData['time'])) {
            try {
                $requestedTime = Carbon::parse($bookingData['time'])->format('H:i:s');
            } catch (\Exception $e) {
                Log::warning('Could not parse requested time', ['time' => $bookingData['time']]);
            }
        }
        
        // Create callback request
        $callback = CallbackRequest::create([
            'company_id' => $company->id,
            'branch_id' => $call->branch_id,
            'call_id' => $call->id,
            'customer_phone' => $call->from_number,
            'customer_name' => $bookingData['name'] ?? $call->extracted_name ?? null,
            'requested_service' => $bookingData['service'] ?? $bookingData['notes'] ?? null,
            'requested_date' => $requestedDate,
            'requested_time' => $requestedTime,
            'reason' => $reason,
            'error_details' => [
                'technical_error' => $errorDetails,
                'booking_data' => $bookingData,
                'timestamp' => now()->toIso8601String()
            ],
            'call_summary' => $callSummary,
            'priority' => $priority,
            'auto_close_after_hours' => $company->settings['callback_handling']['auto_close_hours'] ?? 24
        ]);
        
        // Send notifications
        $this->sendNotifications($callback);
        
        // Log callback creation
        Log::info('Callback request created', [
            'callback_id' => $callback->id,
            'call_id' => $call->id,
            'reason' => $reason,
            'priority' => $priority
        ]);
        
        return $callback;
    }
    
    /**
     * Generate call summary from transcript
     */
    protected function generateCallSummary(Call $call): string
    {
        $summary = '';
        
        // Use AI summary if available
        if ($call->summary) {
            $summary = $call->summary;
        } elseif ($call->transcript) {
            // Extract key information from transcript
            $summary = $this->extractKeyInfoFromTranscript($call->transcript);
        }
        
        // Add extracted data
        $extractedInfo = [];
        if ($call->extracted_name) {
            $extractedInfo[] = "Kunde: " . $call->extracted_name;
        }
        if ($call->extracted_date && $call->extracted_time) {
            $extractedInfo[] = "Gewünschter Termin: " . $call->extracted_date . " um " . $call->extracted_time;
        }
        
        if (!empty($extractedInfo)) {
            $summary .= "\n\nExtrahierte Informationen:\n" . implode("\n", $extractedInfo);
        }
        
        return $summary ?: 'Keine Zusammenfassung verfügbar';
    }
    
    /**
     * Extract key information from transcript
     */
    protected function extractKeyInfoFromTranscript(string $transcript): string
    {
        // Take first 500 characters as summary
        $summary = substr($transcript, 0, 500);
        
        // Look for key phrases
        $keyPhrases = [
            'termin' => 'Terminwunsch erwähnt',
            'beratung' => 'Beratung gewünscht',
            'problem' => 'Problem geschildert',
            'dringend' => 'Als dringend markiert',
            'rückruf' => 'Rückruf erbeten'
        ];
        
        $foundPhrases = [];
        foreach ($keyPhrases as $phrase => $description) {
            if (stripos($transcript, $phrase) !== false) {
                $foundPhrases[] = $description;
            }
        }
        
        if (!empty($foundPhrases)) {
            $summary .= "\n\nHinweise: " . implode(', ', $foundPhrases);
        }
        
        return $summary;
    }
    
    /**
     * Determine priority based on call content and settings
     */
    protected function determinePriority(Call $call, array $bookingData): string
    {
        $transcript = strtolower($call->transcript ?? '');
        $urgentKeywords = $call->company->settings['callback_handling']['priority_rules']['urgent_keywords'] ?? 
            ['dringend', 'notfall', 'heute', 'sofort', 'eilig'];
        
        // Check for urgent keywords in transcript
        foreach ($urgentKeywords as $keyword) {
            if (str_contains($transcript, strtolower($keyword))) {
                return 'urgent';
            }
        }
        
        // Check if customer is VIP
        $vipPhones = $call->company->settings['callback_handling']['priority_rules']['vip_customers'] ?? [];
        if (in_array($call->from_number, $vipPhones)) {
            return 'high';
        }
        
        // Check if requested date is today or tomorrow
        if (isset($bookingData['date'])) {
            try {
                $requestedDate = Carbon::parse($bookingData['date']);
                if ($requestedDate->isToday() || $requestedDate->isTomorrow()) {
                    return 'high';
                }
            } catch (\Exception $e) {
                // Ignore parsing errors
            }
        }
        
        return 'normal';
    }
    
    /**
     * Send notifications for new callback
     */
    protected function sendNotifications(CallbackRequest $callback): void
    {
        $settings = $callback->company->settings['error_handling']['notifications'] ?? [];
        
        // Email notifications
        if ($settings['email']['enabled'] ?? false) {
            $addresses = $settings['email']['addresses'] ?? [];
            
            foreach ($addresses as $email) {
                try {
                    Mail::to($email)->send(new CallbackNotification($callback));
                } catch (\Exception $e) {
                    Log::error('Failed to send callback notification email', [
                        'email' => $email,
                        'callback_id' => $callback->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // SMS notifications (only for urgent)
        if (($settings['sms']['enabled'] ?? false) && $callback->priority === 'urgent') {
            $this->sendSmsNotifications($callback, $settings['sms']);
        }
        
        // WhatsApp notifications (future implementation)
        if ($settings['whatsapp']['enabled'] ?? false) {
            // TODO: Implement WhatsApp notifications
        }
    }
    
    /**
     * Send SMS notifications
     */
    protected function sendSmsNotifications(CallbackRequest $callback, array $smsSettings): void
    {
        $numbers = $smsSettings['numbers'] ?? [];
        
        if (empty($numbers)) {
            return;
        }
        
        // Check if SMS service is available
        if (!app()->bound('App\Services\SmsService')) {
            Log::warning('SMS service not available for callback notifications');
            return;
        }
        
        $smsService = app('App\Services\SmsService');
        $message = sprintf(
            "Dringender Rückruf: %s - %s. Bitte im System prüfen.",
            $callback->customer_name ?: 'Unbekannt',
            $callback->requested_service ?: 'Terminanfrage'
        );
        
        foreach ($numbers as $number) {
            try {
                $smsService->send($number, $message);
            } catch (\Exception $e) {
                Log::error('Failed to send SMS notification', [
                    'number' => $number,
                    'callback_id' => $callback->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Send daily summary email
     */
    public function sendDailySummary(Company $company, string $email): void
    {
        $callbacks = CallbackRequest::where('company_id', $company->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('priority')
            ->orderBy('created_at')
            ->get();
        
        // Check if should send only when pending
        if ($callbacks->isEmpty() && 
            ($company->settings['callback_handling']['only_if_pending'] ?? true)) {
            return;
        }
        
        // Prepare statistics
        $stats = [
            'total' => $callbacks->count(),
            'urgent' => $callbacks->where('priority', 'urgent')->count(),
            'high' => $callbacks->where('priority', 'high')->count(),
            'overdue' => $callbacks->filter(fn($cb) => $cb->shouldAutoClose())->count(),
            'today' => $callbacks->filter(fn($cb) => $cb->created_at->isToday())->count()
        ];
        
        try {
            Mail::to($email)->send(new CallbackSummaryNotification($company, $callbacks, $stats));
            
            Log::info('Callback summary sent', [
                'company_id' => $company->id,
                'email' => $email,
                'callback_count' => $callbacks->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send callback summary', [
                'company_id' => $company->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Auto-close old callbacks
     */
    public function autoCloseOldCallbacks(): int
    {
        $count = 0;
        
        CallbackRequest::pending()
            ->chunk(100, function ($callbacks) use (&$count) {
                foreach ($callbacks as $callback) {
                    if ($callback->shouldAutoClose()) {
                        $callback->autoClose();
                        $count++;
                    }
                }
            });
        
        if ($count > 0) {
            Log::info('Auto-closed callbacks', ['count' => $count]);
        }
        
        return $count;
    }
    
    /**
     * Get callback statistics for company
     */
    public function getStatistics(Company $company, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?: now()->startOfMonth();
        $to = $to ?: now()->endOfMonth();
        
        $query = CallbackRequest::where('company_id', $company->id)
            ->whereBetween('created_at', [$from, $to]);
        
        return [
            'total' => $query->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'auto_closed' => (clone $query)->where('status', 'auto_closed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime($company, $from, $to),
            'by_priority' => [
                'urgent' => (clone $query)->where('priority', 'urgent')->count(),
                'high' => (clone $query)->where('priority', 'high')->count(),
                'normal' => (clone $query)->where('priority', 'normal')->count(),
                'low' => (clone $query)->where('priority', 'low')->count()
            ],
            'by_reason' => [
                'calcom_error' => (clone $query)->where('reason', 'calcom_error')->count(),
                'no_availability' => (clone $query)->where('reason', 'no_availability')->count(),
                'technical_error' => (clone $query)->where('reason', 'technical_error')->count(),
                'customer_request' => (clone $query)->where('reason', 'customer_request')->count()
            ]
        ];
    }
    
    /**
     * Calculate average resolution time
     */
    protected function calculateAverageResolutionTime(Company $company, Carbon $from, Carbon $to): ?float
    {
        $completedCallbacks = CallbackRequest::where('company_id', $company->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('processed_at')
            ->get();
        
        if ($completedCallbacks->isEmpty()) {
            return null;
        }
        
        $totalMinutes = 0;
        foreach ($completedCallbacks as $callback) {
            $totalMinutes += $callback->created_at->diffInMinutes($callback->processed_at);
        }
        
        return round($totalMinutes / $completedCallbacks->count(), 1);
    }
}