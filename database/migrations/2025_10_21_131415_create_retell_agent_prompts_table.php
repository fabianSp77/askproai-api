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
        Schema::create('retell_agent_prompts', function (Blueprint $table) {
            $table->id();
            $table->char('branch_id', 36);
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->longText('prompt_content');
            $table->json('functions_config');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_template')->default(false);
            $table->string('template_name')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->foreignId('deployed_by')->nullable()->constrained('users')->nullifyOnDelete();
            $table->string('retell_agent_id')->nullable();
            $table->unsignedInteger('retell_version')->nullable();
            $table->enum('validation_status', ['pending', 'valid', 'invalid'])->default('pending');
            $table->json('validation_errors')->nullable();
            $table->text('deployment_notes')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['branch_id', 'is_active']);
            $table->index(['branch_id', 'version']);
            $table->unique(['branch_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retell_agent_prompts');
    }
};
