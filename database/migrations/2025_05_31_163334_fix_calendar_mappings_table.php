<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        // Prüfe ob die Tabelle bereits existiert
        if (Schema::hasTable('calendar_mappings')) {
            // Lösche die alte Tabelle
            $this->dropTableIfExists('calendar_mappings');
        }
        
        // Erstelle die Tabelle neu mit dem richtigen Spaltentyp
        $this->createTableIfNotExists('calendar_mappings', function (Blueprint $table) {
            $table->id();
            $table->char('branch_id', 36);
            $table->char('staff_id', 36); // Geändert von unsignedBigInteger zu char(36)
            $table->enum('calendar_type', ['company', 'branch', 'personal']);
            $this->addJsonColumn($table, 'calendar_details', true);
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');

            $table->index(['branch_id', 'staff_id']);
        });
    }

    public function down()
    {
        $this->dropTableIfExists('calendar_mappings');
    }
};
