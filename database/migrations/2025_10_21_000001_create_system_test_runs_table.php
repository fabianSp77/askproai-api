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
        Schema::create('system_test_runs', function (Blueprint $table) {
            $table->id();

            // User who ran the test
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Test type (event_id_verification, availability_check, etc.)
            $table->string('test_type');

            // Status: pending, running, completed, failed
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])
                ->default('pending');

            // Test output (JSON)
            $table->json('output')->nullable();

            // Error message if test failed
            $table->text('error_message')->nullable();

            // Metadata (company context: name, team_id, event_ids)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            // Duration in milliseconds
            $table->integer('duration_ms')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('test_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_test_runs');
    }
};
