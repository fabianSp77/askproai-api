# Cal.com Optimization Load Testing Strategy
**Date**: 2025-11-11
**Status**: Ready for Execution
**Objective**: Validate performance optimizations before production deployment

---

## Quick Reference

**Testing Tool**: k6 (preferred for API testing)
**Total Duration**: 4 hours
**Required**: Docker, k6, Redis CLI, curl
**Pass Criteria**: All 4 scenarios must pass success criteria

---

## Test Environment Setup

### Prerequisites

```bash
# 1. Install k6
brew install k6  # macOS
# OR
sudo snap install k6  # Linux

# 2. Verify tools
k6 version
redis-cli --version
php artisan --version

# 3. Prepare test environment
php artisan config:clear
php artisan cache:clear
php artisan queue:restart
```

### Monitoring Dashboard

```bash
# Terminal 1: k6 execution
k6 run --out json=results.json scenario1-normal-load.js

# Terminal 2: Rate limit monitoring
watch -n 1 'redis-cli get $(echo "calcom_api_rate_limit:$(date +%Y-%m-%d-%H-%M)" | tr -d " ")'

# Terminal 3: Queue depth monitoring
watch -n 2 'php artisan queue:work --once --queue=cache --stop-when-empty'

# Terminal 4: Application logs
tail -f storage/logs/laravel.log | grep -E "Cal.com|cache|rate"

# Terminal 5: Performance metrics
watch -n 5 'curl -s http://localhost:8000/api/performance-metrics | jq .'
```

---

## Scenario 1: Normal Voice Agent Load

**Objective**: Validate optimizations under typical production load

### Configuration

```javascript
// scenario1-normal-load.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Trend } from 'k6/metrics';

// Custom metrics
const cacheHits = new Counter('cache_hits');
const cacheMisses = new Counter('cache_misses');
const availabilityLatency = new Trend('availability_check_latency');
const bookingLatency = new Trend('booking_latency');

export const options = {
  stages: [
    { duration: '2m', target: 10 },   // Ramp up to 10 users
    { duration: '25m', target: 10 },  // Steady state
    { duration: '3m', target: 0 },    // Ramp down
  ],
  thresholds: {
    'http_req_duration{endpoint:availability}': ['p(95)<4000'],  // 95% < 4s
    'http_req_duration{endpoint:booking}': ['p(95)<6000'],       // 95% < 6s
    'cache_hits': ['count>100'],                                  // Min 100 hits
    'http_req_failed': ['rate<0.02'],                            // <2% failure
  },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';
const API_TOKEN = __ENV.API_TOKEN || 'test-token';

export default function () {
  const headers = {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${API_TOKEN}`,
  };

  // 60% check availability
  if (Math.random() < 0.6) {
    const availPayload = JSON.stringify({
      call_id: `call_${__VU}_${__ITER}`,
      service_name: 'Herrenhaarschnitt',
      desired_date: '2025-11-15',
      desired_time: '14:00',
    });

    const startAvail = Date.now();
    const availResp = http.post(
      `${BASE_URL}/api/retell/check_availability`,
      availPayload,
      { headers, tags: { endpoint: 'availability' } }
    );

    const availDuration = Date.now() - startAvail;
    availabilityLatency.add(availDuration);

    check(availResp, {
      'availability: status 200': (r) => r.status === 200,
      'availability: has slots': (r) => {
        const body = JSON.parse(r.body);
        return body.slots && body.slots.length > 0;
      },
    });

    // Track cache hits
    const respHeaders = availResp.headers['X-Cache-Hit'];
    if (respHeaders === 'true') {
      cacheHits.add(1);
    } else {
      cacheMisses.add(1);
    }
  }

  // 20% create booking
  else if (Math.random() < 0.5) {
    const bookPayload = JSON.stringify({
      call_id: `call_${__VU}_${__ITER}_book`,
      service_name: 'Herrenhaarschnitt',
      customer_name: `Test User ${__VU}`,
      customer_email: `test${__VU}@example.com`,
      customer_phone: '+49123456789',
      appointment_date: '2025-11-15',
      appointment_time: '15:00',
    });

    const startBook = Date.now();
    const bookResp = http.post(
      `${BASE_URL}/api/retell/create_booking`,
      bookPayload,
      { headers, tags: { endpoint: 'booking' } }
    );

    const bookDuration = Date.now() - startBook;
    bookingLatency.add(bookDuration);

    check(bookResp, {
      'booking: status 200 or 201': (r) => r.status === 200 || r.status === 201,
      'booking: has confirmation': (r) => {
        const body = JSON.parse(r.body);
        return body.booking_id || body.appointment_id;
      },
    });
  }

  // 20% find alternatives
  else {
    const altPayload = JSON.stringify({
      call_id: `call_${__VU}_${__ITER}_alt`,
      service_name: 'Damenhaarschnitt',
      desired_date: '2025-11-20',
      desired_time: '10:00',
    });

    const altResp = http.post(
      `${BASE_URL}/api/retell/find_alternatives`,
      altPayload,
      { headers, tags: { endpoint: 'alternatives' } }
    );

    check(altResp, {
      'alternatives: status 200': (r) => r.status === 200,
      'alternatives: has suggestions': (r) => {
        const body = JSON.parse(r.body);
        return body.alternatives && body.alternatives.length > 0;
      },
    });
  }

  // Think time: 2-5 seconds between requests (realistic voice agent pauses)
  sleep(2 + Math.random() * 3);
}

export function handleSummary(data) {
  return {
    'scenario1-summary.json': JSON.stringify(data, null, 2),
    stdout: textSummary(data, { indent: ' ', enableColors: true }),
  };
}
```

### Success Criteria

| Metric | Target | Validation Method |
|--------|--------|-------------------|
| P95 Latency (availability) | <4,000ms | k6 threshold |
| P95 Latency (booking) | <6,000ms | k6 threshold |
| Cache Hit Rate | >55% | cacheHits / (cacheHits + cacheMisses) |
| Rate Limit Usage | <70 req/min | Redis monitoring |
| Failure Rate | <2% | k6 threshold |
| Circuit Breaker Opens | 0 | Application logs |

### Execution

```bash
# Run test
k6 run scenario1-normal-load.js

# Verify results
cat scenario1-summary.json | jq '.metrics | {
  "p95_availability": .http_req_duration.values["p(95)"],
  "p95_booking": .booking_latency.values["p(95)"],
  "cache_hit_rate": (.cache_hits.values.count / (.cache_hits.values.count + .cache_misses.values.count) * 100),
  "failure_rate": .http_req_failed.values.rate
}'
```

---

## Scenario 2: Peak Traffic Burst

**Objective**: Validate system behavior under 2.5× normal load

### Configuration

```javascript
// scenario2-peak-burst.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 25 },  // Rapid spike to 25 users
    { duration: '8m', target: 25 },   // Sustained peak
    { duration: '1m30s', target: 0 }, // Rapid ramp down
  ],
  thresholds: {
    'http_req_duration': ['p(95)<6000'],  // Allow degradation
    'http_req_failed': ['rate<0.05'],     // <5% failure (relaxed)
  },
};

// Similar implementation to Scenario 1 but with aggressive load

export default function () {
  // Shortened think time to simulate burst
  const thinkTime = 0.5 + Math.random() * 1.5;  // 0.5-2s (vs 2-5s normal)

  // ... same request logic as Scenario 1 ...

  sleep(thinkTime);
}
```

### Success Criteria

| Metric | Target | Notes |
|--------|--------|-------|
| P95 Latency | <6,000ms | Degradation allowed |
| Circuit Breaker | CLOSED | Must not open |
| Rate Limit Violations | <2 per 10min | Acceptable transient spikes |
| Cache Stampede Events | <2 per burst | Request coalescing effectiveness |
| Queue Recovery | <30 seconds | After burst ends |

### Execution

```bash
# Run test
k6 run scenario2-peak-burst.js

# Monitor rate limit during burst
# In separate terminal:
while true; do
  current_min=$(date +%Y-%m-%d-%H-%M)
  count=$(redis-cli get "calcom_api_rate_limit:$current_min")
  echo "[$current_min] Requests: ${count:-0}/120"
  sleep 5
done
```

---

## Scenario 3: Cache Cold Start

**Objective**: Validate request coalescing and cache warming

### Configuration

```javascript
// scenario3-cold-start.js
import http from 'k6/http';
import { check } from 'k6';

export const options = {
  stages: [
    { duration: '10s', target: 15 },  // Quick ramp
    { duration: '4m50s', target: 15 }, // Monitor cache warming
  ],
  thresholds: {
    'http_req_failed': ['rate<0.05'],
  },
};

export function setup() {
  // Flush Redis cache before test
  const flushResp = http.get(`${BASE_URL}/api/admin/flush-cache`, {
    headers: { 'Authorization': `Bearer ${API_TOKEN}` }
  });

  check(flushResp, { 'cache flushed': (r) => r.status === 200 });

  return { startTime: Date.now() };
}

export default function (data) {
  // Deliberately request same event types to trigger coalescing
  const eventTypes = ['Herrenhaarschnitt', 'Damenhaarschnitt', 'Färben'];
  const service = eventTypes[__ITER % 3];

  // ... availability check requests ...

  sleep(1 + Math.random());
}

export function teardown(data) {
  const duration = (Date.now() - data.startTime) / 1000;
  console.log(`Cache warming completed in ${duration} seconds`);
}
```

### Success Criteria

| Metric | Target | Measurement Window |
|--------|--------|-------------------|
| Request Coalescing | >70% duplicate prevention | First 2 minutes |
| Cache Hit Rate Growth | 0% → 60% | 0 min → 5 min |
| Rate Limit Compliance | No violations | Entire test |
| Circuit Breaker | May open briefly (<60s) | Acceptable |

### Execution

```bash
# Run test
k6 run scenario3-cold-start.js

# Track cache hit rate growth
# In separate terminal:
php artisan tinker --execute="
for (\$i = 0; \$i < 6; \$i++) {
    sleep(60);
    \$hits = Cache::get('test_cache_hits', 0);
    \$misses = Cache::get('test_cache_misses', 0);
    \$total = \$hits + \$misses;
    \$rate = \$total > 0 ? round((\$hits / \$total) * 100, 2) : 0;
    echo \"Minute \$i: Hit rate = \$rate% (\$hits / \$total)\n\";
}
"
```

---

## Scenario 4: Queue Backlog Recovery

**Objective**: Validate async cache clearing job resilience

### Configuration

```bash
# scenario4-queue-recovery.sh

#!/bin/bash

echo "=== Scenario 4: Queue Backlog Recovery ==="

# Step 1: Stop queue workers
echo "[1/6] Stopping queue workers..."
php artisan queue:restart
killall -9 php  # Ensure all workers stopped

# Step 2: Generate bookings (queue jobs accumulate)
echo "[2/6] Generating 80 bookings (queue disabled)..."
for i in {1..80}; do
  curl -s -X POST http://localhost:8000/api/retell/create_booking \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer ${API_TOKEN}" \
    -d "{
      \"call_id\": \"test_queue_$i\",
      \"service_name\": \"Herrenhaarschnitt\",
      \"customer_name\": \"Queue Test $i\",
      \"customer_email\": \"queue$i@test.com\",
      \"appointment_date\": \"2025-11-20\",
      \"appointment_time\": \"10:00\"
    }" > /dev/null

  sleep 0.5  # Throttle to avoid rate limit
done

# Step 3: Check queue depth
echo "[3/6] Checking queue depth..."
queue_count=$(redis-cli LLEN "queues:cache")
echo "Queue depth: $queue_count jobs"

if [ "$queue_count" -lt 70 ]; then
  echo "❌ FAIL: Expected ~80 jobs, found $queue_count"
  exit 1
fi

# Step 4: Restart queue workers and measure recovery
echo "[4/6] Restarting queue workers..."
start_time=$(date +%s)
php artisan queue:work --queue=cache --stop-when-empty &
WORKER_PID=$!

# Step 5: Wait for queue to empty
while [ $(redis-cli LLEN "queues:cache") -gt 0 ]; do
  sleep 0.1
done

end_time=$(date +%s)
recovery_duration=$((end_time - start_time))

# Step 6: Validate recovery
echo "[5/6] Queue cleared!"
echo "Recovery duration: ${recovery_duration} seconds"

if [ "$recovery_duration" -lt 5 ]; then
  echo "✅ PASS: Recovery completed in ${recovery_duration}s (< 5s target)"
else
  echo "⚠️ WARN: Recovery took ${recovery_duration}s (> 5s target)"
fi

# Cleanup
kill $WORKER_PID 2>/dev/null
php artisan queue:restart

echo "[6/6] Scenario complete!"
```

### Success Criteria

| Metric | Target | Critical |
|--------|--------|----------|
| Queue Processing Time | <5 seconds for 80 jobs | ✅ Yes |
| Worker Stability | No crashes | ✅ Yes |
| Cache Staleness Alert | Monitoring alert triggered | ⚠️ Manual check |
| Booking Completion | All 80 bookings confirmed | ✅ Yes |

### Execution

```bash
chmod +x scenario4-queue-recovery.sh
./scenario4-queue-recovery.sh
```

---

## Results Analysis

### Automated Report Generation

```bash
# analysis-script.sh

#!/bin/bash

echo "=== Cal.com Performance Test Results ==="
echo ""

# Scenario 1: Normal Load
echo "## Scenario 1: Normal Load"
if [ -f "scenario1-summary.json" ]; then
  cat scenario1-summary.json | jq -r '
    "P95 Availability: \(.metrics.http_req_duration.values["p(95)"])ms",
    "P95 Booking: \(.metrics.booking_latency.values["p(95)"])ms",
    "Cache Hit Rate: \((.metrics.cache_hits.values.count / (.metrics.cache_hits.values.count + .metrics.cache_misses.values.count) * 100) | round)%",
    "Failure Rate: \((.metrics.http_req_failed.values.rate * 100) | round)%"
  '
  echo ""
fi

# Scenario 2: Peak Burst
echo "## Scenario 2: Peak Burst"
if [ -f "scenario2-summary.json" ]; then
  cat scenario2-summary.json | jq -r '
    "P95 Latency: \(.metrics.http_req_duration.values["p(95)"])ms",
    "Max Rate/Min: \(.metrics.http_reqs.values.rate * 60 | round)",
    "Failure Rate: \((.metrics.http_req_failed.values.rate * 100) | round)%"
  '
  echo ""
fi

# Scenario 3: Cold Start
echo "## Scenario 3: Cold Start"
if [ -f "scenario3-summary.json" ]; then
  cat scenario3-summary.json | jq -r '
    "Initial Cache Misses: \(.metrics.cache_misses.values.count)",
    "Final Cache Hits: \(.metrics.cache_hits.values.count)",
    "Coalescing Effectiveness: \(.metrics.coalesced_requests.values.count) duplicates prevented"
  '
  echo ""
fi

# Scenario 4: Queue Recovery
echo "## Scenario 4: Queue Recovery"
echo "Check scenario4-queue-recovery.sh output for results"
echo ""

# Overall Verdict
echo "## Overall Verdict"
echo ""

all_passed=true

# Check Scenario 1 thresholds
if [ -f "scenario1-summary.json" ]; then
  p95=$(cat scenario1-summary.json | jq '.metrics.http_req_duration.values["p(95)"]')
  if (( $(echo "$p95 > 4000" | bc -l) )); then
    echo "❌ Scenario 1 FAILED: P95 latency ${p95}ms > 4000ms"
    all_passed=false
  else
    echo "✅ Scenario 1 PASSED"
  fi
fi

# Check Scenario 2 thresholds
if [ -f "scenario2-summary.json" ]; then
  p95=$(cat scenario2-summary.json | jq '.metrics.http_req_duration.values["p(95)"]')
  if (( $(echo "$p95 > 6000" | bc -l) )); then
    echo "❌ Scenario 2 FAILED: P95 latency ${p95}ms > 6000ms"
    all_passed=false
  else
    echo "✅ Scenario 2 PASSED"
  fi
fi

if [ "$all_passed" = true ]; then
  echo ""
  echo "🎉 ALL TESTS PASSED - Ready for production deployment"
  exit 0
else
  echo ""
  echo "⚠️ SOME TESTS FAILED - Review results before deployment"
  exit 1
fi
```

### Execution

```bash
chmod +x analysis-script.sh
./analysis-script.sh
```

---

## Appendix: Metrics Dashboard

### Real-time Performance Metrics API

**Create endpoint**: `routes/api.php`

```php
// Add performance metrics endpoint
Route::get('/performance-metrics', function () {
    $monitor = app(\App\Services\CalcomPerformanceMonitor::class);

    return response()->json([
        'timestamp' => now()->toIso8601String(),
        'rate_limit' => $monitor->getRateLimitStatus(),
        'slots_available' => $monitor->getMetricsSummary('/slots/available', 5),
        'bookings' => $monitor->getMetricsSummary('/bookings', 5),
        'queue_depth' => Redis::llen('queues:cache'),
        'cache_stats' => [
            'hits' => Cache::get('test_cache_hits', 0),
            'misses' => Cache::get('test_cache_misses', 0),
        ],
    ]);
});
```

### Grafana Dashboard (Optional)

**If using Grafana + Prometheus**:

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'laravel'
    metrics_path: '/metrics'
    static_configs:
      - targets: ['localhost:8000']
    scrape_interval: 5s
```

**Key Metrics to Monitor**:
- `calcom_api_requests_total` (counter)
- `calcom_api_latency_seconds` (histogram)
- `calcom_cache_hit_rate` (gauge)
- `calcom_rate_limit_remaining` (gauge)
- `calcom_queue_depth` (gauge)

---

## Checklist

### Pre-Test

- [ ] Database indexes deployed (`php artisan migrate`)
- [ ] Smart cache invalidation deployed (Phase 2)
- [ ] Async cache clearing deployed (Phase 3)
- [ ] Queue workers running (`php artisan queue:work`)
- [ ] Monitoring tools installed (k6, Redis CLI)
- [ ] Test credentials configured (`API_TOKEN`)
- [ ] Backup created (`php artisan backup:run`)

### During Test

- [ ] Monitor rate limit usage (Redis)
- [ ] Monitor queue depth (Redis LLEN)
- [ ] Monitor application logs (Laravel log)
- [ ] Monitor circuit breaker status (`CalcomService`)
- [ ] Monitor database query performance (slow query log)

### Post-Test

- [ ] All 4 scenarios executed successfully
- [ ] Results analyzed (`analysis-script.sh`)
- [ ] Performance report generated
- [ ] Anomalies investigated
- [ ] Production deployment approved or blocked
- [ ] Monitoring alerts configured

---

**Prepared By**: Performance Engineering Team
**Date**: 2025-11-11
**Execution Window**: 4 hours
**Next Steps**: Execute tests, analyze results, deploy to production
