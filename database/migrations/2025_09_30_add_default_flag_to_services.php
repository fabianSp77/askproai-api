<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'is_default')) {
                $table->boolean('is_default')->default(false)
                    ->comment('Default service for this company to use for phone calls');
            }
            if (!Schema::hasColumn('services', 'priority')) {
                $table->integer('priority')->default(50)
                    ->comment('Service priority for selection (lower number = higher priority)');
            }
        });

        // Set default services for each company
        // Company 15 (AskProAI) - Service 47 should be default
        DB::table('services')
            ->where('id', 47)
            ->update([
                'is_default' => true,
                'priority' => 10,
                'updated_at' => now()
            ]);

        // Company 1 - Service 40 should be default
        DB::table('services')
            ->where('id', 40)
            ->update([
                'is_default' => true,
                'priority' => 10,
                'updated_at' => now()
            ]);

        // Set priority for other AskProAI services
        DB::table('services')
            ->where('company_id', 15)
            ->where('name', 'LIKE', '%30 Minuten%')
            ->where('id', '!=', 47)
            ->update([
                'priority' => 20,
                'updated_at' => now()
            ]);

        DB::table('services')
            ->where('company_id', 15)
            ->where('name', 'LIKE', '%15 Minuten%')
            ->update([
                'priority' => 30,
                'updated_at' => now()
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['is_default', 'priority']);
        });
    }
};