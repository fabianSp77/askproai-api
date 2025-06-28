<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * KRITISCH: Diese Pivot-Tabellen wurden fälschlicherweise gelöscht
     * Sie sind essentiell für die Many-to-Many Beziehungen
     */
    public function up(): void
    {
        // 1. onboarding_progress - Wird von OnboardingService verwendet
        if (!Schema::hasTable('onboarding_progress')) {
            $this->createTableIfNotExists('onboarding_progress', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('user_id');
                $table->string('current_step');
                $this->addJsonColumn($table, 'completed_steps', true);
                $this->addJsonColumn($table, 'step_data', true);
                $table->integer('progress_percentage')->default(0);
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();
                
                $table->index('company_id');
                $table->index('user_id');
                $table->index(['company_id', 'is_completed']);
            });
        }

        // 2. staff_branches - Pivot für Staff <-> Branch Beziehung
        if (!Schema::hasTable('staff_branches')) {
            $this->createTableIfNotExists('staff_branches', function (Blueprint $table) {
                // WICHTIG: staff.id ist UUID, branches.id ist UUID
                $table->uuid('staff_id');
                $table->uuid('branch_id');
                $table->timestamps();
                
                // Indizes für Performance
                $table->index('staff_id');
                $table->index('branch_id');
                $table->primary(['staff_id', 'branch_id']);
            });
        }

        // 3. branch_service - Pivot für Branch <-> Service Beziehung
        if (!Schema::hasTable('branch_service')) {
            $this->createTableIfNotExists('branch_service', function (Blueprint $table) {
                // branches.id ist UUID, services.id ist BIGINT
                $table->uuid('branch_id');
                $table->unsignedBigInteger('service_id');
                $table->decimal('price', 10, 2)->nullable();
                $table->integer('duration')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                
                $table->index('branch_id');
                $table->index('service_id');
                $table->primary(['branch_id', 'service_id']);
            });
        }

        // 4. service_staff - Pivot für Service <-> Staff Beziehung
        if (!Schema::hasTable('service_staff')) {
            $this->createTableIfNotExists('service_staff', function (Blueprint $table) {
                // services.id ist BIGINT, staff.id ist UUID
                $table->unsignedBigInteger('service_id');
                $table->uuid('staff_id');
                $table->integer('duration_minutes')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                
                $table->index('service_id');
                $table->index('staff_id');
                $table->primary(['service_id', 'staff_id']);
            });
        }

        // 5. integrations - Möglicherweise noch verwendet
        if (!Schema::hasTable('integrations')) {
            $this->createTableIfNotExists('integrations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('type'); // 'calcom', 'retell', etc.
                $table->string('status')->default('pending');
                $this->addJsonColumn($table, 'configuration', true);
                $this->addJsonColumn($table, 'credentials', true);
                $table->timestamp('last_sync_at')->nullable();
                $table->text('last_error')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index('company_id');
                $table->index('type');
                $table->index(['company_id', 'type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('integrations');
        $this->dropTableIfExists('service_staff');
        $this->dropTableIfExists('branch_service');
        $this->dropTableIfExists('staff_branches');
        $this->dropTableIfExists('onboarding_progress');
    }
};