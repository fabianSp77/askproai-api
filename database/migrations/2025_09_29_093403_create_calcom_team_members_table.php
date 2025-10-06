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
        if (Schema::hasTable('calcom_team_members')) {
            return;
        }

        Schema::create('calcom_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->integer('calcom_team_id');
            $table->integer('calcom_user_id');
            $table->string('email');
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('role')->default('member'); // owner, admin, member
            $table->boolean('accepted')->default(true);
            $table->json('availability')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('calcom_team_id');
            $table->index('calcom_user_id');
            $table->unique(['calcom_team_id', 'calcom_user_id'], 'unique_team_member');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calcom_team_members');
    }
};
