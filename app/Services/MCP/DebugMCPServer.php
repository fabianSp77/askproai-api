<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\PortalUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

class DebugMCPServer
{
    protected string $name = 'debug';

    protected string $version = '1.0.0';

    protected string $description = 'Debug server for authentication, session, routing, and middleware issues';

    protected array $tools = [];

    public function __construct()
    {
        $this->initializeTools();
    }

    /**
     * Add a tool definition.
     */
    protected function addTool(array $tool): void
    {
        $this->tools[$tool['name']] = $tool;
    }

    /**
     * Initialize the MCP server with tool definitions.
     */
    protected function initializeTools(): void
    {
        // Debug authentication state
        $this->addTool([
            'name' => 'debug_auth_state',
            'description' => 'Debug current authentication state for all guards and sessions',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'verbose' => [
                        'type' => 'boolean',
                        'description' => 'Include detailed session data',
                        'default' => false,
                    ],
                ],
            ],
        ]);

        // Trace request flow
        $this->addTool([
            'name' => 'trace_request_flow',
            'description' => 'Trace how a request flows through middleware and routes',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'method' => [
                        'type' => 'string',
                        'description' => 'HTTP method (GET, POST, etc)',
                        'default' => 'GET',
                    ],
                    'uri' => [
                        'type' => 'string',
                        'description' => 'Request URI to trace',
                    ],
                    'headers' => [
                        'type' => 'object',
                        'description' => 'Request headers',
                        'default' => [],
                    ],
                ],
                'required' => ['uri'],
            ],
        ]);

        // Debug route conflicts
        $this->addTool([
            'name' => 'debug_route_conflicts',
            'description' => 'Find and analyze route conflicts and duplicates',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'pattern' => [
                        'type' => 'string',
                        'description' => 'Route pattern to search for conflicts',
                    ],
                ],
            ],
        ]);

        // Monitor middleware execution
        $this->addTool([
            'name' => 'monitor_middleware',
            'description' => 'Monitor middleware execution order and behavior',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'route' => [
                        'type' => 'string',
                        'description' => 'Route name or URI to check middleware for',
                    ],
                ],
            ],
        ]);

        // Debug session issues
        $this->addTool([
            'name' => 'debug_session',
            'description' => 'Debug session data, configuration, and issues',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'session_id' => [
                        'type' => 'string',
                        'description' => 'Specific session ID to debug',
                    ],
                    'guard' => [
                        'type' => 'string',
                        'description' => 'Auth guard to check',
                        'default' => 'web',
                    ],
                ],
            ],
        ]);

        // Analyze authentication flow
        $this->addTool([
            'name' => 'analyze_auth_flow',
            'description' => 'Analyze the complete authentication flow for a user',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'email' => [
                        'type' => 'string',
                        'description' => 'User email to analyze',
                    ],
                    'user_type' => [
                        'type' => 'string',
                        'enum' => ['admin', 'portal'],
                        'description' => 'Type of user',
                        'default' => 'portal',
                    ],
                ],
                'required' => ['email'],
            ],
        ]);

        // Check permissions
        $this->addTool([
            'name' => 'check_permissions',
            'description' => 'Check user permissions and authorization',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'user_id' => [
                        'type' => 'integer',
                        'description' => 'User ID to check permissions for',
                    ],
                    'permission' => [
                        'type' => 'string',
                        'description' => 'Specific permission to check',
                    ],
                    'guard' => [
                        'type' => 'string',
                        'description' => 'Auth guard',
                        'default' => 'web',
                    ],
                ],
            ],
        ]);

        // Debug CSRF issues
        $this->addTool([
            'name' => 'debug_csrf',
            'description' => 'Debug CSRF token issues and verification',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'token' => [
                        'type' => 'string',
                        'description' => 'CSRF token to verify',
                    ],
                    'session_id' => [
                        'type' => 'string',
                        'description' => 'Session ID to check token against',
                    ],
                ],
            ],
        ]);

        // List all routes
        $this->addTool([
            'name' => 'list_routes',
            'description' => 'List all registered routes with details',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'filter' => [
                        'type' => 'string',
                        'description' => 'Filter routes by URI pattern',
                    ],
                    'method' => [
                        'type' => 'string',
                        'description' => 'Filter by HTTP method',
                    ],
                    'middleware' => [
                        'type' => 'string',
                        'description' => 'Filter by middleware',
                    ],
                ],
            ],
        ]);

        // Debug cache issues
        $this->addTool([
            'name' => 'debug_cache',
            'description' => 'Debug cache issues and inspect cached data',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'key' => [
                        'type' => 'string',
                        'description' => 'Cache key to inspect',
                    ],
                    'store' => [
                        'type' => 'string',
                        'description' => 'Cache store to use',
                        'default' => 'default',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get available tools.
     */
    public function getTools(): array
    {
        return array_values($this->tools);
    }

    /**
     * Execute a tool.
     */
    public function executeTool(string $tool, array $params = []): array
    {
        return match ($tool) {
            'debug_auth_state' => $this->debugAuthState($params),
            'trace_request_flow' => $this->traceRequestFlow($params),
            'debug_route_conflicts' => $this->debugRouteConflicts($params),
            'monitor_middleware' => $this->monitorMiddleware($params),
            'debug_session' => $this->debugSession($params),
            'analyze_auth_flow' => $this->analyzeAuthFlow($params),
            'check_permissions' => $this->checkPermissions($params),
            'debug_csrf' => $this->debugCsrf($params),
            'list_routes' => $this->listRoutes($params),
            'debug_cache' => $this->debugCache($params),
            default => ['error' => 'Unknown tool: ' . $tool]
        };
    }

    /**
     * Debug authentication state.
     */
    protected function debugAuthState(array $params): array
    {
        $verbose = $params['verbose'] ?? false;
        $state = [];

        // Check all guards
        $guards = ['web', 'portal', 'sanctum', 'admin'];
        foreach ($guards as $guard) {
            try {
                $auth = Auth::guard($guard);
                $user = $auth->user();

                $state[$guard] = [
                    'authenticated' => $auth->check(),
                    'user' => $user ? [
                        'id' => $user->id,
                        'email' => $user->email ?? null,
                        'type' => get_class($user),
                        'company_id' => $user->company_id ?? null,
                    ] : null,
                    'session_id' => Session::getId(),
                ];

                if ($verbose && $user) {
                    $state[$guard]['session_data'] = Session::all();
                }
            } catch (\Exception $e) {
                $state[$guard] = [
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Check request user
        if (request()) {
            $state['request'] = [
                'user' => request()->user() ? [
                    'id' => request()->user()->id,
                    'type' => get_class(request()->user()),
                ] : null,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => request()->session()->getId(),
            ];
        }

        // Check API tokens
        $state['sanctum_tokens'] = DB::table('personal_access_tokens')
            ->where('last_used_at', '>', now()->subHours(24))
            ->count();

        return $state;
    }

    /**
     * Trace request flow through middleware and routes.
     */
    protected function traceRequestFlow(array $params): array
    {
        $method = strtoupper($params['method'] ?? 'GET');
        $uri = $params['uri'];
        $headers = $params['headers'] ?? [];

        // Create a mock request
        $request = Request::create($uri, $method, [], [], [], [], null);
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        $trace = [
            'request' => [
                'method' => $method,
                'uri' => $uri,
                'headers' => $headers,
            ],
            'route' => null,
            'middleware' => [],
            'errors' => [],
        ];

        try {
            // Find matching route
            $routes = Route::getRoutes();
            $route = $routes->match($request);

            if ($route) {
                $trace['route'] = [
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'action' => $route->getActionName(),
                    'controller' => $route->getControllerClass(),
                    'method' => $route->getActionMethod(),
                    'parameters' => $route->parameters(),
                ];

                // Get middleware
                $middleware = $route->gatherMiddleware();
                foreach ($middleware as $m) {
                    $middlewareClass = is_object($m) ? get_class($m) : $m;
                    $trace['middleware'][] = [
                        'class' => $middlewareClass,
                        'parameters' => $this->parseMiddlewareParameters($m),
                    ];
                }

                // Check route compilation
                try {
                    $compiled = $route->getCompiled();
                    $trace['route']['compiled'] = [
                        'regex' => $compiled->getRegex(),
                        'variables' => $compiled->getVariables(),
                    ];
                } catch (\Exception $e) {
                    $trace['errors'][] = 'Route compilation error: ' . $e->getMessage();
                }
            } else {
                $trace['errors'][] = 'No matching route found';
            }
        } catch (\Exception $e) {
            $trace['errors'][] = 'Route matching error: ' . $e->getMessage();
        }

        return $trace;
    }

    /**
     * Debug route conflicts.
     */
    protected function debugRouteConflicts(array $params): array
    {
        $pattern = $params['pattern'] ?? null;
        $routes = Route::getRoutes();
        $conflicts = [];
        $routeMap = [];

        foreach ($routes as $route) {
            $key = $route->methods()[0] . ':' . $route->uri();

            if ($pattern && ! str_contains($route->uri(), $pattern)) {
                continue;
            }

            if (! isset($routeMap[$key])) {
                $routeMap[$key] = [];
            }

            $routeMap[$key][] = [
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $route->gatherMiddleware(),
                'domain' => $route->getDomain(),
            ];
        }

        // Find conflicts
        foreach ($routeMap as $key => $routes) {
            if (count($routes) > 1) {
                $conflicts[$key] = $routes;
            }
        }

        // Also check for similar routes that might conflict
        $allRoutes = [];
        foreach (Route::getRoutes() as $route) {
            if ($pattern && ! str_contains($route->uri(), $pattern)) {
                continue;
            }

            $allRoutes[] = [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
            ];
        }

        return [
            'conflicts' => $conflicts,
            'total_routes' => count($allRoutes),
            'routes' => $allRoutes,
        ];
    }

    /**
     * Monitor middleware execution.
     */
    protected function monitorMiddleware(array $params): array
    {
        $routeNameOrUri = $params['route'] ?? null;
        $route = null;

        // Find route
        if ($routeNameOrUri) {
            $routes = Route::getRoutes();

            // Try by name first
            $route = $routes->getByName($routeNameOrUri);

            // Try by URI if not found by name
            if (! $route) {
                foreach ($routes as $r) {
                    if ($r->uri() === $routeNameOrUri) {
                        $route = $r;

                        break;
                    }
                }
            }
        }

        if (! $route) {
            return ['error' => 'Route not found'];
        }

        $middleware = $route->gatherMiddleware();
        $analysis = [
            'route' => [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
            ],
            'middleware' => [],
            'execution_order' => [],
        ];

        // Analyze each middleware
        foreach ($middleware as $index => $m) {
            $middlewareClass = is_object($m) ? get_class($m) : $m;
            $params = $this->parseMiddlewareParameters($m);

            $analysis['middleware'][] = [
                'order' => $index + 1,
                'class' => $middlewareClass,
                'parameters' => $params,
                'alias' => $this->getMiddlewareAlias($middlewareClass),
            ];

            $analysis['execution_order'][] = $middlewareClass;
        }

        // Get global middleware
        $kernel = app(\App\Http\Kernel::class);
        $analysis['global_middleware'] = $this->getKernelMiddleware($kernel);

        return $analysis;
    }

    /**
     * Debug session issues.
     */
    protected function debugSession(array $params): array
    {
        $sessionId = $params['session_id'] ?? Session::getId();
        $guard = $params['guard'] ?? 'web';

        $debug = [
            'session_id' => $sessionId,
            'current_session_id' => Session::getId(),
            'session_driver' => config('session.driver'),
            'session_lifetime' => config('session.lifetime'),
            'session_domain' => config('session.domain'),
            'session_path' => config('session.path'),
            'session_secure' => config('session.secure'),
            'session_same_site' => config('session.same_site'),
        ];

        // Get session data
        try {
            $debug['session_data'] = Session::all();
            $debug['csrf_token'] = Session::token();

            // Check auth session data
            $authKey = 'login_' . $guard . '_' . sha1(get_class(app('auth')->guard($guard)->getProvider()));
            $debug['auth_session_key'] = $authKey;
            $debug['auth_session_value'] = Session::get($authKey);

            // Check remember token
            $rememberKey = 'remember_' . $guard . '_' . sha1(get_class(app('auth')->guard($guard)->getProvider()));
            $debug['remember_key'] = $rememberKey;
            $debug['has_remember_token'] = Session::has($rememberKey);
        } catch (\Exception $e) {
            $debug['session_error'] = $e->getMessage();
        }

        // Check session file if using file driver
        if (config('session.driver') === 'file') {
            $sessionFile = storage_path('framework/sessions/' . $sessionId);
            $debug['session_file_exists'] = file_exists($sessionFile);
            if (file_exists($sessionFile)) {
                $debug['session_file_size'] = filesize($sessionFile);
                $debug['session_file_modified'] = date('Y-m-d H:i:s', filemtime($sessionFile));
            }
        }

        return $debug;
    }

    /**
     * Analyze authentication flow.
     */
    protected function analyzeAuthFlow(array $params): array
    {
        $email = $params['email'];
        $userType = $params['user_type'] ?? 'portal';

        $analysis = [
            'user_type' => $userType,
            'email' => $email,
            'user_found' => false,
            'authentication_possible' => false,
            'issues' => [],
            'recommendations' => [],
        ];

        // Find user
        $user = null;
        if ($userType === 'admin') {
            $user = User::where('email', $email)->first();
            $analysis['table'] = 'users';
            $analysis['guard'] = 'web';
        } else {
            $user = PortalUser::where('email', $email)->first();
            $analysis['table'] = 'portal_users';
            $analysis['guard'] = 'portal';
        }

        if ($user) {
            $analysis['user_found'] = true;
            $analysis['user'] = [
                'id' => $user->id,
                'email' => $user->email,
                'active' => $user->is_active ?? true,
                'company_id' => $user->company_id ?? null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            // Check if user can authenticate
            if ($userType === 'portal' && isset($user->is_active) && ! $user->is_active) {
                $analysis['issues'][] = 'User is not active';
                $analysis['recommendations'][] = 'Activate user account';
            }

            // Check company
            if (isset($user->company_id)) {
                $company = Company::find($user->company_id);
                if ($company) {
                    $analysis['company'] = [
                        'id' => $company->id,
                        'name' => $company->name,
                        'active' => $company->is_active ?? true,
                    ];

                    if (isset($company->is_active) && ! $company->is_active) {
                        $analysis['issues'][] = 'Company is not active';
                        $analysis['recommendations'][] = 'Activate company account';
                    }
                } else {
                    $analysis['issues'][] = 'Company not found';
                }
            }

            $analysis['authentication_possible'] = empty($analysis['issues']);
        } else {
            $analysis['issues'][] = 'User not found in ' . $analysis['table'] . ' table';
            $analysis['recommendations'][] = 'Create user account or check email address';
        }

        // Check routes
        $loginRoute = $userType === 'admin' ? 'admin.login' : 'portal.login';
        $analysis['login_route'] = [
            'name' => $loginRoute,
            'exists' => Route::has($loginRoute),
        ];

        return $analysis;
    }

    /**
     * Check user permissions.
     */
    protected function checkPermissions(array $params): array
    {
        $userId = $params['user_id'] ?? null;
        $permission = $params['permission'] ?? null;
        $guard = $params['guard'] ?? 'web';

        $result = [
            'user_id' => $userId,
            'guard' => $guard,
            'permission' => $permission,
            'has_permission' => false,
            'user_permissions' => [],
            'user_roles' => [],
        ];

        try {
            // Get user
            $user = null;
            if ($userId) {
                if ($guard === 'portal') {
                    $user = PortalUser::find($userId);
                } else {
                    $user = User::find($userId);
                }
            } else {
                $user = Auth::guard($guard)->user();
            }

            if ($user) {
                $result['user'] = [
                    'id' => $user->id,
                    'email' => $user->email ?? null,
                    'type' => get_class($user),
                ];

                // Check specific permission if provided
                if ($permission && method_exists($user, 'can')) {
                    $result['has_permission'] = $user->can($permission);
                }

                // Get all permissions if using Spatie
                if (method_exists($user, 'getAllPermissions')) {
                    $result['user_permissions'] = $user->getAllPermissions()->pluck('name')->toArray();
                }

                // Get roles if using Spatie
                if (method_exists($user, 'getRoleNames')) {
                    $result['user_roles'] = $user->getRoleNames()->toArray();
                }

                // Check if super admin
                if (method_exists($user, 'hasRole')) {
                    $result['is_super_admin'] = $user->hasRole('super-admin');
                }
            } else {
                $result['error'] = 'User not found';
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Debug CSRF issues.
     */
    protected function debugCsrf(array $params): array
    {
        $token = $params['token'] ?? null;
        $sessionId = $params['session_id'] ?? Session::getId();

        $debug = [
            'current_token' => Session::token(),
            'session_id' => $sessionId,
            'token_provided' => $token,
            'token_valid' => false,
            'token_regenerated_at' => Session::get('_token_generated_at'),
        ];

        // Verify token if provided
        if ($token) {
            $debug['token_valid'] = hash_equals(Session::token(), $token);
        }

        // Check CSRF middleware exceptions
        $verifyCsrfToken = app(\App\Http\Middleware\VerifyCsrfToken::class);
        $reflection = new \ReflectionClass($verifyCsrfToken);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $debug['csrf_exceptions'] = $property->getValue($verifyCsrfToken);

        // Check if CSRF is disabled
        $debug['csrf_enabled'] = ! app()->environment('testing');

        return $debug;
    }

    /**
     * List all routes.
     */
    protected function listRoutes(array $params): array
    {
        $filter = $params['filter'] ?? null;
        $method = $params['method'] ?? null;
        $middleware = $params['middleware'] ?? null;

        $routes = [];
        foreach (Route::getRoutes() as $route) {
            // Apply filters
            if ($filter && ! str_contains($route->uri(), $filter)) {
                continue;
            }

            if ($method && ! in_array(strtoupper($method), $route->methods())) {
                continue;
            }

            $routeMiddleware = $route->gatherMiddleware();
            if ($middleware) {
                $hasMiddleware = false;
                foreach ($routeMiddleware as $m) {
                    if (str_contains($m, $middleware)) {
                        $hasMiddleware = true;

                        break;
                    }
                }
                if (! $hasMiddleware) {
                    continue;
                }
            }

            $routes[] = [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $routeMiddleware,
                'domain' => $route->getDomain(),
            ];
        }

        return [
            'total' => count($routes),
            'routes' => $routes,
        ];
    }

    /**
     * Debug cache issues.
     */
    protected function debugCache(array $params): array
    {
        $key = $params['key'] ?? null;
        $store = $params['store'] ?? 'default';

        $debug = [
            'default_store' => config('cache.default'),
            'stores' => array_keys(config('cache.stores', [])),
            'prefix' => config('cache.prefix'),
        ];

        if ($key) {
            try {
                $cache = Cache::store($store === 'default' ? null : $store);
                $debug['key'] = $key;
                $debug['exists'] = $cache->has($key);

                if ($debug['exists']) {
                    $value = $cache->get($key);
                    $debug['value'] = is_string($value) || is_numeric($value) ? $value : gettype($value);
                    $debug['size'] = strlen(serialize($value));
                }

                // Try to get TTL if Redis
                if (config('cache.default') === 'redis') {
                    try {
                        $ttl = Cache::store('redis')->getRedis()->ttl(config('cache.prefix') . ':' . $key);
                        $debug['ttl'] = $ttl > 0 ? $ttl : 'no expiration';
                    } catch (\Exception $e) {
                        // Ignore TTL errors
                    }
                }
            } catch (\Exception $e) {
                $debug['error'] = $e->getMessage();
            }
        }

        return $debug;
    }

    /**
     * Parse middleware parameters.
     */
    protected function parseMiddlewareParameters($middleware): array
    {
        if (is_string($middleware) && str_contains($middleware, ':')) {
            $parts = explode(':', $middleware, 2);

            return explode(',', $parts[1]);
        }

        return [];
    }

    /**
     * Get middleware alias.
     */
    protected function getMiddlewareAlias(string $middleware): ?string
    {
        $kernel = app(\App\Http\Kernel::class);
        $reflection = new \ReflectionClass($kernel);

        try {
            $property = $reflection->getProperty('middlewareAliases');
            $property->setAccessible(true);
            $aliases = $property->getValue($kernel);

            foreach ($aliases as $alias => $class) {
                if ($class === $middleware) {
                    return $alias;
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }

    /**
     * Get kernel middleware.
     */
    protected function getKernelMiddleware($kernel): array
    {
        $reflection = new \ReflectionClass($kernel);
        $middleware = [];

        try {
            $property = $reflection->getProperty('middleware');
            $property->setAccessible(true);
            $middleware['global'] = $property->getValue($kernel);

            $property = $reflection->getProperty('middlewareGroups');
            $property->setAccessible(true);
            $middleware['groups'] = $property->getValue($kernel);

            $property = $reflection->getProperty('middlewareAliases');
            $property->setAccessible(true);
            $middleware['aliases'] = $property->getValue($kernel);
        } catch (\Exception $e) {
            $middleware['error'] = $e->getMessage();
        }

        return $middleware;
    }

    /**
     * Health check.
     */
    public function healthCheck(): array
    {
        return [
            'healthy' => true,
            'status' => 'operational',
            'message' => 'Debug MCP Server is running',
            'tools_count' => count($this->tools),
        ];
    }

    /**
     * Get server info.
     */
    public function getInfo(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'tools_count' => count($this->tools),
        ];
    }
}
