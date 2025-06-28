<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        $this->createTableIfNotExists('kunden', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // weitere Felder …
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTableIfExists('kunden');
    }
};
