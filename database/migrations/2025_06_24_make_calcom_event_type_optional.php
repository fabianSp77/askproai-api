<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Drop the foreign key constraint temporarily
        Schema::table('appointments', function (Blueprint $table) {
            $this->dropForeignKey('appointments', 'appointments_calcom_event_type_id_foreign');
        });
        
        // Re-add it with more lenient settings
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('calcom_event_type_id')
                ->references('id')
                ->on('calcom_event_types')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
        
        // Also ensure the column is nullable
        Schema::table('appointments', function (Blueprint $table) {
            $table->bigInteger('calcom_event_type_id')->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Restore original constraint
        Schema::table('appointments', function (Blueprint $table) {
            $this->dropForeignKey('appointments', 'appointments_calcom_event_type_id_foreign');
            
            $table->foreign('calcom_event_type_id')
                ->references('id')
                ->on('calcom_event_types')
                ->onDelete('set null');
        });
    }
};