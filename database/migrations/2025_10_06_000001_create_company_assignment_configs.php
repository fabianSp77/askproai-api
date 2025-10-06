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
        Schema::create('company_assignment_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            // Business model selection
            $table->enum('assignment_model', [
                'any_staff',      // Modell 1: Egal wer (first available)
                'service_staff',  // Modell 2: Nur Qualifizierte (service restrictions)
            ])->default('any_staff');

            // Fallback when primary model fails
            $table->enum('fallback_model', [
                'any_staff',
                'service_staff',
            ])->nullable();

            // Model-specific configuration
            $table->json('config_metadata')->nullable()
                ->comment('Model settings, timeouts, preferences, etc.');

            // Version control
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Constraints
            $table->unique(['company_id', 'is_active'], 'unique_company_active');
            $table->index(['company_id', 'assignment_model'], 'idx_company_model');
            $table->index(['assignment_model', 'is_active'], 'idx_model_active');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_assignment_configs');
    }
};
