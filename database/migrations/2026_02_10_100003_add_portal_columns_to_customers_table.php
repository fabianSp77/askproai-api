<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * H2 + H3: Add portal authentication columns to customers table
     * - portal_access_token (line 97): guarded, hidden field
     * - portal_token_expires_at (line 80): datetime cast
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'portal_access_token')) {
                $table->string('portal_access_token', 64)->nullable()->unique()->after('email_verified_at');
            }

            if (!Schema::hasColumn('customers', 'portal_token_expires_at')) {
                $table->timestamp('portal_token_expires_at')->nullable()->after('portal_access_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'portal_token_expires_at')) {
                $table->dropColumn('portal_token_expires_at');
            }

            if (Schema::hasColumn('customers', 'portal_access_token')) {
                $table->dropColumn('portal_access_token');
            }
        });
    }
};
