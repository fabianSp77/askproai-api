# API Gateway Architecture Design for Business Portal

## Executive Summary

This document outlines a comprehensive API Gateway pattern implementation for the Business Portal, designed to provide:
- Centralized request routing and management
- Advanced rate limiting and caching
- Authentication/Authorization abstraction
- API versioning and backward compatibility
- Circuit breaker and service discovery patterns
- High performance (<200ms target) with monitoring

## Current State Analysis

**Existing Architecture:**
- 67+ Portal controllers in Laravel monolith
- React frontend consuming REST APIs
- Multiple auth systems (customer, admin)
- Basic CORS handling
- Some rate limiting middleware exists
- Redis available for caching

**Key Issues:**
- No centralized API management
- Inconsistent authentication patterns
- Limited rate limiting strategies
- No API versioning strategy
- No circuit breaker patterns
- Mixed responsibility in controllers

## Proposed API Gateway Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        API Gateway Layer                        │
├─────────────────────────────────────────────────────────────────┤
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐     │
│  │ Rate Limiter  │  │ Auth Gateway  │  │ Circuit Breaker│     │
│  └───────────────┘  └───────────────┘  └───────────────┘     │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐     │
│  │ Cache Layer   │  │ Request Trans │  │ Response Trans │     │
│  └───────────────┘  └───────────────┘  └───────────────┘     │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐     │
│  │ Version Mgmt  │  │ Load Balancer │  │ Monitoring    │     │
│  └───────────────┘  └───────────────┘  └───────────────┘     │
├─────────────────────────────────────────────────────────────────┤
│                    Service Discovery                           │
├─────────────────────────────────────────────────────────────────┤
│  Business Services (Current Laravel Controllers)              │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌──────────┐ │
│  │ Dashboard   │ │ Calls       │ │Appointments │ │Customers │ │
│  │ Service     │ │ Service     │ │ Service     │ │ Service  │ │
│  └─────────────┘ └─────────────┘ └─────────────┘ └──────────┘ │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌──────────┐ │
│  │ Analytics   │ │ Settings    │ │ Billing     │ │ Team     │ │
│  │ Service     │ │ Service     │ │ Service     │ │ Service  │ │
│  └─────────────┘ └─────────────┘ └─────────────┘ └──────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

## Implementation Phases

### Phase 1: Gateway Foundation (Weeks 1-2)
1. **API Gateway Middleware Stack**
2. **Request/Response Pipeline**
3. **Basic Service Registry**
4. **Monitoring Infrastructure**

### Phase 2: Core Features (Weeks 3-4)
1. **Advanced Rate Limiting**
2. **Caching Layer**
3. **Authentication Gateway**
4. **API Versioning**

### Phase 3: Advanced Features (Weeks 5-6)
1. **Circuit Breaker Pattern**
2. **Request/Response Transformation**
3. **Service Discovery**
4. **Load Balancing**

### Phase 4: Migration & Optimization (Weeks 7-8)
1. **Backward Compatibility Layer**
2. **Performance Optimization**
3. **Documentation & Training**
4. **Monitoring Dashboards**

## Detailed Design Specifications

### 1. API Gateway Core Components

#### 1.1 Gateway Manager
```php
<?php

namespace App\Gateway;

class ApiGatewayManager
{
    private ServiceRegistry $serviceRegistry;
    private RateLimiter $rateLimiter;
    private CacheManager $cache;
    private CircuitBreaker $circuitBreaker;
    private AuthGateway $auth;
    
    public function handle(Request $request): Response
    {
        // Pipeline: Auth → Rate Limit → Cache → Circuit Breaker → Service
        return $this->pipeline($request)
            ->through([
                AuthenticationGateway::class,
                RateLimitingGateway::class,
                CachingGateway::class,
                CircuitBreakerGateway::class,
                ServiceDiscoveryGateway::class,
            ])
            ->thenReturn();
    }
}
```

#### 1.2 Service Registry
```php
<?php

namespace App\Gateway\Discovery;

class ServiceRegistry
{
    private array $services = [];
    
    public function register(string $name, ServiceDefinition $definition): void
    {
        $this->services[$name] = $definition;
    }
    
    public function resolve(string $path, string $method): ?ServiceDefinition
    {
        foreach ($this->services as $service) {
            if ($service->matches($path, $method)) {
                return $service;
            }
        }
        return null;
    }
    
    public function getHealthyServices(string $name): array
    {
        return array_filter(
            $this->services[$name]->getInstances(),
            fn($instance) => $instance->isHealthy()
        );
    }
}
```

### 2. Rate Limiting Strategy

#### 2.1 Multi-Tier Rate Limiting
```php
<?php

namespace App\Gateway\RateLimit;

class AdvancedRateLimiter
{
    public function __construct(
        private CacheInterface $cache,
        private array $configs
    ) {}
    
    public function checkLimits(Request $request): RateLimitResult
    {
        $user = $request->user();
        $endpoint = $this->resolveEndpoint($request);
        
        $checks = [
            // Global rate limit
            $this->checkGlobalLimit($request->ip()),
            
            // User-specific limit
            $user ? $this->checkUserLimit($user->id) : null,
            
            // Company-specific limit  
            $user ? $this->checkCompanyLimit($user->company_id) : null,
            
            // Endpoint-specific limit
            $this->checkEndpointLimit($endpoint, $user?->id ?? $request->ip()),
            
            // Resource-specific limit (e.g., calls/appointments)
            $this->checkResourceLimit($endpoint, $user?->company_id),
        ];
        
        return $this->evaluateChecks(array_filter($checks));
    }
    
    private function getConfig(string $endpoint, ?int $companyId = null): array
    {
        // Tiered rate limiting based on subscription
        $tier = $this->getCompanyTier($companyId);
        
        return [
            'dashboard' => [
                'free' => ['requests' => 100, 'window' => 3600],
                'pro' => ['requests' => 500, 'window' => 3600],
                'enterprise' => ['requests' => 2000, 'window' => 3600],
            ],
            'calls' => [
                'free' => ['requests' => 50, 'window' => 3600],
                'pro' => ['requests' => 200, 'window' => 3600],
                'enterprise' => ['requests' => 1000, 'window' => 3600],
            ],
            'appointments' => [
                'free' => ['requests' => 30, 'window' => 3600],
                'pro' => ['requests' => 150, 'window' => 3600],
                'enterprise' => ['requests' => 500, 'window' => 3600],
            ],
        ][$endpoint][$tier] ?? ['requests' => 60, 'window' => 3600];
    }
}
```

#### 2.2 Rate Limit Configuration
```php
// config/gateway.php
return [
    'rate_limiting' => [
        'enabled' => true,
        'default_limits' => [
            'requests_per_minute' => 60,
            'burst_requests' => 10,
        ],
        'endpoint_limits' => [
            'business/api/dashboard' => [
                'requests_per_minute' => 30,
                'requests_per_hour' => 1000,
            ],
            'business/api/calls' => [
                'requests_per_minute' => 100,
                'requests_per_hour' => 3000,
            ],
            'business/api/appointments' => [
                'requests_per_minute' => 50,
                'requests_per_hour' => 1500,
            ],
        ],
        'company_tier_multipliers' => [
            'free' => 1.0,
            'pro' => 3.0,
            'enterprise' => 10.0,
        ],
    ],
];
```

### 3. Caching Layer Design

#### 3.1 Multi-Level Caching Strategy
```php
<?php

namespace App\Gateway\Cache;

class GatewayCacheManager
{
    public function __construct(
        private RedisCache $l1Cache,      // Fast, small cache
        private RedisCache $l2Cache,      // Larger, persistent cache
        private DatabaseCache $l3Cache    // Backup cache
    ) {}
    
    public function get(string $key, callable $callback = null, int $ttl = null): mixed
    {
        // L1 Cache (1-5 minutes)
        if ($value = $this->l1Cache->get($key)) {
            return $value;
        }
        
        // L2 Cache (5-30 minutes)
        if ($value = $this->l2Cache->get($key)) {
            $this->l1Cache->put($key, $value, min($ttl ?? 300, 300));
            return $value;
        }
        
        // L3 Cache (30 minutes - 2 hours)
        if ($value = $this->l3Cache->get($key)) {
            $this->l2Cache->put($key, $value, min($ttl ?? 1800, 1800));
            $this->l1Cache->put($key, $value, 300);
            return $value;
        }
        
        // Generate and cache
        if ($callback) {
            $value = $callback();
            $this->putAll($key, $value, $ttl);
            return $value;
        }
        
        return null;
    }
    
    private function getCacheKey(Request $request): string
    {
        $user = $request->user();
        return sprintf(
            'api:%s:%s:%s:%s',
            $request->path(),
            $request->method(),
            $user?->company_id ?? 'guest',
            md5($request->getQueryString() ?? '')
        );
    }
}
```

#### 3.2 Smart Cache Invalidation
```php
<?php

namespace App\Gateway\Cache;

class CacheInvalidator
{
    private array $dependencies = [
        'business/api/dashboard' => [
            'calls.*', 'appointments.*', 'customers.*'
        ],
        'business/api/calls' => [
            'calls.*', 'appointments.created', 'customers.updated'
        ],
        'business/api/appointments' => [
            'appointments.*', 'customers.updated', 'staff.updated'
        ],
    ];
    
    public function invalidateByEvent(string $event): void
    {
        foreach ($this->dependencies as $endpoint => $events) {
            if ($this->matchesPattern($event, $events)) {
                $this->invalidateEndpoint($endpoint);
            }
        }
    }
    
    private function invalidateEndpoint(string $endpoint): void
    {
        $pattern = "api:{$endpoint}:*";
        $keys = Redis::keys($pattern);
        
        if (!empty($keys)) {
            Redis::del($keys);
            Log::info("Cache invalidated for endpoint: {$endpoint}", [
                'keys_count' => count($keys)
            ]);
        }
    }
}
```

### 4. Authentication & Authorization Gateway

#### 4.1 Unified Auth Gateway
```php
<?php

namespace App\Gateway\Auth;

class AuthenticationGateway
{
    public function __construct(
        private SessionAuthenticator $session,
        private TokenAuthenticator $token,
        private ApiKeyAuthenticator $apiKey
    ) {}
    
    public function authenticate(Request $request): AuthResult
    {
        // Try multiple auth methods in order of preference
        $authenticators = [
            $this->session,   // For web sessions
            $this->token,     // For API tokens
            $this->apiKey,    // For service-to-service
        ];
        
        foreach ($authenticators as $authenticator) {
            if ($authenticator->canHandle($request)) {
                $result = $authenticator->authenticate($request);
                
                if ($result->isSuccess()) {
                    $this->setContext($request, $result);
                    return $result;
                }
            }
        }
        
        return AuthResult::failed('No valid authentication found');
    }
    
    private function setContext(Request $request, AuthResult $result): void
    {
        $request->setUserResolver(fn() => $result->getUser());
        
        // Set company context for multi-tenancy
        if ($company = $result->getUser()?->company) {
            app()->instance('current.company', $company);
        }
        
        // Set permissions context
        $permissions = $this->resolvePermissions($result->getUser());
        app()->instance('user.permissions', $permissions);
    }
}
```

#### 4.2 Authorization Policy Gateway
```php
<?php

namespace App\Gateway\Auth;

class AuthorizationGateway
{
    public function authorize(Request $request, AuthResult $authResult): AuthorizationResult
    {
        $user = $authResult->getUser();
        $endpoint = $this->resolveEndpoint($request);
        $action = $this->resolveAction($request);
        
        // Company isolation check
        if (!$this->checkCompanyIsolation($user, $request)) {
            return AuthorizationResult::denied('Company isolation violation');
        }
        
        // Permission check
        if (!$this->checkPermissions($user, $endpoint, $action)) {
            return AuthorizationResult::denied('Insufficient permissions');
        }
        
        // Resource-specific authorization
        if ($resourceId = $this->extractResourceId($request)) {
            if (!$this->checkResourceAccess($user, $endpoint, $resourceId)) {
                return AuthorizationResult::denied('Resource access denied');
            }
        }
        
        return AuthorizationResult::allowed();
    }
    
    private function checkCompanyIsolation(User $user, Request $request): bool
    {
        // Extract company context from request path or parameters
        $requestCompany = $this->extractCompanyContext($request);
        
        return !$requestCompany || $requestCompany === $user->company_id;
    }
}
```

### 5. API Versioning Strategy

#### 5.1 Version Management
```php
<?php

namespace App\Gateway\Versioning;

class ApiVersionManager
{
    private array $versions = [
        'v1' => [
            'supported' => true,
            'deprecated' => false,
            'sunset_date' => null,
            'namespace' => 'App\\Http\\Controllers\\Portal\\Api\\V1',
        ],
        'v2' => [
            'supported' => true,
            'deprecated' => false,
            'sunset_date' => null,
            'namespace' => 'App\\Http\\Controllers\\Portal\\Api\\V2',
        ],
    ];
    
    public function resolveVersion(Request $request): string
    {
        // 1. Header-based versioning (preferred)
        if ($version = $request->header('API-Version')) {
            return $this->validateVersion($version);
        }
        
        // 2. Accept header versioning
        if ($accept = $request->header('Accept')) {
            if (preg_match('/application\/vnd\.askproai\.v(\d+)\+json/', $accept, $matches)) {
                return $this->validateVersion('v' . $matches[1]);
            }
        }
        
        // 3. URL path versioning (fallback)
        if (preg_match('/\/api\/v(\d+)\//', $request->path(), $matches)) {
            return $this->validateVersion('v' . $matches[1]);
        }
        
        // 4. Default to latest stable
        return $this->getDefaultVersion();
    }
    
    public function transformRequest(Request $request, string $fromVersion, string $toVersion): Request
    {
        $transformer = $this->getRequestTransformer($fromVersion, $toVersion);
        return $transformer ? $transformer->transform($request) : $request;
    }
    
    public function transformResponse(Response $response, string $fromVersion, string $toVersion): Response
    {
        $transformer = $this->getResponseTransformer($fromVersion, $toVersion);
        return $transformer ? $transformer->transform($response) : $response;
    }
}
```

#### 5.2 Backward Compatibility Layer
```php
<?php

namespace App\Gateway\Versioning\Transformers;

class V1ToV2RequestTransformer implements RequestTransformerInterface
{
    public function transform(Request $request): Request
    {
        $data = $request->all();
        
        // Transform field names
        $fieldMappings = [
            'customer_phone' => 'phone_number',
            'appointment_date' => 'scheduled_at',
            'staff_id' => 'assigned_staff_id',
        ];
        
        foreach ($fieldMappings as $oldField => $newField) {
            if (isset($data[$oldField])) {
                $data[$newField] = $data[$oldField];
                unset($data[$oldField]);
            }
        }
        
        // Transform data structures
        if (isset($data['filters'])) {
            $data['filters'] = $this->transformFilters($data['filters']);
        }
        
        $request->merge($data);
        return $request;
    }
    
    private function transformFilters(array $filters): array
    {
        // Convert old filter format to new format
        $newFilters = [];
        
        foreach ($filters as $key => $value) {
            match($key) {
                'date_from' => $newFilters['date_range']['from'] = $value,
                'date_to' => $newFilters['date_range']['to'] = $value,
                'status' => $newFilters['status']['in'] = is_array($value) ? $value : [$value],
                default => $newFilters[$key] = $value,
            };
        }
        
        return $newFilters;
    }
}
```

### 6. Circuit Breaker Pattern

#### 6.1 Circuit Breaker Implementation
```php
<?php

namespace App\Gateway\CircuitBreaker;

class CircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';
    
    public function __construct(
        private CacheInterface $cache,
        private array $config
    ) {}
    
    public function call(string $service, callable $callback): mixed
    {
        $state = $this->getState($service);
        
        switch ($state) {
            case self::STATE_OPEN:
                if ($this->shouldAttemptReset($service)) {
                    $this->setState($service, self::STATE_HALF_OPEN);
                    return $this->executeWithFallback($service, $callback);
                }
                return $this->getFallbackResponse($service);
                
            case self::STATE_HALF_OPEN:
                return $this->executeWithMonitoring($service, $callback);
                
            case self::STATE_CLOSED:
            default:
                return $this->executeWithMonitoring($service, $callback);
        }
    }
    
    private function executeWithMonitoring(string $service, callable $callback): mixed
    {
        $startTime = microtime(true);
        
        try {
            $result = $callback();
            $this->recordSuccess($service, microtime(true) - $startTime);
            return $result;
            
        } catch (Exception $e) {
            $this->recordFailure($service, $e);
            
            if ($this->shouldOpenCircuit($service)) {
                $this->setState($service, self::STATE_OPEN);
                $this->setOpenTimestamp($service);
            }
            
            return $this->getFallbackResponse($service);
        }
    }
    
    private function shouldOpenCircuit(string $service): bool
    {
        $failures = $this->getFailureCount($service);
        $threshold = $this->config['failure_threshold'][$service] ?? 5;
        $window = $this->config['time_window'][$service] ?? 60;
        
        return $failures >= $threshold;
    }
    
    private function getFallbackResponse(string $service): array
    {
        // Return cached response or default fallback
        if ($cached = $this->getCachedResponse($service)) {
            return $cached;
        }
        
        return [
            'error' => 'Service temporarily unavailable',
            'fallback' => true,
            'service' => $service,
            'retry_after' => $this->getRetryAfter($service),
        ];
    }
}
```

### 7. Request/Response Transformation

#### 7.1 Request Pipeline
```php
<?php

namespace App\Gateway\Pipeline;

class RequestPipeline
{
    public function __construct(
        private array $transformers = []
    ) {}
    
    public function process(Request $request): Request
    {
        return array_reduce(
            $this->transformers,
            fn($request, $transformer) => $transformer->transform($request),
            $request
        );
    }
    
    public function addTransformer(RequestTransformerInterface $transformer): self
    {
        $this->transformers[] = $transformer;
        return $this;
    }
}

class SecurityTransformer implements RequestTransformerInterface
{
    public function transform(Request $request): Request
    {
        // Sanitize input
        $data = $request->all();
        
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = strip_tags($value);
                $value = trim($value);
            }
        });
        
        $request->merge($data);
        return $request;
    }
}

class ValidationTransformer implements RequestTransformerInterface
{
    public function transform(Request $request): Request
    {
        $validator = $this->getValidator($request);
        
        if ($validator && $validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $request;
    }
    
    private function getValidator(Request $request): ?Validator
    {
        $rules = $this->getValidationRules($request->path(), $request->method());
        
        return $rules ? Validator::make($request->all(), $rules) : null;
    }
}
```

### 8. Service Discovery for Future Microservices

#### 8.1 Service Registry Design
```php
<?php

namespace App\Gateway\Discovery;

class ServiceDefinition
{
    public function __construct(
        public string $name,
        public string $version,
        public array $endpoints,
        public array $healthChecks,
        public array $loadBalancing,
        public array $circuitBreaker
    ) {}
    
    public function matches(string $path, string $method): bool
    {
        foreach ($this->endpoints as $endpoint) {
            if ($this->matchesPattern($path, $endpoint['pattern']) && 
                in_array($method, $endpoint['methods'])) {
                return true;
            }
        }
        return false;
    }
    
    public function getHealthyInstances(): array
    {
        return array_filter(
            $this->getInstances(),
            fn($instance) => $this->checkHealth($instance)
        );
    }
    
    private function checkHealth(ServiceInstance $instance): bool
    {
        foreach ($this->healthChecks as $check) {
            if (!$this->executeHealthCheck($instance, $check)) {
                return false;
            }
        }
        return true;
    }
}

class ServiceInstance
{
    public function __construct(
        public string $id,
        public string $host,
        public int $port,
        public array $metadata = []
    ) {}
    
    public function getUrl(): string
    {
        return "http://{$this->host}:{$this->port}";
    }
    
    public function isHealthy(): bool
    {
        // Implement health check logic
        return true;
    }
}
```

### 9. Monitoring and Observability

#### 9.1 Gateway Metrics
```php
<?php

namespace App\Gateway\Monitoring;

class GatewayMetrics
{
    public function __construct(
        private MetricsCollector $collector
    ) {}
    
    public function recordRequest(Request $request, Response $response, float $duration): void
    {
        $this->collector->increment('gateway.requests.total', [
            'method' => $request->method(),
            'endpoint' => $this->normalizeEndpoint($request->path()),
            'status' => $response->getStatusCode(),
            'company' => $request->user()?->company_id ?? 'anonymous',
        ]);
        
        $this->collector->histogram('gateway.request.duration', $duration, [
            'endpoint' => $this->normalizeEndpoint($request->path()),
        ]);
        
        if ($response->getStatusCode() >= 400) {
            $this->collector->increment('gateway.errors.total', [
                'status' => $response->getStatusCode(),
                'endpoint' => $this->normalizeEndpoint($request->path()),
            ]);
        }
    }
    
    public function recordCacheHit(string $key, bool $hit): void
    {
        $this->collector->increment('gateway.cache.' . ($hit ? 'hits' : 'misses'), [
            'cache_key_type' => $this->getCacheKeyType($key),
        ]);
    }
    
    public function recordRateLimitHit(string $key, string $endpoint): void
    {
        $this->collector->increment('gateway.rate_limit.hits', [
            'endpoint' => $endpoint,
            'key_type' => strpos($key, 'user:') === 0 ? 'user' : 'ip',
        ]);
    }
}
```

### 10. Configuration Management

#### 10.1 Gateway Configuration
```php
// config/gateway.php
<?php

return [
    'enabled' => env('API_GATEWAY_ENABLED', true),
    
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
    
    'rate_limiting' => [
        'enabled' => true,
        'default_limits' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 3000,
            'burst_requests' => 10,
        ],
        'endpoint_overrides' => [
            'business/api/dashboard' => ['requests_per_minute' => 30],
            'business/api/calls' => ['requests_per_minute' => 100],
            'business/api/appointments' => ['requests_per_minute' => 50],
        ],
        'company_tier_multipliers' => [
            'free' => 1.0,
            'pro' => 3.0,
            'enterprise' => 10.0,
        ],
    ],
    
    'caching' => [
        'enabled' => true,
        'default_ttl' => 300, // 5 minutes
        'endpoint_ttls' => [
            'business/api/dashboard' => 60,  // 1 minute
            'business/api/calls' => 30,      // 30 seconds
            'business/api/appointments' => 120, // 2 minutes
            'business/api/settings' => 1800, // 30 minutes
        ],
        'cache_keys' => [
            'include_user_context' => true,
            'include_company_context' => true,
            'include_query_params' => true,
        ],
    ],
    
    'circuit_breaker' => [
        'enabled' => true,
        'failure_threshold' => 5,
        'timeout' => 60, // seconds
        'services' => [
            'calcom' => [
                'failure_threshold' => 3,
                'timeout' => 30,
            ],
            'retell' => [
                'failure_threshold' => 5,
                'timeout' => 60,
            ],
        ],
    ],
    
    'versioning' => [
        'default_version' => 'v2',
        'supported_versions' => ['v1', 'v2'],
        'deprecated_versions' => [],
        'header_name' => 'API-Version',
    ],
    
    'monitoring' => [
        'metrics_enabled' => true,
        'detailed_logging' => env('APP_DEBUG', false),
        'slow_request_threshold' => 1000, // ms
    ],
];
```

## Migration Strategy

### Phase 1: Gateway Foundation (Week 1-2)

1. **Create Gateway Middleware Stack**
   ```bash
   php artisan make:middleware ApiGatewayMiddleware
   php artisan make:middleware GatewayMetricsMiddleware
   ```

2. **Implement Service Registry**
   - Create basic service definitions
   - Register existing controllers as services
   - Implement health checks

3. **Add Monitoring Infrastructure**
   - Set up metrics collection
   - Create monitoring dashboard
   - Implement alerting

### Phase 2: Core Features (Week 3-4)

1. **Enhanced Rate Limiting**
   - Replace basic middleware with advanced rate limiter
   - Add company-tier based limits
   - Implement burst protection

2. **Caching Layer**
   - Implement multi-level caching
   - Add cache invalidation strategy
   - Monitor cache hit rates

3. **Auth Gateway**
   - Unify authentication methods
   - Add authorization policies
   - Implement company isolation

### Phase 3: Advanced Features (Week 5-6)

1. **Circuit Breaker**
   - Implement circuit breaker pattern
   - Add fallback responses
   - Monitor service health

2. **Request/Response Transformation**
   - Add input sanitization
   - Implement response formatting
   - Add validation pipeline

3. **API Versioning**
   - Implement version resolution
   - Add backward compatibility
   - Create migration guides

### Phase 4: Migration & Optimization (Week 7-8)

1. **Backward Compatibility**
   - Ensure existing APIs work unchanged
   - Add deprecation warnings
   - Create migration documentation

2. **Performance Optimization**
   - Optimize cache strategies
   - Tune rate limiting
   - Monitor response times

3. **Documentation & Training**
   - Update API documentation
   - Train development team
   - Create operational runbooks

## Performance Targets & Monitoring

### Performance Targets
- **Gateway Overhead**: < 10ms additional latency
- **Cache Hit Rate**: > 80% for cacheable endpoints
- **Rate Limit Accuracy**: 99.9% accuracy
- **Circuit Breaker Response**: < 100ms for fallback
- **Memory Usage**: < 64MB additional per request

### Key Metrics to Monitor
1. **Request Metrics**
   - Request rate (requests/second)
   - Response time percentiles (p50, p95, p99)
   - Error rates by endpoint and status code

2. **Gateway Metrics**
   - Cache hit/miss rates
   - Rate limit violations
   - Circuit breaker state changes
   - Authentication success/failure rates

3. **Resource Metrics**
   - Memory usage
   - CPU utilization
   - Redis cache memory usage
   - Database connection pool usage

### Performance Monitoring Dashboard
```php
// routes/web.php
Route::get('/admin/gateway/metrics', [GatewayMetricsController::class, 'dashboard'])
    ->middleware(['auth:admin'])
    ->name('admin.gateway.metrics');
```

## Security Considerations

### 1. Input Validation
- All requests pass through validation pipeline
- Automatic sanitization of user input
- SQL injection protection
- XSS prevention

### 2. Rate Limiting Security
- DDoS protection through rate limiting
- Company isolation enforcement
- Suspicious pattern detection
- Automatic IP blocking for abuse

### 3. Authentication Security
- Multiple auth method support
- Session security hardening
- API key rotation support
- Company context validation

### 4. Data Privacy
- Request/response logging controls
- PII detection and masking
- GDPR compliance features
- Audit trail maintenance

## Technology Recommendations

### Core Technologies
- **Laravel 10+**: Main framework
- **Redis**: Caching and rate limiting
- **Prometheus**: Metrics collection
- **Grafana**: Monitoring dashboards

### Additional Tools
- **Laravel Telescope**: Request debugging
- **Laravel Horizon**: Queue monitoring
- **Sentry**: Error tracking
- **New Relic**: Application monitoring

## Conclusion

This API Gateway design provides a comprehensive solution for managing the Business Portal APIs while maintaining backward compatibility and high performance. The phased implementation approach allows for gradual migration with minimal disruption to existing functionality.

The gateway will provide:
- Centralized API management
- Advanced rate limiting and caching
- Robust authentication and authorization
- API versioning and backward compatibility
- Circuit breaker patterns for resilience
- Comprehensive monitoring and observability

This architecture positions the system for future microservices migration while immediately improving the current monolithic setup's reliability, security, and performance.