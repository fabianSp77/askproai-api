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
        if (!Schema::hasColumn('companies', 'trial_ends_at')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->timestamp('trial_ends_at')->nullable()->after('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('companies', 'trial_ends_at')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('trial_ends_at');
            });
        }
    }
};