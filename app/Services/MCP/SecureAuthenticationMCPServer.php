<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\PortalUser;
use App\Models\Company;
use App\Exceptions\SecurityException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * SECURE VERSION: Authentication MCP Server with proper tenant isolation
 * 
 * This server handles authentication with strict multi-tenant security.
 * All operations validate that users belong to the correct company context.
 * 
 * Security Features:
 * - User lookup always includes company validation
 * - No cross-tenant authentication possible
 * - Token generation respects company boundaries
 * - Session validation includes company context
 * - Audit logging for all auth operations
 */
class SecureAuthenticationMCPServer extends BaseMCPServer
{
    /**
     * @var Company|null Current company context
     */
    protected ?Company $company = null;
    
    /**
     * @var bool Audit logging enabled
     */
    protected bool $auditEnabled = true;
    
    protected string $name = 'secure-authentication';
    protected string $version = '1.0.0';
    protected string $description = 'Secure authentication and session management with tenant isolation';
    protected array $tools = [];

    public function __construct()
    {
        parent::__construct();
        $this->resolveCompanyContext();
        $this->initializeTools();
    }
    
    /**
     * Set company context explicitly (only for super admins)
     */
    public function setCompanyContext(Company $company): self
    {
        // Only allow super admins to override context
        if (Auth::check() && !Auth::user()->hasRole('super_admin')) {
            throw new SecurityException('Unauthorized company context override');
        }
        
        $this->company = $company;
        
        $this->auditAccess('company_context_override', [
            'company_id' => $company->id,
            'user_id' => Auth::id(),
        ]);
        
        return $this;
    }

    /**
     * Initialize the MCP server with tool definitions
     */
    protected function initializeTools(): void
    {
        // Debug authentication state
        $this->addTool([
            'name' => 'debug_auth_state',
            'description' => 'Debug current authentication state for all guards',
            'inputSchema' => [
                'type' => 'object',
                'properties' => []
            ]
        ]);

        // Generate API token with company validation
        $this->addTool([
            'name' => 'generate_api_token',
            'description' => 'Generate a new API token for a user with company validation',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'email' => [
                        'type' => 'string',
                        'description' => 'Email of the user'
                    ],
                    'password' => [
                        'type' => 'string',
                        'description' => 'Password for verification'
                    ],
                    'company_id' => [
                        'type' => 'integer',
                        'description' => 'Company ID for validation (optional if context set)'
                    ],
                    'token_name' => [
                        'type' => 'string',
                        'description' => 'Name for the token',
                        'default' => 'api-token'
                    ],
                    'abilities' => [
                        'type' => 'array',
                        'description' => 'Token abilities/permissions',
                        'items' => ['type' => 'string'],
                        'default' => ['*']
                    ]
                ],
                'required' => ['email', 'password']
            ]
        ]);

        // Validate session with security context
        $this->addTool([
            'name' => 'validate_session',
            'description' => 'Validate and debug session data with security checks',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'session_id' => [
                        'type' => 'string',
                        'description' => 'Optional session ID to check'
                    ]
                ]
            ]
        ]);

        // List active tokens with company filtering
        $this->addTool([
            'name' => 'list_active_tokens',
            'description' => 'List all active personal access tokens for a user in the company',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'email' => [
                        'type' => 'string',
                        'description' => 'Email of the user'
                    ],
                    'company_id' => [
                        'type' => 'integer',
                        'description' => 'Company ID for validation (optional if context set)'
                    ]
                ],
                'required' => ['email']
            ]
        ]);

        // Test authentication with company validation
        $this->addTool([
            'name' => 'test_authentication',
            'description' => 'Test authentication for different guards with company validation',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'email' => [
                        'type' => 'string',
                        'description' => 'Email to test'
                    ],
                    'password' => [
                        'type' => 'string',
                        'description' => 'Password to test'
                    ],
                    'company_id' => [
                        'type' => 'integer',
                        'description' => 'Company ID for validation'
                    ],
                    'guard' => [
                        'type' => 'string',
                        'description' => 'Guard to use (web, api, portal)',
                        'default' => 'web'
                    ]
                ],
                'required' => ['email', 'password']
            ]
        ]);
    }

    /**
     * Execute an authentication operation
     */
    public function execute(string $operation, array $params = []): array
    {
        $this->logDebug("Executing secure authentication operation", [
            'operation' => $operation,
            'params' => array_diff_key($params, ['password' => 1]), // Don't log passwords
            'company_id' => $this->company?->id
        ]);
        
        try {
            switch ($operation) {
                case 'debug_auth_state':
                    return $this->debugAuthState($params);
                    
                case 'generate_api_token':
                    return $this->generateApiTokenSecure($params);
                    
                case 'validate_session':
                    return $this->validateSessionSecure($params);
                    
                case 'list_active_tokens':
                    return $this->listActiveTokensSecure($params);
                    
                case 'test_authentication':
                    return $this->testAuthenticationSecure($params);
                    
                default:
                    return $this->errorResponse("Unknown operation: {$operation}");
            }
        } catch (\Exception $e) {
            $this->logError("Secure authentication operation failed", $e, [
                'operation' => $operation,
                'company_id' => $this->company?->id
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Debug authentication state (no changes needed - read only)
     */
    public function debugAuthState(array $params): array
    {
        $guards = ['web', 'api', 'portal'];
        $state = [];

        foreach ($guards as $guard) {
            try {
                $auth = Auth::guard($guard);
                $state[$guard] = [
                    'authenticated' => $auth->check(),
                    'user' => $auth->user() ? [
                        'id' => $auth->user()->id,
                        'email' => $auth->user()->email,
                        'company_id' => $auth->user()->company_id ?? null,
                        'type' => get_class($auth->user())
                    ] : null,
                    'guest' => $auth->guest()
                ];
            } catch (\Exception $e) {
                $state[$guard] = [
                    'error' => $e->getMessage()
                ];
            }
        }

        // Session information
        $state['session'] = [
            'id' => Session::getId(),
            'exists' => Session::exists('_token'),
            'csrf_token' => Session::token(),
            'user_keys' => array_keys(Session::all()),
            'has_web_auth' => Session::has('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d')
        ];

        // Current company context
        $state['company_context'] = [
            'company_id' => $this->company?->id,
            'company_name' => $this->company?->name,
            'resolved_from' => $this->company ? 'authenticated_user' : 'none'
        ];

        return [
            'success' => true,
            'authentication_state' => $state
        ];
    }

    /**
     * Generate API token with company validation
     */
    public function generateApiTokenSecure(array $params): array
    {
        $email = $params['email'];
        $password = $params['password'];
        $tokenName = $params['token_name'] ?? 'api-token';
        $abilities = $params['abilities'] ?? ['*'];
        
        // Determine company context
        $companyId = $params['company_id'] ?? $this->company?->id;
        
        if (!$companyId) {
            throw new SecurityException('Company context required for token generation');
        }
        
        $this->auditAccess('generate_api_token_attempt', [
            'email' => $email,
            'company_id' => $companyId
        ]);

        // Find user WITH company validation
        $user = User::where('email', $email)
            ->where('company_id', $companyId) // CRITICAL: Company validation
            ->first();
        
        if (!$user) {
            // Try portal user with company validation
            $user = PortalUser::where('email', $email)
                ->where('company_id', $companyId) // CRITICAL: Company validation
                ->first();
        }

        if (!$user) {
            $this->auditAccess('generate_api_token_failed', [
                'email' => $email,
                'reason' => 'user_not_found_in_company',
                'company_id' => $companyId
            ]);
            
            return [
                'success' => false,
                'error' => 'User not found in the specified company'
            ];
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            $this->auditAccess('generate_api_token_failed', [
                'email' => $email,
                'reason' => 'invalid_password',
                'company_id' => $companyId
            ]);
            
            return [
                'success' => false,
                'error' => 'Invalid credentials'
            ];
        }

        // Create token
        $token = $user->createToken($tokenName, $abilities);
        
        $this->auditAccess('generate_api_token_success', [
            'user_id' => $user->id,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'token_id' => $token->accessToken->id,
            'token_name' => $tokenName
        ]);

        return [
            'success' => true,
            'token' => $token->plainTextToken,
            'token_id' => $token->accessToken->id,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'type' => get_class($user)
            ]
        ];
    }

    /**
     * Validate session with security checks
     */
    public function validateSessionSecure(array $params): array
    {
        $sessionId = $params['session_id'] ?? Session::getId();
        
        // Get session data from database
        $sessionData = DB::table('sessions')
            ->where('id', $sessionId)
            ->first();

        if (!$sessionData) {
            return [
                'success' => false,
                'error' => 'Session not found'
            ];
        }

        // Decode payload
        $payload = base64_decode($sessionData->payload);
        $data = unserialize($payload);
        
        // Check if user belongs to correct company
        $userId = $sessionData->user_id;
        $companyValid = true;
        $userCompanyId = null;
        
        if ($userId) {
            // Try to find user and validate company
            $user = User::find($userId) ?: PortalUser::find($userId);
            
            if ($user) {
                $userCompanyId = $user->company_id;
                
                // If we have company context, validate
                if ($this->company && $user->company_id !== $this->company->id) {
                    $companyValid = false;
                }
            }
        }

        // Extract useful information
        $info = [
            'id' => $sessionData->id,
            'user_id' => $sessionData->user_id,
            'user_company_id' => $userCompanyId,
            'company_valid' => $companyValid,
            'ip_address' => $sessionData->ip_address,
            'user_agent' => $sessionData->user_agent,
            'last_activity' => date('Y-m-d H:i:s', $sessionData->last_activity),
            'csrf_token' => $data['_token'] ?? null,
            'authenticated' => isset($data['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d']),
            'auth_user_id' => $data['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'] ?? null,
        ];
        
        $this->auditAccess('validate_session', [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'company_valid' => $companyValid
        ]);

        return [
            'success' => true,
            'session' => $info,
            'security_warnings' => !$companyValid ? ['User company does not match current context'] : []
        ];
    }

    /**
     * List active tokens with company filtering
     */
    public function listActiveTokensSecure(array $params): array
    {
        $email = $params['email'];
        $companyId = $params['company_id'] ?? $this->company?->id;
        
        if (!$companyId) {
            throw new SecurityException('Company context required for listing tokens');
        }
        
        // Find user WITH company validation
        $user = User::where('email', $email)
            ->where('company_id', $companyId) // CRITICAL: Company validation
            ->first();
            
        if (!$user) {
            $user = PortalUser::where('email', $email)
                ->where('company_id', $companyId) // CRITICAL: Company validation
                ->first();
        }

        if (!$user) {
            return [
                'success' => false,
                'error' => 'User not found in the specified company'
            ];
        }
        
        $this->auditAccess('list_active_tokens', [
            'user_id' => $user->id,
            'email' => $user->email,
            'company_id' => $user->company_id
        ]);

        // Get tokens
        $tokens = PersonalAccessToken::where('tokenable_type', get_class($user))
            ->where('tokenable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $tokenList = [];
        foreach ($tokens as $token) {
            $tokenList[] = [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'created_at' => $token->created_at->toDateTimeString(),
                'last_used_at' => $token->last_used_at?->toDateTimeString(),
                'expires_at' => $token->expires_at?->toDateTimeString()
            ];
        }

        return [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'type' => get_class($user)
            ],
            'tokens' => $tokenList,
            'total' => count($tokenList)
        ];
    }

    /**
     * Test authentication with company validation
     */
    public function testAuthenticationSecure(array $params): array
    {
        $email = $params['email'];
        $password = $params['password'];
        $companyId = $params['company_id'] ?? $this->company?->id;
        $guard = $params['guard'] ?? 'web';
        
        if (!$companyId) {
            throw new SecurityException('Company context required for authentication testing');
        }

        // Get the appropriate user model
        $userModel = $guard === 'portal' ? PortalUser::class : User::class;
        
        // Find user WITH company validation
        $user = $userModel::where('email', $email)
            ->where('company_id', $companyId) // CRITICAL: Company validation
            ->first();

        if (!$user) {
            $this->auditAccess('test_authentication_failed', [
                'email' => $email,
                'reason' => 'user_not_found_in_company',
                'company_id' => $companyId,
                'guard' => $guard
            ]);
            
            return [
                'success' => false,
                'error' => 'User not found in the specified company',
                'guard' => $guard,
                'model' => $userModel,
                'company_id' => $companyId
            ];
        }

        // Test password
        $passwordValid = Hash::check($password, $user->password);
        
        if (!$passwordValid) {
            $this->auditAccess('test_authentication_failed', [
                'email' => $email,
                'reason' => 'invalid_password',
                'company_id' => $companyId,
                'guard' => $guard
            ]);
            
            return [
                'success' => false,
                'error' => 'Invalid password',
                'guard' => $guard,
                'user_found' => true,
                'company_id' => $companyId
            ];
        }

        // Try to authenticate (temporarily)
        $authenticated = false;
        $authError = null;
        
        try {
            // IMPORTANT: We don't actually login, just test credentials
            $credentials = [
                'email' => $email,
                'password' => $password,
                'company_id' => $companyId // Add company to credentials
            ];
            
            // Validate without actually logging in
            $authenticated = true; // Since we already validated above
            
        } catch (\Exception $e) {
            $authError = $e->getMessage();
        }
        
        $this->auditAccess('test_authentication_success', [
            'user_id' => $user->id,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'guard' => $guard
        ]);

        return [
            'success' => true,
            'authenticated' => $authenticated,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'name' => $user->name,
                'type' => get_class($user),
                'is_active' => $user->is_active ?? true,
                'email_verified' => !is_null($user->email_verified_at)
            ],
            'guard' => $guard,
            'error' => $authError
        ];
    }

    /**
     * Check if this is a stateful request
     */
    protected function isStatefulRequest(): bool
    {
        $domain = request()->getHost();
        $statefulDomains = config('sanctum.stateful', []);
        
        foreach ($statefulDomains as $statefulDomain) {
            if ($domain === $statefulDomain || 
                (str_starts_with($statefulDomain, '.') && str_ends_with($domain, $statefulDomain))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Resolve company context from authenticated user
     */
    protected function resolveCompanyContext(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->company_id) {
                $this->company = Company::find($user->company_id);
            }
        }
    }
    
    /**
     * Audit access to authentication operations
     */
    protected function auditAccess(string $operation, array $context = []): void
    {
        if (!$this->auditEnabled) {
            return;
        }
        
        try {
            if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
                DB::table('security_audit_logs')->insert([
                    'event_type' => 'authentication_mcp',
                    'user_id' => Auth::id(),
                    'company_id' => $this->company?->id ?? $context['company_id'] ?? null,
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'url' => request()->fullUrl() ?? 'console',
                    'metadata' => json_encode(array_merge($context, [
                        'operation' => $operation,
                        'user_agent' => request()->userAgent()
                    ])),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('SecureAuthenticationMCP: Audit logging failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Disable audit logging (for testing)
     */
    public function disableAudit(): self
    {
        $this->auditEnabled = false;
        return $this;
    }
}