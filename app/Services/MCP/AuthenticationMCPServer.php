<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\PortalUser;
use App\Models\Company;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticationMCPServer
{
    protected string $name = 'authentication';
    protected string $version = '1.0.0';
    protected string $description = 'Authentication and session management for portal users and admins';
    protected array $tools = [];

    public function __construct()
    {
        $this->initializeTools();
    }

    /**
     * Add a tool definition
     */
    protected function addTool(array $tool): void
    {
        $this->tools[$tool['name']] = $tool;
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

        // Generate API token
        $this->addTool([
            'name' => 'generate_api_token',
            'description' => 'Generate a new API token for a user',
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

        // Validate session
        $this->addTool([
            'name' => 'validate_session',
            'description' => 'Validate and debug session data',
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

        // List active tokens
        $this->addTool([
            'name' => 'list_active_tokens',
            'description' => 'List all active personal access tokens for a user',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'email' => [
                        'type' => 'string',
                        'description' => 'Email of the user'
                    ]
                ],
                'required' => ['email']
            ]
        ]);

        // Check CSRF configuration
        $this->addTool([
            'name' => 'check_csrf_config',
            'description' => 'Check CSRF token configuration and exceptions',
            'inputSchema' => [
                'type' => 'object',
                'properties' => []
            ]
        ]);

        // Test authentication
        $this->addTool([
            'name' => 'test_authentication',
            'description' => 'Test authentication for different guards',
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
     * Debug authentication state
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

        // Cookie information
        $state['cookies'] = [
            'has_session_cookie' => request()->hasCookie('askproai_session'),
            'has_xsrf_token' => request()->hasCookie('XSRF-TOKEN'),
            'cookie_names' => array_keys(request()->cookies->all())
        ];

        // Sanctum information
        $state['sanctum'] = [
            'stateful_domains' => config('sanctum.stateful'),
            'current_domain' => request()->getHost(),
            'is_stateful' => $this->isStatefulRequest()
        ];

        return [
            'success' => true,
            'authentication_state' => $state
        ];
    }

    /**
     * Generate API token
     */
    public function generateApiToken(array $params): array
    {
        $email = $params['email'];
        $password = $params['password'];
        $tokenName = $params['token_name'] ?? 'api-token';
        $abilities = $params['abilities'] ?? ['*'];

        // Try to find user
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            // Try portal user
            $user = PortalUser::where('email', $email)->first();
        }

        if (!$user || !Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'error' => 'Invalid credentials'
            ];
        }

        // Create token
        $token = $user->createToken($tokenName, $abilities);

        return [
            'success' => true,
            'token' => $token->plainTextToken,
            'token_id' => $token->accessToken->id,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'type' => get_class($user)
            ]
        ];
    }

    /**
     * Validate session
     */
    public function validateSession(array $params): array
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

        // Extract useful information
        $info = [
            'id' => $sessionData->id,
            'user_id' => $sessionData->user_id,
            'ip_address' => $sessionData->ip_address,
            'user_agent' => $sessionData->user_agent,
            'last_activity' => date('Y-m-d H:i:s', $sessionData->last_activity),
            'csrf_token' => $data['_token'] ?? null,
            'authenticated' => isset($data['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d']),
            'auth_user_id' => $data['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'] ?? null,
            'flash_data' => $data['_flash'] ?? null
        ];

        return [
            'success' => true,
            'session' => $info,
            'raw_keys' => array_keys($data)
        ];
    }

    /**
     * List active tokens
     */
    public function listActiveTokens(array $params): array
    {
        $email = $params['email'];
        
        // Find user
        $user = User::where('email', $email)->first() 
            ?: PortalUser::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'error' => 'User not found'
            ];
        }

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
                'type' => get_class($user)
            ],
            'tokens' => $tokenList,
            'total' => count($tokenList)
        ];
    }

    /**
     * Check CSRF configuration
     */
    public function checkCsrfConfig(array $params): array
    {
        $csrfMiddleware = new \App\Http\Middleware\VerifyCsrfToken(
            app(),
            app('encrypter')
        );
        
        $reflection = new \ReflectionClass($csrfMiddleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $exceptions = $property->getValue($csrfMiddleware);

        return [
            'success' => true,
            'csrf_config' => [
                'exceptions' => $exceptions,
                'sanctum_middleware' => config('sanctum.middleware'),
                'session_driver' => config('session.driver'),
                'session_domain' => config('session.domain'),
                'session_secure' => config('session.secure'),
                'sanctum_stateful_domains' => config('sanctum.stateful')
            ]
        ];
    }

    /**
     * Test authentication
     */
    public function testAuthentication(array $params): array
    {
        $email = $params['email'];
        $password = $params['password'];
        $guard = $params['guard'] ?? 'web';

        // Get the appropriate user model
        $userModel = $guard === 'portal' ? PortalUser::class : User::class;
        $user = $userModel::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'error' => 'User not found',
                'guard' => $guard,
                'model' => $userModel
            ];
        }

        // Test password
        $passwordValid = Hash::check($password, $user->password);

        // Try to authenticate
        $authenticated = false;
        $authError = null;
        
        try {
            $authenticated = Auth::guard($guard)->attempt([
                'email' => $email,
                'password' => $password
            ]);
        } catch (\Exception $e) {
            $authError = $e->getMessage();
        }

        return [
            'success' => $authenticated,
            'guard' => $guard,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'active' => $user->is_active ?? true,
                'type' => get_class($user)
            ],
            'password_valid' => $passwordValid,
            'authenticated' => $authenticated,
            'error' => $authError
        ];
    }

    /**
     * Check if request is stateful
     */
    private function isStatefulRequest(): bool
    {
        $request = request();
        
        if (! $request) {
            return false;
        }

        $domain = $request->headers->get('referer') ?: $request->headers->get('origin');

        if (! $domain) {
            return false;
        }

        $domain = parse_url($domain, PHP_URL_HOST);

        $stateful = config('sanctum.stateful', []);

        foreach ($stateful as $statefulDomain) {
            if ($domain === $statefulDomain || 
                (str_starts_with($statefulDomain, '.') && 
                 str_ends_with($domain, $statefulDomain))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle tool calls
     */
    public function handleToolCall(string $toolName, array $arguments): array
    {
        return match($toolName) {
            'debug_auth_state' => $this->debugAuthState($arguments),
            'generate_api_token' => $this->generateApiToken($arguments),
            'validate_session' => $this->validateSession($arguments),
            'list_active_tokens' => $this->listActiveTokens($arguments),
            'check_csrf_config' => $this->checkCsrfConfig($arguments),
            'test_authentication' => $this->testAuthentication($arguments),
            default => ['error' => "Unknown tool: {$toolName}"]
        };
    }
}