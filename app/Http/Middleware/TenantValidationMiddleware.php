<?php

namespace App\Http\Middleware;

use App\Services\TenantContextService;
use App\Exceptions\TenantContextException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Validation Middleware
 * 
 * This middleware ensures proper tenant context is established for all requests
 * and validates tenant isolation throughout the request lifecycle.
 * 
 * Key Features:
 * - Automatic tenant context detection and validation
 * - Security monitoring for tenant isolation breaches
 * - Performance optimized with minimal overhead
 * - Comprehensive audit logging
 */
class TenantValidationMiddleware
{
    protected TenantContextService $tenantContext;
    
    public function __construct(TenantContextService $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }
    
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next, string ...$options): Response
    {
        $startTime = microtime(true);
        
        // Parse middleware options
        $config = $this->parseOptions($options);
        
        // Set up tenant context early in the request
        $this->establishTenantContext($request, $config);
        
        try {
            $response = $next($request);
            
            // Validate tenant isolation after request processing
            $this->validateTenantIsolation($request, $response, $config);
            
            return $response;
        } catch (TenantContextException $e) {
            // Handle tenant context exceptions gracefully
            return $this->handleTenantException($e, $request);
        } finally {
            // Log performance metrics
            $this->logPerformanceMetrics($request, microtime(true) - $startTime);
        }
    }
    
    /**
     * Parse middleware options
     */
    protected function parseOptions(array $options): array
    {
        $config = [
            'require_tenant' => true,
            'allow_cross_tenant' => false,
            'audit_level' => 'standard',
            'performance_monitoring' => true
        ];
        
        foreach ($options as $option) {
            switch ($option) {
                case 'optional':
                    $config['require_tenant'] = false;
                    break;
                case 'cross_tenant':
                    $config['allow_cross_tenant'] = true;
                    break;
                case 'strict_audit':
                    $config['audit_level'] = 'strict';
                    break;
                case 'no_audit':
                    $config['audit_level'] = 'none';
                    break;
                case 'no_performance':
                    $config['performance_monitoring'] = false;
                    break;
            }
        }
        
        return $config;
    }
    
    /**
     * Establish tenant context for the request
     */
    protected function establishTenantContext(Request $request, array $config): void
    {
        // Check if we already have a valid tenant context
        $currentCompanyId = $this->tenantContext->getCurrentCompanyId();
        
        if ($currentCompanyId) {
            // Context already established, validate it
            $this->validateExistingContext($request, $currentCompanyId, $config);
            return;
        }
        
        // Try to establish context from authentication
        if ($user = $request->user()) {
            if (isset($user->company_id) && $user->company_id) {
                $this->tenantContext->setWebAuthContext(
                    $user->company_id,
                    'middleware_auth_detection'
                );
                
                $this->auditTenantOperation('context_established_from_auth', [
                    'company_id' => $user->company_id,
                    'user_id' => $user->id,
                    'route' => $request->route()?->getName()
                ]);
                
                return;
            }
        }
        
        // Check portal authentication
        if ($portalUser = auth('portal')->user()) {
            if (isset($portalUser->company_id) && $portalUser->company_id) {
                $this->tenantContext->setWebAuthContext(
                    $portalUser->company_id,
                    'middleware_portal_auth'
                );
                
                $this->auditTenantOperation('context_established_from_portal', [
                    'company_id' => $portalUser->company_id,
                    'user_id' => $portalUser->id,
                    'route' => $request->route()?->getName()
                ]);
                
                return;
            }
        }
        
        // Handle missing tenant context
        $this->handleMissingTenantContext($request, $config);
    }
    
    /**
     * Validate existing tenant context
     */
    protected function validateExistingContext(Request $request, int $companyId, array $config): void
    {
        // Ensure the authenticated user belongs to the current tenant context
        if ($user = $request->user()) {
            if (isset($user->company_id) && $user->company_id !== $companyId) {
                $this->auditTenantOperation('context_user_mismatch', [
                    'context_company_id' => $companyId,
                    'user_company_id' => $user->company_id,
                    'user_id' => $user->id,
                    'route' => $request->route()?->getName()
                ]);
                
                throw new TenantContextException(
                    'User does not belong to current tenant context'
                );
            }
        }
        
        // Check for suspicious tenant switching attempts
        $this->detectTenantSwitchingAttempts($request, $companyId);
    }
    
    /**
     * Handle missing tenant context
     */
    protected function handleMissingTenantContext(Request $request, array $config): void
    {
        if ($config['require_tenant']) {
            // Check if this is a route that should have tenant context
            $routeName = $request->route()?->getName();
            $exemptRoutes = [
                'login',
                'register',
                'password.*',
                'verification.*',
                'webhooks.*',
                'health-check',
                'metrics'
            ];
            
            $isExempt = false;
            foreach ($exemptRoutes as $pattern) {
                if (fnmatch($pattern, $routeName)) {
                    $isExempt = true;
                    break;
                }
            }
            
            if (!$isExempt) {
                $this->auditTenantOperation('missing_tenant_context', [
                    'route' => $routeName,
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                    'has_auth' => auth()->check()
                ]);
                
                throw new TenantContextException(
                    'No tenant context available for authenticated request'
                );
            }
        }
    }
    
    /**
     * Detect tenant switching attempts
     */
    protected function detectTenantSwitchingAttempts(Request $request, int $currentCompanyId): void
    {
        // Check for suspicious headers or parameters
        $suspiciousSources = [
            'header_company_id' => $request->header('X-Company-Id'),
            'header_tenant_id' => $request->header('X-Tenant-Id'),
            'query_company_id' => $request->query('company_id'),
            'query_tenant_id' => $request->query('tenant_id'),
            'post_company_id' => $request->input('company_id'),
            'post_tenant_id' => $request->input('tenant_id')
        ];
        
        $violations = [];
        foreach ($suspiciousSources as $source => $value) {
            if ($value && (int)$value !== $currentCompanyId) {
                $violations[$source] = $value;
            }
        }
        
        if (!empty($violations)) {
            $this->auditTenantOperation('tenant_switching_attempt', [
                'current_company_id' => $currentCompanyId,
                'attempted_sources' => $violations,
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            // Log as security incident
            Log::channel('security')->critical('Tenant switching attempt detected', [
                'current_company_id' => $currentCompanyId,
                'violations' => $violations,
                'request' => [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ],
                'user' => [
                    'id' => auth()->id(),
                    'email' => auth()->user()?->email
                ]
            ]);
        }
    }
    
    /**
     * Validate tenant isolation after request processing
     */
    protected function validateTenantIsolation(Request $request, Response $response, array $config): void
    {
        if ($config['audit_level'] === 'none') {
            return;
        }
        
        // Check if any cross-tenant operations were performed
        $auditLog = $this->tenantContext->getAuditLog();
        $crossTenantOperations = array_filter($auditLog, function ($entry) {
            return str_contains($entry['event'], 'cross_tenant');
        });
        
        if (!empty($crossTenantOperations) && !$config['allow_cross_tenant']) {
            $this->auditTenantOperation('unauthorized_cross_tenant_operation', [
                'operations' => $crossTenantOperations,
                'route' => $request->route()?->getName()
            ]);
            
            Log::channel('security')->warning('Unauthorized cross-tenant operations detected', [
                'operations' => $crossTenantOperations,
                'request' => [
                    'url' => $request->fullUrl(),
                    'method' => $request->method()
                ]
            ]);
        }
        
        // Add tenant context headers to response for debugging
        if (app()->hasDebugModeEnabled() && $this->tenantContext->getCurrentCompanyId()) {
            $response->headers->set('X-Tenant-Context', $this->tenantContext->getCurrentCompanyId());
            $response->headers->set('X-Tenant-Operations', count($auditLog));
        }
    }
    
    /**
     * Handle tenant context exceptions
     */
    protected function handleTenantException(TenantContextException $e, Request $request): Response
    {
        $this->auditTenantOperation('tenant_exception', [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl()
        ]);
        
        // Return appropriate response based on request type
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant access denied',
                'message' => 'You do not have permission to access this resource.',
                'code' => $e->getCode()
            ], $e->getCode());
        }
        
        // Redirect to appropriate error page
        if ($e->getCode() === 403) {
            return redirect()->route('tenant.access-denied')
                ->with('error', 'Access denied. Please contact your administrator.');
        }
        
        return response()->view('errors.tenant-context', [
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ], $e->getCode());
    }
    
    /**
     * Log performance metrics
     */
    protected function logPerformanceMetrics(Request $request, float $executionTime): void
    {
        if ($executionTime > 0.1) { // Log slow tenant validations
            Log::info('Tenant validation performance', [
                'execution_time' => $executionTime,
                'route' => $request->route()?->getName(),
                'company_id' => $this->tenantContext->getCurrentCompanyId()
            ]);
        }
    }
    
    /**
     * Audit tenant operations
     */
    protected function auditTenantOperation(string $event, array $data = []): void
    {
        Log::channel('security')->info("TenantMiddleware: {$event}", array_merge($data, [
            'timestamp' => now()->toISOString(),
            'request_id' => request()->header('X-Request-ID', uniqid()),
            'middleware' => self::class
        ]));
    }
}