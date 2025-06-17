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
            $table->foreignUuid('staff_id')->constrained()->onDelete('cascade');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('lock_token')->unique();
            $table->timestamp('lock_expires_at');
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for efficient querying
            $table->index(['staff_id', 'starts_at', 'ends_at']);
            $table->index('lock_expires_at');
            $table->index('lock_token');
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
