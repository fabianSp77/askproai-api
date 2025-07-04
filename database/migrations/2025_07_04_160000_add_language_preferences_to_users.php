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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'interface_language')) {
                $table->string('interface_language', 5)->default('de')->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'content_language')) {
                $table->string('content_language', 5)->default('de')->after('interface_language');
            }
            if (!Schema::hasColumn('users', 'auto_translate_content')) {
                $table->boolean('auto_translate_content')->default(true)->after('content_language');
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
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'interface_language',
                'content_language', 
                'auto_translate_content'
            ]);
        });
    }
};