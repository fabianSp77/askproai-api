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
        Schema::table('calls', function (Blueprint $table) {
            // Dedicated backup field for customer data
            if (!Schema::hasColumn('calls', 'customer_data_backup')) {
                $table->json('customer_data_backup')->nullable()->after('custom_analysis_data');
            }
            if (!Schema::hasColumn('calls', 'customer_data_collected_at')) {
                $table->timestamp('customer_data_collected_at')->nullable()->after('customer_data_backup');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('customer_data_backup');
            $table->dropColumn('customer_data_collected_at');
        });
    }
};
