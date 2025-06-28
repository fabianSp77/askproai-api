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
        $this->createTableIfNotExists('customer_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->foreignId('company_id');
            
            // Booking preferences
            $this->addJsonColumn($table, 'preferred_days_of_week'); // [1,3,5] = Mon, Wed, Fri
            $this->addJsonColumn($table, 'preferred_time_slots'); // ["morning", "afternoon"]
            $table->time('earliest_booking_time')->nullable();
            $table->time('latest_booking_time')->nullable();
            $table->integer('preferred_duration_minutes')->nullable();
            $table->integer('advance_booking_days')->default(7); // How far in advance they prefer to book
            
            // Service preferences
            $this->addJsonColumn($table, 'preferred_services'); // Array of service IDs
            $this->addJsonColumn($table, 'avoided_services');
            $this->addJsonColumn($table, 'preferred_staff_ids'); // Array of staff IDs
            $this->addJsonColumn($table, 'avoided_staff_ids');
            $table->foreignId('preferred_branch_id')->nullable();
            
            // Communication preferences
            $table->boolean('reminder_24h')->default(true);
            $table->boolean('reminder_2h')->default(true);
            $table->boolean('reminder_sms')->default(false);
            $table->boolean('reminder_whatsapp')->default(false);
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('birthday_greetings')->default(true);
            $this->addJsonColumn($table, 'communication_blackout_times'); // Times not to contact
            
            // Special requirements
            $this->addJsonColumn($table, 'accessibility_needs');
            $this->addJsonColumn($table, 'health_conditions');
            $this->addJsonColumn($table, 'allergies');
            $table->text('special_instructions')->nullable();
            
            // Behavior patterns (auto-learned)
            $this->addJsonColumn($table, 'booking_patterns'); // ML-derived patterns
            $this->addJsonColumn($table, 'cancellation_patterns');
            $table->float('punctuality_score')->default(1.0); // 0-1 score
            $table->float('reliability_score')->default(1.0);
            $this->addJsonColumn($table, 'service_history'); // Frequency of different services
            
            // Pricing preferences
            $table->boolean('price_sensitive')->default(false);
            $table->decimal('average_spend', 10, 2)->default(0);
            $this->addJsonColumn($table, 'preferred_payment_methods');
            $table->boolean('auto_charge_enabled')->default(false);
            
            $table->timestamps();
            
            // Indexes - handle SQLite compatibility
            if (!$this->isSQLite()) {
                $table->unique(['customer_id', 'company_id']);
                $table->index('preferred_branch_id');
            }
            
            // Foreign keys - only for non-SQLite
            if (!$this->isSQLite()) {
                $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('preferred_branch_id')->references('id')->on('branches')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('customer_preferences');
    }
};