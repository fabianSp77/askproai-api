<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * MCP Authentication Middleware
 * 
 * Provides secure authentication for MCP endpoints:
 * - JWT/Bearer token validation
 * - API key authentication
 * - Rate limiting per company/reseller
 * - Request signature verification
 * - IP whitelisting for sensitive operations
 */
class MCPAuthenticationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        try {
            // Rate limiting check first
            if (!$this->checkRateLimit($request)) {
                return $this->rateLimitExceeded();
            }
            
            // Authenticate the request
            $authResult = $this->authenticateRequest($request, $guards);
            if (!$authResult['success']) {
                return $this->authenticationFailed($authResult['error']);
            }
            
            // Add authenticated context to request
            $request->merge([
                'mcp_auth_context' => $authResult['context']
            ]);
            
            // Log successful authentication
            $this->logAuthenticationSuccess($request, $authResult['context']);
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('MCP Authentication error', [
                'error' => $e->getMessage(),
                'request_uri' => $request->getUri(),
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Authentication failed',
                'code' => 'AUTH_ERROR'
            ], 500);
        }
    }
    
    /**
     * Authenticate the incoming request
     */
    protected function authenticateRequest(Request $request, array $guards): array
    {
        // Try JWT authentication first
        if ($request->bearerToken()) {
            return $this->authenticateJWT($request->bearerToken(), $request);
        }
        
        // Try API key authentication
        if ($request->header('X-API-Key')) {
            return $this->authenticateApiKey($request->header('X-API-Key'), $request);
        }
        
        // Try signature authentication for webhooks
        if ($request->header('X-Signature')) {
            return $this->authenticateSignature($request);
        }
        
        return [
            'success' => false,
            'error' => 'No valid authentication method provided'
        ];
    }
    
    /**
     * Authenticate using JWT Bearer token
     */
    protected function authenticateJWT(string $token, Request $request): array
    {
        try {
            // Validate JWT token structure
            $tokenParts = explode('.', $token);
            if (count($tokenParts) !== 3) {
                return ['success' => false, 'error' => 'Invalid JWT format'];
            }
            
            // Decode and validate payload
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            if (!$payload) {
                return ['success' => false, 'error' => 'Invalid JWT payload'];
            }
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return ['success' => false, 'error' => 'Token expired'];
            }
            
            // Validate signature
            $secret = config('app.mcp_jwt_secret');
            $expectedSignature = hash_hmac('sha256', $tokenParts[0] . '.' . $tokenParts[1], $secret, true);
            $actualSignature = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[2]));
            
            if (!hash_equals($expectedSignature, $actualSignature)) {
                return ['success' => false, 'error' => 'Invalid signature'];
            }
            
            // Check IP whitelist if configured
            if (isset($payload['allowed_ips']) && !in_array($request->ip(), $payload['allowed_ips'])) {
                return ['success' => false, 'error' => 'IP not authorized'];
            }
            
            return [
                'success' => true,
                'context' => [
                    'auth_type' => 'jwt',
                    'company_id' => $payload['company_id'] ?? null,
                    'reseller_id' => $payload['reseller_id'] ?? null,
                    'permissions' => $payload['permissions'] ?? [],
                    'ip' => $request->ip(),
                    'expires_at' => $payload['exp'] ?? null
                ]
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'JWT validation failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Authenticate using API key
     */
    protected function authenticateApiKey(string $apiKey, Request $request): array
    {
        try {
            // Check API key format
            if (!preg_match('/^mcp_[a-zA-Z0-9]{32}$/', $apiKey)) {
                return ['success' => false, 'error' => 'Invalid API key format'];
            }
            
            // Check cache first for performance
            $cacheKey = "mcp_api_key_{$apiKey}";
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                if ($cached === 'invalid') {
                    return ['success' => false, 'error' => 'Invalid API key'];
                }
                
                return [
                    'success' => true,
                    'context' => array_merge($cached, [
                        'auth_type' => 'api_key',
                        'ip' => $request->ip()
                    ])
                ];
            }
            
            // Validate against database
            $apiKeyRecord = \App\Models\MCPApiKey::where('key', $apiKey)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();
            
            if (!$apiKeyRecord) {
                Cache::put($cacheKey, 'invalid', 300); // Cache negative result
                return ['success' => false, 'error' => 'Invalid or expired API key'];
            }
            
            // Check IP restrictions
            if ($apiKeyRecord->allowed_ips && !in_array($request->ip(), $apiKeyRecord->allowed_ips)) {
                return ['success' => false, 'error' => 'IP not authorized for this API key'];
            }
            
            $context = [
                'company_id' => $apiKeyRecord->company_id,
                'reseller_id' => $apiKeyRecord->reseller_id,
                'permissions' => $apiKeyRecord->permissions ?? [],
                'rate_limit' => $apiKeyRecord->rate_limit ?? 100
            ];
            
            // Cache positive result
            Cache::put($cacheKey, $context, 300);
            
            return [
                'success' => true,
                'context' => array_merge($context, [
                    'auth_type' => 'api_key',
                    'ip' => $request->ip()
                ])
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'API key validation failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Authenticate using request signature (for webhooks)
     */
    protected function authenticateSignature(Request $request): array
    {
        try {
            $signature = $request->header('X-Signature');
            $timestamp = $request->header('X-Timestamp');
            $companyId = $request->input('company_id');
            
            if (!$signature || !$timestamp || !$companyId) {
                return ['success' => false, 'error' => 'Missing signature headers'];
            }
            
            // Check timestamp to prevent replay attacks
            if (abs(time() - (int)$timestamp) > 300) { // 5 minutes tolerance
                return ['success' => false, 'error' => 'Request timestamp too old'];
            }
            
            // Get webhook secret for company
            $company = \App\Models\Company::find($companyId);
            if (!$company) {
                return ['success' => false, 'error' => 'Company not found'];
            }
            
            $secret = $company->settings['mcp_webhook_secret'] ?? null;
            if (!$secret) {
                return ['success' => false, 'error' => 'Webhook secret not configured'];
            }
            
            // Verify signature
            $payload = $request->getContent();
            $expectedSignature = hash_hmac('sha256', $timestamp . $payload, $secret);
            
            if (!hash_equals($signature, $expectedSignature)) {
                return ['success' => false, 'error' => 'Invalid signature'];
            }
            
            return [
                'success' => true,
                'context' => [
                    'auth_type' => 'webhook_signature',
                    'company_id' => $companyId,
                    'ip' => $request->ip(),
                    'timestamp' => $timestamp
                ]
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Signature validation failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check rate limiting
     */
    protected function checkRateLimit(Request $request): bool
    {
        $key = $this->getRateLimitKey($request);
        
        // Different limits based on authentication type
        $maxAttempts = 60; // Default
        $decayMinutes = 1;
        
        // Higher limits for authenticated API requests
        if ($request->bearerToken() || $request->header('X-API-Key')) {
            $maxAttempts = 1000;
            $decayMinutes = 1;
        }
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return false;
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        return true;
    }
    
    /**
     * Get rate limit key
     */
    protected function getRateLimitKey(Request $request): string
    {
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            return 'mcp_api_' . $apiKey;
        }
        
        $companyId = $request->input('company_id');
        if ($companyId) {
            return 'mcp_company_' . $companyId;
        }
        
        return 'mcp_ip_' . $request->ip();
    }
    
    /**
     * Log successful authentication
     */
    protected function logAuthenticationSuccess(Request $request, array $context): void
    {
        Log::info('MCP Authentication successful', [
            'auth_type' => $context['auth_type'],
            'company_id' => $context['company_id'] ?? null,
            'ip' => $context['ip'],
            'endpoint' => $request->path(),
            'method' => $request->method()
        ]);
    }
    
    /**
     * Return rate limit exceeded response
     */
    protected function rateLimitExceeded(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Rate limit exceeded',
            'code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => 60
        ], 429);
    }
    
    /**
     * Return authentication failed response
     */
    protected function authenticationFailed(string $error): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $error,
            'code' => 'AUTHENTICATION_FAILED'
        ], 401);
    }
}