<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** INT  ➜  DECIMAL(10,2)  (unsigned, 2 Nachkommastellen) */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->unsigned()->change();
        });
    }

    /** Rollback: wieder INT */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->integer('price')->unsigned()->change();
        });
    }
};
