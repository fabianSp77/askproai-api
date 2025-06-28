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
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('category_id');
            $table->index('order');
        });
        
        Schema::table('knowledge_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('knowledge_categories', 'order')) {
                $table->integer('order')->default(0)->after('parent_id');
                $table->index('order');
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
        
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->dropColumn('order');
        });
        
        Schema::table('knowledge_categories', function (Blueprint $table) {
            if (Schema::hasColumn('knowledge_categories', 'order')) {
                $table->dropColumn('order');
            }
        });
    }
};
