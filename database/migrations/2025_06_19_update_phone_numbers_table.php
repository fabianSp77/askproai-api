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
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('phone_numbers', 'company_id')) {
                $table->unsignedBigInteger('company_id')->after('id');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'type')) {
                $table->enum('type', ['direct', 'hotline'])->default('direct')->after('number');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'routing_config')) {
                $this->addJsonColumn($table, 'routing_config', true)->after('type');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'agent_id')) {
                $table->string('agent_id')->nullable()->after('routing_config');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'description')) {
                $table->string('description')->nullable()->after('agent_id');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'created_at')) {
                $table->timestamps();
            }
            
            // Add indexes if they don't exist using the compatible method
            if (!$this->indexExists('phone_numbers', 'phone_numbers_company_id_index')) {
                $table->index('company_id');
            }
            
            if (!$this->indexExists('phone_numbers', 'phone_numbers_type_index')) {
                $table->index('type');
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
        
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['type']);
            
            $table->dropColumn([
                'company_id',
                'type',
                'routing_config',
                'agent_id',
                'description'
            ]);
        });
    }
};