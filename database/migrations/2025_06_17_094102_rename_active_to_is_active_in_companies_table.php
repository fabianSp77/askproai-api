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
        Schema::table('companies', function (Blueprint $table) {
            // Check if 'active' column exists and 'is_active' doesn't
            if (Schema::hasColumn('companies', 'active') && !Schema::hasColumn('companies', 'is_active')) {
                $table->renameColumn('active', 'is_active');
            }
            // If 'is_active' already exists, do nothing
            elseif (!Schema::hasColumn('companies', 'is_active') && !Schema::hasColumn('companies', 'active')) {
                // Neither column exists, create is_active
                $table->boolean('is_active')->default(true)->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'is_active') && !Schema::hasColumn('companies', 'active')) {
                $table->renameColumn('is_active', 'active');
            }
        });
    }
};
