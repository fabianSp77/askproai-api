<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabelle existiert bereits – nur falls sie fehlt neu anlegen
        if (! Schema::hasTable('retell_agents')) {
            Schema::create('retell_agents', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('retell_agents');
    }
};
