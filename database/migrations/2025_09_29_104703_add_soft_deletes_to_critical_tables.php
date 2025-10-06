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
        // Add soft delete to branches
        if (!Schema::hasColumn('branches', 'deleted_at')) {
            
        if (!Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft delete to staff
        if (!Schema::hasColumn('staff', 'deleted_at')) {
            
        if (!Schema::hasTable('staff')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft delete to customers
        if (!Schema::hasColumn('customers', 'deleted_at')) {
            
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft delete to calls
        if (!Schema::hasColumn('calls', 'deleted_at')) {
            
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft delete to appointments
        if (!Schema::hasColumn('appointments', 'deleted_at')) {
            
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft delete to phone_numbers
        if (!Schema::hasColumn('phone_numbers', 'deleted_at')) {
            
        if (!Schema::hasTable('phone_numbers')) {
            return;
        }

        Schema::table('phone_numbers', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft delete to services if not exists
        if (!Schema::hasColumn('services', 'deleted_at')) {
            
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove soft delete from branches
        if (Schema::hasColumn('branches', 'deleted_at')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove soft delete from staff
        if (Schema::hasColumn('staff', 'deleted_at')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove soft delete from customers
        if (Schema::hasColumn('customers', 'deleted_at')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove soft delete from calls
        if (Schema::hasColumn('calls', 'deleted_at')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove soft delete from appointments
        if (Schema::hasColumn('appointments', 'deleted_at')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove soft delete from phone_numbers
        if (Schema::hasColumn('phone_numbers', 'deleted_at')) {
            Schema::table('phone_numbers', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Remove soft delete from services
        if (Schema::hasColumn('services', 'deleted_at')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};