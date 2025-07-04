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
        // First, consolidate data from language_preference into preferred_language
        if (Schema::hasColumn('customers', 'language_preference') && 
            Schema::hasColumn('customers', 'preferred_language')) {
            
            // Copy data from language_preference to preferred_language where it's null
            DB::table('customers')
                ->whereNull('preferred_language')
                ->whereNotNull('language_preference')
                ->update([
                    'preferred_language' => DB::raw('language_preference')
                ]);
            
            // Set default language for remaining null values
            DB::table('customers')
                ->whereNull('preferred_language')
                ->update([
                    'preferred_language' => 'de'
                ]);
        }
        
        // Drop the duplicate column if it exists
        if (!$this->isSQLite() && Schema::hasColumn('customers', 'language_preference')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('language_preference');
            });
        }
        
        // Add index for performance
        Schema::table('customers', function (Blueprint $table) {
            if (!$this->indexExists('customers', 'customers_preferred_language_index')) {
                $table->index('preferred_language');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->isSQLite()) {
            return;
        }
        
        // Remove index
        Schema::table('customers', function (Blueprint $table) {
            if ($this->indexExists('customers', 'customers_preferred_language_index')) {
                $table->dropIndex(['preferred_language']);
            }
        });
        
        // We don't restore the language_preference column as it was duplicate
    }
    
};