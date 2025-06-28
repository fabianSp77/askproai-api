<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calcom_event_types', function (Blueprint $table) {
            // Zeiteinstellungen
            if (!Schema::hasColumn('calcom_event_types', 'minimum_booking_notice')) {
                $table->integer('minimum_booking_notice')->nullable()
                    ->comment('Minimum Vorlaufzeit in Minuten');
            }
            if (!Schema::hasColumn('calcom_event_types', 'booking_future_limit')) {
                $table->integer('booking_future_limit')->nullable()
                    ->comment('Maximale Buchungsreichweite in Tagen');
            }
            if (!Schema::hasColumn('calcom_event_types', 'time_slot_interval')) {
                $table->integer('time_slot_interval')->nullable()
                    ->comment('Zeitschritte in Minuten (z.B. 15, 30)');
            }
            if (!Schema::hasColumn('calcom_event_types', 'buffer_before')) {
                $table->integer('buffer_before')->nullable()
                    ->comment('Pufferzeit vor dem Termin in Minuten');
            }
            if (!Schema::hasColumn('calcom_event_types', 'buffer_after')) {
                $table->integer('buffer_after')->nullable()
                    ->comment('Pufferzeit nach dem Termin in Minuten');
            }
            
            // Locations/Orte
            if (!Schema::hasColumn('calcom_event_types', 'locations')) {
                $this->addJsonColumn($table, 'locations', true)
                    ->comment('Verfügbare Locations [{type, value, displayName}]');
            }
            
            // Erweiterte Features
            if (!Schema::hasColumn('calcom_event_types', 'custom_fields')) {
                $this->addJsonColumn($table, 'custom_fields', true)
                    ->comment('Custom Fields Konfiguration');
            }
            if (!Schema::hasColumn('calcom_event_types', 'max_bookings_per_day')) {
                $table->integer('max_bookings_per_day')->nullable()
                    ->comment('Max Buchungen pro Tag');
            }
            if (!Schema::hasColumn('calcom_event_types', 'seats_per_time_slot')) {
                $table->integer('seats_per_time_slot')->nullable()
                    ->comment('Plätze pro Zeitslot für Gruppenbuchungen');
            }
            if (!Schema::hasColumn('calcom_event_types', 'schedule_id')) {
                $table->string('schedule_id')->nullable()
                    ->comment('Cal.com Schedule ID für Verfügbarkeiten');
            }
            if (!Schema::hasColumn('calcom_event_types', 'recurring_config')) {
                $this->addJsonColumn($table, 'recurring_config', true)
                    ->comment('Wiederkehrende Event Konfiguration');
            }
            
            // Setup & Sync Status
            if (!Schema::hasColumn('calcom_event_types', 'setup_status')) {
                $table->enum('setup_status', ['incomplete', 'partial', 'complete'])
                    ->default('incomplete')
                    ->comment('Setup-Status des Event Types');
            }
            if (!Schema::hasColumn('calcom_event_types', 'setup_checklist')) {
                $this->addJsonColumn($table, 'setup_checklist', true)
                    ->comment('Checklist was noch konfiguriert werden muss');
            }
            if (!Schema::hasColumn('calcom_event_types', 'webhook_settings')) {
                $this->addJsonColumn($table, 'webhook_settings', true)
                    ->comment('Webhook-spezifische Einstellungen');
            }
            if (!Schema::hasColumn('calcom_event_types', 'calcom_url')) {
                $table->string('calcom_url')->nullable()
                    ->comment('Direkt-Link zum Event Type in Cal.com');
            }
            
            // Indexe für Performance
            try {
                $table->index('setup_status');
            } catch (\Exception $e) {
                // Index already exists
            }
            try {
                $table->index(['company_id', 'setup_status']);
            } catch (\Exception $e) {
                // Index already exists
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('calcom_event_types', function (Blueprint $table) {
            $table->dropColumn([
                'minimum_booking_notice',
                'booking_future_limit',
                'time_slot_interval',
                'buffer_before',
                'buffer_after',
                'locations',
                'custom_fields',
                'max_bookings_per_day',
                'seats_per_time_slot',
                'schedule_id',
                'recurring_config',
                'setup_status',
                'setup_checklist',
                'webhook_settings',
                'calcom_url'
            ]);
        });
    }
};
