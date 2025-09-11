<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for push notifications and webhook tracking
     */
    public function up(): void
    {
        // Push subscriptions for browser/mobile notifications
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token', 500)->unique();
            $table->string('device_type', 50)->nullable(); // browser, ios, android
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('invalidated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'active']);
            $table->index('token');
        });
        
        // Webhook delivery tracking
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 36);
            $table->string('notification_id', 50);
            $table->string('event_type', 50);
            $table->string('status', 20); // pending, success, failed, error
            $table->integer('http_code')->nullable();
            $table->integer('attempt_count')->default(1);
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->index(['tenant_id', 'status']);
            $table->index('notification_id');
            $table->index('created_at');
        });
        
        // Invoice metadata for tracking generated invoices
        Schema::create('invoice_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->string('tenant_id', 36);
            $table->foreignId('topup_id')->nullable()->constrained('balance_topups');
            $table->foreignId('transaction_id')->nullable()->constrained();
            $table->decimal('amount', 10, 2);
            $table->string('filename', 255);
            $table->integer('sequence_number');
            $table->string('type', 20)->default('invoice'); // invoice, credit_note, statement
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->index(['tenant_id', 'created_at']);
            $table->index('invoice_number');
        });
        
        // Notification preferences per user
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('email_billing')->default(true);
            $table->boolean('email_warnings')->default(true);
            $table->boolean('email_marketing')->default(false);
            $table->boolean('sms_billing')->default(false);
            $table->boolean('sms_warnings')->default(false);
            $table->boolean('push_enabled')->default(false);
            $table->boolean('push_billing')->default(true);
            $table->boolean('push_warnings')->default(true);
            $table->json('quiet_hours')->nullable(); // {"start": "22:00", "end": "08:00"}
            $table->string('timezone', 50)->default('Europe/Berlin');
            $table->timestamps();
            
            $table->unique('user_id');
        });
        
        // Add notification fields to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'phone_verified')) {
                $table->boolean('phone_verified')->default(false)->after('phone');
            }
            if (!Schema::hasColumn('users', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable();
            }
        });
        
        // Add webhook fields to tenants table
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'webhook_url')) {
                $table->string('webhook_url', 500)->nullable();
            }
            if (!Schema::hasColumn('tenants', 'webhook_secret')) {
                $table->string('webhook_secret', 100)->nullable();
            }
            if (!Schema::hasColumn('tenants', 'webhook_disabled_at')) {
                $table->timestamp('webhook_disabled_at')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'webhook_disabled_reason')) {
                $table->string('webhook_disabled_reason')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'webhook_url',
                'webhook_secret',
                'webhook_disabled_at',
                'webhook_disabled_reason'
            ]);
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'phone_verified',
                'notification_preferences'
            ]);
        });
        
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('invoice_metadata');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('push_subscriptions');
    }
};