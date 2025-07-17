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
        if (!Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
                $table->string('to');
                $table->string('from')->nullable();
                $table->string('subject');
                $table->string('type', 50); // appointment_confirmation, reminder, marketing, etc.
                $table->text('content')->nullable();
                $table->string('status', 20)->default('pending'); // pending, sent, failed
                $table->timestamp('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable(); // Additional data like appointment_id, etc.
                $table->timestamps();
                
                // Indexes
                $table->index(['customer_id', 'sent_at']);
                $table->index(['company_id', 'type']);
                $table->index('status');
                $table->index('sent_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};