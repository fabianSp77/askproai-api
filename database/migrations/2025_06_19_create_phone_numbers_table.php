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
        if (!Schema::hasTable('phone_numbers')) {
            $this->createTableIfNotExists('phone_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('company_id');
            $table->uuid('branch_id')->nullable();
            $table->string('number', 50)->unique();
            $table->enum('type', ['direct', 'hotline'])->default('direct');
            $this->addJsonColumn($table, 'routing_config', true);
            $table->string('agent_id')->nullable();
            $table->boolean('active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('company_id');
            $table->index('branch_id');
            $table->index('number');
            $table->index('active');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('phone_numbers');
    }
};