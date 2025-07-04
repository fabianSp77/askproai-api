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
        if (!Schema::hasTable('ml_job_progress')) {
            Schema::create('ml_job_progress', function (Blueprint $table) {
                $table->id();
                $table->string('job_id')->unique();
                $table->string('job_type'); // 'training' or 'analysis'
                $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
                $table->integer('total_items')->default(0);
                $table->integer('processed_items')->default(0);
                $table->decimal('progress_percentage', 5, 2)->default(0);
                $table->string('current_step')->nullable();
                $table->text('message')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                
                $table->index('status');
                $table->index('job_type');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_job_progress');
    }
};