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
            // Track consent and data forwarding
            $table->boolean('consent_given')->default(false)->after('customer_id');
            $table->boolean('data_forwarded')->default(false)->after('consent_given');
            $table->timestamp('consent_at')->nullable()->after('data_forwarded');
            $table->timestamp('forwarded_at')->nullable()->after('consent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Drop columns
            $table->dropColumn([
                'consent_given',
                'data_forwarded',
                'consent_at',
                'forwarded_at'
            ]);
        });
    }
};
