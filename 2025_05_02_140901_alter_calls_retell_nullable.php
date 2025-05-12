<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            // UNIQUE-Index entfernen - Name lautet standardmäßig calls_retell_call_id_unique
            $t->dropUnique('calls_retell_call_id_unique');

            // Spalte nullable machen
            $t->string('retell_call_id')->nullable()->change();

            // normaler Index anlegen
            $t->index('retell_call_id', 'idx_calls_retell');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            $t->dropIndex('idx_calls_retell');
            $t->string('retell_call_id')->nullable(false)->unique()->change();
        });
    }
};
