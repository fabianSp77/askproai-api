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
        Schema::create('user_filter_presets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('resource', 50); // e.g., 'calls', 'customers', 'appointments'
            $table->string('name', 100);
            $table->json('filters'); // Stored filter configuration
            $table->boolean('is_default')->default(false);
            $table->boolean('is_shared')->default(false); // Can be shared with team
            $table->integer('usage_count')->default(0); // Track popularity
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'resource']);
            $table->index(['resource', 'is_shared']);
            $table->unique(['user_id', 'resource', 'name']);
            
            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_filter_presets');
    }
};