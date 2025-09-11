<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Multi-Tenant Foundation with Permissions
     * Consolidates: tenants, balance, slug, api_key, calcom_team, permissions tables
     */
    public function up(): void
    {
        // Create tenants table with all fields consolidated
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->string('api_key')->unique()->index();
            $table->integer('balance_cents')->default(0);
            $table->string('calcom_team_slug')->nullable()->index();
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('timezone')->default('Europe/Berlin');
            $table->string('language')->default('de');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            
            // Performance indexes
            $table->index('is_active');
            $table->index(['slug', 'is_active']);
        });

        // Spatie Permission Tables
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        // Permissions table
        Schema::create($tableNames['permissions'] ?? 'permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->string('description')->nullable();
            $table->string('group')->nullable();
            $table->timestamps();
            
            $table->unique(['name', 'guard_name']);
            $table->index('guard_name');
            $table->index('group');
        });

        // Roles table
        Schema::create($tableNames['roles'] ?? 'roles', function (Blueprint $table) use ($teams, $columnNames) {
            $table->bigIncrements('id');
            
            if ($teams || config('permission.testing')) {
                $table->uuid($columnNames['team_foreign_key'] ?? 'team_id')->nullable()->index();
            }
            
            $table->string('name');
            $table->string('guard_name');
            $table->string('description')->nullable();
            $table->integer('hierarchy')->default(0);
            $table->timestamps();
            
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'] ?? 'team_id', 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
            
            $table->index('guard_name');
        });

        // Model has permissions (direct permissions)
        Schema::create($tableNames['model_has_permissions'] ?? 'model_has_permissions', function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');
            $table->uuid($columnNames['model_morph_key'] ?? 'model_id');
            
            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'] ?? 'permissions')
                ->onDelete('cascade');
            
            if ($teams) {
                $table->uuid($columnNames['team_foreign_key'] ?? 'team_id')->nullable()->index();
            }
            
            $table->primary(
                [$pivotPermission, $columnNames['model_morph_key'] ?? 'model_id', 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
            
            $table->index([$columnNames['model_morph_key'] ?? 'model_id', 'model_type']);
        });

        // Model has roles
        Schema::create($tableNames['model_has_roles'] ?? 'model_has_roles', function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');
            $table->uuid($columnNames['model_morph_key'] ?? 'model_id');
            
            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'] ?? 'roles')
                ->onDelete('cascade');
            
            if ($teams) {
                $table->uuid($columnNames['team_foreign_key'] ?? 'team_id')->nullable()->index();
            }
            
            $table->primary(
                [$pivotRole, $columnNames['model_morph_key'] ?? 'model_id', 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
            
            $table->index([$columnNames['model_morph_key'] ?? 'model_id', 'model_type']);
        });

        // Role has permissions
        Schema::create($tableNames['role_has_permissions'] ?? 'role_has_permissions', function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);
            
            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'] ?? 'permissions')
                ->onDelete('cascade');
            
            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'] ?? 'roles')
                ->onDelete('cascade');
            
            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        
        Schema::dropIfExists($tableNames['role_has_permissions'] ?? 'role_has_permissions');
        Schema::dropIfExists($tableNames['model_has_roles'] ?? 'model_has_roles');
        Schema::dropIfExists($tableNames['model_has_permissions'] ?? 'model_has_permissions');
        Schema::dropIfExists($tableNames['roles'] ?? 'roles');
        Schema::dropIfExists($tableNames['permissions'] ?? 'permissions');
        Schema::dropIfExists('tenants');
    }
};