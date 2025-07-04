<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure company_id exists on users table
        if (!Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('email');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            });
        }
        
        // Copy data from tenant_id to company_id if needed
        if (Schema::hasColumn('users', 'tenant_id') && Schema::hasColumn('users', 'company_id')) {
            \DB::table('users')
                ->whereNotNull('tenant_id')
                ->whereNull('company_id')
                ->update(['company_id' => \DB::raw('tenant_id')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't reverse this as it's a consistency fix
    }
};