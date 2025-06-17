<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            // First, allow null values temporarily
            $table->char('branch_id_new', 36)->nullable()->after('branch_id');
        });
        
        // Copy data if any exists
        DB::statement('UPDATE appointments SET branch_id_new = branch_id WHERE branch_id IS NOT NULL');
        
        Schema::table('appointments', function (Blueprint $table) {
            // Drop the old column
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            
            // Rename the new column
            $table->renameColumn('branch_id_new', 'branch_id');
            
            // Add foreign key
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Reverse the migration
            $table->bigInteger('branch_id_old')->unsigned()->nullable()->after('branch_id');
        });
        
        // Note: This will fail if UUIDs can't be converted to integers
        DB::statement('UPDATE appointments SET branch_id_old = branch_id WHERE branch_id IS NOT NULL');
        
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->renameColumn('branch_id_old', 'branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }
};