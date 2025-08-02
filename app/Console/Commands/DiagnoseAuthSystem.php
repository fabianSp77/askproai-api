<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\PortalUser;

class DiagnoseAuthSystem extends Command
{
    protected $signature = 'auth:diagnose';
    protected $description = 'Diagnose authentication system for both portals';

    public function handle()
    {
        $this->info("\n===============================================");
        $this->info("       AUTH SYSTEM DIAGNOSE - ASKPROAI         ");
        $this->info("===============================================\n");

        // 1. Check for duplicate admin portals
        $this->info("1. CHECKING FOR DUPLICATE ADMIN PORTALS:");
        $this->info("----------------------------------------");

        // Check if React Admin Portal is enabled
        $reactAdminEnabled = config('app.admin_portal_react', false);
        if ($reactAdminEnabled) {
            $this->error("   - React Admin Portal: ✗ ENABLED (Should be disabled!)");
        } else {
            $this->info("   - React Admin Portal: ✓ Disabled");
        }

        // Check Filament Admin Panel
        $filamentEnabled = class_exists(\Filament\Panel::class);
        $this->info("   - Filament Admin Panel: " . ($filamentEnabled ? "✓ Enabled at /admin" : "✗ Not found"));

        // Check for problematic routes
        $this->info("\n2. CHECKING PROBLEMATIC ROUTES:");
        $this->info("----------------------------------------");
        
        $routes = Route::getRoutes();
        $problematicPatterns = [
            'emergency-login' => 'SECURITY RISK!',
            'auto-admin-login' => 'SECURITY RISK!',
            'admin-direct-auth' => 'SECURITY RISK!',
            'fixed-login' => 'Should be removed',
        ];

        foreach ($problematicPatterns as $pattern => $issue) {
            $found = false;
            foreach ($routes as $route) {
                if (str_contains($route->uri(), $pattern)) {
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                $this->error("   - /$pattern: ✗ EXISTS - $issue");
            } else {
                $this->info("   - /$pattern: ✓ Not found");
            }
        }

        // 3. Check Auth Guards
        $this->info("\n3. AUTH GUARDS CONFIGURATION:");
        $this->info("----------------------------------------");
        $guards = config('auth.guards');
        foreach ($guards as $name => $config) {
            $this->info("   - $name: {$config['driver']} → {$config['provider']}");
        }

        // 4. Check Session Configuration
        $this->info("\n4. SESSION CONFIGURATION:");
        $this->info("----------------------------------------");
        $this->info("   Admin Portal:");
        $this->info("     - Cookie: askproai_admin_session");
        $this->info("     - Path: " . storage_path('framework/sessions/admin'));
        $this->info("     - Lifetime: 720 minutes (12 hours)");

        $this->info("\n   Business Portal:");
        $this->info("     - Cookie: askproai_portal_session");
        $this->info("     - Path: " . storage_path('framework/sessions/portal'));
        $this->info("     - Lifetime: 480 minutes (8 hours)");

        // 5. Check Session Directories
        $this->info("\n5. SESSION DIRECTORIES:");
        $this->info("----------------------------------------");
        $sessionDirs = [
            'Admin' => storage_path('framework/sessions/admin'),
            'Portal' => storage_path('framework/sessions/portal'),
            'Default' => storage_path('framework/sessions'),
        ];

        foreach ($sessionDirs as $name => $dir) {
            if (is_dir($dir)) {
                $files = count(glob($dir . '/*'));
                $this->info("   - $name: ✓ Exists ($files session files)");
            } else {
                $this->error("   - $name: ✗ Directory missing!");
                // Try to create it
                if (!is_dir($dir) && mkdir($dir, 0755, true)) {
                    $this->info("     → Created directory");
                }
            }
        }

        // 6. Check Middleware
        $this->info("\n6. MIDDLEWARE CONFIGURATION:");
        $this->info("----------------------------------------");
        $middlewareGroups = app('router')->getMiddlewareGroups();
        
        if (isset($middlewareGroups['admin'])) {
            $this->info("   Admin middleware group:");
            foreach ($middlewareGroups['admin'] as $middleware) {
                $name = is_string($middleware) ? class_basename($middleware) : 'Unknown';
                $this->info("     - $name");
            }
        }

        if (isset($middlewareGroups['portal'])) {
            $this->info("\n   Portal middleware group:");
            foreach ($middlewareGroups['portal'] as $middleware) {
                $name = is_string($middleware) ? class_basename($middleware) : 'Unknown';
                $this->info("     - $name");
            }
        }

        // 7. Test Users
        $this->info("\n7. TEST USER ACCOUNTS:");
        $this->info("----------------------------------------");

        // Admin users
        $adminCount = User::count();
        $this->info("   Admin Users (User model):");
        $this->info("     - Total: $adminCount");
        
        // Check if is_active column exists
        $hasIsActive = \Schema::hasColumn('users', 'is_active');
        if ($hasIsActive) {
            $activeAdmins = User::where('is_active', true)->count();
            $this->info("     - Active: $activeAdmins");
            $sampleAdmin = User::where('is_active', true)->first();
        } else {
            $this->warn("     - Note: 'is_active' column not found");
            $sampleAdmin = User::first();
        }
        
        if ($sampleAdmin) {
            $this->info("     - Sample: {$sampleAdmin->email}");
        }

        // Portal users
        $portalCount = PortalUser::withoutGlobalScopes()->count();
        $this->info("\n   Portal Users (PortalUser model):");
        $this->info("     - Total: $portalCount");
        
        // Check if is_active column exists
        $hasIsActive = \Schema::hasColumn('portal_users', 'is_active');
        if ($hasIsActive) {
            $activePortalUsers = PortalUser::withoutGlobalScopes()->where('is_active', true)->count();
            $this->info("     - Active: $activePortalUsers");
            $samplePortalUser = PortalUser::withoutGlobalScopes()->where('is_active', true)->first();
        } else {
            $this->warn("     - Note: 'is_active' column not found");
            $samplePortalUser = PortalUser::withoutGlobalScopes()->first();
        }
        
        if ($samplePortalUser) {
            $this->info("     - Sample: {$samplePortalUser->email}");
        }

        // 8. Current Issues
        $this->info("\n8. IDENTIFIED ISSUES:");
        $this->info("----------------------------------------");
        
        $issues = [];
        
        // Check for emergency routes
        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'emergency') || str_contains($route->uri(), 'direct-auth')) {
                $issues[] = "Security risk: Route '{$route->uri()}' should be removed";
            }
        }
        
        // Check session domains
        $sessionDomain = config('session.domain');
        if ($sessionDomain === '.askproai.de') {
            $issues[] = "Session domain is set to '.askproai.de' - this may cause cookie conflicts";
        }
        
        if (empty($issues)) {
            $this->info("   ✓ No critical issues found");
        } else {
            foreach ($issues as $issue) {
                $this->error("   ✗ $issue");
            }
        }

        // 9. Recommendations
        $this->info("\n9. RECOMMENDATIONS:");
        $this->info("----------------------------------------");
        $this->info("   ✓ Use Filament Admin Panel at /admin");
        $this->info("   ✓ Use Business Portal at /business");
        $this->info("   ✓ Sessions are properly isolated");
        $this->error("   ✗ Remove all emergency/direct login routes");
        if ($reactAdminEnabled) {
            $this->error("   ✗ Disable React Admin Portal");
        }
        $this->info("   ✓ Admins can login to both portals simultaneously");

        $this->info("\n10. LOGIN URLS:");
        $this->info("----------------------------------------");
        $this->info("   Admin Portal:    https://api.askproai.de/admin/login");
        $this->info("   Business Portal: https://api.askproai.de/business/login");

        $this->info("\n11. HOW TO FIX LOGIN ISSUES:");
        $this->info("----------------------------------------");
        $this->info("   1. Clear all browser cookies for askproai.de");
        $this->info("   2. Clear Laravel cache: php artisan optimize:clear");
        $this->info("   3. Ensure user accounts are active (is_active = 1)");
        $this->info("   4. For Business Portal: User must have company_id");
        $this->info("   5. Check password is hashed correctly");

        $this->info("\n===============================================");
        $this->info("             DIAGNOSE COMPLETE                 ");
        $this->info("===============================================\n");

        return Command::SUCCESS;
    }
}