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
        Schema::create('agent_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('retell_agent_id')->constrained('retell_agents')->onDelete('cascade');
            
            // Assignment criteria
            $table->string('assignment_type'); // time_based, service_based, branch_based, customer_segment
            $table->json('criteria'); // Specific criteria for assignment
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            
            // Time-based assignments
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('days_of_week')->nullable(); // [1,2,3,4,5] for Mon-Fri
            
            // Service-based assignments
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('cascade');
            
            // Branch-based assignments
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            
            // A/B testing
            $table->boolean('is_test')->default(false);
            $table->integer('traffic_percentage')->nullable(); // For A/B testing
            $table->dateTime('test_start_date')->nullable();
            $table->dateTime('test_end_date')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'is_active', 'priority']);
            $table->index(['company_id', 'assignment_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_assignments');
    }
};