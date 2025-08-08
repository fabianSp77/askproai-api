<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyMCPToken
{
    /**
     * Handle an incoming MCP request.
     * Verifies the Bearer token for MCP server authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the authorization header
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            Log::warning('MCP request missing Authorization header', [
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Authorization header required'
            ], 401);
        }
        
        // Extract Bearer token
        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            Log::warning('MCP request with invalid Authorization format', [
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid authorization format. Use: Bearer <token>'
            ], 401);
        }
        
        $providedToken = $matches[1];
        
        // Get expected MCP tokens from config
        $validTokens = $this->getValidMCPTokens();
        
        // Verify token
        if (!in_array($providedToken, $validTokens, true)) {
            Log::warning('MCP request with invalid token', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'token_prefix' => substr($providedToken, 0, 8) . '...'
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid MCP token'
            ], 403);
        }
        
        // Token is valid - identify which service/agent is calling
        $tokenIdentity = $this->identifyToken($providedToken);
        if ($tokenIdentity) {
            $request->merge(['mcp_identity' => $tokenIdentity]);
            
            // Rate limiting per token
            if (!$this->checkRateLimit($providedToken, $request->ip())) {
                Log::warning('MCP rate limit exceeded', [
                    'identity' => $tokenIdentity,
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Rate limit exceeded'
                ], 429);
            }
            
            Log::info('MCP request authenticated', [
                'identity' => $tokenIdentity,
                'tool' => $request->input('tool'),
                'ip' => $request->ip()
            ]);
        }
        
        return $next($request);
    }
    
    /**
     * Get valid MCP tokens from configuration
     * 
     * @return array
     */
    protected function getValidMCPTokens(): array
    {
        $tokens = [];
        
        // Primary MCP token
        if ($token = config('retell-mcp.security.mcp_token')) {
            $tokens[] = $token;
        }
        
        // Additional tokens from environment (comma-separated)
        if ($envTokens = env('MCP_VALID_TOKENS')) {
            $tokens = array_merge($tokens, explode(',', $envTokens));
        }
        
        // Fallback token for testing (only in non-production)
        if (app()->environment('local', 'staging')) {
            $tokens[] = env('MCP_TEST_TOKEN', 'test_mcp_token_2024');
        }
        
        return array_filter($tokens);
    }
    
    /**
     * Identify which service/agent the token belongs to
     * 
     * @param string $token
     * @return string|null
     */
    protected function identifyToken(string $token): ?string
    {
        // Map tokens to identities (can be extended)
        $tokenMap = [
            env('MCP_RETELL_AGENT_TOKEN') => 'retell_agent_primary',
            env('MCP_RETELL_AGENT_BACKUP_TOKEN') => 'retell_agent_backup',
            env('MCP_TEST_TOKEN', 'test_mcp_token_2024') => 'test_client'
        ];
        
        return $tokenMap[$token] ?? 'unknown';
    }
    
    /**
     * Check rate limit for token
     */
    protected function checkRateLimit(string $token, string $ip): bool
    {
        $limit = config('retell-mcp.security.rate_limit_per_token', 100);
        $key = 'mcp_rate_limit:' . md5($token . ':' . $ip);
        
        $current = Cache::get($key, 0);
        if ($current >= $limit) {
            return false;
        }
        
        Cache::put($key, $current + 1, 60); // 1 minute window
        return true;
    }
}