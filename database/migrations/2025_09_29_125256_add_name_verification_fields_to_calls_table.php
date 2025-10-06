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
        
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            // Add verification fields for customer names
            $table->boolean('customer_name_verified')
                  ->default(false)
                  
                  ->comment('Whether the customer name has been verified (true=phone match, false=anonymous)');

            $table->decimal('verification_confidence', 5, 2)
                  ->nullable()
                  
                  ->comment('Confidence level of customer identification (0-100)');

            $table->enum('verification_method', ['phone_match', 'anonymous_name', 'manual', 'unknown'])
                  ->nullable()
                  
                  ->comment('Method used to verify customer identity');

            // Add indexes for filtering
            $table->index('customer_name_verified');
            $table->index('verification_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['customer_name_verified']);
            $table->dropIndex(['verification_method']);
            $table->dropColumn('customer_name_verified');
            $table->dropColumn('verification_confidence');
            $table->dropColumn('verification_method');
        });
    }
};
