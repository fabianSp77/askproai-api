# Circuit Breaker Implementation - AskProAI

## Overview

Implemented a comprehensive Circuit Breaker pattern for all external service integrations to ensure fault tolerance and system resilience.

## Components Implemented

### 1. Core Circuit Breaker Classes
- **CircuitBreaker.php**: Core implementation with states (CLOSED, OPEN, HALF_OPEN)
- **CircuitBreakerManager.php**: Centralized management for all service circuit breakers
- **CircuitBreakerService.php**: Service layer for circuit breaker operations
- **CircuitState.php**: State constants for circuit breaker states
- **HasCircuitBreaker.php**: Trait for easy integration into services

### 2. Services with Circuit Breaker Protection

#### Cal.com API (CalcomV2Service)
- Already integrated with circuit breaker
- Failure threshold: 3 (configurable)
- Timeout: 30 seconds
- Protects all API calls to Cal.com

#### Retell.ai API (RetellV2Service)
- Already integrated with circuit breaker
- Failure threshold: 5 (configurable)
- Timeout: 60 seconds
- Protects all API calls to Retell.ai

#### Stripe API (StripeServiceWithCircuitBreaker)
- New wrapper class created
- Failure threshold: 10 (configurable)
- Timeout: 120 seconds
- Fallback strategies for critical operations

### 3. Monitoring & Management

#### Filament Admin Page
- **CircuitBreakerMonitor.php**: Real-time monitoring dashboard
- Shows state, health score, and metrics for all services
- Allows manual reset and force-open for testing

#### Artisan Commands
```bash
# Check status of all circuit breakers
php artisan circuit-breaker:status

# Test circuit breakers with real API calls
php artisan circuit-breaker:status --test

# Reset all circuit breakers
php artisan circuit-breaker:status --reset

# Check specific service
php artisan circuit-breaker:status --service=calcom
```

#### API Health Endpoints
```
GET /api/health/circuit-breakers         # Overall status (requires auth)
GET /api/health/circuit-breakers/calcom  # Specific service status (requires auth)
```

### 4. Database Support
- **circuit_breaker_metrics** table for historical tracking
- Stores success/failure rates, response times, and states
- Used for health score calculation

## Configuration

### Environment Variables
```env
# Global defaults
CIRCUIT_BREAKER_FAILURE_THRESHOLD=5
CIRCUIT_BREAKER_SUCCESS_THRESHOLD=2
CIRCUIT_BREAKER_TIMEOUT=60
CIRCUIT_BREAKER_HALF_OPEN_REQUESTS=3

# Service-specific
CALCOM_CIRCUIT_BREAKER_THRESHOLD=3
CALCOM_CIRCUIT_BREAKER_TIMEOUT=30

RETELL_CIRCUIT_BREAKER_THRESHOLD=5
RETELL_CIRCUIT_BREAKER_TIMEOUT=60

STRIPE_CIRCUIT_BREAKER_THRESHOLD=10
STRIPE_CIRCUIT_BREAKER_TIMEOUT=120
```

### Configuration File
Located at: `config/circuit_breaker.php`

## How It Works

1. **CLOSED State** (Normal Operation)
   - All requests pass through
   - Failures are counted
   - Opens when failure threshold reached

2. **OPEN State** (Service Down)
   - All requests fail fast
   - Fallback strategies used if available
   - Waits for timeout before attempting recovery

3. **HALF_OPEN State** (Testing Recovery)
   - Limited requests allowed through
   - Success threshold must be met to close
   - Any failure returns to OPEN state

## Usage Example

```php
// Automatic usage in services
$calcomService = app(CalcomV2Service::class);
$result = $calcomService->getEventTypes(); // Protected by circuit breaker

// Manual usage with fallback
$circuitBreaker = app(CircuitBreakerManager::class);
$result = $circuitBreaker->call('stripe', 
    function() {
        // Primary operation
        return $this->stripe->customers->create([...]);
    },
    function($exception) {
        // Fallback operation
        return $this->createLocalCustomerOnly();
    }
);
```

## Health Score Calculation

Health scores (0-100) are calculated based on:
- Success rate over last 5 minutes
- Average response time
- Recent failure patterns

## Benefits

1. **Fault Tolerance**: Prevents cascading failures
2. **Fast Fail**: No waiting for timeouts when service is down
3. **Automatic Recovery**: Self-healing when service recovers
4. **Monitoring**: Real-time visibility into service health
5. **Performance**: Reduces load on failing services

## Next Steps

1. Add more granular circuit breakers per operation type
2. Implement adaptive thresholds based on time of day
3. Add alerting when circuit breakers open
4. Create service-specific fallback strategies
5. Add circuit breaker events to audit log