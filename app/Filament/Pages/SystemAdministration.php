<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use App\Models\User;
use App\Models\Company;
use App\Models\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class SystemAdministration extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.system-administration';
    protected static ?string $title = 'System Administration';
    protected static ?string $navigationLabel = '⚙️ System Admin';

    // Public properties for the view
    public array $systemStats = [];
    public array $databaseStats = [];
    public array $cacheStats = [];
    public array $securityStats = [];
    public array $recentActivity = [];
    public array $systemHealth = [];

    /**
     * 🔒 SECURITY: Only Super-Admin can access this page
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Only Super-Admin roles can access
        return $user->hasRole(['super-admin', 'super_admin', 'Super Admin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        // Double-check permissions
        $user = auth()->user();
        if (!$user || !$user->hasRole(['super-admin', 'super_admin', 'Super Admin'])) {
            Notification::make()
                ->title('Zugriff verweigert')
                ->body('Nur Super-Admins können auf diese Seite zugreifen.')
                ->danger()
                ->send();

            redirect()->to('/admin');
            return;
        }

        // Load data into public properties
        $this->systemStats = $this->getSystemStats();
        $this->databaseStats = $this->getDatabaseStats();
        $this->cacheStats = $this->getCacheStats();
        $this->securityStats = $this->getSecurityStats();
        $this->recentActivity = $this->getRecentActivity();
        $this->systemHealth = $this->getSystemHealth();
    }

    private function getSystemStats(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug') ? '🔴 Enabled' : '✅ Disabled',
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'url' => config('app.url'),
            'disk_usage' => $this->getDiskUsage(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ];
    }

    private function getDatabaseStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_companies' => Company::count(),
            'resellers' => Company::where('company_type', 'reseller')->count(),
            'customers' => Company::where('company_type', 'client')->count(),
            'total_calls' => Call::count(),
            'calls_today' => Call::whereDate('created_at', today())->count(),
            'calls_this_month' => Call::whereMonth('created_at', now()->month)->count(),
            'database_size' => $this->getDatabaseSize(),
        ];
    }

    private function getCacheStats(): array
    {
        try {
            $cacheDriver = config('cache.default');
            $cacheWorks = Cache::has('test') || Cache::put('test', true, 1);

            return [
                'driver' => $cacheDriver,
                'status' => $cacheWorks ? '✅ Working' : '❌ Not Working',
                'enabled' => $cacheWorks,
            ];
        } catch (\Exception $e) {
            return [
                'driver' => 'unknown',
                'status' => '❌ Error: ' . $e->getMessage(),
                'enabled' => false,
            ];
        }
    }

    private function getSecurityStats(): array
    {
        // Get users by role
        $superAdmins = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super-admin', 'super_admin', 'Super Admin']);
        })->count();

        $resellers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['reseller_admin', 'reseller_owner', 'reseller_support']);
        })->count();

        $customers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['company_admin', 'company_owner', 'company_staff']);
        })->count();

        // Recent failed logins (if activity log exists)
        $failedLogins = 0;
        if (DB::getSchemaBuilder()->hasTable('activity_log')) {
            $failedLogins = DB::table('activity_log')
                ->where('description', 'like', '%failed%login%')
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->count();
        }

        return [
            'super_admins' => $superAdmins,
            'resellers' => $resellers,
            'customers' => $customers,
            'total_users' => User::count(),
            'failed_logins_7d' => $failedLogins,
            'ssl_enabled' => request()->secure() ? '✅ Yes' : '⚠️ No',
        ];
    }

    private function getRecentActivity(): array
    {
        // Get recent users (last 10)
        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at->diffForHumans(),
                    'roles' => $user->roles->pluck('name')->join(', '),
                ];
            })
            ->toArray();

        // Get recent companies (last 10)
        $recentCompanies = Company::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'type' => $company->is_reseller ? 'Reseller' : 'Customer',
                    'created_at' => $company->created_at->diffForHumans(),
                ];
            })
            ->toArray();

        return [
            'users' => $recentUsers,
            'companies' => $recentCompanies,
        ];
    }

    private function getSystemHealth(): array
    {
        $health = [
            'database' => '✅ OK',
            'cache' => '✅ OK',
            'storage' => '✅ OK',
            'queue' => '⚠️ Unknown',
            'overall' => '✅ Healthy',
        ];

        $issues = [];

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $health['database'] = '❌ Error';
            $issues[] = 'Database connection failed';
            $health['overall'] = '❌ Unhealthy';
        }

        // Check cache
        try {
            Cache::put('health-check', true, 1);
            if (!Cache::get('health-check')) {
                $health['cache'] = '⚠️ Warning';
                $issues[] = 'Cache not working properly';
            }
        } catch (\Exception $e) {
            $health['cache'] = '❌ Error';
            $issues[] = 'Cache error: ' . $e->getMessage();
            $health['overall'] = '❌ Unhealthy';
        }

        // Check storage
        $diskFreeSpace = disk_free_space('/');
        $diskTotalSpace = disk_total_space('/');
        $diskUsagePercent = (($diskTotalSpace - $diskFreeSpace) / $diskTotalSpace) * 100;

        if ($diskUsagePercent > 90) {
            $health['storage'] = '❌ Critical';
            $issues[] = 'Disk usage above 90%';
            $health['overall'] = '❌ Unhealthy';
        } elseif ($diskUsagePercent > 80) {
            $health['storage'] = '⚠️ Warning';
            $issues[] = 'Disk usage above 80%';
            if ($health['overall'] === '✅ Healthy') {
                $health['overall'] = '⚠️ Warning';
            }
        }

        return [
            'status' => $health,
            'issues' => $issues,
        ];
    }

    private function getDiskUsage(): array
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $used = $total - $free;
        $usedPercent = ($used / $total) * 100;

        return [
            'free' => $this->formatBytes($free),
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'percent' => round($usedPercent, 2),
        ];
    }

    private function getDatabaseSize(): string
    {
        try {
            $database = config('database.connections.mysql.database');
            $result = DB::select("
                SELECT
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ", [$database]);

            if (!empty($result)) {
                return $result[0]->size_mb . ' MB';
            }
        } catch (\Exception $e) {
            return 'Unknown';
        }

        return 'Unknown';
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    // Action methods for quick tasks
    public function clearCache(): void
    {
        try {
            Artisan::call('cache:clear');

            Notification::make()
                ->title('Cache geleert')
                ->body('Der Application Cache wurde erfolgreich geleert.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler')
                ->body('Cache konnte nicht geleert werden: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function clearViewCache(): void
    {
        try {
            Artisan::call('view:clear');

            Notification::make()
                ->title('View Cache geleert')
                ->body('Der View Cache wurde erfolgreich geleert.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler')
                ->body('View Cache konnte nicht geleert werden: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function optimizeApp(): void
    {
        try {
            Artisan::call('optimize');

            Notification::make()
                ->title('App optimiert')
                ->body('Die Application wurde optimiert (config, routes, views gecacht).')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler')
                ->body('Optimierung fehlgeschlagen: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
