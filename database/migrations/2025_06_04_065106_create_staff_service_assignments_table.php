<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::create('staff_service_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('staff_id');
            $table->uuid('master_service_id');
            $table->uuid('branch_id');
            $table->string('calcom_user_id')->nullable();
            $this->addJsonColumn($table, 'availability_rules', true);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('master_service_id')->references('id')->on('master_services')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->unique(['staff_id', 'master_service_id', 'branch_id'], 'staff_service_branch_unique');
            $table->index(['branch_id', 'active']);
            $table->index(['staff_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_service_assignments');
    }
};
