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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'status')) {
                $table->string('status')->default('active')->after('company_id');
            }
            if (!Schema::hasColumn('customers', 'customer_type')) {
                $table->string('customer_type')->default('private')->nullable()->after('status');
            }
            if (!Schema::hasColumn('customers', 'no_show_count')) {
                $table->integer('no_show_count')->default(0)->after('notes');
            }
            if (!Schema::hasColumn('customers', 'appointment_count')) {
                $table->integer('appointment_count')->default(0)->after('no_show_count');
            }
            if (!Schema::hasColumn('customers', 'is_vip')) {
                $table->boolean('is_vip')->default(false)->after('appointment_count');
            }
            if (!Schema::hasColumn('customers', 'birthday')) {
                $table->date('birthday')->nullable()->after('is_vip');
            }
            if (!Schema::hasColumn('customers', 'sort_order')) {
                $table->integer('sort_order')->nullable()->after('birthday');
            }
        });
        
        // Add indexes for performance if they don't exist
        if (!$this->indexExists('customers', 'customers_status_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('status');
            });
        }
        if (!$this->indexExists('customers', 'customers_customer_type_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('customer_type');
            });
        }
        if (!$this->indexExists('customers', 'customers_company_id_status_index')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index(['company_id', 'status']);
            });
        }
        
        // Update existing customers to have active status
        if (Schema::hasColumn('customers', 'status')) {
            \DB::table('customers')->whereNull('status')->update(['status' => 'active']);
        }
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