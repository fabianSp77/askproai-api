<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Erstelle staff_branches falls nicht vorhanden
        if (!Schema::hasTable('staff_branches')) {
            Schema::create('staff_branches', function (Blueprint $table) {
                $table->id();
                $table->uuid('staff_id');
                $table->uuid('branch_id');
                $table->timestamps();
                
                $table->unique(['staff_id', 'branch_id']);
                $table->index('staff_id');
                $table->index('branch_id');
            });
        }
        
        // Erstelle staff_services falls nicht vorhanden
        if (!Schema::hasTable('staff_services')) {
            Schema::create('staff_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('staff_id');
                $table->uuid('service_id');
                $table->timestamps();
                
                $table->unique(['staff_id', 'service_id']);
                $table->index('staff_id');
                $table->index('service_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_branches');
        Schema::dropIfExists('staff_services');
    }
};
