<?php

namespace App\Console\Commands;

use App\Models\PortalUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ReflectionClass;

class TestPortalLoginFix extends Command
{
    protected $signature = 'test:portal-login-fix';
    protected $description = 'Test portal login session key fixes';

    public function handle()
    {
        $this->info('=== PORTAL LOGIN SESSION KEY FIX TEST ===');
        $this->newLine();

        // Step 1: Verify session key generation consistency
        $this->info('1. CHECKING SESSION KEY GENERATION');
        
        $guard = Auth::guard('portal');
        $expectedKey = $guard->getName();
        $calculatedKey = 'login_portal_' . sha1(\Illuminate\Auth\SessionGuard::class);
        
        $this->line("Guard session key: $expectedKey");
        $this->line("Calculated key: $calculatedKey");
        
        if ($expectedKey === $calculatedKey) {
            $this->info('Keys match: YES ✅');
        } else {
            $this->error('Keys match: NO ❌');
        }
        
        $this->newLine();
        
        // Step 2: Test user authentication
        $this->info('2. TESTING USER AUTHENTICATION');
        
        // Get demo user
        $user = PortalUser::where('email', 'demo@askproai.de')->first();
        
        if (!$user) {
            $this->error('❌ Demo user not found!');
            
            // Check if CompanyScope is interfering
            $userWithoutScope = PortalUser::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->where('email', 'demo@askproai.de')
                ->first();
                
            if ($userWithoutScope) {
                $this->warn('User exists but CompanyScope is blocking access!');
                $this->line("User ID: {$userWithoutScope->id}, Company: {$userWithoutScope->company_id}");
            }
            
            return 1;
        }
        
        $this->info("✅ Demo user found: ID={$user->id}, Company={$user->company_id}");
        
        // Test password verification
        $passwordCorrect = Hash::check('password', $user->password);
        if ($passwordCorrect) {
            $this->info('Password verification: PASS ✅');
        } else {
            $this->error('Password verification: FAIL ❌');
        }
        
        // Attempt login
        Auth::guard('portal')->login($user);
        $isAuthenticated = Auth::guard('portal')->check();
        
        if ($isAuthenticated) {
            $this->info('Authentication after login: PASS ✅');
            $authUser = Auth::guard('portal')->user();
            $this->line("Authenticated user ID: " . $authUser->id);
        } else {
            $this->error('Authentication after login: FAIL ❌');
        }
        
        $this->newLine();
        
        // Step 3: Check session data
        $this->info('3. CHECKING SESSION DATA');
        $sessionKey = $guard->getName();
        $sessionData = session()->all();
        
        if (isset($sessionData[$sessionKey])) {
            $this->info("Session has portal auth key ($sessionKey): YES ✅");
        } else {
            $this->error("Session has portal auth key ($sessionKey): NO ❌");
        }
        
        if (isset($sessionData['portal_user_id'])) {
            $this->info('Session has portal_user_id: YES ✅');
        } else {
            $this->error('Session has portal_user_id: NO ❌');
        }
        
        if (isset($sessionData['company_id'])) {
            $this->info('Session has company_id: YES ✅');
        } else {
            $this->error('Session has company_id: NO ❌');
        }
        
        // Show all session keys
        $this->newLine();
        $this->line('All session keys:');
        foreach (array_keys($sessionData) as $key) {
            $this->line("  - $key");
        }
        
        $this->newLine();
        
        // Step 4: Test middleware
        $this->info('4. TESTING MIDDLEWARE');
        
        // Test SharePortalSession middleware
        if (class_exists(\App\Http\Middleware\SharePortalSession::class)) {
            $this->info('SharePortalSession middleware exists: YES ✅');
            
            // Check if it uses correct session key
            $reflection = new ReflectionClass(\App\Http\Middleware\SharePortalSession::class);
            $source = file_get_contents($reflection->getFileName());
            
            if (strpos($source, '$guard->getName()') !== false) {
                $this->info('SharePortalSession uses guard->getName(): YES ✅');
            } else {
                $this->error('SharePortalSession uses guard->getName(): NO ❌');
            }
        } else {
            $this->error('SharePortalSession middleware exists: NO ❌');
        }
        
        $this->newLine();
        
        // Step 5: Test actual login flow
        $this->info('5. TESTING ACTUAL LOGIN FLOW');
        
        // Clear any existing session
        Auth::guard('portal')->logout();
        session()->flush();
        
        // Simulate login
        $credentials = [
            'email' => 'demo@askproai.de',
            'password' => 'password'
        ];
        
        if (Auth::guard('portal')->attempt($credentials)) {
            $this->info('Login attempt: SUCCESS ✅');
            
            // Check session after login
            $sessionData = session()->all();
            $sessionKey = Auth::guard('portal')->getName();
            
            if (isset($sessionData[$sessionKey])) {
                $this->info('Session key set after login: YES ✅');
                $this->line("Session key value: " . $sessionData[$sessionKey]);
            } else {
                $this->error('Session key set after login: NO ❌');
            }
        } else {
            $this->error('Login attempt: FAILED ❌');
        }
        
        $this->newLine();
        
        // Summary
        $this->info('=== TEST SUMMARY ===');
        $this->line('- Session key consistency: ' . ($expectedKey === $calculatedKey ? 'PASS ✅' : 'FAIL ❌'));
        $this->line('- User authentication: ' . ($isAuthenticated ? 'PASS ✅' : 'FAIL ❌'));
        $this->line('- Session data correct: ' . (isset($sessionData[$sessionKey]) ? 'PASS ✅' : 'FAIL ❌'));
        
        $this->newLine();
        $this->info('Test complete.');
        
        return 0;
    }
}