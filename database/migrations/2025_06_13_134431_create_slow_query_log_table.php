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
        Schema::create('slow_query_log', function (Blueprint $table) {
            $table->id();
            $table->text('sql');
            $table->float('time'); // Execution time in milliseconds
            $table->string('connection', 50);
            $table->json('backtrace')->nullable();
            $table->timestamp('created_at');
            
            // Indexes for analysis
            $table->index('created_at');
            $table->index('time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slow_query_log');
    }
};