<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates master event definition table for notification system.
     * Defines all available notification events and their default configurations.
     */
    public function up(): void
    {
        if (Schema::hasTable('notification_event_mappings')) {
            return;
        }

        Schema::create('notification_event_mappings', function (Blueprint $table) {
            $table->id();

            // Event identification
            $table->string('event_type', 100)->unique()
                ->comment('Unique event identifier used throughout the system');
            $table->string('event_label', 255)
                ->comment('Human-readable event name for UI display');

            // Event categorization
            $table->enum('event_category', ['booking', 'reminder', 'modification', 'callback', 'system'])
                ->comment('Event category for organization and filtering');

            // Default channel configuration
            $table->json('default_channels')
                ->comment('Default notification channels for this event: ["email", "sms", "whatsapp", "push"]');

            // Event documentation
            $table->text('description')
                ->comment('Detailed description of what triggers this event and when it fires');

            // Event control flags
            $table->boolean('is_system_event')->default(true)
                ->comment('True for core system events, false for custom/company-specific events');
            $table->boolean('is_active')->default(true)
                ->comment('Global enable/disable switch for this event type');

            // Additional metadata
            $table->json('metadata')->nullable()
                ->comment('Additional event properties: {variables, constraints, timing_rules, etc.}');

            $table->timestamps();

            // Indexes for performance
            $table->index(['event_category', 'is_active'], 'notif_event_category_active_idx');
            $table->index('is_system_event', 'notif_event_system_idx');
        });

        // Seed core notification events
        $this->seedCoreEvents();
    }

    /**
     * Seed the table with core notification events
     */
    private function seedCoreEvents(): void
    {
        $coreEvents = [
            // Booking Events
            [
                'event_type' => 'booking_confirmed',
                'event_label' => 'Buchungsbestätigung',
                'event_category' => 'booking',
                'default_channels' => json_encode(['email', 'sms']),
                'description' => 'Triggered when a new appointment is successfully created and confirmed. Sent immediately to the customer.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'appointment_date', 'appointment_time', 'service_name', 'branch_address', 'staff_name'],
                    'timing' => 'immediate',
                    'priority' => 'high'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_type' => 'booking_pending',
                'event_label' => 'Buchungsanfrage eingegangen',
                'event_category' => 'booking',
                'default_channels' => json_encode(['email']),
                'description' => 'Triggered when a booking request is received but awaiting confirmation or payment. Informs customer that request is being processed.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'requested_date', 'requested_time', 'service_name'],
                    'timing' => 'immediate',
                    'priority' => 'medium'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Reminder Events
            [
                'event_type' => 'reminder_24h',
                'event_label' => 'Erinnerung 24 Stunden vorher',
                'event_category' => 'reminder',
                'default_channels' => json_encode(['email', 'sms', 'whatsapp']),
                'description' => 'Reminder sent 24 hours before the scheduled appointment. Includes appointment details and modification options.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'appointment_date', 'appointment_time', 'service_name', 'branch_address', 'cancel_url', 'reschedule_url'],
                    'timing' => 'scheduled',
                    'schedule_offset_hours' => -24,
                    'priority' => 'high'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_type' => 'reminder_2h',
                'event_label' => 'Erinnerung 2 Stunden vorher',
                'event_category' => 'reminder',
                'default_channels' => json_encode(['sms', 'push']),
                'description' => 'Final reminder sent 2 hours before appointment. Brief reminder for immediate attention.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'appointment_time', 'service_name', 'branch_address'],
                    'timing' => 'scheduled',
                    'schedule_offset_hours' => -2,
                    'priority' => 'high'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_type' => 'reminder_1week',
                'event_label' => 'Erinnerung 1 Woche vorher',
                'event_category' => 'reminder',
                'default_channels' => json_encode(['email']),
                'description' => 'Early reminder sent 1 week before appointment. Allows ample time for rescheduling if needed.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'appointment_date', 'appointment_time', 'service_name', 'reschedule_url'],
                    'timing' => 'scheduled',
                    'schedule_offset_hours' => -168,
                    'priority' => 'medium'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Modification Events
            [
                'event_type' => 'cancellation',
                'event_label' => 'Stornierungsbestätigung',
                'event_category' => 'modification',
                'default_channels' => json_encode(['email', 'sms']),
                'description' => 'Confirmation sent when an appointment is cancelled by customer or staff. Includes cancellation details and rebooking options.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'cancelled_date', 'cancelled_time', 'service_name', 'cancellation_reason', 'rebook_url'],
                    'timing' => 'immediate',
                    'priority' => 'high'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_type' => 'reschedule_confirmed',
                'event_label' => 'Umbuchung bestätigt',
                'event_category' => 'modification',
                'default_channels' => json_encode(['email', 'sms']),
                'description' => 'Confirmation sent when an appointment is rescheduled. Shows old and new appointment times.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'old_date', 'old_time', 'new_date', 'new_time', 'service_name', 'branch_address'],
                    'timing' => 'immediate',
                    'priority' => 'high'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_type' => 'appointment_modified',
                'event_label' => 'Terminänderung',
                'event_category' => 'modification',
                'default_channels' => json_encode(['email']),
                'description' => 'Notification when appointment details are modified (service, staff, notes) but time remains the same.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'appointment_date', 'appointment_time', 'changes_summary'],
                    'timing' => 'immediate',
                    'priority' => 'medium'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Callback Events
            [
                'event_type' => 'callback_request_received',
                'event_label' => 'Rückrufanfrage eingegangen',
                'event_category' => 'callback',
                'default_channels' => json_encode(['email', 'sms']),
                'description' => 'Confirmation sent to customer when callback request is received. Includes expected callback timeframe.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'request_time', 'expected_callback_time', 'preferred_contact_method'],
                    'timing' => 'immediate',
                    'priority' => 'medium'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_type' => 'callback_scheduled',
                'event_label' => 'Rückruf terminiert',
                'event_category' => 'callback',
                'default_channels' => json_encode(['email', 'sms']),
                'description' => 'Notification when callback is scheduled with specific date and time.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'callback_date', 'callback_time', 'staff_name'],
                    'timing' => 'immediate',
                    'priority' => 'high'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // System Events
            [
                'event_type' => 'no_show',
                'event_label' => 'Kunde nicht erschienen',
                'event_category' => 'system',
                'default_channels' => json_encode(['email']),
                'description' => 'Notification sent when customer does not show up for appointment. May include rebooking invitation.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'missed_date', 'missed_time', 'service_name', 'rebook_url'],
                    'timing' => 'immediate',
                    'priority' => 'low'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_type' => 'appointment_completed',
                'event_label' => 'Termin abgeschlossen',
                'event_category' => 'system',
                'default_channels' => json_encode(['email']),
                'description' => 'Thank you message sent after appointment completion. May include feedback request or follow-up booking offer.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'service_name', 'completion_date', 'feedback_url', 'rebook_url'],
                    'timing' => 'immediate',
                    'priority' => 'low'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_type' => 'payment_received',
                'event_label' => 'Zahlung erhalten',
                'event_category' => 'system',
                'default_channels' => json_encode(['email']),
                'description' => 'Confirmation sent when payment is processed successfully. Includes receipt details.',
                'is_system_event' => true,
                'is_active' => true,
                'metadata' => json_encode([
                    'variables' => ['customer_name', 'amount', 'payment_method', 'transaction_id', 'receipt_url'],
                    'timing' => 'immediate',
                    'priority' => 'high'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('notification_event_mappings')->insert($coreEvents);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_event_mappings');
    }
};
