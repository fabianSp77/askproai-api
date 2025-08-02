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
        // Event logs table - stores all events
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->json('payload');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index('event_name');
            $table->index('company_id');
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['event_name', 'created_at']);
            $table->index(['company_id', 'created_at']);
        });

        // Event subscriptions table - for webhooks
        Schema::create('event_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->json('event_names'); // Array of event names to subscribe to
            $table->string('webhook_url');
            $table->json('filters')->nullable(); // Additional filters
            $table->boolean('active')->default(true);
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            
            $table->index('active');
            $table->index('company_id');
        });

        // Custom events table - for user-defined events
        Schema::create('custom_events', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category');
            $table->json('schema'); // JSON schema definition
            $table->text('description')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index('category');
            $table->index('company_id');
        });

        // Event audit trail - for compliance
        Schema::create('event_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_log_id')->constrained('event_logs')->onDelete('cascade');
            $table->string('action'); // viewed, replayed, deleted, etc.
            $table->foreignId('performed_by')->constrained('users')->nullOnDelete();
            $table->json('changes')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
            
            $table->index('event_log_id');
            $table->index('performed_by');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_audit_trail');
        Schema::dropIfExists('custom_events');
        Schema::dropIfExists('event_subscriptions');
        Schema::dropIfExists('event_logs');
    }
};