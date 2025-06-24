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
        Schema::create('service_event_type_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->bigInteger('calcom_event_type_id')->unsigned();
            $table->unsignedBigInteger('company_id');
            $table->uuid('branch_id')->nullable();
            $table->json('keywords')->nullable()->comment('Alternative names/keywords for matching');
            $table->integer('priority')->default(0)->comment('Higher priority = preferred match');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes with shorter names
            $table->index(['service_id', 'calcom_event_type_id'], 'idx_service_event');
            $table->index('company_id', 'idx_company');
            $table->index('branch_id', 'idx_branch');
            $table->index('is_active', 'idx_active');
            
            // Foreign keys
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('calcom_event_type_id')->references('calcom_numeric_event_type_id')->on('calcom_event_types')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            
            // Unique constraint to prevent duplicates
            $table->unique(['service_id', 'calcom_event_type_id', 'branch_id'], 'unique_service_event_branch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_event_type_mappings');
    }
};