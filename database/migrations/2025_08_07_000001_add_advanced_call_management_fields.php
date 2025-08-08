<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Priority management
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium')->after('call_status');
            $table->timestamp('priority_updated_at')->nullable()->after('priority');
            $table->unsignedBigInteger('priority_updated_by')->nullable()->after('priority_updated_at');
            
            // Advanced metadata
            $table->json('tags')->nullable()->after('priority_updated_by');
            $table->json('custom_fields')->nullable()->after('tags');
            
            // Performance indexes
            $table->index(['priority', 'created_at']);
            $table->index(['call_status', 'priority']);
            $table->index(['appointment_made', 'priority']);
            
            // Foreign key for priority updater
            $table->foreign('priority_updated_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
        
        // Create call notes table for voice notes and other annotations
        if (!Schema::hasTable('call_notes')) {
            Schema::create('call_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('call_id');
                $table->unsignedBigInteger('user_id');
                $table->enum('type', ['text', 'voice', 'system'])->default('text');
                $table->text('content');
                $table->json('metadata')->nullable(); // For audio data, timestamps, etc.
                $table->timestamps();
                
                $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                
                $table->index(['call_id', 'type']);
                $table->index(['user_id', 'created_at']);
            });
        }
        
        // Create call timeline events table
        if (!Schema::hasTable('call_timeline_events')) {
            Schema::create('call_timeline_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('call_id');
                $table->enum('event_type', ['status_change', 'priority_change', 'note_added', 'recording_added', 'custom']);
                $table->string('event_title');
                $table->text('event_description')->nullable();
                $table->json('event_data')->nullable();
                $table->unsignedBigInteger('triggered_by')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
                
                $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
                $table->foreign('triggered_by')->references('id')->on('users')->onDelete('set null');
                
                $table->index(['call_id', 'occurred_at']);
                $table->index(['event_type', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropForeign(['priority_updated_by']);
            $table->dropIndex(['priority', 'created_at']);
            $table->dropIndex(['call_status', 'priority']);
            $table->dropIndex(['appointment_made', 'priority']);
            $table->dropColumn([
                'priority',
                'priority_updated_at',
                'priority_updated_by',
                'tags',
                'custom_fields'
            ]);
        });
        
        Schema::dropIfExists('call_timeline_events');
        Schema::dropIfExists('call_notes');
    }
};