<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        // Erstelle staff_branches falls nicht vorhanden
        if (!Schema::hasTable('staff_branches')) {
            $this->createTableIfNotExists('staff_branches', function (Blueprint $table) {
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
            $this->createTableIfNotExists('staff_services', function (Blueprint $table) {
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
        $this->dropTableIfExists('staff_branches');
        $this->dropTableIfExists('staff_services');
    }
};
