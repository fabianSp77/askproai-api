# K6 Performance Tests

This directory contains performance test scripts for the AskProAI API using K6.

## Prerequisites

1. Install K6:
```bash
# macOS
brew install k6

# Ubuntu/Debian
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb https://dl.k6.io/deb stable main" | sudo tee -a /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6

# Windows (using Chocolatey)
choco install k6
```

2. Set up test user:
```bash
php artisan tinker
>>> $user = \App\Models\PortalUser::create([
...     'name' => 'Performance Test User',
...     'email' => 'performance-test@example.com',
...     'password' => bcrypt('perftest123'),
...     'company_id' => 1,
...     'role' => 'admin',
... ]);
```

## Test Types

### 1. Load Test (`load-test.js`)
Simulates normal expected load with gradual ramp-up and ramp-down.
- Duration: ~24 minutes
- Peak users: 100
- Use case: Regular performance validation

```bash
k6 run tests/Performance/k6/load-test.js \
  -e BASE_URL=https://api.askproai.de \
  -e TEST_EMAIL=performance-test@example.com \
  -e TEST_PASSWORD=perftest123
```

### 2. Stress Test (`stress-test.js`)
Pushes the system beyond normal capacity to find breaking points.
- Duration: ~26 minutes
- Peak users: 500
- Use case: Finding system limits

```bash
k6 run tests/Performance/k6/stress-test.js \
  -e BASE_URL=https://api.askproai.de
```

### 3. Spike Test (`spike-test.js`)
Tests system behavior under sudden traffic spikes.
- Duration: ~8 minutes
- Spike from 10 to 500 users
- Use case: Testing auto-scaling and recovery

```bash
k6 run tests/Performance/k6/spike-test.js \
  -e BASE_URL=https://api.askproai.de
```

### 4. Soak Test (`soak-test.js`)
Extended test to identify memory leaks and degradation over time.
- Duration: ~4 hours
- Constant 50 users
- Use case: Stability testing

```bash
k6 run tests/Performance/k6/soak-test.js \
  -e BASE_URL=https://api.askproai.de
```

## Running Tests

### Basic Execution
```bash
# Run with default settings
k6 run tests/Performance/k6/load-test.js

# Run with custom VUs (Virtual Users)
k6 run --vus 10 --duration 30s tests/Performance/k6/load-test.js

# Run with environment variables
k6 run -e BASE_URL=http://localhost:8000 tests/Performance/k6/load-test.js
```

### Output Results
```bash
# JSON output
k6 run --out json=results.json tests/Performance/k6/load-test.js

# InfluxDB output (for Grafana dashboards)
k6 run --out influxdb=http://localhost:8086/k6 tests/Performance/k6/load-test.js

# CSV output
k6 run --out csv=results.csv tests/Performance/k6/load-test.js
```

### Cloud Execution
```bash
# Run on K6 Cloud (requires account)
k6 cloud tests/Performance/k6/load-test.js
```

## Metrics

### Default Metrics
- `http_req_duration`: Request duration
- `http_req_failed`: Failed requests
- `http_reqs`: Total requests
- `vus`: Active virtual users
- `iterations`: Test iterations completed

### Custom Metrics
- `errors`: Custom error rate
- `api_latency`: API endpoint latency
- `login_latency`: Authentication latency
- `appointment_creation_latency`: Appointment creation time
- `memory_usage_mb`: System memory usage (soak test)

## Thresholds

Each test defines performance thresholds that must be met:

```javascript
thresholds: {
  http_req_duration: ['p(95)<500'], // 95% of requests < 500ms
  http_req_failed: ['rate<0.1'],    // Error rate < 10%
  errors: ['rate<0.1'],             // Custom errors < 10%
}
```

## CI/CD Integration

### GitHub Actions
```yaml
- name: Run K6 tests
  uses: grafana/k6-action@v0.2.0
  with:
    filename: tests/Performance/k6/load-test.js
    flags: --out json=results.json
  env:
    BASE_URL: ${{ secrets.API_URL }}
    TEST_EMAIL: ${{ secrets.TEST_EMAIL }}
    TEST_PASSWORD: ${{ secrets.TEST_PASSWORD }}
```

### Jenkins
```groovy
stage('Performance Test') {
    steps {
        sh 'k6 run tests/Performance/k6/load-test.js --out json=results.json'
        publishHTML([
            reportDir: '.',
            reportFiles: 'results.json',
            reportName: 'K6 Performance Report'
        ])
    }
}
```

## Analyzing Results

### Console Output
```
✓ status is 200
✓ response time < 500ms

checks.........................: 100.00% ✓ 5432      ✗ 0
data_received..................: 2.3 MB  95 kB/s
data_sent......................: 543 kB  22 kB/s
http_req_blocked...............: avg=1.23ms  min=0s      med=0s      max=123.45ms p(90)=2.34ms  p(95)=3.45ms
http_req_connecting............: avg=0.98ms  min=0s      med=0s      max=98.76ms  p(90)=1.23ms  p(95)=2.34ms
http_req_duration..............: avg=234.56ms min=123.45ms med=234.56ms max=567.89ms p(90)=345.67ms p(95)=456.78ms
  { expected_response:true }...: avg=234.56ms min=123.45ms med=234.56ms max=567.89ms p(90)=345.67ms p(95)=456.78ms
```

### Grafana Dashboard
1. Set up InfluxDB
2. Configure K6 to output to InfluxDB
3. Import K6 dashboard to Grafana
4. Monitor real-time metrics

## Best Practices

1. **Warm-up Period**: Always include a warm-up stage
2. **Think Time**: Add realistic delays between requests
3. **Test Data**: Use separate test accounts and data
4. **Baseline**: Establish performance baselines before changes
5. **Regular Testing**: Run tests as part of CI/CD pipeline
6. **Monitor Resources**: Track CPU, memory, and database metrics

## Troubleshooting

### Common Issues

1. **Authentication Failures**
   - Verify test user exists and is active
   - Check API endpoint URLs
   - Ensure proper token handling

2. **High Error Rates**
   - Check server logs for errors
   - Verify rate limiting settings
   - Monitor database connection pool

3. **Slow Response Times**
   - Check database query performance
   - Review caching strategy
   - Monitor external API calls

### Debug Mode
```bash
# Run with debug output
k6 run --http-debug tests/Performance/k6/load-test.js

# Run with verbose logging
k6 run --verbose tests/Performance/k6/load-test.js
```