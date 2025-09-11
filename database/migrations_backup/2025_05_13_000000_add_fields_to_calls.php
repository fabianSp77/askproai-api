<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (! Schema::hasColumn('calls', 'duration_sec')) {
                $table->unsignedInteger('duration_sec')->nullable();
            }

            if (! Schema::hasColumn('calls', 'details')) {
                $table->json('details')->nullable();
            }

            // weitere Spalten hier in gleicher Weise …
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn([
                'duration_sec',
                'details',
                // weitere Spalten hier …
            ]);
        });
    }
};
