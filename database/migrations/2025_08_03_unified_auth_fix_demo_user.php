<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix demo user to not require 2FA for now
        $demoUser = User::where('email', 'demo@askproai.de')->first();
        
        if ($demoUser) {
            // Remove super admin role temporarily
            $demoUser->removeRole('Super Admin');
            $demoUser->removeRole('super_admin');
            
            // Assign company_admin role instead
            $demoUser->assignRole('company_admin');
            
            // Disable 2FA enforcement
            $demoUser->update([
                'two_factor_enforced' => false,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ]);
            
            \Log::info('Demo user updated for unified auth', [
                'email' => $demoUser->email,
                'roles' => $demoUser->getRoleNames(),
            ]);
        }
        
        // Also ensure demo user has is_active = true
        DB::table('users')->where('email', 'demo@askproai.de')->update(['is_active' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible - manual intervention required
    }
};