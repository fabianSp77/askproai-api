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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type'); // 'admin' or 'portal'
            $table->string('preference_key'); // e.g., 'calls_columns', 'calls_view_type'
            $table->json('preference_value'); // JSON data for flexibility
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'user_type']);
            $table->unique(['user_id', 'user_type', 'preference_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};