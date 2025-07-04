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
        Schema::table('calls', function (Blueprint $table) {
            // Language detection fields
            if (!Schema::hasColumn('calls', 'detected_language')) {
                $table->string('detected_language', 5)->nullable()->after('sentiment');
            }
            if (!Schema::hasColumn('calls', 'language_confidence')) {
                $table->decimal('language_confidence', 3, 2)->nullable()->after('detected_language');
            }
            if (!Schema::hasColumn('calls', 'language_mismatch')) {
                $table->boolean('language_mismatch')->default(false)->after('language_confidence');
            }
            
            // Skip indexes due to table index limit (already has 64 indexes)
        });
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
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn([
                'detected_language',
                'language_confidence',
                'language_mismatch'
            ]);
        });
    }
};