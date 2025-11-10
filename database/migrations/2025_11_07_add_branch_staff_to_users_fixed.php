<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Branch assignment for company_manager role
            if (!Schema::hasColumn('users', 'branch_id')) {
                $table->uuid('branch_id')  // UUID statt unsignedBigInteger!
                    ->nullable()
                    ->after('company_id')
                    ->comment('Branch assignment for company_manager role');

                $table->foreign('branch_id')
                    ->references('id')
                    ->on('branches')
                    ->onDelete('set null')
                    ->onUpdate('cascade');

                $table->index('branch_id');
            }

            // Staff relationship for company_staff role
            if (!Schema::hasColumn('users', 'staff_id')) {
                // Check staff.id type
                $staffIdType = Schema::getColumnType('staff', 'id');
                
                if ($staffIdType === 'char') {
                    $table->uuid('staff_id')->nullable();
                } else {
                    $table->unsignedBigInteger('staff_id')->nullable();
                }
                
                $table->foreign('staff_id')
                    ->references('id')
                    ->on('staff')
                    ->onDelete('set null')
                    ->onUpdate('cascade');

                $table->index('staff_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropIndex(['branch_id']);
                $table->dropColumn('branch_id');
            }

            if (Schema::hasColumn('users', 'staff_id')) {
                $table->dropForeign(['staff_id']);
                $table->dropIndex(['staff_id']);
                $table->dropColumn('staff_id');
            }
        });
    }
};
