<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prüfe ob staff_services existiert, wenn nicht, erstelle sie
        if (!Schema::hasTable('staff_services')) {
            if (Schema::hasTable('staff_service')) {
                // Benenne staff_service zu staff_services um
                Schema::rename('staff_service', 'staff_services');
            } else {
                // Erstelle staff_services neu
                Schema::create('staff_services', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
                    $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
                    $table->timestamps();
                    
                    $table->unique(['staff_id', 'service_id']);
                });
            }
        }
        
        // Stelle sicher, dass branch_staff existiert und korrekt benannt ist
        if (Schema::hasTable('branch_staff') && !Schema::hasTable('staff_branches')) {
            Schema::rename('branch_staff', 'staff_branches');
        }
    }

    public function down(): void
    {
        // Optional: Rückgängig machen
        if (Schema::hasTable('staff_services')) {
            Schema::rename('staff_services', 'staff_service');
        }
        
        if (Schema::hasTable('staff_branches')) {
            Schema::rename('staff_branches', 'branch_staff');
        }
    }
};
