<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Erweitere customers Tabelle um Journey-Status
        Schema::table('customers', function (Blueprint $table) {
            // Journey Status Felder
            if (!Schema::hasColumn('customers', 'journey_status')) {
                $table->string('journey_status')->default('initial_contact')->after('customer_number');
            }
            if (!Schema::hasColumn('customers', 'journey_status_updated_at')) {
                $table->timestamp('journey_status_updated_at')->nullable()->after('journey_status');
            }
            if (!Schema::hasColumn('customers', 'journey_history')) {
                $table->json('journey_history')->nullable()->after('journey_status_updated_at');
            }
            
            // Appointment Tracking (appointment_count existiert bereits)
            if (!Schema::hasColumn('customers', 'last_appointment_at')) {
                $table->timestamp('last_appointment_at')->nullable()->after('appointment_count');
            }
            if (!Schema::hasColumn('customers', 'completed_appointments')) {
                $table->integer('completed_appointments')->default(0)->after('last_appointment_at');
            }
            if (!Schema::hasColumn('customers', 'cancelled_appointments')) {
                $table->integer('cancelled_appointments')->default(0)->after('completed_appointments');
            }
            if (!Schema::hasColumn('customers', 'no_show_appointments')) {
                $table->integer('no_show_appointments')->default(0)->after('cancelled_appointments');
            }
            
            // Revenue & Notes
            if (!Schema::hasColumn('customers', 'total_revenue')) {
                $table->decimal('total_revenue', 10, 2)->default(0)->after('no_show_appointments');
            }
            if (!Schema::hasColumn('customers', 'tags')) {
                $table->json('tags')->nullable()->after('total_revenue');
            }
            if (!Schema::hasColumn('customers', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('tags');
            }
            
            // Indizes für Performance (nur wenn noch nicht vorhanden)
            if (!collect(Schema::getIndexes('customers'))->pluck('name')->contains('customers_journey_status_index')) {
                $table->index('journey_status');
            }
            if (!collect(Schema::getIndexes('customers'))->pluck('name')->contains('customers_company_id_journey_status_index')) {
                $table->index(['company_id', 'journey_status']);
            }
        });
        
        // 2. Journey-Stage Definitionen
        Schema::create('customer_journey_stages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->string('color')->default('#6B7280'); // Tailwind gray-500
            $table->string('icon')->default('heroicon-o-user');
            $table->json('next_stages')->nullable(); // Mögliche Folgestatus
            $table->json('automation_rules')->nullable(); // Automatische Aktionen
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // 3. Journey Events für Historie
        Schema::create('customer_journey_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // call_received, appointment_booked, etc.
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->json('event_data')->nullable();
            $table->string('triggered_by')->nullable(); // system, user, webhook
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('related_type')->nullable(); // Call, Appointment, etc.
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamps();
            
            $table->index(['customer_id', 'created_at']);
            $table->index(['company_id', 'event_type']);
            $table->index(['related_type', 'related_id']);
        });
        
        // 4. Customer Touchpoints (Alle Interaktionen)
        Schema::create('customer_touchpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('type'); // call, appointment, email, sms, note
            $table->string('channel')->nullable(); // phone, web, email, walk-in
            $table->string('direction')->nullable(); // inbound, outbound
            $table->string('status')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('occurred_at');
            $table->string('touchpointable_type')->nullable();
            $table->unsignedBigInteger('touchpointable_id')->nullable();
            $table->timestamps();
            
            $table->index(['customer_id', 'occurred_at']);
            $table->index(['company_id', 'type']);
            $table->index(['touchpointable_type', 'touchpointable_id']);
        });
        
        // 5. Standard Journey Stages einfügen
        DB::table('customer_journey_stages')->insert([
            [
                'code' => 'initial_contact',
                'name' => 'Erstkontakt',
                'description' => 'Kunde hat zum ersten Mal angerufen',
                'order' => 1,
                'color' => '#6B7280',
                'icon' => 'heroicon-o-phone',
                'next_stages' => json_encode(['appointment_scheduled', 'follow_up_needed', 'not_interested']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'appointment_scheduled',
                'name' => 'Termin vereinbart',
                'description' => 'Ein Termin wurde gebucht',
                'order' => 2,
                'color' => '#3B82F6',
                'icon' => 'heroicon-o-calendar',
                'next_stages' => json_encode(['appointment_completed', 'appointment_cancelled', 'no_show']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'appointment_completed',
                'name' => 'Termin wahrgenommen',
                'description' => 'Kunde ist zum Termin erschienen',
                'order' => 3,
                'color' => '#10B981',
                'icon' => 'heroicon-o-check-circle',
                'next_stages' => json_encode(['regular_customer', 'follow_up_appointment', 'completed']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'regular_customer',
                'name' => 'Stammkunde',
                'description' => 'Kunde kommt regelmäßig',
                'order' => 4,
                'color' => '#8B5CF6',
                'icon' => 'heroicon-o-star',
                'next_stages' => json_encode(['vip_customer', 'inactive']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'no_show',
                'name' => 'Nicht erschienen',
                'description' => 'Kunde ist nicht zum Termin erschienen',
                'order' => 10,
                'color' => '#EF4444',
                'icon' => 'heroicon-o-x-circle',
                'next_stages' => json_encode(['follow_up_needed', 'blocked', 'inactive']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'cancelled',
                'name' => 'Abgesagt',
                'description' => 'Termin wurde abgesagt',
                'order' => 11,
                'color' => '#F59E0B',
                'icon' => 'heroicon-o-ban',
                'next_stages' => json_encode(['appointment_scheduled', 'not_interested']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'follow_up_needed',
                'name' => 'Nachfassen erforderlich',
                'description' => 'Kunde sollte kontaktiert werden',
                'order' => 12,
                'color' => '#F97316',
                'icon' => 'heroicon-o-phone-arrow-up-right',
                'next_stages' => json_encode(['appointment_scheduled', 'not_interested']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'not_interested',
                'name' => 'Kein Interesse',
                'description' => 'Kunde hat kein Interesse',
                'order' => 20,
                'color' => '#6B7280',
                'icon' => 'heroicon-o-minus-circle',
                'next_stages' => json_encode(['inactive']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'blocked',
                'name' => 'Blockiert',
                'description' => 'Kunde wurde blockiert',
                'order' => 21,
                'color' => '#DC2626',
                'icon' => 'heroicon-o-shield-exclamation',
                'next_stages' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'inactive',
                'name' => 'Inaktiv',
                'description' => 'Kunde ist seit längerem inaktiv',
                'order' => 22,
                'color' => '#9CA3AF',
                'icon' => 'heroicon-o-pause',
                'next_stages' => json_encode(['initial_contact']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'journey_status',
                'journey_status_updated_at',
                'journey_history',
                'last_appointment_at',
                'completed_appointments',
                'cancelled_appointments',
                'no_show_appointments',
                'total_revenue',
                'tags',
                'internal_notes'
            ]);
        });
        
        Schema::dropIfExists('customer_touchpoints');
        Schema::dropIfExists('customer_journey_events');
        Schema::dropIfExists('customer_journey_stages');
    }
};