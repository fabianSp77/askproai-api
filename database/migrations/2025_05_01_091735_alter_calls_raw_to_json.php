<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void
    {
        if ($this->isSQLite()) {
            // SQLite doesn't support changing column types easily
            // The column will remain as TEXT but we'll treat it as JSON in the application
            return;
        }
        
        Schema::table('calls', function (Blueprint $t) {
            // LONGTEXT ➜ JSON  (funktioniert ab MySQL 5.7 / MariaDB 10.2)
            $t->json('raw')->nullable()->change();
        });
    }

    public function down(): void
    {
        if ($this->isSQLite()) {
            // SQLite doesn't support changing column types
            return;
        }
        
        Schema::table('calls', function (Blueprint $t) {
            $t->longText('raw')->nullable()->change();
        });
    }
};
