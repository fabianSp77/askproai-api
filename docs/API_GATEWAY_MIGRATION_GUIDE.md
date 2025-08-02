# API Gateway Migration Guide

## Overview

This guide provides step-by-step instructions for implementing the API Gateway pattern in the Business Portal while maintaining backward compatibility and achieving the <200ms performance target.

## Prerequisites

- Laravel 10+ application
- Redis available for caching and rate limiting
- Existing Business Portal APIs operational
- Testing environment for validation

## Phase 1: Gateway Foundation (Week 1-2)

### Step 1.1: Install Gateway Components

1. **Register the Service Provider**
   ```php
   // config/app.php
   'providers' => [
       // ... other providers
       App\Providers\ApiGatewayServiceProvider::class,
   ],
   ```

2. **Publish Configuration**
   ```bash
   php artisan vendor:publish --tag=gateway-config
   ```

3. **Update Environment Variables**
   ```env
   # .env
   API_GATEWAY_ENABLED=true
   GATEWAY_RATE_LIMITING_ENABLED=true
   GATEWAY_CACHING_ENABLED=true
   GATEWAY_CIRCUIT_BREAKER_ENABLED=true
   GATEWAY_METRICS_ENABLED=true
   
   # Rate Limiting
   GATEWAY_DEFAULT_REQUESTS_PER_HOUR=300
   GATEWAY_USER_REQUESTS_PER_HOUR=500
   GATEWAY_COMPANY_REQUESTS_PER_HOUR=2000
   
   # Caching
   GATEWAY_DEFAULT_CACHE_TTL=300
   GATEWAY_CACHING_ENABLED=true
   
   # Monitoring
   GATEWAY_SLOW_REQUEST_THRESHOLD=1000
   GATEWAY_METRICS_ENABLED=true
   ```

### Step 1.2: Register Gateway Middleware

1. **Add to HTTP Kernel**
   ```php
   // app/Http/Kernel.php
   protected $middleware = [
       // ... existing middleware
       \App\Gateway\Middleware\ApiGatewayMiddleware::class,
   ];
   ```

2. **Test Gateway Registration**
   ```bash
   php artisan gateway:status
   ```

### Step 1.3: Baseline Performance Testing

1. **Create Performance Test Script**
   ```bash
   # Create test script
   cat > test_performance.sh << 'EOF'
   #!/bin/bash
   echo "Testing baseline performance..."
   
   # Test dashboard endpoint
   curl -w "@curl-format.txt" -s -o /dev/null \
     -H "Authorization: Bearer $API_TOKEN" \
     https://api.askproai.de/business/api/dashboard
   
   # Test calls endpoint
   curl -w "@curl-format.txt" -s -o /dev/null \
     -H "Authorization: Bearer $API_TOKEN" \
     https://api.askproai.de/business/api/calls
   EOF
   
   chmod +x test_performance.sh
   ```

2. **Create curl format file**
   ```bash
   cat > curl-format.txt << 'EOF'
        time_namelookup:  %{time_namelookup}\n
           time_connect:  %{time_connect}\n
        time_appconnect:  %{time_appconnect}\n
       time_pretransfer:  %{time_pretransfer}\n
          time_redirect:  %{time_redirect}\n
     time_starttransfer:  %{time_starttransfer}\n
                        ----------\n
             time_total:  %{time_total}\n
   EOF
   ```

3. **Run baseline tests**
   ```bash
   ./test_performance.sh > baseline_performance.txt
   ```

## Phase 2: Core Features (Week 3-4)

### Step 2.1: Enable Advanced Rate Limiting

1. **Configure Rate Limits per Company Tier**
   ```php
   // config/gateway.php - Update rate limiting section
   'tier_multipliers' => [
       'free' => 1.0,
       'pro' => 3.0,
       'enterprise' => 10.0,
       'unlimited' => 50.0,
   ],
   ```

2. **Test Rate Limiting**
   ```bash
   # Test rate limit enforcement
   for i in {1..100}; do
     curl -s -o /dev/null -w "%{http_code}\n" \
       -H "Authorization: Bearer $API_TOKEN" \
       https://api.askproai.de/business/api/dashboard
   done | grep -c 429
   ```

3. **Monitor Rate Limit Metrics**
   ```bash
   php artisan gateway:status --json | jq '.rate_limiting'
   ```

### Step 2.2: Implement Caching Layer

1. **Verify Redis Configuration**
   ```bash
   php artisan tinker
   >>> Redis::ping()
   # Should return "+PONG"
   ```

2. **Test Cache Performance**
   ```bash
   # First request (cache miss)
   time curl -s -H "Authorization: Bearer $API_TOKEN" \
     https://api.askproai.de/business/api/dashboard > /dev/null
   
   # Second request (cache hit)
   time curl -s -H "Authorization: Bearer $API_TOKEN" \
     https://api.askproai.de/business/api/dashboard > /dev/null
   ```

3. **Monitor Cache Hit Rates**
   ```bash
   php artisan gateway:status | grep -A 10 "Cache Status"
   ```

### Step 2.3: Authentication Gateway

1. **Configure Authentication Methods**
   ```php
   // config/gateway.php - Add authentication section
   'authentication' => [
       'methods' => ['session', 'token', 'api_key'],
       'session_driver' => 'portal.session',
       'token_driver' => 'sanctum',
   ],
   ```

2. **Test Authentication Flow**
   ```bash
   # Test session auth
   curl -X POST -d "email=test@example.com&password=password" \
     -c cookies.txt https://api.askproai.de/business/login
   
   curl -b cookies.txt https://api.askproai.de/business/api/dashboard
   
   # Test token auth
   curl -H "Authorization: Bearer $API_TOKEN" \
     https://api.askproai.de/business/api/dashboard
   ```

### Step 2.4: API Versioning

1. **Configure Version Management**
   ```php
   // config/gateway.php - Update versioning section
   'versioning' => [
       'default_version' => 'v2',
       'supported_versions' => ['v1', 'v2'],
       'header_name' => 'API-Version',
   ],
   ```

2. **Test Version Resolution**
   ```bash
   # Header-based versioning
   curl -H "API-Version: v1" \
     -H "Authorization: Bearer $API_TOKEN" \
     https://api.askproai.de/business/api/dashboard
   
   # Accept header versioning
   curl -H "Accept: application/vnd.askproai.v2+json" \
     -H "Authorization: Bearer $API_TOKEN" \
     https://api.askproai.de/business/api/dashboard
   ```

## Phase 3: Advanced Features (Week 5-6)

### Step 3.1: Circuit Breaker Implementation

1. **Configure Circuit Breakers**
   ```php
   // config/gateway.php - Update circuit_breaker section
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
   ],
   ```

2. **Test Circuit Breaker**
   ```bash
   # Simulate service failure
   php artisan tinker
   >>> app('App\Gateway\CircuitBreaker\CircuitBreaker')->setState('test_service', 'open')
   
   # Test fallback response
   curl -H "Authorization: Bearer $API_TOKEN" \
     https://api.askproai.de/business/api/dashboard
   ```

### Step 3.2: Request/Response Transformation

1. **Create Transformation Pipeline**
   ```php
   // app/Gateway/Transformers/SecurityTransformer.php
   <?php
   namespace App\Gateway\Transformers;
   
   class SecurityTransformer implements RequestTransformerInterface
   {
       public function transform(Request $request): Request
       {
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
   ```

2. **Test Input Sanitization**
   ```bash
   curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer $API_TOKEN" \
     -d '{"name":"<script>alert(1)</script>Test Name"}' \
     https://api.askproai.de/business/api/customers
   ```

### Step 3.3: Service Discovery Preparation

1. **Create Service Definitions**
   ```php
   // Create service definitions for future microservices
   $registry->register('appointments_service', new ServiceDefinition(
       'appointments',
       'v1',
       [
           ['pattern' => 'business/api/appointments', 'methods' => ['GET', 'POST']],
           ['pattern' => 'business/api/appointments/{id}', 'methods' => ['GET', 'PUT', 'DELETE']],
       ],
       [
           ['type' => 'http', 'url' => '/health', 'interval' => 30]
       ],
       ['strategy' => 'round_robin'],
       ['failure_threshold' => 5, 'timeout' => 60]
   ));
   ```

## Phase 4: Migration & Optimization (Week 7-8)

### Step 4.1: Backward Compatibility Testing

1. **Test All Existing Endpoints**
   ```bash
   # Create comprehensive test script
   cat > test_all_endpoints.sh << 'EOF'
   #!/bin/bash
   
   endpoints=(
     "business/api/dashboard"
     "business/api/calls"
     "business/api/appointments"
     "business/api/customers"
     "business/api/analytics/overview"
     "business/api/settings"
     "business/api/billing"
     "business/api/team"
   )
   
   for endpoint in "${endpoints[@]}"; do
     echo "Testing $endpoint..."
     response=$(curl -s -w "%{http_code}" -o response.json \
       -H "Authorization: Bearer $API_TOKEN" \
       "https://api.askproai.de/$endpoint")
     
     if [ "$response" -eq 200 ]; then
       echo "✅ $endpoint - OK"
     else
       echo "❌ $endpoint - Failed ($response)"
       cat response.json
     fi
   done
   EOF
   
   chmod +x test_all_endpoints.sh
   ./test_all_endpoints.sh
   ```

2. **Compare Response Structures**
   ```bash
   # Before gateway
   curl -H "Authorization: Bearer $API_TOKEN" \
     https://api.askproai.de/business/api/dashboard > before_gateway.json
   
   # After gateway
   curl -H "Authorization: Bearer $API_TOKEN" \
     https://api.askproai.de/business/api/dashboard > after_gateway.json
   
   # Compare
   diff before_gateway.json after_gateway.json
   ```

### Step 4.2: Performance Optimization

1. **Optimize Cache Strategies**
   ```php
   // Fine-tune TTL values based on data volatility
   'endpoint_ttls' => [
       'business/api/dashboard' => 30,      // Very dynamic
       'business/api/calls' => 15,          // Real-time data
       'business/api/calls/{id}' => 600,    // Individual calls stable
       'business/api/customers' => 300,     // Moderate updates
       'business/api/settings' => 1800,     // Rarely changes
   ],
   ```

2. **Monitor Performance Impact**
   ```bash
   # Compare performance after optimization
   ./test_performance.sh > optimized_performance.txt
   diff baseline_performance.txt optimized_performance.txt
   ```

3. **Tune Rate Limits**
   ```php
   // Adjust based on usage patterns
   'endpoint_limits' => [
       'business/api/dashboard' => [
           'requests' => 60,    // Increased for dashboard
           'window' => 3600,
       ],
       'business/api/calls' => [
           'requests' => 300,   // High usage endpoint
           'window' => 3600,
       ],
   ],
   ```

### Step 4.3: Monitoring Setup

1. **Create Monitoring Dashboard**
   ```bash
   php artisan make:command GatewayDashboardCommand
   ```

2. **Set Up Alerts**
   ```php
   // config/gateway.php - Configure alerting
   'alerts' => [
       'high_error_rate' => [
           'threshold' => 5,      // 5% error rate
           'action' => 'email',
           'recipients' => ['admin@askproai.de'],
       ],
       'cache_hit_rate_low' => [
           'threshold' => 70,     // 70% hit rate minimum
           'action' => 'slack',
           'webhook' => env('SLACK_WEBHOOK_URL'),
       ],
   ],
   ```

3. **Create Health Check Endpoint**
   ```php
   // routes/api.php
   Route::get('/gateway/health', function () {
       return app(\App\Gateway\ApiGatewayManager::class)->getHealthStatus();
   });
   ```

### Step 4.4: Documentation and Training

1. **Update API Documentation**
   ```markdown
   ## API Gateway Headers
   
   ### Rate Limiting Headers
   - `X-RateLimit-Limit`: Maximum requests allowed
   - `X-RateLimit-Remaining`: Remaining requests in current window
   - `X-RateLimit-Reset`: When the rate limit resets (timestamp)
   
   ### Caching Headers
   - `X-Cache`: HIT or MISS
   - `X-Cache-Age`: Age of cached content in seconds
   
   ### Versioning Headers
   - `API-Version`: Specify API version (v1, v2)
   ```

2. **Create Operational Runbook**
   ```markdown
   ## Gateway Operations
   
   ### Common Commands
   - `php artisan gateway:status` - Check gateway health
   - `php artisan gateway:cache:clear` - Clear gateway cache
   - `php artisan gateway:metrics` - View performance metrics
   
   ### Troubleshooting
   - High error rate: Check circuit breaker status
   - Slow responses: Check cache hit rates
   - Rate limit errors: Review tier limits and usage patterns
   ```

## Performance Validation

### Target Metrics
- **Gateway Overhead**: < 10ms additional latency
- **Cache Hit Rate**: > 80% for cacheable endpoints
- **Error Rate**: < 1% under normal conditions
- **P99 Response Time**: < 200ms for cached responses

### Validation Commands

```bash
# Performance test
php artisan gateway:performance-test

# Load test
ab -n 1000 -c 10 -H "Authorization: Bearer $API_TOKEN" \
  https://api.askproai.de/business/api/dashboard

# Cache performance
php artisan gateway:cache-test

# Rate limiting test
php artisan gateway:rate-limit-test
```

## Rollback Strategy

### Quick Rollback (Emergency)
```bash
# Disable gateway immediately
php artisan config:set gateway.enabled false
php artisan config:cache

# Or via environment
echo "API_GATEWAY_ENABLED=false" >> .env
php artisan config:cache
```

### Gradual Rollback
```bash
# Disable specific components
php artisan config:set gateway.rate_limiting.enabled false
php artisan config:set gateway.caching.enabled false
php artisan config:set gateway.circuit_breaker.enabled false
php artisan config:cache
```

### Complete Removal
```bash
# Remove middleware from kernel
# Comment out ApiGatewayMiddleware in app/Http/Kernel.php

# Remove service provider
# Comment out ApiGatewayServiceProvider in config/app.php

php artisan config:cache
php artisan route:cache
```

## Monitoring and Alerting

### Key Metrics to Track
1. **Request Volume**: Requests per minute/hour
2. **Response Times**: P50, P95, P99 percentiles
3. **Error Rates**: By endpoint and overall
4. **Cache Performance**: Hit rates, memory usage
5. **Rate Limiting**: Violations per hour
6. **Circuit Breaker**: State changes, failure rates

### Alert Conditions
```yaml
alerts:
  - name: "High Error Rate"
    condition: "error_rate > 5%"
    window: "5 minutes"
    action: "email + slack"
    
  - name: "High Latency"
    condition: "p99_response_time > 500ms"
    window: "10 minutes"
    action: "email"
    
  - name: "Low Cache Hit Rate"
    condition: "cache_hit_rate < 70%"
    window: "15 minutes"
    action: "slack"
    
  - name: "Circuit Breaker Open"
    condition: "circuit_breaker_state = open"
    window: "immediate"
    action: "email + slack"
```

## Post-Migration Checklist

### Week 1 Post-Migration
- [ ] Monitor error rates hourly
- [ ] Check performance metrics daily
- [ ] Validate cache hit rates
- [ ] Test all critical user journeys
- [ ] Review rate limiting effectiveness

### Week 2-4 Post-Migration
- [ ] Analyze performance trends
- [ ] Optimize cache TTL values
- [ ] Adjust rate limits based on usage
- [ ] Fine-tune circuit breaker thresholds
- [ ] Update documentation with learnings

### Monthly Reviews
- [ ] Performance benchmark comparison
- [ ] Cost analysis (cache, computing resources)
- [ ] User feedback review
- [ ] Security audit
- [ ] Capacity planning for growth

## Success Criteria

### Performance
- ✅ P99 response time < 200ms for cached endpoints
- ✅ Gateway overhead < 10ms
- ✅ Cache hit rate > 80%
- ✅ Error rate < 1%

### Reliability
- ✅ 99.9% uptime maintained
- ✅ Circuit breakers prevent cascading failures
- ✅ Rate limiting prevents abuse
- ✅ Graceful degradation under load

### Scalability
- ✅ Support for 100+ concurrent users
- ✅ Handle 1000+ requests/minute
- ✅ Memory usage < 512MB additional
- ✅ Prepared for microservices migration

This migration guide provides a comprehensive approach to implementing the API Gateway pattern while maintaining backward compatibility and achieving performance targets. Follow each phase carefully and validate thoroughly before proceeding to the next phase.