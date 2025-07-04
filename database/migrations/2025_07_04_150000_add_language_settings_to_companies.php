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
        Schema::table('companies', function (Blueprint $table) {
            // Language settings
            $table->string('default_language', 5)->default('de')->after('timezone');
            $table->json('supported_languages')->nullable()->after('default_language');
            $table->boolean('auto_translate')->default(true)->after('supported_languages');
            $table->string('translation_provider')->default('deepl')->after('auto_translate');
            
            // Add index for performance
            $table->index('default_language');
        });
        
        // Set default supported languages for existing companies
        if (!$this->isSQLite()) {
            DB::table('companies')->update([
                'supported_languages' => json_encode(['de', 'en'])
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->isSQLite()) {
            // SQLite doesn't support dropping columns well
            return;
        }
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['default_language']);
            
            $table->dropColumn([
                'default_language',
                'supported_languages', 
                'auto_translate',
                'translation_provider'
            ]);
        });
    }
};