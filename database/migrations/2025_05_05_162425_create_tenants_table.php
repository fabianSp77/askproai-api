<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        $this->createTableIfNotExists('tenants', function (Blueprint $table) {
            $table->id();                     // BIGINT unsigned PK
            $table->string('name');
            // weitere Pflichtspalten hier …
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTableIfExists('tenants');
    }
};
