# Load Testing Suite

Load tests for the AskPro AI Gateway using [k6](https://k6.io/).

## Quick Start

```bash
# Install k6
sudo apt install k6  # Ubuntu/Debian
brew install k6      # macOS

# Run baseline test
./tests/load/run-all.sh baseline

# Run all tests (baseline, normal, peak)
./tests/load/run-all.sh all
```

## Test Scenarios

| Scenario | VUs | Duration | Purpose |
|----------|-----|----------|---------|
| **Baseline** | 5 | 2min | Establish baseline metrics |
| **Normal Load** | 30 | 5min | Typical production traffic |
| **Peak Load** | 100 | 5min | Target capacity (50-100 calls) |
| **Stress Test** | 200 | 10min | Find breaking point |

## Configuration

Set environment variables before running:

```bash
export K6_BASE_URL="https://your-api.com"
export K6_TEST_COMPANY_ID="1"
export K6_API_KEY="your-api-key"  # If needed

# For stress tests (avoid hitting Cal.com)
export K6_MOCK_MODE="true"
```

## Mock Mode

For stress tests (200+ VUs), enable mock mode to avoid:
- Cal.com rate limiting
- Retell API costs
- External API bottlenecks

```bash
# Enable mock endpoints in Laravel
echo "LOAD_TEST_MOCK_MODE=true" >> .env
php artisan config:clear

# Run stress test with mocking
K6_MOCK_MODE=true ./run-all.sh stress
```

## Performance Targets

### Voice Hot Path (< 500ms p99)
- `check-customer`: p99 < 300ms
- `check-availability`: p99 < 500ms
- `collect-appointment`: p99 < 400ms

### Booking Flow (< 3s p99)
- `book-appointment`: p99 < 3000ms
- Webhook processing: p99 < 2000ms

### Error Rates
- Voice calls: > 99% success
- Booking: > 98% success

## Results

Results are saved to `tests/load/results/`:
- JSON metrics: `{scenario}_{timestamp}.json`
- Console summary: stdout

## Monitoring During Tests

```bash
# Watch queue depth
watch -n 1 'redis-cli LLEN queues:default'

# Watch worker logs
tail -f storage/logs/worker.log

# Watch Laravel logs
tail -f storage/logs/laravel.log

# Check supervisor status
sudo supervisorctl status
```

## Troubleshooting

### High Error Rates
1. Check Cal.com rate limits
2. Increase queue workers
3. Enable mock mode for stress tests

### High Latency
1. Check database connection count
2. Verify Redis is responding
3. Check Cal.com API health

### Tests Hang
1. Verify API is running
2. Check network connectivity
3. Reduce VU count
