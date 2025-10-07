<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add company_id for multi-tenant isolation in Cal.com host mappings.
     * Ensures hosts can only be mapped to staff within the same company.
     */
    public function up(): void
    {
        Schema::table('calcom_host_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->after('staff_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calcom_host_mappings', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
