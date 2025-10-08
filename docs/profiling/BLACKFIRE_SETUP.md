# Blackfire.io Setup for Production Memory Profiling

Blackfire is a production-safe profiler ideal for intermittent memory issues.

## Installation (ARM64 Compatible)

```bash
# 1. Install Blackfire probe (PHP extension)
wget -O - https://packages.blackfire.io/gpg.key | sudo apt-key add -
echo "deb http://packages.blackfire.io/debian any main" | sudo tee /etc/apt/sources.list.d/blackfire.list
sudo apt-get update
sudo apt-get install blackfire-php

# 2. Configure with your credentials
sudo blackfire-agent --register --server-id=<your-server-id> --server-token=<your-server-token>

# 3. Install CLI tool
wget https://get.blackfire.io/blackfire.tar.gz
tar -xzf blackfire.tar.gz
sudo mv blackfire /usr/local/bin/
blackfire config

# 4. Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

## Verify Installation

```bash
# Check probe is loaded
php -m | grep blackfire

# Check agent is running
sudo systemctl status blackfire-agent
```

## Usage Strategies

### Strategy 1: Triggered Profiling

Profile specific requests when memory issues occur:

```bash
# Profile a specific URL with authentication
blackfire curl \
  --header "Cookie: laravel_session=YOUR_SESSION" \
  https://your-app.com/filament/admin

# Profile with custom header to force profiling
blackfire curl \
  --header "X-Force-Memory-Profile: true" \
  https://your-app.com/api/endpoint
```

### Strategy 2: Continuous Profiling

Enable auto-profiling for matching requests:

```php
// config/blackfire.php
return [
    'auto_profile' => env('BLACKFIRE_AUTO_PROFILE', false),

    'auto_profile_conditions' => [
        // Profile requests to Filament
        'path' => ['/filament/*'],

        // Profile when memory usage is high
        'memory_threshold' => 1536 * 1024 * 1024, // 1.5GB
    ],
];
```

### Strategy 3: Comparison Profiling

Profile successful vs failed requests:

```bash
# Profile a request that succeeds
blackfire curl https://your-app.com/endpoint > success.json

# Profile the same request when it fails (retry until it fails)
for i in {1..10}; do
  blackfire curl https://your-app.com/endpoint > attempt_$i.json
  if grep -q "memory" attempt_$i.json; then
    echo "Found OOM in attempt $i"
    break
  fi
done

# Compare profiles
blackfire compare success.json attempt_X.json
```

## Reading Blackfire Memory Data

### Key Metrics to Check

1. **Memory Timeline**
   - Look for sudden spikes
   - Identify which function caused the spike
   - Check cumulative memory allocation

2. **Call Graph**
   - Find functions with high "Exclusive Memory"
   - Look for unexpected deep recursion
   - Identify hot paths with memory allocation

3. **SQL Queries**
   - Large result sets being loaded into memory
   - N+1 queries multiplying memory usage
   - Missing eager loading

4. **Object Allocation**
   - Models being instantiated repeatedly
   - Collections not being released
   - Circular references preventing GC

### Blackfire Assertions for Memory

Create performance assertions to detect issues automatically:

```yaml
# .blackfire.yaml
tests:
    "Memory should stay under 1.5GB":
        path: "/filament/.*"
        assertions:
            - "main.peak_memory < 1.5gb"
            - "metrics.sql.queries.count < 500"

    "Model instantiation should be reasonable":
        path: "/.*"
        assertions:
            - "metrics.eloquent.model.created.count < 1000"

    "Session size should be reasonable":
        path: "/.*"
        assertions:
            - "metrics.session.size < 50mb"
```

## ARM64 Limitations

If Blackfire doesn't work on ARM64:

### Alternative: Tideways

```bash
# Install Tideways (better ARM64 support)
curl -sSfL https://s3-eu-west-1.amazonaws.com/tideways/scripts/install.sh | sh

# Configure
sudo tideways-daemon --address 0.0.0.0:9135

# Add to php.ini
extension=tideways_xhprof.so
tideways.api_key=YOUR_API_KEY
```

### Alternative: XHProf (Open Source)

```bash
# Install XHProf
sudo pecl install xhprof

# Add to php.ini
extension=xhprof.so

# Use programmatically
<?php
xhprof_enable(XHPROF_FLAGS_MEMORY);
// Your code here
$data = xhprof_disable();

// Save for analysis
file_put_contents('/tmp/xhprof.json', json_encode($data));
```

## Integration with Our Custom Profiling

Combine Blackfire with our custom tools:

```php
// app/Http/Middleware/ConditionalBlackfire.php
public function handle(Request $request, Closure $next)
{
    $memoryUsage = memory_get_usage(true);

    // Trigger Blackfire when memory is high
    if ($memoryUsage > 1536 * 1024 * 1024 && extension_loaded('blackfire')) {
        $probe = \BlackfireProbe::getMainInstance();
        $probe->enable();

        $response = $next($request);

        $probe->close();
        return $response;
    }

    return $next($request);
}
```

## Expected Insights from Blackfire

After profiling, you should be able to answer:

1. **What is consuming memory?**
   - Session data? Models? Cache? Query results?

2. **When does memory spike?**
   - During model boot? Global scopes? Navigation building?

3. **Why is it inconsistent?**
   - Session state differences? Cache state? User permissions?

4. **Where to optimize?**
   - Specific function calls, query patterns, loading strategies

## Next Steps After Profiling

1. **Identify the culprit** from Blackfire timeline
2. **Implement targeted fix** based on findings
3. **Re-profile** to verify improvement
4. **Add assertions** to prevent regression
