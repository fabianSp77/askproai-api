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
        // Dunning configuration per company
        Schema::create('dunning_configurations', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id');
            $table->boolean('enabled')->default(true);
            
            // Retry attempts configuration
            $table->integer('max_retry_attempts')->default(3);
            $table->json('retry_delays')->default('{"1": 3, "2": 5, "3": 7}'); // Days between retries
            
            // Grace period settings
            $table->integer('grace_period_days')->default(3);
            $table->boolean('pause_service_on_failure')->default(false);
            $table->integer('pause_after_days')->default(14);
            
            // Email templates
            $table->boolean('send_payment_failed_email')->default(true);
            $table->boolean('send_retry_warning_email')->default(true);
            $table->boolean('send_service_paused_email')->default(true);
            $table->boolean('send_payment_recovered_email')->default(true);
            
            // Escalation settings
            $table->boolean('enable_manual_review')->default(true);
            $table->integer('manual_review_after_attempts')->default(2);
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->unique('company_id');
            
            // Foreign key commented out due to different ID types
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
        
        // Dunning history/status for each invoice
        Schema::create('dunning_processes', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id');
            $table->uuid('invoice_id');
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            
            // Status
            $table->enum('status', ['active', 'resolved', 'failed', 'paused', 'cancelled']);
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            
            // Retry tracking
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('last_retry_at')->nullable();
            
            // Amount tracking
            $table->decimal('original_amount', 10, 2);
            $table->decimal('remaining_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            
            // Failure reason
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            
            // Actions taken
            $table->boolean('service_paused')->default(false);
            $table->timestamp('service_paused_at')->nullable();
            $table->boolean('manual_review_requested')->default(false);
            $table->timestamp('manual_review_requested_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['status', 'next_retry_at']);
            $table->index('stripe_invoice_id');
            $table->index('stripe_subscription_id');
            $table->index('invoice_id');
            
            // Foreign keys commented out
            // $table->foreign('company_id')->references('id')->on('companies');
            // $table->foreign('invoice_id')->references('id')->on('invoices');
        });
        
        // Dunning activity log
        Schema::create('dunning_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dunning_process_id')->constrained()->onDelete('cascade');
            $table->uuid('company_id');
            
            // Activity type
            $table->enum('type', [
                'retry_scheduled',
                'retry_attempted', 
                'retry_succeeded',
                'retry_failed',
                'email_sent',
                'service_paused',
                'service_resumed',
                'manual_review_requested',
                'manually_resolved',
                'escalated',
                'cancelled'
            ]);
            
            // Activity details
            $table->string('description');
            $table->json('details')->nullable();
            $table->string('performed_by')->nullable(); // System or user ID
            
            // Results
            $table->boolean('successful')->default(true);
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['dunning_process_id', 'type']);
            $table->index('company_id');
        });
        
        // Add dunning fields to invoices if not exists
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'dunning_status')) {
                    $table->string('dunning_status')->nullable()->after('status');
                    $table->integer('payment_attempts')->default(0)->after('dunning_status');
                    $table->timestamp('last_payment_attempt_at')->nullable()->after('payment_attempts');
                    $table->index('dunning_status');
                }
            });
        }
        
        // Add dunning fields to companies if not exists
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (!Schema::hasColumn('companies', 'billing_status')) {
                    $table->enum('billing_status', ['active', 'warning', 'suspended', 'cancelled'])
                        ->default('active')
                        ->after('is_active');
                    $table->timestamp('billing_suspended_at')->nullable()->after('billing_status');
                    $table->index('billing_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove columns from existing tables
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (Schema::hasColumn('companies', 'billing_status')) {
                    $table->dropColumn(['billing_status', 'billing_suspended_at']);
                }
            });
        }
        
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (Schema::hasColumn('invoices', 'dunning_status')) {
                    $table->dropColumn(['dunning_status', 'payment_attempts', 'last_payment_attempt_at']);
                }
            });
        }
        
        // Drop tables
        Schema::dropIfExists('dunning_activities');
        Schema::dropIfExists('dunning_processes');
        Schema::dropIfExists('dunning_configurations');
    }
};