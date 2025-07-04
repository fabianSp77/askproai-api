<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->enum('role', ['owner', 'admin', 'manager', 'staff']);
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes', 1000)->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->boolean('two_factor_enforced')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->json('settings')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->string('preferred_language', 5)->default('de');
            $table->string('timezone')->default('Europe/Berlin');
            $table->rememberToken();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'email']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_users');
    }
};