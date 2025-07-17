<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('industry_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_en');
            $table->string('icon')->default('heroicon-o-building-office');
            $table->text('description');
            $table->json('default_services'); // Pre-configured services
            $table->json('default_hours'); // Default working hours
            $table->json('ai_personality'); // Default AI agent personality
            $table->json('common_questions'); // Industry-specific FAQs
            $table->json('booking_rules'); // Industry-specific rules
            $table->integer('setup_time_estimate')->default(300); // seconds
            $table->integer('popularity_score')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('industry_templates');
    }
};