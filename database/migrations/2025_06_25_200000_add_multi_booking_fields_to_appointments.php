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
        Schema::table('appointments', function (Blueprint $table) {
            // Multi-appointment booking support
            if (!Schema::hasColumn('appointments', 'parent_appointment_id')) {
                $table->unsignedBigInteger('parent_appointment_id')->nullable()->after('id');
            }
            
            if (!Schema::hasColumn('appointments', 'recurrence_rule')) {
                $this->addJsonColumn($table, 'recurrence_rule', true)->after('metadata');
            }
            
            if (!Schema::hasColumn('appointments', 'series_id')) {
                $table->string('series_id')->nullable()->index()->after('external_id');
            }
            
            if (!Schema::hasColumn('appointments', 'group_booking_id')) {
                $table->string('group_booking_id')->nullable()->index()->after('series_id');
            }
            
            if (!Schema::hasColumn('appointments', 'booking_type')) {
                $table->enum('booking_type', ['single', 'recurring', 'group', 'package'])->default('single')->after('status');
            }
            
            // Package booking support
            if (!Schema::hasColumn('appointments', 'package_sessions_total')) {
                $table->integer('package_sessions_total')->nullable();
            }
            
            if (!Schema::hasColumn('appointments', 'package_sessions_used')) {
                $table->integer('package_sessions_used')->default(0);
            }
            
            if (!Schema::hasColumn('appointments', 'package_expires_at')) {
                $table->date('package_expires_at')->nullable();
            }
            
            // Add foreign key for parent appointment
            $this->addForeignKey($table, 'parent_appointment_id', 'appointments');
        });
        
        // Add indexes for performance using compatible methods
        $this->addIndexIfNotExists('appointments', ['booking_type', 'starts_at']);
        $this->addIndexIfNotExists('appointments', ['customer_id', 'series_id']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip in SQLite due to limitations with dropping columns
        if (!$this->isSQLite()) {
            // Drop indexes first
            $this->dropIndexIfExists('appointments', 'appointments_booking_type_starts_at_index');
            $this->dropIndexIfExists('appointments', 'appointments_customer_id_series_id_index');
            
            // Drop foreign key
            $this->dropForeignKey('appointments', 'appointments_parent_appointment_id_foreign');
            
            Schema::table('appointments', function (Blueprint $table) {
                // Drop columns
                $columnsToRemove = [];
                
                if (Schema::hasColumn('appointments', 'parent_appointment_id')) {
                    $columnsToRemove[] = 'parent_appointment_id';
                }
                if (Schema::hasColumn('appointments', 'recurrence_rule')) {
                    $columnsToRemove[] = 'recurrence_rule';
                }
                if (Schema::hasColumn('appointments', 'series_id')) {
                    $columnsToRemove[] = 'series_id';
                }
                if (Schema::hasColumn('appointments', 'group_booking_id')) {
                    $columnsToRemove[] = 'group_booking_id';
                }
                if (Schema::hasColumn('appointments', 'booking_type')) {
                    $columnsToRemove[] = 'booking_type';
                }
                if (Schema::hasColumn('appointments', 'package_sessions_total')) {
                    $columnsToRemove[] = 'package_sessions_total';
                }
                if (Schema::hasColumn('appointments', 'package_sessions_used')) {
                    $columnsToRemove[] = 'package_sessions_used';
                }
                if (Schema::hasColumn('appointments', 'package_expires_at')) {
                    $columnsToRemove[] = 'package_expires_at';
                }
                
                if (!empty($columnsToRemove)) {
                    $table->dropColumn($columnsToRemove);
                }
            });
        }
    }
};