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
        // Staff Erweiterungen
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'skills')) {
                $this->addJsonColumn($table, 'skills', true)->after('notes');
            }
            if (!Schema::hasColumn('staff', 'languages')) {
                $this->addJsonColumn($table, 'languages', true)->after('skills');
            }
            if (!Schema::hasColumn('staff', 'certifications')) {
                $this->addJsonColumn($table, 'certifications', true)->after('languages');
            }
            if (!Schema::hasColumn('staff', 'experience_level')) {
                $table->integer('experience_level')->default(1)->after('certifications');
            }
            if (!Schema::hasColumn('staff', 'specializations')) {
                $this->addJsonColumn($table, 'specializations', true)->after('experience_level');
            }
        });
        
        // Customer Preferences
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'preferred_branch_id')) {
                $table->uuid('preferred_branch_id')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('customers', 'preferred_staff_id')) {
                $table->uuid('preferred_staff_id')->nullable()->after('preferred_branch_id');
            }
            if (!Schema::hasColumn('customers', 'language_preference')) {
                $table->string('language_preference', 5)->nullable()->after('preferred_staff_id');
            }
            if (!Schema::hasColumn('customers', 'booking_history_summary')) {
                $this->addJsonColumn($table, 'booking_history_summary', true)->after('language_preference');
            }
        });
        
        // Branch Erweiterungen für Multi-Location Features
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'coordinates')) {
                $this->addJsonColumn($table, 'coordinates', true)->after('country');
            }
            if (!Schema::hasColumn('branches', 'features')) {
                $this->addJsonColumn($table, 'features', true)->after('coordinates');
            }
            if (!Schema::hasColumn('branches', 'transport_info')) {
                $this->addJsonColumn($table, 'transport_info', true)->after('features');
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
        
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['skills', 'languages', 'certifications', 'experience_level', 'specializations']);
        });
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['preferred_branch_id', 'preferred_staff_id', 'language_preference', 'booking_history_summary']);
        });
        
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['coordinates', 'features', 'transport_info']);
        });
    }
};