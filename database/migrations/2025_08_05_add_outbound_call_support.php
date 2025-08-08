<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add outbound call support to companies
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('can_make_outbound_calls')->default(false)->after('is_white_label');
            $table->json('outbound_settings')->nullable()->after('can_make_outbound_calls')
                ->comment('Settings for outbound calls: max concurrent, time restrictions, etc.');
            $table->integer('outbound_call_limit')->nullable()->after('outbound_settings')
                ->comment('Monthly outbound call limit');
            $table->integer('outbound_calls_used')->default(0)->after('outbound_call_limit')
                ->comment('Outbound calls used this month');
        });

        // Add campaign tracking to calls
        Schema::table('calls', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('company_id')
                ->constrained('retell_ai_call_campaigns')->nullOnDelete();
            $table->enum('call_purpose', ['inbound_support', 'outbound_sales', 'outbound_followup', 'outbound_confirmation'])
                ->nullable()->after('direction');
            $table->boolean('is_campaign_call')->default(false)->after('call_purpose');
            
            $table->index(['campaign_id', 'created_at']);
            $table->index(['company_id', 'call_purpose']);
        });

        // Create outbound call templates
        Schema::create('outbound_call_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('template_type', ['sales', 'appointment_reminder', 'follow_up', 'survey', 'custom']);
            $table->text('script_template');
            $table->json('variables')->nullable()->comment('Dynamic variables that can be used in the script');
            $table->json('success_criteria')->nullable()->comment('What constitutes a successful call');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['company_id', 'template_type', 'is_active']);
        });

        // Enhanced campaign targets
        Schema::create('campaign_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('retell_ai_call_campaigns')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('phone_number');
            $table->json('custom_data')->nullable()->comment('Custom data for this target');
            $table->enum('status', ['pending', 'calling', 'completed', 'failed', 'skipped'])->default('pending');
            $table->integer('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('call_id')->nullable()->constrained('calls')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['campaign_id', 'status']);
            $table->index(['phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_targets');
        Schema::dropIfExists('outbound_call_templates');
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn(['campaign_id', 'call_purpose', 'is_campaign_call']);
        });
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'can_make_outbound_calls',
                'outbound_settings',
                'outbound_call_limit',
                'outbound_calls_used'
            ]);
        });
    }
};