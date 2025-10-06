<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationTemplate;
use App\Models\NotificationLog;
use App\Notifications\AppointmentReminder;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentCancelled;
use App\Notifications\NoShowFollowUp;
use App\Notifications\ReviewRequest;
use App\Jobs\SendBulkNotifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationWorkflowService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send appointment reminders based on configured timing
     */
    public function sendAppointmentReminders(): int
    {
        $remindersSent = 0;

        // 24-hour reminders
        $appointments24h = $this->getUpcomingAppointments(24);
        foreach ($appointments24h as $appointment) {
            if (!$this->hasRecentReminder($appointment, '24h')) {
                $this->sendReminder($appointment, '24h');
                $remindersSent++;
            }
        }

        // 2-hour reminders
        $appointments2h = $this->getUpcomingAppointments(2);
        foreach ($appointments2h as $appointment) {
            if (!$this->hasRecentReminder($appointment, '2h')) {
                $this->sendReminder($appointment, '2h');
                $remindersSent++;
            }
        }

        // 15-minute reminders (SMS only)
        $appointments15m = $this->getUpcomingAppointments(0.25);
        foreach ($appointments15m as $appointment) {
            if (!$this->hasRecentReminder($appointment, '15m')) {
                $this->sendReminder($appointment, '15m', ['sms']);
                $remindersSent++;
            }
        }

        return $remindersSent;
    }

    /**
     * Process no-show appointments and send follow-ups
     */
    public function processNoShows(): int
    {
        $processed = 0;

        $noShows = Appointment::where('status', 'scheduled')
            ->where('appointment_date', '<', now()->subHours(2))
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('type', 'no_show_followup')
                    ->where('created_at', '>', now()->subDay());
            })
            ->get();

        foreach ($noShows as $appointment) {
            // Update status to no-show
            $appointment->update(['status' => 'no_show']);

            // Send follow-up notification
            $this->sendNoShowFollowUp($appointment);

            // Create automatic reschedule offer
            $this->createRescheduleOffer($appointment);

            $processed++;
        }

        return $processed;
    }

    /**
     * Send review requests after completed appointments
     */
    public function sendReviewRequests(): int
    {
        $sent = 0;

        $completedAppointments = Appointment::where('status', 'completed')
            ->whereBetween('completed_at', [
                now()->subDays(3),
                now()->subDays(2)
            ])
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('type', 'review_request');
            })
            ->get();

        foreach ($completedAppointments as $appointment) {
            $this->sendReviewRequest($appointment);
            $sent++;
        }

        return $sent;
    }

    /**
     * Send appointment confirmation
     */
    public function sendAppointmentConfirmation(Appointment $appointment): void
    {
        $customer = $appointment->customer;
        $template = $this->getTemplate('appointment_confirmation', $customer->preferred_language);

        $variables = $this->getAppointmentVariables($appointment);

        // Send via preferred channels
        $channels = $this->getCustomerChannels($customer);

        foreach ($channels as $channel) {
            $this->notificationService->send(
                $customer,
                $template,
                $variables,
                $channel
            );
        }

        $this->logNotification($appointment, 'appointment_confirmation', $channels);
    }

    /**
     * Send appointment cancellation notification
     */
    public function sendAppointmentCancellation(Appointment $appointment, string $reason = null): void
    {
        $customer = $appointment->customer;
        $template = $this->getTemplate('appointment_cancellation', $customer->preferred_language);

        $variables = array_merge(
            $this->getAppointmentVariables($appointment),
            ['cancellation_reason' => $reason ?? 'Keine Angabe']
        );

        $channels = $this->getCustomerChannels($customer);

        foreach ($channels as $channel) {
            $this->notificationService->send(
                $customer,
                $template,
                $variables,
                $channel
            );
        }

        $this->logNotification($appointment, 'appointment_cancellation', $channels);
    }

    /**
     * Send bulk marketing campaign
     */
    public function sendMarketingCampaign(array $customerIds, string $templateKey, array $variables = []): void
    {
        $customers = Customer::whereIn('id', $customerIds)
            ->where('marketing_consent', true)
            ->where('status', 'active')
            ->get();

        SendBulkNotifications::dispatch($customers, $templateKey, $variables);
    }

    /**
     * Create custom notification workflow
     */
    public function createWorkflow(string $name, array $steps): NotificationWorkflow
    {
        $workflow = NotificationWorkflow::create([
            'name' => $name,
            'steps' => $steps,
            'is_active' => true
        ]);

        foreach ($steps as $index => $step) {
            $workflow->steps()->create([
                'order' => $index + 1,
                'trigger_type' => $step['trigger'],
                'delay_minutes' => $step['delay'] ?? 0,
                'template_key' => $step['template'],
                'channels' => $step['channels'] ?? ['email'],
                'conditions' => $step['conditions'] ?? []
            ]);
        }

        return $workflow;
    }

    /**
     * Execute a notification workflow
     */
    public function executeWorkflow(NotificationWorkflow $workflow, $trigger, array $data = []): void
    {
        if (!$workflow->is_active) {
            return;
        }

        foreach ($workflow->steps as $step) {
            if ($this->shouldExecuteStep($step, $trigger, $data)) {
                $this->scheduleWorkflowStep($step, $data);
            }
        }
    }

    /**
     * Get upcoming appointments within hours
     */
    protected function getUpcomingAppointments(float $hours): \Illuminate\Database\Eloquent\Collection
    {
        $startTime = now()->addHours($hours - 0.25);
        $endTime = now()->addHours($hours + 0.25);

        return Appointment::where('status', 'scheduled')
            ->whereBetween('appointment_datetime', [$startTime, $endTime])
            ->with(['customer', 'service', 'staff'])
            ->get();
    }

    /**
     * Check if appointment has recent reminder
     */
    protected function hasRecentReminder(Appointment $appointment, string $type): bool
    {
        return NotificationLog::where('appointment_id', $appointment->id)
            ->where('type', "reminder_{$type}")
            ->where('created_at', '>', now()->subHours(12))
            ->exists();
    }

    /**
     * Send appointment reminder
     */
    protected function sendReminder(Appointment $appointment, string $timing, array $channels = null): void
    {
        $customer = $appointment->customer;
        $template = $this->getTemplate("appointment_reminder_{$timing}", $customer->preferred_language);

        $variables = $this->getAppointmentVariables($appointment);
        $channels = $channels ?? $this->getCustomerChannels($customer);

        foreach ($channels as $channel) {
            $this->notificationService->send(
                $customer,
                $template,
                $variables,
                $channel
            );
        }

        $this->logNotification($appointment, "reminder_{$timing}", $channels);
    }

    /**
     * Send no-show follow-up
     */
    protected function sendNoShowFollowUp(Appointment $appointment): void
    {
        $customer = $appointment->customer;
        $template = $this->getTemplate('no_show_followup', $customer->preferred_language);

        $variables = array_merge(
            $this->getAppointmentVariables($appointment),
            [
                'reschedule_link' => route('appointments.reschedule', $appointment->id),
                'contact_phone' => $appointment->company->phone
            ]
        );

        $channels = $this->getCustomerChannels($customer);

        foreach ($channels as $channel) {
            $this->notificationService->send(
                $customer,
                $template,
                $variables,
                $channel
            );
        }

        $this->logNotification($appointment, 'no_show_followup', $channels);
    }

    /**
     * Send review request
     */
    protected function sendReviewRequest(Appointment $appointment): void
    {
        $customer = $appointment->customer;
        $template = $this->getTemplate('review_request', $customer->preferred_language);

        $variables = array_merge(
            $this->getAppointmentVariables($appointment),
            [
                'review_link' => route('reviews.create', [
                    'appointment' => $appointment->id,
                    'token' => encrypt($appointment->id)
                ]),
                'staff_name' => $appointment->staff->name,
                'service_name' => $appointment->service->name
            ]
        );

        $channels = ['email']; // Reviews typically via email only

        $this->notificationService->send(
            $customer,
            $template,
            $variables,
            'email'
        );

        $this->logNotification($appointment, 'review_request', $channels);
    }

    /**
     * Create automatic reschedule offer
     */
    protected function createRescheduleOffer(Appointment $appointment): void
    {
        // Find next available slots
        $availableSlots = app(AppointmentService::class)->getAvailableSlots(
            $appointment->staff_id,
            now()->addDay(),
            $appointment->service->duration,
            5 // Get 5 options
        );

        if (empty($availableSlots)) {
            return;
        }

        $rescheduleOffer = RescheduleOffer::create([
            'appointment_id' => $appointment->id,
            'customer_id' => $appointment->customer_id,
            'available_slots' => $availableSlots,
            'expires_at' => now()->addDays(3),
            'token' => \Str::random(32)
        ]);

        // Send reschedule offer notification
        $customer = $appointment->customer;
        $template = $this->getTemplate('reschedule_offer', $customer->preferred_language);

        $variables = array_merge(
            $this->getAppointmentVariables($appointment),
            [
                'reschedule_link' => route('appointments.reschedule.offer', $rescheduleOffer->token),
                'available_slots' => $availableSlots,
                'expires_at' => $rescheduleOffer->expires_at->format('d.m.Y H:i')
            ]
        );

        $this->notificationService->send(
            $customer,
            $template,
            $variables,
            'email'
        );
    }

    /**
     * Get notification template
     */
    protected function getTemplate(string $key, string $language = 'de'): NotificationTemplate
    {
        return NotificationTemplate::where('key', $key)
            ->where('language', $language)
            ->firstOr(function () use ($key) {
                return NotificationTemplate::where('key', $key)
                    ->where('language', 'de')
                    ->firstOrFail();
            });
    }

    /**
     * Get appointment variables for templates
     */
    protected function getAppointmentVariables(Appointment $appointment): array
    {
        return [
            'customer_name' => $appointment->customer->full_name,
            'appointment_date' => $appointment->appointment_date->format('d.m.Y'),
            'appointment_time' => $appointment->appointment_time,
            'service_name' => $appointment->service->name,
            'staff_name' => $appointment->staff->name,
            'branch_name' => $appointment->branch->name,
            'branch_address' => $appointment->branch->full_address,
            'duration' => $appointment->duration,
            'price' => number_format($appointment->price, 2, ',', '.') . ' €',
            'company_name' => $appointment->company->name,
            'company_phone' => $appointment->company->phone
        ];
    }

    /**
     * Get customer notification channels
     */
    protected function getCustomerChannels(Customer $customer): array
    {
        $channels = [];

        if ($customer->email && in_array($customer->preferred_contact_method, ['email', 'both'])) {
            $channels[] = 'email';
        }

        if ($customer->phone && in_array($customer->preferred_contact_method, ['sms', 'both'])) {
            $channels[] = 'sms';
        }

        if ($customer->whatsapp_number && $customer->whatsapp_consent) {
            $channels[] = 'whatsapp';
        }

        return $channels ?: ['email']; // Default to email
    }

    /**
     * Log notification
     */
    protected function logNotification(Appointment $appointment, string $type, array $channels): void
    {
        NotificationLog::create([
            'appointment_id' => $appointment->id,
            'customer_id' => $appointment->customer_id,
            'type' => $type,
            'channels' => $channels,
            'sent_at' => now()
        ]);
    }

    /**
     * Check if workflow step should be executed
     */
    protected function shouldExecuteStep($step, $trigger, array $data): bool
    {
        if ($step->trigger_type !== $trigger) {
            return false;
        }

        foreach ($step->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Schedule workflow step for execution
     */
    protected function scheduleWorkflowStep($step, array $data): void
    {
        $job = new \App\Jobs\ExecuteWorkflowStep($step, $data);

        if ($step->delay_minutes > 0) {
            $job->delay(now()->addMinutes($step->delay_minutes));
        }

        dispatch($job);
    }

    /**
     * Evaluate workflow condition
     */
    protected function evaluateCondition(array $condition, array $data): bool
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        $dataValue = data_get($data, $field);

        return match ($operator) {
            'equals' => $dataValue == $value,
            'not_equals' => $dataValue != $value,
            'greater_than' => $dataValue > $value,
            'less_than' => $dataValue < $value,
            'contains' => str_contains($dataValue, $value),
            'in' => in_array($dataValue, $value),
            default => false
        };
    }
}