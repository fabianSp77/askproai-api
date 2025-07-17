<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('onboarding_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->integer('current_step')->default(1);
            $table->json('completed_steps')->default('[]');
            $table->json('state_data')->nullable();
            $table->integer('time_elapsed')->default(0); // in seconds
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->string('industry_template')->nullable();
            $table->integer('completion_percentage')->default(0);
            $table->timestamps();
            
            $table->index(['company_id', 'is_completed']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('onboarding_states');
    }
};