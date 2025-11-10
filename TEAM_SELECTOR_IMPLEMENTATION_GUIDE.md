# Team-Selector Implementation Guide

**Step-by-step implementation instructions**

---

## Quick Start

### 1. Run Migrations

```bash
cd /var/www/api-gateway

# Create migration files (copy from TEAM_SELECTOR_ARCHITECTURE.md)
# Then run:
php artisan migrate

# Verify tables created
php artisan tinker
>>> Schema::hasTable('calcom_teams')
=> true
>>> Schema::hasTable('calcom_team_event_types')
=> true
```

### 2. Create Models

```bash
# Copy CalcomTeam.php to app/Models/
# Copy CalcomTeamEventType.php to app/Models/

# Test model loading
php artisan tinker
>>> $team = new \App\Models\CalcomTeam();
>>> $team->getFillable()
```

### 3. Create Service Layer

```bash
# Create service directory
mkdir -p app/Services/Calcom

# Copy CalcomTeamService.php to app/Services/Calcom/

# Test service instantiation
php artisan tinker
>>> $service = app(\App\Services\Calcom\CalcomTeamService::class);
>>> get_class($service)
=> "App\Services\Calcom\CalcomTeamService"
```

### 4. Create API Controller

```bash
# Copy CalcomTeamController.php to app/Http/Controllers/Api/

# Register routes in routes/api.php
```

### 5. Register Observer

Add to `app/Providers/AppServiceProvider.php`:

```php
use App\Models\CalcomTeam;
use App\Observers\CalcomTeamObserver;

public function boot(): void
{
    CalcomTeam::observe(CalcomTeamObserver::class);
}
```

---

## Testing Implementation

### Test 1: Database Setup

```bash
php artisan tinker
```

```php
// Test multi-tenant isolation
$company1 = \App\Models\Company::first();
$company2 = \App\Models\Company::skip(1)->first();

// Create teams
$team1 = \App\Models\CalcomTeam::create([
    'company_id' => $company1->id,
    'calcom_team_id' => 34209,
    'calcom_team_slug' => 'friseur',
    'name' => 'Friseur Team',
    'timezone' => 'Europe/Berlin',
    'is_active' => true,
    'is_default' => true,
]);

$team2 = \App\Models\CalcomTeam::create([
    'company_id' => $company2->id,
    'calcom_team_id' => 34210,
    'calcom_team_slug' => 'salon',
    'name' => 'Salon Team',
    'timezone' => 'Europe/Berlin',
    'is_active' => true,
    'is_default' => true,
]);

// Verify isolation
$teams1 = \App\Models\CalcomTeam::where('company_id', $company1->id)->get();
$teams2 = \App\Models\CalcomTeam::where('company_id', $company2->id)->get();

echo "Company 1 teams: " . $teams1->count() . "\n";
echo "Company 2 teams: " . $teams2->count() . "\n";
```

### Test 2: Service Layer

```bash
php artisan tinker
```

```php
$service = app(\App\Services\Calcom\CalcomTeamService::class);
$company = \App\Models\Company::first();

// Test getTeamsForCompany
$teams = $service->getTeamsForCompany($company->id);
echo "Teams found: " . $teams->count() . "\n";

// Test getDefaultTeam
$defaultTeam = $service->getDefaultTeam($company->id);
echo "Default team: " . $defaultTeam->name . "\n";

// Test caching
$cacheKey = sprintf('company:%d:calcom_teams:all', $company->id);
echo "Cache exists: " . (Cache::has($cacheKey) ? 'YES' : 'NO') . "\n";
```

### Test 3: API Endpoints

```bash
# Get authentication token first
php artisan tinker
```

```php
$user = \App\Models\User::first();
$token = $user->createToken('test')->plainTextToken;
echo "Token: " . $token . "\n";
exit;
```

```bash
# Test API endpoints
TOKEN="your-token-here"

# List teams
curl -X GET "http://localhost:8000/api/calcom/teams" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq

# Get default team
curl -X GET "http://localhost:8000/api/calcom/teams/default" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq

# Sync teams (admin only)
curl -X POST "http://localhost:8000/api/calcom/teams/sync" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq
```

### Test 4: Multi-Tenant Security

```bash
php artisan tinker
```

```php
// Create test users from different companies
$company1 = \App\Models\Company::first();
$company2 = \App\Models\Company::skip(1)->first();

$user1 = \App\Models\User::where('company_id', $company1->id)->first();
$user2 = \App\Models\User::where('company_id', $company2->id)->first();

// Create teams
$team1 = \App\Models\CalcomTeam::create([
    'company_id' => $company1->id,
    'calcom_team_id' => 1001,
    'calcom_team_slug' => 'team1',
    'name' => 'Company 1 Team',
    'is_default' => true,
]);

$team2 = \App\Models\CalcomTeam::create([
    'company_id' => $company2->id,
    'calcom_team_id' => 1002,
    'calcom_team_slug' => 'team2',
    'name' => 'Company 2 Team',
    'is_default' => true,
]);

// Test: User 1 should NOT see Team 2
$service = app(\App\Services\Calcom\CalcomTeamService::class);
$teams1 = $service->getTeamsForCompany($company1->id);
$teams2 = $service->getTeamsForCompany($company2->id);

echo "User1 can see " . $teams1->count() . " team(s)\n";
echo "User2 can see " . $teams2->count() . " team(s)\n";

// Verify IDs don't cross
echo "Team1 ID in User1 results: " . $teams1->contains('id', $team1->id) . "\n";
echo "Team2 ID in User1 results: " . $teams1->contains('id', $team2->id) . " (should be false)\n";
```

---

## Cal.com API Integration

### Test Cal.com Endpoints

```bash
# Set your Cal.com API key
CALCOM_API_KEY="your-api-key"

# Test: Fetch all teams
curl -X GET "https://api.cal.com/v2/teams" \
  -H "Authorization: Bearer $CALCOM_API_KEY" \
  -H "cal-api-version: 2024-08-13" | jq

# Test: Fetch specific team event types
curl -X GET "https://api.cal.com/v2/teams/34209/event-types" \
  -H "Authorization: Bearer $CALCOM_API_KEY" \
  -H "cal-api-version: 2024-08-13" | jq
```

### Sync Teams from Cal.com

```bash
php artisan tinker
```

```php
$service = app(\App\Services\Calcom\CalcomTeamService::class);
$company = \App\Models\Company::first();

// Sync teams
$result = $service->syncTeamsForCompany($company->id);

print_r($result);
// Expected output:
// [
//     'success' => true,
//     'synced' => 1,
//     'errors' => [],
//     'total' => 1
// ]

// Verify synced teams
$teams = \App\Models\CalcomTeam::where('company_id', $company->id)->get();
echo "Total teams: " . $teams->count() . "\n";

foreach ($teams as $team) {
    echo "- {$team->name} (ID: {$team->calcom_team_id})\n";
}
```

---

## Cache Verification

### Test Cache Behavior

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Cache;

$service = app(\App\Services\Calcom\CalcomTeamService::class);
$company = \App\Models\Company::first();

// Clear cache first
$cacheKey = sprintf('company:%d:calcom_teams:all', $company->id);
Cache::forget($cacheKey);

// First call: Cache MISS (slow)
$start = microtime(true);
$teams1 = $service->getTeamsForCompany($company->id);
$time1 = round((microtime(true) - $start) * 1000, 2);
echo "First call (cache miss): {$time1}ms\n";

// Second call: Cache HIT (fast)
$start = microtime(true);
$teams2 = $service->getTeamsForCompany($company->id);
$time2 = round((microtime(true) - $start) * 1000, 2);
echo "Second call (cache hit): {$time2}ms\n";

// Verify cache speedup
echo "Speedup: " . round($time1 / $time2, 2) . "x\n";

// Verify cache key exists
echo "Cache exists: " . (Cache::has($cacheKey) ? 'YES' : 'NO') . "\n";
```

### Test Cache Invalidation

```bash
php artisan tinker
```

```php
$company = \App\Models\Company::first();
$service = app(\App\Services\Calcom\CalcomTeamService::class);

// Warm cache
$teams = $service->getTeamsForCompany($company->id);
$cacheKey = sprintf('company:%d:calcom_teams:all', $company->id);

echo "Cache before update: " . (Cache::has($cacheKey) ? 'EXISTS' : 'MISSING') . "\n";

// Update a team (should trigger observer to invalidate cache)
$team = \App\Models\CalcomTeam::where('company_id', $company->id)->first();
$team->update(['name' => 'Updated Team Name']);

echo "Cache after update: " . (Cache::has($cacheKey) ? 'EXISTS' : 'MISSING') . "\n";
// Should print: MISSING (cache was invalidated)
```

---

## Performance Testing

### Test Concurrent Requests

Create test script: `test_team_api_performance.php`

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;

// Get test user token
$user = \App\Models\User::first();
$token = $user->createToken('perf-test')->plainTextToken;

// Simulate 10 concurrent requests
$start = microtime(true);
$responses = [];

for ($i = 0; $i < 10; $i++) {
    $request = Request::create('/api/calcom/teams', 'GET', [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $response = $kernel->handle($request);
    $responses[] = $response->getStatusCode();
}

$duration = round((microtime(true) - $start) * 1000, 2);

echo "10 concurrent requests completed in {$duration}ms\n";
echo "Average per request: " . round($duration / 10, 2) . "ms\n";
echo "Success rate: " . (count(array_filter($responses, fn($code) => $code === 200)) / 10 * 100) . "%\n";
```

Run:
```bash
php test_team_api_performance.php
```

---

## Troubleshooting

### Issue: Teams not visible for user

**Solution:**

```bash
php artisan tinker
```

```php
$user = \App\Models\User::first();
$company = $user->company;

// Check if teams exist for company
$teams = \App\Models\CalcomTeam::where('company_id', $company->id)->get();
echo "Teams for company {$company->id}: " . $teams->count() . "\n";

// Check BelongsToCompany trait is working
$allTeams = \App\Models\CalcomTeam::all();
echo "All teams (should be scoped): " . $allTeams->count() . "\n";

// Verify trait is applied
$traits = class_uses(\App\Models\CalcomTeam::class);
echo "BelongsToCompany trait: " . (in_array('App\Traits\BelongsToCompany', $traits) ? 'YES' : 'NO') . "\n";
```

### Issue: Cache not working

**Solution:**

```bash
# Check Redis connection
php artisan tinker
>>> Cache::store('redis')->get('test-key')

# Verify cache driver
>>> config('cache.default')
=> "redis"

# Test cache manually
>>> Cache::put('test-key', 'test-value', 60);
>>> Cache::get('test-key')
=> "test-value"

# Check Redis is running
redis-cli ping
# Should return: PONG
```

### Issue: Cal.com API errors

**Solution:**

```bash
php artisan tinker
```

```php
$service = app(\App\Services\CalcomService::class);

// Test connection
$result = $service->testConnection();
print_r($result);

// Check API key
echo "API Key configured: " . (config('calcom.api_key') ? 'YES' : 'NO') . "\n";
echo "Base URL: " . config('calcom.base_url') . "\n";

// Test team fetch
try {
    $response = $service->fetchTeams();
    echo "Status: " . $response->status() . "\n";
    print_r($response->json());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Issue: Migration fails

**Solution:**

```bash
# Check current migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback --step=1

# Check for table conflicts
php artisan tinker
>>> Schema::hasTable('calcom_teams')
>>> Schema::hasTable('calcom_team_event_types')

# If tables exist but migration not recorded:
# Manually mark as migrated
# Or drop tables and re-run migration
>>> Schema::dropIfExists('calcom_teams');
>>> Schema::dropIfExists('calcom_team_event_types');
```

---

## Deployment Checklist

### Pre-Deployment (Staging)

- [ ] Run migrations
  ```bash
  php artisan migrate --path=database/migrations/2025_11_10_*.php
  ```

- [ ] Verify backfill
  ```sql
  SELECT company_id, COUNT(*) FROM calcom_teams GROUP BY company_id;
  ```

- [ ] Test API endpoints
  ```bash
  curl -X GET "https://staging.askpro.ai/api/calcom/teams" \
    -H "Authorization: Bearer $TOKEN"
  ```

- [ ] Test multi-tenant isolation
  ```bash
  # User A requests
  curl -X GET "https://staging.askpro.ai/api/calcom/teams" \
    -H "Authorization: Bearer $TOKEN_USER_A"

  # User B requests (should see different teams)
  curl -X GET "https://staging.askpro.ai/api/calcom/teams" \
    -H "Authorization: Bearer $TOKEN_USER_B"
  ```

- [ ] Verify caching
  ```bash
  php artisan tinker
  >>> Cache::has('company:1:calcom_teams:all')
  ```

- [ ] Load test
  ```bash
  ab -n 100 -c 10 -H "Authorization: Bearer $TOKEN" \
    https://staging.askpro.ai/api/calcom/teams
  ```

### Deployment (Production)

- [ ] Backup database
  ```bash
  pg_dump -U postgres -h localhost askpro_production > backup_$(date +%Y%m%d).sql
  ```

- [ ] Deploy code
  ```bash
  git pull origin main
  composer install --no-dev --optimize-autoloader
  ```

- [ ] Run migrations
  ```bash
  php artisan migrate --force
  ```

- [ ] Clear caches
  ```bash
  php artisan cache:clear
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  ```

- [ ] Verify production
  ```bash
  curl -X GET "https://api.askpro.ai/api/calcom/teams" \
    -H "Authorization: Bearer $PROD_TOKEN"
  ```

### Post-Deployment

- [ ] Monitor logs
  ```bash
  tail -f storage/logs/laravel.log | grep -i "calcom"
  ```

- [ ] Check error rates
  ```bash
  # In Laravel Telescope
  # Filter by: status >= 400
  # Path contains: /api/calcom/teams
  ```

- [ ] Verify cache hit rate
  ```bash
  redis-cli
  > INFO stats
  # Look for: keyspace_hits, keyspace_misses
  # Target: >95% hit rate
  ```

- [ ] Test end-to-end booking flow
  - User selects team
  - Event types filter by team
  - Booking creates with correct team_id

---

## Quick Reference Commands

```bash
# Create test team
php artisan tinker
>>> \App\Models\CalcomTeam::create([
    'company_id' => 1,
    'calcom_team_id' => 34209,
    'calcom_team_slug' => 'friseur',
    'name' => 'Friseur Team',
    'is_default' => true
]);

# Sync teams from Cal.com
>>> app(\App\Services\Calcom\CalcomTeamService::class)
    ->syncTeamsForCompany(1);

# Clear team cache
>>> Cache::forget('company:1:calcom_teams:all');

# List all teams
>>> \App\Models\CalcomTeam::with('company', 'branch')->get();

# Get default team
>>> app(\App\Services\Calcom\CalcomTeamService::class)
    ->getDefaultTeam(1);

# Test API endpoint
curl -X GET "http://localhost:8000/api/calcom/teams" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq
```

---

**Related Files:**
- Full architecture: `TEAM_SELECTOR_ARCHITECTURE.md`
- Quick summary: `TEAM_SELECTOR_ARCHITECTURE_SUMMARY.md`
