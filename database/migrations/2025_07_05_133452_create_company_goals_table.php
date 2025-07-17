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
        Schema::create('company_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('template_type')->nullable(); // e.g., 'max_appointments', 'data_collection', 'revenue_optimization', 'custom'
            $table->json('configuration')->nullable(); // Additional configuration data
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_goals');
    }
};
