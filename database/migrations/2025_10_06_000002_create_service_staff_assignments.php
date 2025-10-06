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
        Schema::create('service_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('service_id');
            $table->char('staff_id', 36);  // UUID format to match staff table

            // Priority for assignment (lower = higher priority)
            $table->integer('priority_order')->default(0)
                ->comment('Lower = higher priority. 0 = highest, 999 = lowest');

            // Temporal validity
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable()
                ->comment('Start date for assignment');
            $table->date('effective_until')->nullable()
                ->comment('End date for assignment');

            $table->timestamps();

            // Constraints
            $table->unique(['service_id', 'staff_id', 'is_active'], 'unique_service_staff');
            $table->index(['service_id', 'is_active', 'priority_order'], 'idx_service_lookup');
            $table->index(['staff_id', 'is_active'], 'idx_staff_lookup');
            $table->index(['company_id', 'service_id'], 'idx_company_service');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->onDelete('cascade');

            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_staff_assignments');
    }
};
