<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if columns already exist and add them if they don't
        if (!Schema::hasColumn('retell_call_sessions', 'branch_id')) {
            Schema::table('retell_call_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('company_id')->index();
            });
        }

        if (!Schema::hasColumn('retell_call_sessions', 'phone_number')) {
            Schema::table('retell_call_sessions', function (Blueprint $table) {
                $table->string('phone_number', 50)->nullable()->after('branch_id');
            });
        }

        if (!Schema::hasColumn('retell_call_sessions', 'branch_name')) {
            Schema::table('retell_call_sessions', function (Blueprint $table) {
                $table->string('branch_name', 255)->nullable()->after('phone_number');
            });
        }

        // Backfill existing data from calls table (only where valid branch exists)
        // Using try-catch to handle strict mode warnings
        try {
            DB::statement("SET SESSION sql_mode = ''");
            DB::statement("
                UPDATE retell_call_sessions rcs
                INNER JOIN calls c ON rcs.call_id = c.external_id
                INNER JOIN branches b ON c.branch_id = b.id
                SET
                    rcs.branch_id = b.id,
                    rcs.phone_number = b.phone_number,
                    rcs.branch_name = b.name
                WHERE rcs.branch_id IS NULL
                  AND c.branch_id IS NOT NULL
            ");
            DB::statement("SET SESSION sql_mode = 'TRADITIONAL'");
        } catch (\Exception $e) {
            // Skip backfill if it fails - new calls will have the data
            \Log::warning('Backfill skipped: ' . $e->getMessage());
        }

        // Add foreign key constraint after backfilling data (check if it doesn't exist)
        // This is optional - if it fails, the columns will still work
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'retell_call_sessions'
                AND CONSTRAINT_NAME = 'retell_call_sessions_branch_id_foreign'
            ");

            if (empty($foreignKeys)) {
                Schema::table('retell_call_sessions', function (Blueprint $table) {
                    $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                });
            }
        } catch (\Exception $e) {
            // Foreign key is optional - columns will still work without it
            \Log::warning('Foreign key creation skipped: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retell_call_sessions', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'phone_number', 'branch_name']);
        });
    }
};
