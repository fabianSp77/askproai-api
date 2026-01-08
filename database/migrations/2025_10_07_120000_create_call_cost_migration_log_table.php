<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Audit table for call cost migration - enables full rollback capability
     * and provides complete audit trail for compliance.
     */
    public function up(): void
    {
        if (Schema::hasTable('call_cost_migration_log')) {
            return;
        }

        Schema::create('call_cost_migration_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->string('migration_batch', 50);

            // Old values (before migration)
            $table->decimal('old_retell_cost_usd', 10, 6)->nullable();
            $table->integer('old_retell_cost_eur_cents')->nullable();
            $table->integer('old_base_cost')->nullable();
            $table->decimal('old_exchange_rate_used', 10, 6)->nullable();

            // New values (after migration)
            $table->decimal('new_retell_cost_usd', 10, 6)->nullable();
            $table->integer('new_retell_cost_eur_cents')->nullable();
            $table->integer('new_base_cost')->nullable();
            $table->decimal('new_exchange_rate_used', 10, 6)->nullable();

            // Metadata
            $table->json('cost_breakdown_source')->nullable();
            $table->string('migration_reason')->nullable();
            $table->string('status', 50)->default('success'); // success, error, skipped, flagged
            $table->text('error_message')->nullable();

            $table->timestamp('migrated_at')->useCurrent();

            // Indexes for performance
            $table->index('call_id');
            $table->index('migration_batch');
            $table->index('status');
            $table->index('migrated_at');

            // Foreign key (optional - depends on your schema)
            // $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_cost_migration_log');
    }
};
