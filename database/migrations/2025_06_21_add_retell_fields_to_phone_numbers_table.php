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
        Schema::table('phone_numbers', function (Blueprint $table) {
            // Add company_id for multi-tenancy
            if (!Schema::hasColumn('phone_numbers', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $this->addForeignKey($table, 'company_id', 'companies');
            }
            
            // Add Retell-specific fields
            if (!Schema::hasColumn('phone_numbers', 'retell_phone_id')) {
                $table->string('retell_phone_id')->nullable()->after('number');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'retell_agent_id')) {
                $table->string('retell_agent_id')->nullable()->after('retell_phone_id');
            }
            
            // Add is_primary flag
            if (!Schema::hasColumn('phone_numbers', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('active');
            }
            
            // Add type field
            if (!Schema::hasColumn('phone_numbers', 'type')) {
                $table->string('type', 50)->default('office')->after('is_primary');
                // Types: office, mobile, fax, retell, hotline
            }
            
            // Rename active to is_active for consistency (skip in SQLite)
            if (!$this->isSQLite() && Schema::hasColumn('phone_numbers', 'active') && !Schema::hasColumn('phone_numbers', 'is_active')) {
                $table->renameColumn('active', 'is_active');
            }
            
            // Add capabilities as JSON
            if (!Schema::hasColumn('phone_numbers', 'capabilities')) {
                $this->addJsonColumn($table, 'capabilities', true)->after('type');
                // Example: {"sms": true, "voice": true, "whatsapp": false}
            }
            
            // Add metadata for additional information
            if (!Schema::hasColumn('phone_numbers', 'metadata')) {
                $this->addJsonColumn($table, 'metadata', true)->after('capabilities');
            }
            
        });
        
        // Add indexes for performance using compatible methods
        $this->addIndexIfNotExists('phone_numbers', 'retell_phone_id');
        $this->addIndexIfNotExists('phone_numbers', 'retell_agent_id');
        $this->addIndexIfNotExists('phone_numbers', 'type');
        if (!$this->isSQLite()) {
            $this->addIndexIfNotExists('phone_numbers', ['company_id', 'is_active']);
        } else {
            // For SQLite, use is_active or active depending on what exists
            if (Schema::hasColumn('phone_numbers', 'is_active')) {
                $this->addIndexIfNotExists('phone_numbers', ['company_id', 'is_active']);
            } else if (Schema::hasColumn('phone_numbers', 'active')) {
                $this->addIndexIfNotExists('phone_numbers', ['company_id', 'active']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        // Drop indexes first using compatible methods
        $this->dropIndexIfExists('phone_numbers', 'phone_numbers_company_id_is_active_index');
        $this->dropIndexIfExists('phone_numbers', 'phone_numbers_company_id_active_index');
        $this->dropIndexIfExists('phone_numbers', 'phone_numbers_type_index');
        $this->dropIndexIfExists('phone_numbers', 'phone_numbers_retell_agent_id_index');
        $this->dropIndexIfExists('phone_numbers', 'phone_numbers_retell_phone_id_index');
        
        Schema::table('phone_numbers', function (Blueprint $table) {
            
            // Drop columns
            if (Schema::hasColumn('phone_numbers', 'metadata')) {
                $table->dropColumn('metadata');
            }
            
            if (Schema::hasColumn('phone_numbers', 'capabilities')) {
                $table->dropColumn('capabilities');
            }
            
            if (Schema::hasColumn('phone_numbers', 'type')) {
                $table->dropColumn('type');
            }
            
            if (Schema::hasColumn('phone_numbers', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
            
            if (Schema::hasColumn('phone_numbers', 'retell_agent_id')) {
                $table->dropColumn('retell_agent_id');
            }
            
            if (Schema::hasColumn('phone_numbers', 'retell_phone_id')) {
                $table->dropColumn('retell_phone_id');
            }
            
            // Rename back to active (skip in SQLite)
            if (!$this->isSQLite() && Schema::hasColumn('phone_numbers', 'is_active') && !Schema::hasColumn('phone_numbers', 'active')) {
                $table->renameColumn('is_active', 'active');
            }
            
            // Drop foreign key and column
            if (Schema::hasColumn('phone_numbers', 'company_id')) {
                $this->dropForeignKey('phone_numbers', 'phone_numbers_company_id_foreign');
                $table->dropColumn('company_id');
            }
        });
    }
};