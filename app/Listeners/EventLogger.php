<?php

namespace App\Listeners;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventLogger
{
    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            // Appointment events
            \App\Events\AppointmentCreated::class => 'logAppointmentCreated',
            \App\Events\AppointmentUpdated::class => 'logAppointmentUpdated',
            \App\Events\AppointmentCancelled::class => 'logAppointmentCancelled',
            \App\Events\AppointmentRescheduled::class => 'logAppointmentRescheduled',
            
            // Call events
            \App\Events\CallCreated::class => 'logCallCreated',
            \App\Events\CallUpdated::class => 'logCallUpdated',
            \App\Events\CallCompleted::class => 'logCallCompleted',
            \App\Events\CallFailed::class => 'logCallFailed',
            
            // Customer events
            \App\Events\CustomerCreated::class => 'logCustomerCreated',
            \App\Events\CustomerMerged::class => 'logCustomerMerged',
            
            // Other business events
            \App\Events\MetricsUpdated::class => 'logMetricsUpdated',
            \App\Events\MCPAlertTriggered::class => 'logMCPAlert',
        ];
    }

    public function logAppointmentCreated(\App\Events\AppointmentCreated $event): void
    {
        $appointment = \App\Models\Appointment::find($event->appointmentId);
        if (!$appointment) return;

        $this->logEvent('appointment.created', [
            'entity_type' => 'appointment',
            'entity_id' => $appointment->id,
            'appointment_id' => $appointment->id,
            'customer_id' => $appointment->customer_id,
            'staff_id' => $appointment->staff_id,
            'service_id' => $appointment->service_id,
            'branch_id' => $appointment->branch_id,
            'starts_at' => $appointment->starts_at,
            'ends_at' => $appointment->ends_at,
            'status' => $appointment->status
        ], $appointment->company_id);
    }

    public function logAppointmentUpdated(\App\Events\AppointmentUpdated $event): void
    {
        $appointment = \App\Models\Appointment::find($event->appointmentId);
        if (!$appointment) return;

        $this->logEvent('appointment.updated', [
            'entity_type' => 'appointment',
            'entity_id' => $appointment->id,
            'appointment_id' => $appointment->id,
            'changes' => $event->changes ?? [],
            'status' => $appointment->status
        ], $appointment->company_id);
    }

    public function logAppointmentCancelled(\App\Events\AppointmentCancelled $event): void
    {
        $appointment = \App\Models\Appointment::find($event->appointmentId);
        if (!$appointment) return;

        $this->logEvent('appointment.cancelled', [
            'entity_type' => 'appointment',
            'entity_id' => $appointment->id,
            'appointment_id' => $appointment->id,
            'cancelled_by' => $event->cancelledBy ?? auth()->id(),
            'reason' => $event->reason ?? null
        ], $appointment->company_id);
    }

    public function logAppointmentRescheduled(\App\Events\AppointmentRescheduled $event): void
    {
        $appointment = \App\Models\Appointment::find($event->appointmentId);
        if (!$appointment) return;

        $this->logEvent('appointment.rescheduled', [
            'entity_type' => 'appointment',
            'entity_id' => $appointment->id,
            'appointment_id' => $appointment->id,
            'old_starts_at' => $event->oldStartsAt,
            'new_starts_at' => $appointment->starts_at,
            'rescheduled_by' => auth()->id()
        ], $appointment->company_id);
    }

    public function logCallCreated(\App\Events\CallCreated $event): void
    {
        $call = \App\Models\Call::find($event->callId);
        if (!$call) return;

        $this->logEvent('call.created', [
            'entity_type' => 'call',
            'entity_id' => $call->id,
            'call_id' => $call->id,
            'from_number' => $call->from_number,
            'to_number' => $call->to_number,
            'type' => $call->type,
            'status' => $call->status
        ], $call->company_id);
    }

    public function logCallUpdated(\App\Events\CallUpdated $event): void
    {
        $call = \App\Models\Call::find($event->callId);
        if (!$call) return;

        $this->logEvent('call.updated', [
            'entity_type' => 'call',
            'entity_id' => $call->id,
            'call_id' => $call->id,
            'status' => $call->status,
            'changes' => $event->changes ?? []
        ], $call->company_id);
    }

    public function logCallCompleted(\App\Events\CallCompleted $event): void
    {
        $call = \App\Models\Call::find($event->callId);
        if (!$call) return;

        $this->logEvent('call.completed', [
            'entity_type' => 'call',
            'entity_id' => $call->id,
            'call_id' => $call->id,
            'duration' => $call->duration,
            'recording_url' => $call->recording_url,
            'appointment_created' => $call->appointment_id ? true : false
        ], $call->company_id);
    }

    public function logCallFailed(\App\Events\CallFailed $event): void
    {
        $call = \App\Models\Call::find($event->callId);
        if (!$call) return;

        $this->logEvent('call.failed', [
            'entity_type' => 'call',
            'entity_id' => $call->id,
            'call_id' => $call->id,
            'reason' => $event->reason ?? 'unknown',
            'error' => $event->error ?? null
        ], $call->company_id);
    }

    public function logCustomerCreated(\App\Events\CustomerCreated $event): void
    {
        $customer = \App\Models\Customer::find($event->customerId);
        if (!$customer) return;

        $this->logEvent('customer.created', [
            'entity_type' => 'customer',
            'entity_id' => $customer->id,
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'source' => $customer->source ?? 'manual'
        ], $customer->company_id);
    }

    public function logCustomerMerged(\App\Events\CustomerMerged $event): void
    {
        $targetCustomer = \App\Models\Customer::find($event->targetCustomerId);
        if (!$targetCustomer) return;

        $this->logEvent('customer.merged', [
            'entity_type' => 'customer',
            'entity_id' => $targetCustomer->id,
            'from_id' => $event->sourceCustomerId,
            'to_id' => $event->targetCustomerId,
            'merged_by' => auth()->id()
        ], $targetCustomer->company_id);
    }

    public function logMetricsUpdated(\App\Events\MetricsUpdated $event): void
    {
        $this->logEvent('metrics.updated', [
            'metric_type' => $event->metricType,
            'values' => $event->values,
            'period' => $event->period
        ], $event->companyId);
    }

    public function logMCPAlert(\App\Events\MCPAlertTriggered $event): void
    {
        $this->logEvent('mcp.alert', [
            'alert_type' => $event->alertType,
            'severity' => $event->severity,
            'message' => $event->message,
            'context' => $event->context
        ], $event->companyId ?? null);
    }

    /**
     * Log an event to the database
     */
    private function logEvent(string $eventName, array $payload, ?int $companyId = null): void
    {
        try {
            DB::table('event_logs')->insert([
                'event_name' => $eventName,
                'payload' => json_encode($payload),
                'user_id' => auth()->id(),
                'company_id' => $companyId ?? auth()->user()?->company_id,
                'metadata' => json_encode([
                    'source' => 'system',
                    'environment' => app()->environment(),
                    'session_id' => session()->getId()
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now()
            ]);

            // Trigger webhooks asynchronously
            if ($companyId) {
                dispatch(new \App\Jobs\TriggerEventWebhooks($eventName, $payload, $companyId));
            }
        } catch (\Exception $e) {
            Log::error('Failed to log event', [
                'event_name' => $eventName,
                'error' => $e->getMessage()
            ]);
        }
    }
}