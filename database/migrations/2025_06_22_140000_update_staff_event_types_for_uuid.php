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
        // Drop the existing table if it exists
        Schema::dropIfExists('staff_event_types');
        
        // Create new table with UUID support for staff, but keep bigint for calcom_event_type_id
        Schema::create('staff_event_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('staff_id');
            $table->unsignedBigInteger('calcom_event_type_id');
            $table->string('calcom_user_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('custom_duration')->nullable();
            $table->decimal('custom_price', 10, 2)->nullable();
            $table->json('availability_override')->nullable();
            $table->integer('max_bookings_per_day')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index('staff_id');
            $table->index('calcom_event_type_id');
            $table->unique(['staff_id', 'calcom_event_type_id']);
            
            // Foreign keys
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('calcom_event_type_id')->references('id')->on('calcom_event_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_event_types');
        
        // Recreate the old structure
        Schema::create('staff_event_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('event_type_id');
            $table->boolean('is_primary')->default(false);
            $table->integer('max_bookings_per_day')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('event_type_id')->references('id')->on('calcom_event_types')->onDelete('cascade');
            $table->unique(['staff_id', 'event_type_id']);
        });
    }
};