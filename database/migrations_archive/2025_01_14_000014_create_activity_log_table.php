<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated activity log table with all columns
     * Based on spatie/laravel-activitylog package
     */
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('tenant_id')->nullable();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('event')->nullable(); // created, updated, deleted, etc.
            $table->nullableMorphs('subject', 'subject'); // What was acted upon
            $table->nullableMorphs('causer', 'causer'); // Who performed the action
            $table->json('properties')->nullable();
            $table->string('batch_uuid')->nullable(); // For batch operations
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('log_name');
            $table->index('event');
            $table->index(['tenant_id', 'log_name']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};