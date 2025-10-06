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
        // Add unique constraint to email column (allowing NULLs)
        
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            // First ensure no duplicate emails exist
            DB::statement("UPDATE customers c1 INNER JOIN (
                SELECT email, MIN(id) as keep_id
                FROM customers
                WHERE email IS NOT NULL AND email != ''
                GROUP BY email
            ) c2 ON c1.email = c2.email AND c1.id != c2.keep_id
            SET c1.email = CONCAT(c1.email, '_duplicate_', c1.id)");

            // Add unique index (NULLs are allowed)
            $table->unique('email', 'customers_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_email_unique');
        });
    }
};