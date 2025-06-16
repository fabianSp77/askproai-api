<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_type_import_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->uuid('branch_id');
            $table->unsignedMediumInteger('user_id');
            $table->string('import_type')->default('manual'); // manual, scheduled
            $table->integer('total_found')->default(0);
            $table->integer('total_imported')->default(0);
            $table->integer('total_skipped')->default(0);
            $table->integer('total_errors')->default(0);
            $table->json('import_details')->nullable();
            $table->json('error_details')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('user_id')->references('user_id')->on('users');
            
            $table->index(['company_id', 'branch_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_type_import_logs');
    }
};