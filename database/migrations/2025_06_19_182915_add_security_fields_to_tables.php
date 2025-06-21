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
        // Create security audit logs table
        Schema::create('security_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->index(); // login, failed_login, suspicious_activity, etc.
            $table->string('severity', 20)->default('info'); // info, warning, error, critical
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable(); // User model class
            $table->string('resource_type')->nullable(); // Model being accessed
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('action')->nullable(); // create, read, update, delete
            $this->addJsonColumn($table, 'metadata', true); // Additional context
            $table->text('threat_indicators')->nullable(); // Detected threat patterns
            $table->string('correlation_id', 36)->nullable()->index();
            $table->timestamps();
            
            $table->index(['created_at', 'event_type']);
            $table->index(['user_id', 'user_type']);
            $table->index(['resource_type', 'resource_id']);
        });
        
        // Create webhook deduplication table
        Schema::create('webhook_deduplication', function (Blueprint $table) {
            $table->id();
            $table->string('webhook_id')->unique();
            $table->string('provider', 50); // retell, calcom, stripe
            $table->string('event_type', 50)->nullable();
            $table->string('signature')->nullable();
            $this->addJsonColumn($table, 'headers', true);
            $this->addJsonColumn($table, 'payload', false);
            $this->addJsonColumn($table, 'response', true);
            $table->integer('response_status')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->boolean('is_replay_attack')->default(false);
            $table->timestamps();
            
            $table->index(['created_at', 'provider']);
            $table->index('event_type');
        });
        
        // Create rate limiting table
        Schema::create('rate_limit_violations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index(); // IP address or user identifier
            $table->string('route')->nullable();
            $table->string('method', 10)->nullable();
            $table->integer('limit');
            $table->integer('attempts');
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $this->addJsonColumn($table, 'headers', true);
            $table->timestamp('reset_at')->nullable();
            $table->timestamps();
            
            $table->index(['created_at', 'key']);
        });
        
        // Add security fields to existing tables
        
        // Add to customers table
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'last_security_check')) {
                    $table->timestamp('last_security_check')->nullable();
                }
                if (!Schema::hasColumn('customers', 'security_flags')) {
                    $this->addJsonColumn($table, 'security_flags', true); // suspicious_activity, blocked, etc.
                }
                if (!Schema::hasColumn('customers', 'failed_verification_attempts')) {
                    $table->integer('failed_verification_attempts')->default(0);
                }
            });
        }
        
        // Add to users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable();
                }
                if (!Schema::hasColumn('users', 'last_login_ip')) {
                    $table->string('last_login_ip', 45)->nullable();
                }
                if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                    $table->integer('failed_login_attempts')->default(0);
                }
                if (!Schema::hasColumn('users', 'locked_until')) {
                    $table->timestamp('locked_until')->nullable();
                }
                if (!Schema::hasColumn('users', 'two_factor_secret')) {
                    $table->text('two_factor_secret')->nullable();
                }
                if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                    $table->text('two_factor_recovery_codes')->nullable();
                }
            });
        }
        
        // Add to companies table
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (!Schema::hasColumn('companies', 'security_settings')) {
                    $this->addJsonColumn($table, 'security_settings', true); // password_policy, 2fa_required, etc.
                }
                if (!Schema::hasColumn('companies', 'allowed_ip_addresses')) {
                    $this->addJsonColumn($table, 'allowed_ip_addresses', true);
                }
                if (!Schema::hasColumn('companies', 'webhook_signing_secret')) {
                    $table->string('webhook_signing_secret')->nullable();
                }
            });
        }
        
        // Add to api_call_logs if exists
        if (Schema::hasTable('api_call_logs')) {
            Schema::table('api_call_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('api_call_logs', 'threat_score')) {
                    $table->integer('threat_score')->default(0);
                }
                if (!Schema::hasColumn('api_call_logs', 'blocked')) {
                    $table->boolean('blocked')->default(false);
                }
                if (!Schema::hasColumn('api_call_logs', 'block_reason')) {
                    $table->string('block_reason')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop security tables
        Schema::dropIfExists('security_audit_logs');
        Schema::dropIfExists('webhook_deduplication');
        Schema::dropIfExists('rate_limit_violations');
        
        // Remove security fields from existing tables
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn([
                    'last_security_check',
                    'security_flags',
                    'failed_verification_attempts'
                ]);
            });
        }
        
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'last_login_at',
                    'last_login_ip',
                    'failed_login_attempts',
                    'locked_until',
                    'two_factor_secret',
                    'two_factor_recovery_codes'
                ]);
            });
        }
        
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn([
                    'security_settings',
                    'allowed_ip_addresses',
                    'webhook_signing_secret'
                ]);
            });
        }
        
        if (Schema::hasTable('api_call_logs')) {
            Schema::table('api_call_logs', function (Blueprint $table) {
                $table->dropColumn([
                    'threat_score',
                    'blocked',
                    'block_reason'
                ]);
            });
        }
    }
};