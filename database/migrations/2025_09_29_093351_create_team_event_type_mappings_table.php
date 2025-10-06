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
        if (Schema::hasTable('team_event_type_mappings')) {
            return;
        }

        Schema::create('team_event_type_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->integer('calcom_team_id');
            $table->integer('calcom_event_type_id');
            $table->string('event_type_name');
            $table->string('event_type_slug')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->boolean('is_team_event')->default(false);
            $table->json('hosts')->nullable(); // Store array of host IDs
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('calcom_team_id');
            $table->unique(['calcom_team_id', 'calcom_event_type_id'], 'unique_team_event_type');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_event_type_mappings');
    }
};
