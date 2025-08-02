<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the API Gateway layer including rate limiting,
    | caching, circuit breakers, and service discovery.
    |
    */

    'enabled' => env('API_GATEWAY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Gateway Pipeline
    |--------------------------------------------------------------------------
    |
    | Middleware pipeline for processing requests through the gateway.
    | Order matters - middleware will be applied in the specified order.
    |
    */
    'pipeline' => [
        'middlewares' => [
            \App\Gateway\Middleware\AuthenticationGateway::class,
            \App\Gateway\Middleware\AuthorizationGateway::class,
            \App\Gateway\Middleware\RateLimitingGateway::class,
            \App\Gateway\Middleware\CachingGateway::class,
            \App\Gateway\Middleware\CircuitBreakerGateway::class,
            \App\Gateway\Middleware\MetricsGateway::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting policies for different endpoints and user tiers.
    |
    */
    'rate_limiting' => [
        'enabled' => env('GATEWAY_RATE_LIMITING_ENABLED', true),
        
        // Global rate limits (per IP)
        'global_ip_limit' => [
            'requests' => env('GATEWAY_GLOBAL_REQUESTS_PER_HOUR', 1000),
            'window' => 3600, // 1 hour
        ],
        
        // User-specific limits
        'user_limit' => [
            'requests' => env('GATEWAY_USER_REQUESTS_PER_HOUR', 500),
            'window' => 3600,
        ],
        
        // Company-specific limits (before tier multiplier)
        'company_limit' => [
            'requests' => env('GATEWAY_COMPANY_REQUESTS_PER_HOUR', 2000),
            'window' => 3600,
        ],
        
        // Default endpoint limits
        'default_limits' => [
            'requests' => env('GATEWAY_DEFAULT_REQUESTS_PER_HOUR', 300),
            'window' => 3600,
        ],
        
        // Endpoint-specific limits
        'endpoint_limits' => [
            'business/api/dashboard' => [
                'requests' => 100,
                'window' => 3600,
            ],
            'business/api/calls' => [
                'requests' => 200,
                'window' => 3600,
            ],
            'business/api/calls/{id}' => [
                'requests' => 500,
                'window' => 3600,
            ],
            'business/api/appointments' => [
                'requests' => 150,
                'window' => 3600,
            ],
            'business/api/customers' => [
                'requests' => 100,
                'window' => 3600,
            ],
            'business/api/analytics/*' => [
                'requests' => 50,
                'window' => 3600,
            ],
            'business/api/billing' => [
                'requests' => 30,
                'window' => 3600,
            ],
            'business/api/settings' => [
                'requests' => 20,
                'window' => 3600,
            ],
        ],
        
        // Resource-specific limits
        'resource_limits' => [
            'calls' => [
                'requests' => 200,
                'window' => 3600,
            ],
            'appointments' => [
                'requests' => 100,
                'window' => 3600,
            ],
            'customers' => [
                'requests' => 80,
                'window' => 3600,
            ],
            'analytics' => [
                'requests' => 50,
                'window' => 3600,
            ],
        ],
        
        // Company tier multipliers
        'tier_multipliers' => [
            'free' => 1.0,
            'pro' => 3.0,
            'enterprise' => 10.0,
            'unlimited' => 50.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure multi-level caching for API responses.
    |
    */
    'caching' => [
        'enabled' => env('GATEWAY_CACHING_ENABLED', true),
        
        // Default TTL for cached responses (seconds)
        'default_ttl' => env('GATEWAY_DEFAULT_CACHE_TTL', 300), // 5 minutes
        
        // Endpoint-specific TTLs
        'endpoint_ttls' => [
            'business/api/dashboard' => 60,      // 1 minute - frequently updated
            'business/api/calls' => 30,          // 30 seconds - real-time data
            'business/api/calls/{id}' => 300,    // 5 minutes - individual calls don't change often
            'business/api/appointments' => 120,   // 2 minutes - moderately dynamic
            'business/api/customers' => 600,     // 10 minutes - less frequently updated
            'business/api/analytics/*' => 900,   // 15 minutes - aggregated data
            'business/api/settings' => 1800,     // 30 minutes - rarely changes
            'business/api/team' => 600,          // 10 minutes - team data is stable
            'business/api/billing' => 1800,      // 30 minutes - billing data is stable
        ],
        
        // Cache key configuration
        'cache_keys' => [
            'include_user_context' => true,
            'include_company_context' => true,
            'include_query_params' => true,
            'include_headers' => ['Accept-Language'], // Headers to include in cache key
        ],
        
        // Cache levels configuration
        'levels' => [
            'l1' => [
                'ttl_max' => 300,        // Max 5 minutes for L1
                'memory_limit' => '128M', // Memory limit for L1 cache
            ],
            'l2' => [
                'ttl_max' => 3600,       // Max 1 hour for L2
                'memory_limit' => '512M', // Memory limit for L2 cache
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Configure circuit breaker patterns for service resilience.
    |
    */
    'circuit_breaker' => [
        'enabled' => env('GATEWAY_CIRCUIT_BREAKER_ENABLED', true),
        
        // Default circuit breaker settings
        'default' => [
            'failure_threshold' => 5,    // Number of failures before opening
            'timeout' => 60,             // Seconds to wait before trying again
            'success_threshold' => 3,     // Successful calls needed to close circuit
        ],
        
        // Service-specific settings
        'services' => [
            'calcom' => [
                'failure_threshold' => 3,
                'timeout' => 30,
                'success_threshold' => 2,
            ],
            'retell' => [
                'failure_threshold' => 5,
                'timeout' => 60,
                'success_threshold' => 3,
            ],
            'database' => [
                'failure_threshold' => 10,
                'timeout' => 10,
                'success_threshold' => 5,
            ],
        ],
        
        // Fallback responses
        'fallbacks' => [
            'dashboard' => [
                'data' => [],
                'message' => 'Dashboard temporarily unavailable',
                'fallback' => true,
            ],
            'calls' => [
                'data' => [],
                'pagination' => ['total' => 0, 'per_page' => 10, 'current_page' => 1],
                'message' => 'Call data temporarily unavailable',
                'fallback' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Versioning Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API versioning strategy and backward compatibility.
    |
    */
    'versioning' => [
        'default_version' => env('GATEWAY_DEFAULT_API_VERSION', 'v2'),
        'supported_versions' => ['v1', 'v2'],
        'deprecated_versions' => [],
        'sunset_versions' => [], // Versions that will be removed soon
        
        // Version resolution order
        'resolution_order' => [
            'header',        // API-Version header
            'accept_header', // Accept: application/vnd.askproai.v2+json
            'url_path',      // /api/v2/endpoint
            'query_param',   // ?version=v2
        ],
        
        // Headers
        'header_name' => 'API-Version',
        'accept_header_pattern' => '/application\/vnd\.askproai\.v(\d+)\+json/',
        
        // Backward compatibility
        'enable_transformations' => true,
        'transformation_cache_ttl' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Discovery Configuration
    |--------------------------------------------------------------------------
    |
    | Configure service registry and discovery for microservices migration.
    |
    */
    'service_discovery' => [
        'enabled' => env('GATEWAY_SERVICE_DISCOVERY_ENABLED', false),
        
        // Service registry type
        'registry_type' => env('GATEWAY_REGISTRY_TYPE', 'static'), // static, consul, etcd
        
        // Health check configuration
        'health_checks' => [
            'enabled' => true,
            'interval' => 30,      // Seconds between health checks
            'timeout' => 5,        // Health check timeout
            'failure_threshold' => 3, // Failures before marking unhealthy
        ],
        
        // Load balancing
        'load_balancing' => [
            'strategy' => env('GATEWAY_LOAD_BALANCING_STRATEGY', 'round_robin'), // round_robin, least_connections, weighted
            'health_check_required' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Observability
    |--------------------------------------------------------------------------
    |
    | Configure metrics collection and monitoring.
    |
    */
    'monitoring' => [
        'metrics_enabled' => env('GATEWAY_METRICS_ENABLED', true),
        'detailed_logging' => env('GATEWAY_DETAILED_LOGGING', env('APP_DEBUG', false)),
        
        // Performance thresholds
        'slow_request_threshold' => env('GATEWAY_SLOW_REQUEST_THRESHOLD', 1000), // ms
        'error_rate_threshold' => env('GATEWAY_ERROR_RATE_THRESHOLD', 5), // percentage
        
        // Metrics collection
        'collect_request_metrics' => true,
        'collect_cache_metrics' => true,
        'collect_rate_limit_metrics' => true,
        'collect_circuit_breaker_metrics' => true,
        
        // Metric retention
        'metrics_retention_days' => env('GATEWAY_METRICS_RETENTION_DAYS', 30),
        
        // Alerting
        'alerts' => [
            'high_error_rate' => [
                'threshold' => 10, // percentage
                'window' => 300,   // seconds
            ],
            'high_latency' => [
                'threshold' => 2000, // ms
                'window' => 300,     // seconds
            ],
            'cache_hit_rate_low' => [
                'threshold' => 50, // percentage
                'window' => 600,   // seconds
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for the API Gateway.
    |
    */
    'security' => [
        // Request validation
        'validate_content_type' => true,
        'max_request_size' => env('GATEWAY_MAX_REQUEST_SIZE', 10 * 1024 * 1024), // 10MB
        
        // Headers
        'required_headers' => [
            'User-Agent',
        ],
        'blocked_headers' => [
            'X-Forwarded-For', // Will be overridden by gateway
        ],
        
        // IP filtering
        'ip_whitelist' => env('GATEWAY_IP_WHITELIST') ? explode(',', env('GATEWAY_IP_WHITELIST')) : [],
        'ip_blacklist' => env('GATEWAY_IP_BLACKLIST') ? explode(',', env('GATEWAY_IP_BLACKLIST')) : [],
        
        // Rate limiting for security
        'security_rate_limits' => [
            'login_attempts' => [
                'requests' => 5,
                'window' => 300, // 5 minutes
            ],
            'password_reset' => [
                'requests' => 3,
                'window' => 600, // 10 minutes
            ],
        ],
    ],
];