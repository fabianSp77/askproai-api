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
        // Add fields to customers table for preferences
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'preferred_branch_id')) {
                $table->uuid('preferred_branch_id')->nullable();
                $table->foreign('preferred_branch_id')->references('id')->on('branches')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('customers', 'preferred_staff_id')) {
                $table->uuid('preferred_staff_id')->nullable()->after('preferred_branch_id');
                $table->foreign('preferred_staff_id')->references('id')->on('staff')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('customers', 'location_data')) {
                $this->addJsonColumn($table, 'location_data', true)->comment('Customer location info (city, postal code, coordinates)');
            }
        });
        
        // Add fields to staff table for multi-location support
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'skills')) {
                $this->addJsonColumn($table, 'skills', true)->comment('Array of skill identifiers');
            }
            
            if (!Schema::hasColumn('staff', 'languages')) {
                $this->addJsonColumn($table, 'languages', true)->default('["de"]')->comment('Array of language codes');
            }
            
            if (!Schema::hasColumn('staff', 'mobility_radius_km')) {
                $table->integer('mobility_radius_km')->nullable()->comment('How far staff can travel between branches');
            }
            
            if (!Schema::hasColumn('staff', 'specializations')) {
                $this->addJsonColumn($table, 'specializations', true)->comment('Special certifications or expertise');
            }
            
            if (!Schema::hasColumn('staff', 'average_rating')) {
                $table->decimal('average_rating', 3, 2)->nullable()->comment('Average customer rating');
            }
            
            if (!Schema::hasColumn('staff', 'certifications')) {
                $this->addJsonColumn($table, 'certifications', true)->comment('Professional certifications');
            }
        });
        
        // Add fields to services table for skill requirements
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'required_skills')) {
                $this->addJsonColumn($table, 'required_skills', true)->comment('Skills required to perform this service');
            }
            
            if (!Schema::hasColumn('services', 'required_certifications')) {
                $this->addJsonColumn($table, 'required_certifications', true)->comment('Certifications required');
            }
            
            if (!Schema::hasColumn('services', 'complexity_level')) {
                $table->enum('complexity_level', ['basic', 'intermediate', 'advanced', 'expert'])->default('basic');
            }
        });
        
        // Add fields to branches table for location
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'coordinates')) {
                $this->addJsonColumn($table, 'coordinates', true)->comment('Latitude and longitude');
            }
            
            if (!Schema::hasColumn('branches', 'service_radius_km')) {
                $table->integer('service_radius_km')->nullable()->default(0)->comment('Service area radius');
            }
            
            if (!Schema::hasColumn('branches', 'accepts_walkins')) {
                $table->boolean('accepts_walkins')->default(false);
            }
            
            if (!Schema::hasColumn('branches', 'parking_available')) {
                $table->boolean('parking_available')->default(false);
            }
            
            if (!Schema::hasColumn('branches', 'public_transport_access')) {
                $table->text('public_transport_access')->nullable();
            }
        });
        
        // Create staff_branches junction table for multi-branch staff
        if (!Schema::hasTable('staff_branches')) {
            $this->createTableIfNotExists('staff_branches', function (Blueprint $table) {
                $table->uuid('staff_id');
                $table->uuid('branch_id');
                $table->boolean('is_primary')->default(false);
                $this->addJsonColumn($table, 'working_days', true)->comment('Specific days at this branch');
                $table->decimal('travel_compensation', 8, 2)->nullable();
                $table->timestamps();
                
                $table->primary(['staff_id', 'branch_id']);
                $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                
                $table->index('branch_id');
            });
        }
        
        // Add fields to appointments for multi-location tracking
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'booking_metadata')) {
                $this->addJsonColumn($table, 'booking_metadata', true)->comment('Booking context (search criteria, alternatives shown, etc)');
            }
            
            if (!Schema::hasColumn('appointments', 'travel_time_minutes')) {
                $table->integer('travel_time_minutes')->nullable()->comment('Estimated customer travel time');
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
        
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['booking_metadata', 'travel_time_minutes']);
        });
        
        $this->dropTableIfExists('staff_branches');
        
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['coordinates', 'service_radius_km', 'accepts_walkins', 'parking_available', 'public_transport_access']);
        });
        
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['required_skills', 'required_certifications', 'complexity_level']);
        });
        
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['skills', 'languages', 'mobility_radius_km', 'specializations', 'average_rating', 'certifications']);
        });
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['preferred_branch_id']);
            $table->dropForeign(['preferred_staff_id']);
            $table->dropColumn(['preferred_branch_id', 'preferred_staff_id', 'location_data']);
        });
    }
};
