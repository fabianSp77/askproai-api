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
        // Skip if table already exists
        if (Schema::hasTable('service_case_categories')) {
            return;
        }

        Schema::create('service_case_categories', function (Blueprint $table) {
            $table->id();

            // Multi-tenant isolation
            $table->unsignedBigInteger('company_id');

            // Category identity
            $table->string('name', 100);
            $table->string('slug', 100);

            // Hierarchical structure
            $table->unsignedBigInteger('parent_id')->nullable();

            // AI intent matching
            $table->json('intent_keywords')->nullable();
            $table->decimal('confidence_threshold', 3, 2)->default(0.70);

            // Default case configuration
            $table->enum('default_case_type', ['incident', 'request', 'inquiry'])->nullable();
            $table->enum('default_priority', ['critical', 'high', 'normal', 'low'])->nullable();

            // Output configuration
            $table->unsignedBigInteger('output_configuration_id')->nullable();

            // Status and ordering
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Standard timestamps
            $table->timestamps();

            // Unique constraint for slug per company
            $table->unique(['company_id', 'slug']);

            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index(['parent_id']);
            $table->index('sort_order');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('service_case_categories')->onDelete('cascade');
            $table->foreign('output_configuration_id')->references('id')->on('service_output_configurations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_case_categories');
    }
};
