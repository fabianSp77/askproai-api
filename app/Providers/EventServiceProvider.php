<?php

namespace App\Providers;

use App\Events\Appointments\AppointmentBooked;
use App\Events\Appointments\AppointmentCancellationRequested;
use App\Events\Appointments\AppointmentCancelled;
use App\Events\Appointments\AppointmentPolicyViolation;
use App\Events\Appointments\AppointmentRescheduled;
use App\Events\Appointments\CallbackEscalated;
use App\Events\Appointments\CallbackRequested;
use App\Listeners\Appointments\AssignCallbackToStaff;
use App\Listeners\Appointments\InvalidateWeekCacheListener;
use App\Listeners\Appointments\SendCancellationNotifications;
use App\Listeners\Appointments\SyncToCalcomOnBooked;
use App\Listeners\Appointments\SyncToCalcomOnCancelled;
use App\Listeners\Appointments\SyncToCalcomOnRescheduled;
use App\Listeners\Appointments\TriggerPolicyEnforcement;
use App\Listeners\Appointments\UpdateModificationStats;
use App\Listeners\AppointmentNotificationListener;
use App\Events\AppointmentCreated;
use App\Events\AppointmentUpdated;
use App\Events\AppointmentDeleted;
use App\Events\ConfigurationUpdated;
use App\Events\ConfigurationCreated;
use App\Events\ConfigurationDeleted;
use App\Listeners\InvalidateConfigurationCache;
use App\Listeners\LogConfigurationChange;
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

        // Booking Events (Phase 2: Bidirectional Cal.com Sync)
        AppointmentBooked::class => [
            // SyncToCalcomOnBooked::class,  // ⚠️ TEMPORARILY DISABLED - migration pending
            InvalidateWeekCacheListener::class . '@handleBooked', // Clear week availability cache
        ],

        // Cancellation Events
        AppointmentCancellationRequested::class => [
            SendCancellationNotifications::class,
            UpdateModificationStats::class,
            AppointmentNotificationListener::class . '@handleCancelled',
        ],

        AppointmentCancelled::class => [
            // SyncToCalcomOnCancelled::class,  // ⚠️ TEMPORARILY DISABLED - migration pending
            InvalidateWeekCacheListener::class . '@handleCancelled', // Clear week availability cache
        ],

        // Reschedule Events
        AppointmentRescheduled::class => [
            UpdateModificationStats::class,
            AppointmentNotificationListener::class . '@handleRescheduled',
            // SyncToCalcomOnRescheduled::class,  // ⚠️ TEMPORARILY DISABLED - migration pending
            // SendRescheduleNotifications::class, // TODO: Create this listener
            InvalidateWeekCacheListener::class . '@handleRescheduled', // Clear week availability cache
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

        // Configuration Events (Phase 2: Event System & Synchronisation)
        ConfigurationUpdated::class => [
            InvalidateConfigurationCache::class . '@handleUpdated',
            LogConfigurationChange::class . '@handleUpdated',
        ],

        ConfigurationCreated::class => [
            InvalidateConfigurationCache::class . '@handleCreated',
            LogConfigurationChange::class . '@handleCreated',
        ],

        ConfigurationDeleted::class => [
            InvalidateConfigurationCache::class . '@handleDeleted',
            LogConfigurationChange::class . '@handleDeleted',
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        // Register model observers
        Call::observe(CallObserver::class);
    }
}

