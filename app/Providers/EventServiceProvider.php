<?php

namespace App\Providers;

use App\Events\Appointments\AppointmentCancellationRequested;
use App\Events\Appointments\AppointmentPolicyViolation;
use App\Events\Appointments\AppointmentRescheduled;
use App\Events\Appointments\CallbackEscalated;
use App\Events\Appointments\CallbackRequested;
use App\Listeners\Appointments\AssignCallbackToStaff;
use App\Listeners\Appointments\SendCancellationNotifications;
use App\Listeners\Appointments\TriggerPolicyEnforcement;
use App\Listeners\Appointments\UpdateModificationStats;
use App\Listeners\AppointmentNotificationListener;
use App\Events\AppointmentCreated;
use App\Events\AppointmentUpdated;
use App\Events\AppointmentDeleted;
use App\Models\Call;
use App\Observers\CallObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event → Listener mappings for appointment modifications
     *
     * All listeners implement ShouldQueue for async processing
     */
    protected $listen = [
        // Appointment Lifecycle Events (with Notifications)
        AppointmentCreated::class => [
            AppointmentNotificationListener::class . '@handleCreated',
        ],

        AppointmentUpdated::class => [
            AppointmentNotificationListener::class . '@handleUpdated',
        ],

        AppointmentDeleted::class => [
            AppointmentNotificationListener::class . '@handleDeleted',
        ],

        // Cancellation Events
        AppointmentCancellationRequested::class => [
            SendCancellationNotifications::class,
            UpdateModificationStats::class,
            AppointmentNotificationListener::class . '@handleCancelled',
        ],

        // Reschedule Events
        AppointmentRescheduled::class => [
            UpdateModificationStats::class,
            AppointmentNotificationListener::class . '@handleRescheduled',
            // SendRescheduleNotifications::class, // TODO: Create this listener
        ],

        // Policy Violation Events
        AppointmentPolicyViolation::class => [
            TriggerPolicyEnforcement::class,
        ],

        // Callback Events
        CallbackRequested::class => [
            AssignCallbackToStaff::class,
        ],

        CallbackEscalated::class => [
            // NotifyManagers::class, // TODO: Create this listener
            // UpdateEscalationStats::class, // TODO: Create this listener
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        // Register model observers
        Call::observe(CallObserver::class);
    }
}

