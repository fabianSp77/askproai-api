<?php

namespace App\Services;

use App\Models\Call;
use App\Repositories\CallRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\AppointmentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\CallCompleted;
use App\Events\CallFailed;

class CallService
{
    protected CallRepository $callRepository;
    protected CustomerRepository $customerRepository;
    protected AppointmentRepository $appointmentRepository;
    protected RetellService $retellService;

    public function __construct(
        CallRepository $callRepository,
        CustomerRepository $customerRepository,
        AppointmentRepository $appointmentRepository,
        RetellService $retellService
    ) {
        $this->callRepository = $callRepository;
        $this->customerRepository = $customerRepository;
        $this->appointmentRepository = $appointmentRepository;
        $this->retellService = $retellService;
    }

    /**
     * Process incoming call webhook
     */
    public function processWebhook(array $webhookData): Call
    {
        return DB::transaction(function () use ($webhookData) {
            // Find or create call record
            $call = $this->callRepository->findOneBy([
                'retell_call_id' => $webhookData['call_id']
            ]);

            if (!$call) {
                $call = $this->createCall($webhookData);
            } else {
                $this->updateCall($call, $webhookData);
            }

            // Process based on event type
            switch ($webhookData['event'] ?? null) {
                case 'call_started':
                    $this->handleCallStarted($call, $webhookData);
                    break;
                    
                case 'call_ended':
                    $this->handleCallEnded($call, $webhookData);
                    break;
                    
                case 'call_analyzed':
                    $this->handleCallAnalyzed($call, $webhookData);
                    break;
            }

            return $call->fresh(['customer', 'appointment']);
        });
    }

    /**
     * Create new call record
     */
    protected function createCall(array $data): Call
    {
        // Find or create customer by phone
        $customer = null;
        if (!empty($data['from_number'])) {
            $customer = $this->customerRepository->findOrCreate([
                'phone' => $data['from_number'],
                'name' => $data['customer_name'] ?? 'Unknown',
                'company_id' => $data['company_id'] ?? auth()->user()?->company_id,
            ]);
        }

        return $this->callRepository->create([
            'retell_call_id' => $data['call_id'],
            'call_id' => $data['call_id'],
            'from_number' => $data['from_number'] ?? null,
            'to_number' => $data['to_number'] ?? null,
            'customer_id' => $customer?->id,
            'agent_id' => $data['agent_id'] ?? null,
            'status' => $data['status'] ?? 'initiated',
            'direction' => $data['direction'] ?? 'inbound',
            'company_id' => $data['company_id'] ?? auth()->user()?->company_id,
            'webhook_data' => $data,
        ]);
    }

    /**
     * Update existing call
     */
    protected function updateCall(Call $call, array $data): void
    {
        $updateData = [
            'status' => $data['status'] ?? $call->status,
            'duration_seconds' => $data['call_duration'] ?? $call->duration_seconds,
            'recording_url' => $data['recording_url'] ?? $call->recording_url,
            'transcript' => $data['transcript'] ?? $call->transcript,
            'analysis' => $data['call_analysis'] ?? $call->analysis,
            'cost_cents' => isset($data['price']) ? $data['price'] * 100 : $call->cost_cents,
            'webhook_data' => array_merge($call->webhook_data ?? [], $data),
        ];

        $this->callRepository->update($call->id, $updateData);
    }

    /**
     * Handle call started event
     */
    protected function handleCallStarted(Call $call, array $data): void
    {
        Log::info('Call started', [
            'call_id' => $call->id,
            'retell_call_id' => $call->retell_call_id,
        ]);

        // Update status
        $this->callRepository->update($call->id, [
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Handle call ended event
     */
    protected function handleCallEnded(Call $call, array $data): void
    {
        $updateData = [
            'status' => 'completed',
            'ended_at' => now(),
            'duration_seconds' => $data['call_duration'] ?? 0,
        ];

        // Check if appointment was created
        if (!empty($data['variables']['appointment_created']) && !$call->appointment_id) {
            $appointmentData = $data['variables']['appointment_data'] ?? [];
            if (!empty($appointmentData)) {
                $appointment = $this->createAppointmentFromCall($call, $appointmentData);
                $updateData['appointment_id'] = $appointment->id;
            }
        }

        $this->callRepository->update($call->id, $updateData);

        // Fire event
        event(new CallCompleted($call->fresh()));
    }

    /**
     * Handle call analyzed event
     */
    protected function handleCallAnalyzed(Call $call, array $data): void
    {
        $this->callRepository->update($call->id, [
            'transcript' => $data['transcript'] ?? null,
            'analysis' => $data['call_analysis'] ?? null,
            'sentiment' => $data['sentiment'] ?? null,
        ]);

        // Extract structured data if available
        if (!empty($data['structured_data'])) {
            $this->processStructuredData($call, $data['structured_data']);
        }
    }

    /**
     * Create appointment from call data
     */
    protected function createAppointmentFromCall(Call $call, array $appointmentData): \App\Models\Appointment
    {
        $appointmentService = app(AppointmentService::class);
        
        return $appointmentService->create([
            'customer_name' => $appointmentData['customer_name'] ?? $call->customer->name,
            'customer_phone' => $appointmentData['customer_phone'] ?? $call->from_number,
            'customer_email' => $appointmentData['customer_email'] ?? null,
            'staff_id' => $appointmentData['staff_id'],
            'service_id' => $appointmentData['service_id'] ?? null,
            'branch_id' => $appointmentData['branch_id'],
            'starts_at' => $appointmentData['start_time'],
            'ends_at' => $appointmentData['end_time'],
            'notes' => 'Booked via phone call #' . $call->retell_call_id,
            'source' => 'phone',
            'company_id' => $call->company_id,
        ]);
    }

    /**
     * Process structured data from call analysis
     */
    protected function processStructuredData(Call $call, array $structuredData): void
    {
        // Update customer information if available
        if ($call->customer && !empty($structuredData['customer'])) {
            $customerData = array_filter([
                'name' => $structuredData['customer']['name'] ?? null,
                'email' => $structuredData['customer']['email'] ?? null,
            ]);
            
            if (!empty($customerData)) {
                $this->customerRepository->update($call->customer->id, $customerData);
            }
        }

        // Store structured data
        $this->callRepository->update($call->id, [
            'structured_data' => $structuredData,
        ]);
    }

    /**
     * Get call statistics
     */
    public function getStatistics(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        return $this->callRepository->getStatistics($startDate, $endDate);
    }

    /**
     * Refresh call data from Retell
     */
    public function refreshCallData(int $callId): bool
    {
        $call = $this->callRepository->findOrFail($callId);
        
        if (!$call->retell_call_id) {
            return false;
        }

        try {
            $retellData = $this->retellService->getCall($call->retell_call_id);
            
            if ($retellData) {
                $this->updateCall($call, $retellData);
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to refresh call data', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Mark call as failed
     */
    public function markAsFailed(int $callId, string $reason): void
    {
        $call = $this->callRepository->findOrFail($callId);
        
        $this->callRepository->update($callId, [
            'status' => 'failed',
            'error_message' => $reason,
            'ended_at' => now(),
        ]);

        event(new CallFailed($call->fresh()));
    }
}