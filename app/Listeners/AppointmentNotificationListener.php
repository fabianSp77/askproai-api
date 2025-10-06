<?php

namespace App\Listeners;

use App\Events\AppointmentCreated;
use App\Events\AppointmentUpdated;
use App\Events\AppointmentDeleted;
use App\Events\Appointments\AppointmentCancellationRequested;
use App\Events\Appointments\AppointmentRescheduled;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationConfiguration;
use Illuminate\Support\Facades\Log;

/**
 * AppointmentNotificationListener
 *
 * Listens to appointment events and dispatches notifications based on configurations.
 * Implements hierarchical configuration resolution: Staff → Service → Branch → Company
 */
class AppointmentNotificationListener
{
    /**
     * Handle appointment created event.
     */
    public function handleCreated(AppointmentCreated $event): void
    {
        $this->dispatchNotifications($event->appointment, 'appointment.created');
    }

    /**
     * Handle appointment updated event.
     */
    public function handleUpdated(AppointmentUpdated $event): void
    {
        $this->dispatchNotifications($event->appointment, 'appointment.updated');
    }

    /**
     * Handle appointment cancelled event.
     */
    public function handleCancelled(AppointmentCancellationRequested $event): void
    {
        $this->dispatchNotifications($event->appointment, 'appointment.cancelled');
    }

    /**
     * Handle appointment rescheduled event.
     */
    public function handleRescheduled(AppointmentRescheduled $event): void
    {
        $this->dispatchNotifications($event->appointment, 'appointment.rescheduled');
    }

    /**
     * Handle appointment deleted event.
     */
    public function handleDeleted(AppointmentDeleted $event): void
    {
        $this->dispatchNotifications($event->appointment, 'appointment.deleted');
    }

    /**
     * Dispatch notifications based on configurations.
     *
     * Resolves configuration hierarchy and dispatches SendNotificationJob for each enabled configuration.
     *
     * @param \App\Models\Appointment $appointment
     * @param string $eventType
     */
    protected function dispatchNotifications($appointment, string $eventType): void
    {
        // Load necessary relationships
        $appointment->load(['company', 'branch', 'service', 'staff', 'customer']);

        // Find all matching configurations in hierarchical order
        $configurations = $this->resolveConfigurations($appointment, $eventType);

        if ($configurations->isEmpty()) {
            Log::info('No notification configurations found', [
                'appointment_id' => $appointment->id,
                'event_type' => $eventType,
            ]);
            return;
        }

        // Dispatch notification job for each configuration
        foreach ($configurations as $config) {
            if ($config->is_enabled) {
                SendNotificationJob::dispatch($config, $appointment, $eventType)
                    ->onQueue('notifications');

                Log::info('Notification job dispatched', [
                    'config_id' => $config->id,
                    'appointment_id' => $appointment->id,
                    'event_type' => $eventType,
                    'channel' => $config->channel,
                ]);
            }
        }
    }

    /**
     * Resolve notification configurations in hierarchical order.
     *
     * Priority (highest to lowest):
     * 1. Staff-level configuration
     * 2. Service-level configuration
     * 3. Branch-level configuration
     * 4. Company-level configuration
     *
     * @param \App\Models\Appointment $appointment
     * @param string $eventType
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function resolveConfigurations($appointment, string $eventType)
    {
        $configurations = collect();

        // 1. Check Staff-level configuration
        if ($appointment->staff_id) {
            $staffConfig = NotificationConfiguration::where('configurable_type', 'App\\Models\\Staff')
                ->where('configurable_id', $appointment->staff_id)
                ->where('event_type', $eventType)
                ->where('is_enabled', true)
                ->first();

            if ($staffConfig) {
                $configurations->push($staffConfig);
                Log::debug('Found staff-level notification config', [
                    'config_id' => $staffConfig->id,
                    'staff_id' => $appointment->staff_id,
                ]);
            }
        }

        // 2. Check Service-level configuration
        if ($appointment->service_id) {
            $serviceConfig = NotificationConfiguration::where('configurable_type', 'App\\Models\\Service')
                ->where('configurable_id', $appointment->service_id)
                ->where('event_type', $eventType)
                ->where('is_enabled', true)
                ->first();

            if ($serviceConfig) {
                $configurations->push($serviceConfig);
                Log::debug('Found service-level notification config', [
                    'config_id' => $serviceConfig->id,
                    'service_id' => $appointment->service_id,
                ]);
            }
        }

        // 3. Check Branch-level configuration
        if ($appointment->branch_id) {
            $branchConfig = NotificationConfiguration::where('configurable_type', 'App\\Models\\Branch')
                ->where('configurable_id', $appointment->branch_id)
                ->where('event_type', $eventType)
                ->where('is_enabled', true)
                ->first();

            if ($branchConfig) {
                $configurations->push($branchConfig);
                Log::debug('Found branch-level notification config', [
                    'config_id' => $branchConfig->id,
                    'branch_id' => $appointment->branch_id,
                ]);
            }
        }

        // 4. Check Company-level configuration (fallback)
        $companyConfig = NotificationConfiguration::where('configurable_type', 'App\\Models\\Company')
            ->where('configurable_id', $appointment->company_id)
            ->where('event_type', $eventType)
            ->where('is_enabled', true)
            ->first();

        if ($companyConfig) {
            $configurations->push($companyConfig);
            Log::debug('Found company-level notification config', [
                'config_id' => $companyConfig->id,
                'company_id' => $appointment->company_id,
            ]);
        }

        return $configurations;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe($events): array
    {
        return [
            AppointmentCreated::class => 'handleCreated',
            AppointmentUpdated::class => 'handleUpdated',
            AppointmentCancellationRequested::class => 'handleCancelled',
            AppointmentRescheduled::class => 'handleRescheduled',
            AppointmentDeleted::class => 'handleDeleted',
        ];
    }
}
