<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('query');
            $table->string('selected_type')->nullable();
            $table->unsignedBigInteger('selected_id')->nullable();
            $table->string('context')->nullable(); // Where the search was performed
            $table->integer('results_count')->default(0);
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('query');
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_history');
    }
};