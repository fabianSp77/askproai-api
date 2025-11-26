<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add retell_conversation_flow_id to companies and branches tables.
     * 
     * This allows each company/branch to have its own Retell AI conversation flow,
     * enabling multi-tenant voice AI configuration.
     * 
     * @see https://dashboard.retellai.com/agents
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('retell_conversation_flow_id', 100)
                ->nullable()
                ->after('retell_agent_id')
                ->comment('Retell AI Conversation Flow ID for this company');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('retell_conversation_flow_id', 100)
                ->nullable()
                ->after('retell_agent_id')
                ->comment('Retell AI Conversation Flow ID (overrides company setting)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('retell_conversation_flow_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('retell_conversation_flow_id');
        });
    }
};
