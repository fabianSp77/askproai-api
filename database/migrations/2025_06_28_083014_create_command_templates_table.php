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
        Schema::create('command_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->string('category')->default('general');
            $table->text('description')->nullable();
            $table->text('command_template');
            $table->json('parameters')->nullable();
            $table->json('nlp_keywords')->nullable();
            $table->string('shortcut')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->integer('usage_count')->default(0);
            $table->float('success_rate')->default(100);
            $table->float('avg_execution_time')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('category');
            $table->index('is_public');
            $table->index('company_id');
            $table->index('created_by');
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_templates');
    }
};
