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
        // Check if the table exists first
        if (!Schema::hasTable('laravel_users')) {
            return;
        }
        
        if (!Schema::hasColumn('laravel_users', 'email_verified_at')) {
            Schema::table('laravel_users', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the table exists first
        if (!Schema::hasTable('laravel_users')) {
            return;
        }
        
        if (Schema::hasColumn('laravel_users', 'email_verified_at')) {
            Schema::table('laravel_users', function (Blueprint $table) {
                $table->dropColumn('email_verified_at');
            });
        }
    }
};