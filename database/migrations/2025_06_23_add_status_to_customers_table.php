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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('status')->default('active')->after('company_id');
            $table->string('customer_type')->default('private')->nullable()->after('status');
            $table->integer('no_show_count')->default(0)->after('notes');
            $table->integer('appointment_count')->default(0)->after('no_show_count');
            $table->boolean('is_vip')->default(false)->after('appointment_count');
            $table->date('birthday')->nullable()->after('birthdate');
            $table->integer('sort_order')->nullable()->after('birthday');
            
            // Add indexes for performance
            $table->index('status');
            $table->index('customer_type');
            $table->index(['company_id', 'status']);
        });
        
        // Update existing customers to have active status
        \DB::table('customers')->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
            $table->dropIndex(['customer_type']);
            $table->dropIndex(['status']);
            
            $table->dropColumn([
                'status',
                'customer_type',
                'no_show_count',
                'appointment_count',
                'is_vip',
                'birthday',
                'sort_order'
            ]);
        });
    }
};