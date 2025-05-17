<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Erstellt die Pivot-Tabelle branch_staff nur,
     * wenn sie nicht bereits existiert.
     */
    public function up(): void
    {
        if (! Schema::hasTable('branch_staff')) {
            Schema::create('branch_staff', function (Blueprint $table) {
                $table->char('branch_id', 36);
                $table->unsignedBigInteger('staff_id');
                $table->timestamps();

                $table->primary(['branch_id', 'staff_id']);
            });
        }
    }

    /**
     * Rollback: Tabelle wieder löschen,
     * aber nur falls sie wirklich existiert.
     */
    public function down(): void
    {
        if (Schema::hasTable('branch_staff')) {
            Schema::dropIfExists('branch_staff');
        }
    }
};
