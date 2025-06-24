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
        Schema::create('appointment_locks', function (Blueprint $table) {
            $table->id();
            $table->string('lock_token', 64)->unique();
            $table->uuid('branch_id');
            $table->uuid('staff_id')->nullable();
            $table->bigInteger('event_type_id')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('expires_at');
            $table->string('holder_type', 50)->default('system'); // 'system', 'user', 'api'
            $table->string('holder_id', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['branch_id', 'starts_at', 'ends_at'], 'idx_appointment_locks_branch_time');
            $table->index(['staff_id', 'starts_at', 'ends_at'], 'idx_appointment_locks_staff_time');
            $table->index('expires_at');
            $table->index('lock_token');
            
            // Foreign keys
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_locks');
    }
};