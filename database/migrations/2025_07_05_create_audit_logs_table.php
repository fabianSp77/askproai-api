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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            
            // User information
            $table->morphs('user'); // user_id and user_type for portal users and admin users
            $table->string('user_name');
            $table->string('user_email');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            // Action details
            $table->string('action'); // export_pdf, export_csv, view_billing, update_permissions, etc.
            $table->string('module'); // calls, billing, settings, etc.
            $table->text('description');
            
            // Affected resource
            $table->nullableMorphs('auditable'); // auditable_id and auditable_type
            
            // Additional data
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable(); // Store additional context
            
            // Risk level
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'created_at']);
            $table->index(['user_id', 'user_type']);
            $table->index('action');
            $table->index('module');
            $table->index('risk_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};