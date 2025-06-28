<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createTableIfNotExists('onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('current_step')->default('welcome');
            $this->addJsonColumn($table, 'completed_steps', true);
            $this->addJsonColumn($table, 'step_data', true);
            $table->integer('progress_percentage')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            
            $table->index(['company_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('onboarding_progress');
    }
};