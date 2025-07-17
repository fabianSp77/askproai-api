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
        Schema::create('portal_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('module');
            $table->string('description');
            $table->boolean('is_critical')->default(false); // Marks critical permissions like billing, costs
            $table->boolean('admin_only')->default(false); // Only company admin and super admin
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('module');
            $table->index('is_critical');
        });

        // Portal user permissions pivot table
        Schema::create('portal_user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_user_id')->constrained()->onDelete('cascade');
            $table->foreignId('portal_permission_id')->constrained()->onDelete('cascade');
            $table->timestamp('granted_at');
            $table->unsignedBigInteger('granted_by_user_id')->nullable();
            $table->string('granted_by_user_type')->nullable();
            
            $table->unique(['portal_user_id', 'portal_permission_id'], 'portal_user_perm_unique');
        });

        // Company-wide permission settings
        Schema::create('company_permission_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreignId('portal_permission_id')->constrained()->onDelete('cascade');
            
            // Default settings for new users
            $table->boolean('enabled_by_default')->default(false);
            $table->boolean('visible_to_all')->default(false);
            
            // Restrictions
            $table->json('restricted_to_roles')->nullable(); // ['admin', 'manager']
            $table->json('restricted_to_branches')->nullable(); // branch IDs
            
            $table->timestamps();
            
            $table->unique(['company_id', 'portal_permission_id'], 'company_perm_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_permission_settings');
        Schema::dropIfExists('portal_user_permissions');
        Schema::dropIfExists('portal_permissions');
    }
};