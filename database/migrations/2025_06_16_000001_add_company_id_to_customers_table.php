<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->index('company_id');
            }
        });

        // Update existing customers to have company_id based on their appointments
        if (Schema::hasColumn('customers', 'company_id')) {
            if ($this->isSQLite()) {
                // SQLite doesn't support UPDATE with JOIN, use a different approach
                $appointments = DB::table('appointments')
                    ->select('customer_id', 'company_id')
                    ->whereNotNull('company_id')
                    ->distinct()
                    ->get();
                    
                foreach ($appointments as $appointment) {
                    DB::table('customers')
                        ->where('id', $appointment->customer_id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $appointment->company_id]);
                }
            } else {
                // MySQL/MariaDB supports UPDATE with JOIN
                DB::statement('
                    UPDATE customers c
                    JOIN (
                        SELECT DISTINCT customer_id, company_id 
                        FROM appointments 
                        WHERE company_id IS NOT NULL
                    ) a ON c.id = a.customer_id
                    SET c.company_id = a.company_id
                    WHERE c.company_id IS NULL
                ');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });
    }
};
