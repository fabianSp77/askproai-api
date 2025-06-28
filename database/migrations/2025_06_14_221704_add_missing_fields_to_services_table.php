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
        Schema::table('services', function (Blueprint $table) {
            // Add missing columns from the Service model
            if (!Schema::hasColumn('services', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
            }
            if (!Schema::hasColumn('services', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable();
            }
            if (!Schema::hasColumn('services', 'tenant_id')) {
                $table->uuid('tenant_id')->nullable();
            }
            if (!Schema::hasColumn('services', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('services', 'price')) {
                $table->decimal('price', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('services', 'default_duration_minutes')) {
                $table->integer('default_duration_minutes')->default(30);
            }
            if (!Schema::hasColumn('services', 'active')) {
                $table->boolean('active')->default(true);
            }
            if (!Schema::hasColumn('services', 'category')) {
                $table->string('category')->nullable();
            }
            if (!Schema::hasColumn('services', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            if (!Schema::hasColumn('services', 'min_staff_required')) {
                $table->integer('min_staff_required')->default(1);
            }
            if (!Schema::hasColumn('services', 'max_bookings_per_day')) {
                $table->integer('max_bookings_per_day')->nullable();
            }
            if (!Schema::hasColumn('services', 'buffer_time_minutes')) {
                $table->integer('buffer_time_minutes')->default(0);
            }
            if (!Schema::hasColumn('services', 'is_online_bookable')) {
                $table->boolean('is_online_bookable')->default(true);
            }
            if (!Schema::hasColumn('services', 'calcom_event_type_id')) {
                $table->integer('calcom_event_type_id')->nullable();
            }
            if (!Schema::hasColumn('services', 'duration')) {
                $table->integer('duration')->nullable();
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
    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
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
