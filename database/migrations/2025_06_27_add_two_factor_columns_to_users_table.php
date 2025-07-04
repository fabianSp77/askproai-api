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
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('users', 'two_factor_enforced')) {
                // Custom fields for admin enforcement and method selection
                $table->boolean('two_factor_enforced')
                    ->default(false)
                    ->after('two_factor_confirmed_at');
            }
            
            if (!Schema::hasColumn('users', 'two_factor_method')) {
                $table->enum('two_factor_method', ['authenticator', 'sms'])
                    ->default('authenticator')
                    ->nullable()
                    ->after('two_factor_enforced');
            }
            
            if (!Schema::hasColumn('users', 'two_factor_phone_number')) {
                $table->string('two_factor_phone_number')
                    ->nullable()
                    ->after('two_factor_method')
                    ->comment('Phone number for SMS 2FA');
            }
            
            if (!Schema::hasColumn('users', 'two_factor_phone_verified')) {
                $table->boolean('two_factor_phone_verified')
                    ->default(false)
                    ->after('two_factor_phone_number');
            }
        });
        
        // Add indexes separately to avoid issues
        Schema::table('users', function (Blueprint $table) {
            if (!collect(Schema::getIndexes('users'))->pluck('name')->contains('users_two_factor_enforced_index')) {
                $table->index('two_factor_enforced');
            }
            if (!collect(Schema::getIndexes('users'))->pluck('name')->contains('users_two_factor_confirmed_at_index')) {
                $table->index('two_factor_confirmed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes if they exist
            if (collect(Schema::getIndexes('users'))->pluck('name')->contains('users_two_factor_enforced_index')) {
                $table->dropIndex(['two_factor_enforced']);
            }
            if (collect(Schema::getIndexes('users'))->pluck('name')->contains('users_two_factor_confirmed_at_index')) {
                $table->dropIndex(['two_factor_confirmed_at']);
            }
            
            // Only drop custom columns, not Fortify's core columns
            $columnsToRemove = [];
            if (Schema::hasColumn('users', 'two_factor_enforced')) {
                $columnsToRemove[] = 'two_factor_enforced';
            }
            if (Schema::hasColumn('users', 'two_factor_method')) {
                $columnsToRemove[] = 'two_factor_method';
            }
            if (Schema::hasColumn('users', 'two_factor_phone_number')) {
                $columnsToRemove[] = 'two_factor_phone_number';
            }
            if (Schema::hasColumn('users', 'two_factor_phone_verified')) {
                $columnsToRemove[] = 'two_factor_phone_verified';
            }
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
};