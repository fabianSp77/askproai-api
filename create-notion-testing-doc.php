<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\NotionMCPServer;

$notion = new NotionMCPServer();

// First, let's find the parent page
$searchResult = $notion->executeTool('search_pages', [
    'query' => 'Documentation'
]);

if (!$searchResult['success']) {
    echo "Failed to search for parent page: " . $searchResult['error'] . "\n";
    exit(1);
}

echo "Found pages:\n";
foreach ($searchResult['data']['pages'] as $page) {
    echo "- {$page['title']} (ID: {$page['id']})\n";
}

// Use the configured parent page ID or fallback to first search result
$parentId = env('NOTION_PARENT_PAGE_ID');
if (!$parentId && !empty($searchResult['data']['pages'])) {
    $parentId = $searchResult['data']['pages'][0]['id'];
}

if (!$parentId) {
    echo "No parent page found. Please set NOTION_PARENT_PAGE_ID in .env\n";
    exit(1);
}

echo "\nUsing parent page ID: {$parentId}\n";

// Create the testing documentation page
$content = <<<'MARKDOWN'
## 📊 Testing Overview

**Current Status:** 43 tests (up from 31)
**Test Coverage:** ~12%
**Success Rate:** 93%
**Last Updated:** July 14, 2025

## ✅ Test Suites Status

| Test Suite | Status | Tests Passing |
|------------|--------|---------------|
| DashboardMetricsServiceTest | ✅ Passing | 8/8 |
| SimpleTest | ✅ Passing | 2/2 |
| CriticalFixesTest | ⚠️ Partial | 4/6 |
| WebhookDeduplicationServiceTest | ✅ Passing | 11/11 |
| MockServicesTest | ✅ Passing | 5/5 |
| DatabaseConnectionTest | ✅ Passing | 2/2 |
| SensitiveDataMaskerTest | ⚠️ Partial | 8/9 |

## 🔧 Key Fixes Applied Today (July 14, 2025)

1. **Changed Call model relationship from BelongsTo to HasOne** - Resolved relationship issues in multi-tenant environment
2. **Added SQLite JSON query compatibility** - Tests now work with both SQLite and MySQL
3. **Fixed float comparisons with assertEqualsWithDelta** - Resolved precision issues in metrics calculations
4. **Resolved TenantScope issues** - Proper handling of global scope in test environment

## 🏗️ Test Infrastructure

- **PHPUnit Version:** 11.5.3
- **Test Database:** SQLite in-memory
- **Isolation:** RefreshDatabase trait
- **Framework:** Laravel 11.x with Pest/PHPUnit

## 🎯 Common Test Patterns

### Multi-tenant Testing with TenantScope
```php
// Disable TenantScope for global queries
$company = Company::withoutGlobalScope(TenantScope::class)->first();

// Set current company context
app()->instance('currentCompany', $company);

// Now queries will be scoped to this company
$appointments = Appointment::count();
```

### SQLite vs MySQL Compatibility
```php
// Use JSON_EXTRACT for both databases
$query = Call::query()
    ->whereRaw("JSON_EXTRACT(retell_data, '$.duration') > ?", [0]);

// Avoid MySQL-specific functions
// Bad: DATE_SUB(NOW(), INTERVAL 30 DAY)
// Good: Carbon::now()->subDays(30)
```

### Float Comparison Precision
```php
// Use assertEqualsWithDelta for float comparisons
$this->assertEqualsWithDelta(80.5, $result['trend'], 0.1);

// Not this:
$this->assertEquals(80.5, $result['trend']); // May fail due to precision
```

## 📈 Testing Roadmap

| Phase | Timeline | Target | Status |
|-------|----------|--------|--------|
| Phase 1 | Current | 40+ tests, ~12% coverage | ✅ Achieved |
| Phase 2 | Next Sprint | 100+ tests, 25% coverage | 🚧 Planned |
| Phase 3 | Q2 2025 | 300+ tests, 50% coverage | 📅 Future |
| Phase 4 | Q3 2025 | 500+ tests, 70% coverage | 📅 Future |

## ⚡ Quick Commands

```bash
# Run all working tests
./run-working-tests.sh

# Run specific test suite
php artisan test tests/Unit/Dashboard/DashboardMetricsServiceTest.php

# Run with code coverage
php artisan test --coverage

# Run tests in parallel (faster)
php artisan test --parallel

# Run only unit tests
php artisan test --testsuite=Unit

# Run tests and stop on first failure
php artisan test --stop-on-failure

# Run tests with detailed output
php artisan test --verbose
```

## 🔄 Continuous Integration

💡 **Pro Tip:** Tests run automatically on each push via GitHub Actions. Check the Actions tab for CI results.

## 📝 Next Steps

- [ ] Fix failing tests in CriticalFixesTest (2 failures)
- [ ] Fix nested data masking in SensitiveDataMaskerTest
- [ ] Add tests for critical business flows (booking, payment)
- [ ] Increase code coverage to 25%
- [ ] Set up code coverage reporting in CI
MARKDOWN;

$createResult = $notion->executeTool('create_page', [
    'parent_id' => $parentId,
    'title' => '🧪 Testing Documentation - AskProAI',
    'content' => $content
]);

if ($createResult['success']) {
    echo "\n✅ Successfully created Notion page!\n";
    echo "Page ID: " . $createResult['data']['page_id'] . "\n";
    if (isset($createResult['data']['url'])) {
        echo "URL: " . $createResult['data']['url'] . "\n";
    }
} else {
    echo "\n❌ Failed to create page: " . $createResult['error'] . "\n";
}