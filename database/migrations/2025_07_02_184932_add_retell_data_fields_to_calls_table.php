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
        Schema::table('calls', function (Blueprint $table) {
            $table->string('agent_name')->nullable()->comment('Full name of the AI agent');
            $table->string('urgency_level')->nullable()->comment('Call urgency: high/medium/low');
            $table->integer('no_show_count')->default(0)->comment('Previous no-shows');
            $table->integer('reschedule_count')->default(0)->comment('Number of reschedules');
            $table->boolean('first_visit')->nullable()->comment('Is first visit');
            $table->string('insurance_type')->nullable()->comment('Type of insurance');
            $table->string('insurance_company')->nullable()->comment('Insurance provider');
            $table->json('custom_analysis_data')->nullable()->comment('All custom analysis fields');
            $table->text('call_summary')->nullable()->comment('AI-generated call summary');
            $table->json('llm_token_usage')->nullable()->comment('Token usage statistics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('agent_name');
            $table->dropColumn('urgency_level');
            $table->dropColumn('no_show_count');
            $table->dropColumn('reschedule_count');
            $table->dropColumn('first_visit');
            $table->dropColumn('insurance_type');
            $table->dropColumn('insurance_company');
            $table->dropColumn('custom_analysis_data');
            $table->dropColumn('call_summary');
            $table->dropColumn('llm_token_usage');
        });
    }
};