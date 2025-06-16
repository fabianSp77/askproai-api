<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

trait SimplifiedMigrations
{
    protected function setUpTraits()
    {
        parent::setUpTraits();
        
        // Use in-memory SQLite database
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        
        // Run simplified migrations
        $this->runSimplifiedMigrations();
    }

    /**
     * Run simplified migrations for testing repositories
     */
    protected function runSimplifiedMigrations(): void
    {
        // Create companies table
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique()->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('contact_person')->nullable();
                $table->json('opening_hours')->nullable();
                $table->string('calcom_api_key')->nullable();
                $table->string('calcom_user_id')->nullable();
                $table->string('retell_api_key')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        // Create branches table
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->string('city')->nullable();
                $table->string('phone_number')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create customers table
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->date('birthdate')->nullable();
                $table->json('tags')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Create staff table
        if (!Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('company_id');
                $table->uuid('branch_id')->nullable();
                $table->uuid('home_branch_id')->nullable();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create services table
        if (!Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->uuid('branch_id')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->integer('default_duration_minutes')->default(60);
                $table->boolean('active')->default(true);
                $table->string('category')->nullable();
                $table->integer('sort_order')->default(0);
                $table->integer('min_staff_required')->default(1);
                $table->integer('max_bookings_per_day')->nullable();
                $table->integer('buffer_time_minutes')->default(0);
                $table->boolean('is_online_bookable')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create appointments table
        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->uuid('branch_id')->nullable();
                $table->uuid('staff_id')->nullable();
                $table->unsignedBigInteger('service_id')->nullable();
                $table->datetime('starts_at');
                $table->datetime('ends_at');
                $table->string('status')->default('scheduled');
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->unsignedBigInteger('call_id')->nullable();
                $table->timestamps();
            });
        }

        // Create calls table
        if (!Schema::hasTable('calls')) {
            Schema::create('calls', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('from_number')->nullable();
                $table->string('to_number')->nullable();
                $table->string('call_id')->nullable();
                $table->string('retell_call_id')->nullable();
                $table->string('status')->default('initiated');
                $table->integer('duration_seconds')->nullable();
                $table->text('transcript')->nullable();
                $table->json('analysis')->nullable();
                $table->integer('cost_cents')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('appointment_id')->nullable();
                $table->string('agent_id')->nullable();
                $table->json('webhook_data')->nullable();
                $table->timestamps();
            });
        }

        // Create agents table
        if (!Schema::hasTable('agents')) {
            Schema::create('agents', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('agent_id')->unique();
                $table->string('type')->default('retell');
                $table->json('config')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
    }
}