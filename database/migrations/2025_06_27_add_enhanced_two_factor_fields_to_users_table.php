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
        Schema::table('users', function (Blueprint $table) {
            // Add enhanced 2FA fields if they don't exist
            if (!Schema::hasColumn('users', 'two_factor_enforced')) {
                $table->boolean('two_factor_enforced')->default(false)->after('two_factor_confirmed_at');
            }
            
            if (!Schema::hasColumn('users', 'two_factor_method')) {
                $table->enum('two_factor_method', ['authenticator', 'sms'])->default('authenticator')->after('two_factor_enforced');
            }
            
            if (!Schema::hasColumn('users', 'two_factor_phone_number')) {
                $table->string('two_factor_phone_number')->nullable()->after('two_factor_method');
            }
            
            if (!Schema::hasColumn('users', 'two_factor_phone_verified')) {
                $table->boolean('two_factor_phone_verified')->default(false)->after('two_factor_phone_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enforced',
                'two_factor_method',
                'two_factor_phone_number',
                'two_factor_phone_verified',
            ]);
        });
    }
};