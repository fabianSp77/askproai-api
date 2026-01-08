<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ServiceCase Notes Migration
 *
 * Design decisions (from Agent Review):
 * - NO company_id: Multi-tenancy inherited via ServiceCase relationship
 * - Self-referential threading via parent_id (adjacency list pattern)
 * - Composite index for common query pattern (service_case_id + created_at)
 * - Soft deletes for audit trail
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_case_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('service_case_notes')
                ->nullOnDelete();
            $table->text('content');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Composite index for common query: get notes for a case, ordered by time
            $table->index(['service_case_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_case_notes');
    }
};
