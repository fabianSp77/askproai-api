<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing null or invalid JSON values
        if (Schema::hasColumn('companies', 'settings')) {
            $this->setJsonDefaults('companies', 'settings', []);
        }
        
        if (Schema::hasColumn('companies', 'metadata')) {
            $this->setJsonDefaults('companies', 'metadata', []);
        }
        
        // Add or modify columns
        $this->addColumnIfNotExists('companies', 'settings', function (Blueprint $table) {
            $this->addJsonColumn($table, 'settings', true);
        });
        
        $this->addColumnIfNotExists('companies', 'metadata', function (Blueprint $table) {
            $this->addJsonColumn($table, 'metadata', true);
        });
        
        // For existing columns, ensure they're the right type
        if (!$this->isSQLite()) {
            $this->changeToJsonColumn('companies', 'settings', true);
            $this->changeToJsonColumn('companies', 'metadata', true);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only modify if not SQLite
        if (!$this->isSQLite()) {
            Schema::table('companies', function (Blueprint $table) {
                $table->json('settings')->nullable()->change();
                $table->json('metadata')->nullable()->change();
            });
        }
    }
};