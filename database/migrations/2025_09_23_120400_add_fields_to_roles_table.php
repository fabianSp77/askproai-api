<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('roles')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('roles', 'color')) {
                $table->string('color', 50)->nullable();
            }
            if (!Schema::hasColumn('roles', 'icon')) {
                $table->string('icon', 100)->nullable();
            }
            if (!Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false);
            }
            if (!Schema::hasColumn('roles', 'priority')) {
                $table->integer('priority')->default(99);
            }
            if (!Schema::hasColumn('roles', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'color',
                'icon',
                'is_system',
                'priority',
                'metadata',
            ]);
        });
    }
};