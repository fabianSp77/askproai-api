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
        if (!Schema::hasTable('customer_interactions')) {
            Schema::create('customer_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            
            // Interaction details
            $table->enum('interaction_type', [
                'phone_call',
                'appointment_booking',
                'appointment_cancellation',
                'appointment_reschedule',
                'inquiry',
                'complaint',
                'feedback',
                'no_show',
                'walk_in',
                'online_booking',
                'sms',
                'email',
                'whatsapp'
            ]);
            
            $table->string('channel')->default('phone'); // phone, web, mobile, in_person
            $table->timestamp('interaction_at');
            $table->integer('duration_seconds')->nullable();
            
            // Call-specific data
            $table->string('call_id')->nullable()->index();
            $table->string('from_phone')->nullable();
            $table->string('to_phone')->nullable();
            $table->enum('call_outcome', [
                'appointment_booked',
                'appointment_cancelled',
                'appointment_rescheduled',
                'information_provided',
                'transferred',
                'voicemail',
                'hung_up',
                'technical_issue'
            ])->nullable();
            
            // Content and context
            $table->text('summary')->nullable();
            $table->json('extracted_data')->nullable(); // AI-extracted information
            $table->json('sentiment_analysis')->nullable(); // Positive/Negative/Neutral scores
            $table->text('transcript')->nullable();
            $table->json('intent_classification')->nullable(); // What customer wanted
            
            // Related entities
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('handled_by')->nullable(); // User or AI agent
            
            // Customer state tracking
            $table->enum('customer_mood', ['happy', 'neutral', 'frustrated', 'angry'])->nullable();
            $table->boolean('issue_resolved')->nullable();
            $table->integer('satisfaction_score')->nullable(); // 1-5 rating
            
            // Follow-up tracking
            $table->boolean('requires_follow_up')->default(false);
            $table->timestamp('follow_up_at')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->boolean('follow_up_completed')->default(false);
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable(); // For categorization
            
            $table->timestamps();
            
            // Indexes
            $table->index(['customer_id', 'interaction_at']);
            $table->index(['company_id', 'interaction_type']);
            $table->index(['requires_follow_up', 'follow_up_at']);
            $table->index('from_phone');
            $table->index(['customer_id', 'call_outcome']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_interactions');
    }
};