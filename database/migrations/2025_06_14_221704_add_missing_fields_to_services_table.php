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
        Schema::table('services', function (Blueprint $table) {
            // Add missing columns from the Service model
            if (!Schema::hasColumn('services', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('services', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('services', 'tenant_id')) {
                $table->uuid('tenant_id')->nullable()->after('branch_id');
            }
            if (!Schema::hasColumn('services', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('services', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('description');
            }
            if (!Schema::hasColumn('services', 'default_duration_minutes')) {
                $table->integer('default_duration_minutes')->default(30)->after('price');
            }
            if (!Schema::hasColumn('services', 'active')) {
                $table->boolean('active')->default(true)->after('default_duration_minutes');
            }
            if (!Schema::hasColumn('services', 'category')) {
                $table->string('category')->nullable()->after('active');
            }
            if (!Schema::hasColumn('services', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('category');
            }
            if (!Schema::hasColumn('services', 'min_staff_required')) {
                $table->integer('min_staff_required')->default(1)->after('sort_order');
            }
            if (!Schema::hasColumn('services', 'max_bookings_per_day')) {
                $table->integer('max_bookings_per_day')->nullable()->after('min_staff_required');
            }
            if (!Schema::hasColumn('services', 'buffer_time_minutes')) {
                $table->integer('buffer_time_minutes')->default(0)->after('max_bookings_per_day');
            }
            if (!Schema::hasColumn('services', 'is_online_bookable')) {
                $table->boolean('is_online_bookable')->default(true)->after('buffer_time_minutes');
            }
            if (!Schema::hasColumn('services', 'calcom_event_type_id')) {
                $table->integer('calcom_event_type_id')->nullable()->after('is_online_bookable');
            }
            if (!Schema::hasColumn('services', 'duration')) {
                $table->integer('duration')->nullable()->after('calcom_event_type_id');
            }
            if (!Schema::hasColumn('services', 'deleted_at')) {
                $table->softDeletes();
            }
            
            // Add indexes
            if (!Schema::hasIndex('services', 'services_company_id_index')) {
                $table->index('company_id');
            }
            if (!Schema::hasIndex('services', 'services_branch_id_index')) {
                $table->index('branch_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'company_id',
                'branch_id', 
                'tenant_id',
                'description',
                'price',
                'default_duration_minutes',
                'active',
                'category',
                'sort_order',
                'min_staff_required',
                'max_bookings_per_day',
                'buffer_time_minutes',
                'is_online_bookable',
                'calcom_event_type_id',
                'duration'
            ]);
            $table->dropSoftDeletes();
        });
    }
};
