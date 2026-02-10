<?php

namespace App\Providers;

use App\Events\Appointments\AppointmentBooked;
use App\Events\Appointments\AppointmentCancellationRequested;
use App\Events\Appointments\AppointmentCancelled;
use App\Events\Appointments\AppointmentPolicyViolation;
use App\Events\Appointments\AppointmentRescheduled;
use App\Events\Appointments\CallbackEscalated;
use App\Events\Appointments\CallbackRequested;
use App\Events\AppointmentWishCreated;
use App\Listeners\SendUnfulfilledWishNotification;
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
use App\Models\Appointment;
use App\Models\Call;
use App\Models\User;
use App\Models\UserInvitation;
use App\Observers\AppointmentObserver;
use App\Observers\CallObserver;
use App\Observers\UserInvitationObserver;
use App\Observers\UserObserver;
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
            SyncToCalcomOnBooked::class,  // ✅ RE-ENABLED - listener is functional and has proper loop prevention
            InvalidateWeekCacheListener::class . '@handleBooked', // Clear week availability cache
        ],

        // Cancellation Events
        AppointmentCancellationRequested::class => [
            SendCancellationNotifications::class,
            UpdateModificationStats::class,
            AppointmentNotificationListener::class . '@handleCancelled',
        ],

        AppointmentCancelled::class => [
            SyncToCalcomOnCancelled::class,  // ✅ RE-ENABLED: Sync cancellations to Cal.com
            InvalidateWeekCacheListener::class . '@handleCancelled', // Clear week availability cache
        ],

        // Reschedule Events
        AppointmentRescheduled::class => [
            UpdateModificationStats::class,
            AppointmentNotificationListener::class . '@handleRescheduled',
            SyncToCalcomOnRescheduled::class,  // ✅ RE-ENABLED 2025-11-25: Sync reschedules to Cal.com
            // TODO: SendRescheduleNotifications listener not yet implemented (would send SMS/email to customer about reschedule)
            InvalidateWeekCacheListener::class . '@handleRescheduled', // Clear week availability cache
        ],

        // Policy Violation Events
        AppointmentPolicyViolation::class => [
            TriggerPolicyEnforcement::class,
        ],

        // 💾 NEW PHASE: Appointment Wish Events (Unfulfilled Requests)
        AppointmentWishCreated::class => [
            SendUnfulfilledWishNotification::class,  // Send email to team
        ],

        // Callback Events
        CallbackRequested::class => [
            AssignCallbackToStaff::class,
        ],

        CallbackEscalated::class => [
            // NOTE: No listeners implemented yet. Consider adding:
            // - NotifyManagers::class - Send alerts to management on escalation
            // - UpdateEscalationStats::class - Track escalation metrics for reporting
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

        // Customer Portal observers
        Appointment::observe(AppointmentObserver::class);
        UserInvitation::observe(UserInvitationObserver::class);
        User::observe(UserObserver::class);
    }
}

