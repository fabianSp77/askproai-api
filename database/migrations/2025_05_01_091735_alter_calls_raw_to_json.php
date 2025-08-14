<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            // LONGTEXT ➜ JSON  (funktioniert ab MySQL 5.7 / MariaDB 10.2)
            $t->json('raw')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            $t->longText('raw')->nullable()->change();
        });
    }
};
