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
        Schema::create('help_search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->integer('results_count')->default(0);
            $table->string('clicked_result')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('portal_user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id', 100)->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('query');
            $table->index('created_at');
            $table->index('portal_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_search_queries');
    }
};
