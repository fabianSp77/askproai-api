<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_hours_templates')) {
            Schema::create('business_hours_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->json('hours');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_hours_templates');
    }
};
