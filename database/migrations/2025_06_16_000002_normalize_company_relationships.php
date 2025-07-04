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
        // Add company_id to branches if it doesn't exist
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->index('company_id');
            }
        });

        // Update branches to use company_id from customers table
        if (Schema::hasColumn('branches', 'customer_id') && Schema::hasColumn('branches', 'company_id')) {
            if ($this->isSQLite()) {
                // SQLite doesn't support UPDATE with JOIN
                $customers = DB::table('customers')
                    ->whereNotNull('company_id')
                    ->get();
                    
                foreach ($customers as $customer) {
                    DB::table('branches')
                        ->where('customer_id', $customer->id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $customer->company_id]);
                }
            } else {
                DB::statement('
                    UPDATE branches b
                    JOIN customers c ON b.customer_id = c.id
                    SET b.company_id = c.company_id
                    WHERE b.company_id IS NULL AND c.company_id IS NOT NULL
                ');
            }
        }

        // Add company_id to staff if it doesn't exist
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->index('company_id');
            }
        });

        // Update staff to use company_id from branches
        if (Schema::hasColumn('staff', 'branch_id') && Schema::hasColumn('staff', 'company_id')) {
            if ($this->isSQLite()) {
                // SQLite doesn't support UPDATE with JOIN
                $branches = DB::table('branches')
                    ->whereNotNull('company_id')
                    ->get();
                    
                foreach ($branches as $branch) {
                    DB::table('staff')
                        ->where('branch_id', $branch->id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $branch->company_id]);
                }
            } else {
                DB::statement('
                    UPDATE staff s
                    JOIN branches b ON s.branch_id = b.id
                    SET s.company_id = b.company_id
                    WHERE s.company_id IS NULL AND b.company_id IS NOT NULL
                ');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove company_id from staff
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });

        // Remove company_id from branches
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });
    }
};
