<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ApplyCriticalSecurityFixes extends Command
{
    protected $signature = 'security:apply-critical-fixes';
    protected $description = 'Apply critical security fixes for SQL injection and multi-tenancy bypass';

    public function handle()
    {
        $this->info('🔐 APPLYING CRITICAL SECURITY FIXES');
        $this->info('===================================');
        
        // Fix 1: SQL Injection in DatabaseMCPServer
        $this->fixDatabaseMCPServer();
        
        // Fix 2: Multi-tenancy bypass in CompanyScope
        $this->fixCompanyScope();
        
        // Fix 3: Implement proper TenantScope
        $this->fixTenantScope();
        
        // Fix 4: Create SQL protection service
        $this->createSqlProtectionService();
        
        // Fix 5: Create authentication validation middleware
        $this->createAuthValidationMiddleware();
        
        $this->info('');
        $this->info('🎉 All critical security fixes applied!');
        $this->info('');
        $this->info('Next steps:');
        $this->info('1. Add ValidateCompanyContext middleware to kernel.php');
        $this->info('2. Clear all caches: php artisan optimize:clear');
        $this->info('3. Run security audit: php artisan askproai:security-audit');
        $this->info('4. Deploy to production immediately');
    }
    
    private function fixDatabaseMCPServer()
    {
        $this->info('1. Fixing SQL Injection vulnerabilities...');
        
        $filePath = app_path('Services/MCP/DatabaseMCPServer.php');
        if (!File::exists($filePath)) {
            $this->error('DatabaseMCPServer.php not found!');
            return;
        }
        
        $content = File::get($filePath);
        
        // Fix getTableColumns method - use parameterized query
        $content = preg_replace(
            '/DB::select\("SHOW COLUMNS FROM [`]?\$table[`]?"\)/',
            'DB::select("SHOW COLUMNS FROM " . self::quoteIdentifier($table))',
            $content
        );
        
        // Fix getTableIndexes method
        $content = preg_replace(
            '/DB::select\("SHOW INDEX FROM [`]?\$table[`]?"\)/',
            'DB::select("SHOW INDEX FROM " . self::quoteIdentifier($table))',
            $content
        );
        
        // Add quoteIdentifier method if not exists
        if (!str_contains($content, 'quoteIdentifier')) {
            $quoteMethod = <<<'PHP'
    
    /**
     * Safely quote an identifier (table/column name)
     */
    protected static function quoteIdentifier(string $identifier): string
    {
        // Remove any non-alphanumeric characters except underscore
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
        return "`{$safe}`";
    }
}
PHP;
            $content = preg_replace('/}\s*$/', $quoteMethod, $content);
        }
        
        // Fix searchTable method to use query builder
        $searchTableFix = <<<'PHP'
    protected function searchTable(string $table, string $searchTerm): array
    {
        if (!in_array($table, $this->allowedTables)) {
            return [];
        }
        
        try {
            // Get searchable columns safely
            $columns = $this->getTableColumns($table);
            $searchableColumns = array_filter($columns, function ($col) {
                return strpos($col['type'], 'varchar') !== false || 
                       strpos($col['type'], 'text') !== false;
            });
            
            if (empty($searchableColumns)) {
                return [];
            }
            
            // Use query builder for safe query construction
            $query = DB::table($table)->limit(20);
            
            $query->where(function($q) use ($searchableColumns, $searchTerm) {
                foreach ($searchableColumns as $col) {
                    $q->orWhere($col['name'], 'LIKE', '%' . $searchTerm . '%');
                }
            });
            
            $results = $query->get()->toArray();
            return array_map(function($item) {
                return (array) $item;
            }, $results);
            
        } catch (\Exception $e) {
            Log::error('Search table error', [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
PHP;
        
        // Replace the searchTable method
        $content = preg_replace(
            '/protected function searchTable\([^{]+\{[^}]+return \$result\[\'data\'\] \?\? \[\];\s*\}/s',
            $searchTableFix,
            $content
        );
        
        File::put($filePath, $content);
        $this->info('✅ Fixed SQL injection vulnerabilities in DatabaseMCPServer');
    }
    
    private function fixCompanyScope()
    {
        $this->info('2. Fixing multi-tenancy bypass...');
        
        $companyScopeContent = <<<'PHP'
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Log;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply if we have a company context
        if ($companyId = $this->getCompanyId()) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }

    /**
     * Get the current company ID from various sources
     * SECURITY: Only trust authenticated user's company_id
     */
    protected function getCompanyId(): ?int
    {
        // ONLY trust the authenticated user's company_id
        if (auth()->check() && auth()->user()->company_id) {
            return (int) auth()->user()->company_id;
        }

        // For system/admin operations (jobs, commands)
        if (app()->runningInConsole()) {
            // Check if a specific company context was set programmatically
            return app()->has('tenant.id') ? (int) app('tenant.id') : null;
        }

        // Log potential security issue if headers are being used
        if (request()->hasHeader('X-Company-Id')) {
            Log::warning('Attempted to use X-Company-Id header for tenant isolation', [
                'ip' => request()->ip(),
                'url' => request()->url(),
                'user_id' => auth()->id()
            ]);
        }

        return null;
    }
}
PHP;

        File::put(app_path('Models/Scopes/CompanyScope.php'), $companyScopeContent);
        $this->info('✅ Fixed multi-tenancy bypass in CompanyScope');
    }
    
    private function fixTenantScope()
    {
        $this->info('3. Implementing proper TenantScope...');
        
        $tenantScopeContent = <<<'PHP'
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Use CompanyScope for actual implementation
        $companyScope = new CompanyScope();
        $companyScope->apply($builder, $model);
    }
}
PHP;

        File::put(app_path('Models/Scopes/TenantScope.php'), $tenantScopeContent);
        $this->info('✅ Implemented proper TenantScope');
    }
    
    private function createSqlProtectionService()
    {
        $this->info('4. Creating SQL injection protection service...');
        
        $sqlProtectionService = <<<'PHP'
<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\DB;

class SqlProtectionService
{
    /**
     * Safely quote a table name
     */
    public static function quoteTable(string $table): string
    {
        // Remove any non-alphanumeric characters except underscore
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        // Wrap in backticks for MySQL
        return "`{$safe}`";
    }
    
    /**
     * Safely quote a column name
     */
    public static function quoteColumn(string $column): string
    {
        // Remove any non-alphanumeric characters except underscore
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        
        // Wrap in backticks for MySQL
        return "`{$safe}`";
    }
    
    /**
     * Validate and sanitize order by direction
     */
    public static function sanitizeOrderDirection(string $direction): string
    {
        $direction = strtoupper($direction);
        return in_array($direction, ['ASC', 'DESC']) ? $direction : 'ASC';
    }
    
    /**
     * Check if a table exists and is allowed
     */
    public static function isTableAllowed(string $table, array $allowedTables): bool
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        return in_array($safe, $allowedTables);
    }
}
PHP;

        // Create Security directory if it doesn't exist
        if (!File::exists(app_path('Services/Security'))) {
            File::makeDirectory(app_path('Services/Security'), 0755, true);
        }

        File::put(app_path('Services/Security/SqlProtectionService.php'), $sqlProtectionService);
        $this->info('✅ Created SQL injection protection service');
    }
    
    private function createAuthValidationMiddleware()
    {
        $this->info('5. Creating authentication validation middleware...');
        
        $authValidationMiddleware = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValidateCompanyContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip for public routes
        if ($request->is('api/health') || $request->is('api/webhook/*')) {
            return $next($request);
        }
        
        // Ensure user is authenticated for protected routes
        if (!auth()->check() && !$request->is('login', 'register', 'password/*')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        // Log and reject any attempts to use X-Company-Id header
        if ($request->hasHeader('X-Company-Id')) {
            Log::warning('Rejected request with X-Company-Id header', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'user_id' => auth()->id(),
                'header_value' => $request->header('X-Company-Id')
            ]);
            
            // Remove the header to prevent any accidental usage
            $request->headers->remove('X-Company-Id');
        }
        
        return $next($request);
    }
}
PHP;

        File::put(app_path('Http/Middleware/ValidateCompanyContext.php'), $authValidationMiddleware);
        $this->info('✅ Created authentication validation middleware');
    }
}