<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add version field to appointments for optimistic locking
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(0)->after('updated_at');
            $table->index('version');
        });
        
        // Add version field to calls for optimistic locking
        Schema::table('calls', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(0)->after('updated_at');
            $table->index('version');
        });
        
        // Add lock_expires_at to appointments for pessimistic locking during booking
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('lock_expires_at')->nullable()->after('version');
            $table->string('lock_token')->nullable()->after('lock_expires_at');
            $table->index(['lock_expires_at', 'lock_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('version');
            $table->dropColumn('lock_expires_at');
            $table->dropColumn('lock_token');
        });
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
