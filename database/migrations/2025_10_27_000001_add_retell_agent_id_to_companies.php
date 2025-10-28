<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Retell Agent ID to Companies Table
 *
 * Testing environment needs direct column for Retell agent mapping
 * Production uses settings JSON, but testing uses direct columns
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('companies', 'retell_agent_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('retell_agent_id', 100)
                    ->nullable()
                    ->after('calcom_team_id')
                    ->comment('Retell AI agent ID for voice booking');
            });
        }

        if (!Schema::hasColumn('companies', 'slug')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('slug')
                    ->nullable()
                    ->unique()
                    ->after('name')
                    ->comment('URL-friendly company identifier');
            });
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['retell_agent_id', 'slug']);
        });
    }
};
