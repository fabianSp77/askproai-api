# Implementation Plan: Category & Date-Parsing Bug Fixes

**Author**: Claude Opus 4.5  
**Date**: 2026-01-08  
**Status**: Ready for Implementation

---

## Executive Summary

This plan addresses two bugs in the Service Gateway system:
1. **Bug 1**: Missing Hardware category keywords for Company 1658
2. **Bug 2**: RelativeTimeParser fails to strip multiple German prefixes (e.g., "seit circa")

---

## Bug 1: Hardware Category Keywords

### Problem Analysis

Company 1658 lacks a dedicated Hardware category with appropriate keywords. Current fallback behavior:
- Default category is "Network & Connectivity" (ID: 98)
- Keywords like "monitor", "display", "bildschirm", "drucker", "hardware", "einschalten", "startet nicht" are missing
- Input: "Monitor lässt sich nicht einschalten" → incorrectly categorized as Network & Connectivity

### Root Cause

The `ThomasIncidentCategoriesSeeder` creates categories for IT service desk scenarios but does NOT include a dedicated Hardware category. The migration `2025_12_31_190000_cleanup_fallback_categories.php` shows that hardware keywords were previously in "Allgemeine Anfrage" (ID 117) but were REMOVED during cleanup (see lines 96-106 in rollback showing what was deleted).

### Solution

Create a new Hardware category OR extend an existing category with hardware-specific keywords.

**Option A (Recommended)**: Create new "Hardware & Peripherals" category
- More ITIL-compliant
- Better reporting granularity
- Proper SLA assignment for hardware issues

**Option B**: Extend "General" category with hardware keywords
- Simpler, less DB changes
- Less granular reporting

### Implementation: Option A

#### Step 1: Database Changes (Tinker/Migration)

```php
// Option 1: Via Tinker (immediate fix for Company 1658)
php artisan tinker

// First, get the output config for infrastructure (same as other hardware-related categories)
$company = \App\Models\Company::find(1658);
$outputConfigId = \App\Models\ServiceCaseCategory::where('company_id', 1658)
    ->where('slug', 'network-connectivity')
    ->first()
    ?->output_configuration_id;

// Create parent category: "Hardware & Peripherals"
$parent = \App\Models\ServiceCaseCategory::create([
    'company_id' => 1658,
    'name' => 'Hardware & Peripherals',
    'slug' => 'hardware-peripherals',
    'parent_id' => null,
    'intent_keywords' => [
        'hardware',
        'gerät',
        'monitor',
        'bildschirm',
        'display',
        'drucker',
        'printer',
        'tastatur',
        'keyboard',
        'maus',
        'mouse',
        'laptop',
        'notebook',
        'pc',
        'computer',
        'rechner',
        'arbeitsplatz',
        'peripherie',
    ],
    'confidence_threshold' => 0.55,
    'default_case_type' => 'incident',
    'default_priority' => 'normal',
    'output_configuration_id' => $outputConfigId,
    'is_active' => true,
    'sort_order' => 15,
]);

// Create child category: "Power & Startup Issues"
\App\Models\ServiceCaseCategory::create([
    'company_id' => 1658,
    'name' => 'HW1: Gerät startet nicht / Stromprobleme',
    'slug' => 'hw1-geraet-startet-nicht',
    'parent_id' => $parent->id,
    'intent_keywords' => [
        'startet nicht',
        'geht nicht an',
        'einschalten',
        'ausschalten',
        'strom',
        'power',
        'hochfahren',
        'booten',
        'boot',
        'blinkt',
        'reagiert nicht',
        'tot',
        'kein bild',
        'schwarzer bildschirm',
        'bleibt dunkel',
        'keine reaktion',
        'lässt sich nicht einschalten',
    ],
    'confidence_threshold' => 0.70,
    'default_case_type' => 'incident',
    'default_priority' => 'high',
    'output_configuration_id' => $outputConfigId,
    'is_active' => true,
    'sort_order' => 151,
]);

// Create child category: "Display Issues"
\App\Models\ServiceCaseCategory::create([
    'company_id' => 1658,
    'name' => 'HW2: Monitor & Display Probleme',
    'slug' => 'hw2-monitor-display-probleme',
    'parent_id' => $parent->id,
    'intent_keywords' => [
        'monitor',
        'bildschirm',
        'display',
        'anzeige',
        'auflösung',
        'flackert',
        'flimmert',
        'streifen',
        'pixel',
        'kein signal',
        'hdmi',
        'displayport',
        'vga',
        'zweiter monitor',
        'externer bildschirm',
        'dual screen',
    ],
    'confidence_threshold' => 0.70,
    'default_case_type' => 'incident',
    'default_priority' => 'normal',
    'output_configuration_id' => $outputConfigId,
    'is_active' => true,
    'sort_order' => 152,
]);

// Create child category: "Printer Issues"
\App\Models\ServiceCaseCategory::create([
    'company_id' => 1658,
    'name' => 'HW3: Drucker & Peripheriegeräte',
    'slug' => 'hw3-drucker-peripherie',
    'parent_id' => $parent->id,
    'intent_keywords' => [
        'drucker',
        'drucken',
        'printer',
        'scanner',
        'scannen',
        'kopieren',
        'kopierer',
        'papierstau',
        'toner',
        'patrone',
        'tinte',
        'druckauftrag',
        'druckjob',
        'druckerwarteschlange',
        'netzwerkdrucker',
        'usb drucker',
    ],
    'confidence_threshold' => 0.70,
    'default_case_type' => 'incident',
    'default_priority' => 'normal',
    'output_configuration_id' => $outputConfigId,
    'is_active' => true,
    'sort_order' => 153,
]);

echo "Hardware categories created successfully for Company 1658\n";
```

#### Step 2: Seeder Update (For New Companies)

Add `createHardwareCategories()` method to `ThomasIncidentCategoriesSeeder.php`:

```php
/**
 * Create Hardware & Peripherals categories.
 *
 * Hierarchy:
 * - Hardware & Peripherals (parent)
 *   - HW1: Gerät startet nicht / Stromprobleme
 *   - HW2: Monitor & Display Probleme  
 *   - HW3: Drucker & Peripheriegeräte
 */
private function createHardwareCategories(Company $company, int $outputConfigId): void
{
    // Level 0: Parent category
    $parent = ServiceCaseCategory::create([
        'company_id' => $company->id,
        'name' => 'Hardware & Peripherals',
        'slug' => 'hardware-peripherals',
        'parent_id' => null,
        'intent_keywords' => [
            'hardware', 'gerät', 'monitor', 'bildschirm', 'display',
            'drucker', 'printer', 'tastatur', 'keyboard', 'maus',
            'mouse', 'laptop', 'notebook', 'pc', 'computer',
            'rechner', 'arbeitsplatz', 'peripherie',
        ],
        'confidence_threshold' => 0.55,
        'default_case_type' => ServiceCase::TYPE_INCIDENT,
        'default_priority' => ServiceCase::PRIORITY_NORMAL,
        'output_configuration_id' => $outputConfigId,
        'is_active' => true,
        'sort_order' => 15,
    ]);

    // Level 1: Power & Startup Issues
    ServiceCaseCategory::create([
        'company_id' => $company->id,
        'name' => 'HW1: Gerät startet nicht / Stromprobleme',
        'slug' => 'hw1-geraet-startet-nicht',
        'parent_id' => $parent->id,
        'intent_keywords' => [
            'startet nicht', 'geht nicht an', 'einschalten', 'ausschalten',
            'strom', 'power', 'hochfahren', 'booten', 'boot',
            'blinkt', 'reagiert nicht', 'tot', 'kein bild',
            'schwarzer bildschirm', 'bleibt dunkel', 'keine reaktion',
            'lässt sich nicht einschalten',
        ],
        'confidence_threshold' => 0.70,
        'default_case_type' => ServiceCase::TYPE_INCIDENT,
        'default_priority' => ServiceCase::PRIORITY_HIGH,
        'output_configuration_id' => $outputConfigId,
        'is_active' => true,
        'sort_order' => 151,
    ]);

    // Level 1: Display Issues
    ServiceCaseCategory::create([
        'company_id' => $company->id,
        'name' => 'HW2: Monitor & Display Probleme',
        'slug' => 'hw2-monitor-display-probleme',
        'parent_id' => $parent->id,
        'intent_keywords' => [
            'monitor', 'bildschirm', 'display', 'anzeige', 'auflösung',
            'flackert', 'flimmert', 'streifen', 'pixel', 'kein signal',
            'hdmi', 'displayport', 'vga', 'zweiter monitor',
            'externer bildschirm', 'dual screen',
        ],
        'confidence_threshold' => 0.70,
        'default_case_type' => ServiceCase::TYPE_INCIDENT,
        'default_priority' => ServiceCase::PRIORITY_NORMAL,
        'output_configuration_id' => $outputConfigId,
        'is_active' => true,
        'sort_order' => 152,
    ]);

    // Level 1: Printer Issues
    ServiceCaseCategory::create([
        'company_id' => $company->id,
        'name' => 'HW3: Drucker & Peripheriegeräte',
        'slug' => 'hw3-drucker-peripherie',
        'parent_id' => $parent->id,
        'intent_keywords' => [
            'drucker', 'drucken', 'printer', 'scanner', 'scannen',
            'kopieren', 'kopierer', 'papierstau', 'toner', 'patrone',
            'tinte', 'druckauftrag', 'druckjob', 'druckerwarteschlange',
            'netzwerkdrucker', 'usb drucker',
        ],
        'confidence_threshold' => 0.70,
        'default_case_type' => ServiceCase::TYPE_INCIDENT,
        'default_priority' => ServiceCase::PRIORITY_NORMAL,
        'output_configuration_id' => $outputConfigId,
        'is_active' => true,
        'sort_order' => 153,
    ]);
}
```

Update `seedForCompany()` to call the new method:

```php
private function seedForCompany(Company $company): void
{
    $outputConfigs = $this->createOutputConfigurations($company);

    $this->createNetworkCategories($company, $outputConfigs['infrastructure']);
    $this->createServerCategories($company, $outputConfigs['infrastructure']);
    $this->createHardwareCategories($company, $outputConfigs['infrastructure']); // NEW
    $this->createM365Categories($company, $outputConfigs['application']);
    $this->createSecurityCategories($company, $outputConfigs['security']);
    $this->createUCCategories($company, $outputConfigs['application']);
    $this->createGeneralCategory($company, $outputConfigs['general']);

    Log::info("Successfully seeded all categories for company: {$company->name}");
}
```

---

## Bug 2: RelativeTimeParser Prefix Stripping

### Problem Analysis

The `extractMinutes()` method in `/var/www/api-gateway/app/Services/RelativeTimeParser.php` (line 392) uses a single `preg_replace` call:

```php
$text = preg_replace('/^(seit|vor|ca\.?|circa|etwa|ungefähr)\s+/u', '', $text);
```

This ONLY removes ONE prefix. German speakers commonly combine prefixes:
- "seit circa fünfzehn Minuten" → after replace: "circa fünfzehn Minuten"
- The pattern `^(\d+|[a-zäöüß]+)\s*minut` then FAILS to match "circa fünfzehn minuten"

### Root Cause

Single-pass regex replacement doesn't handle prefix combinations.

### Solution

Replace single `preg_replace` with iterative stripping using a `while` loop.

### Implementation

#### Code Change in `RelativeTimeParser.php`

**File**: `/var/www/api-gateway/app/Services/RelativeTimeParser.php`

**Lines 387-393** - Replace:

```php
private function extractMinutes(string $text): ?float
{
    $text = mb_strtolower(trim($text));

    // Remove common prefixes
    $text = preg_replace('/^(seit|vor|ca\.?|circa|etwa|ungefähr)\s+/u', '', $text);
```

**With**:

```php
private function extractMinutes(string $text): ?float
{
    $text = mb_strtolower(trim($text));

    // Remove common prefixes ITERATIVELY (handles "seit circa", "vor ungefähr", etc.)
    // Pattern matches one prefix at a time, loop until no more prefixes remain
    $prefixPattern = '/^(seit|vor|ca\.?|circa|etwa|ungefähr|so)\s+/u';
    $maxIterations = 5; // Safety limit to prevent infinite loops
    $iterations = 0;
    
    while ($iterations < $maxIterations && preg_match($prefixPattern, $text)) {
        $text = preg_replace($prefixPattern, '', $text);
        $iterations++;
    }
```

#### Same Fix for `parseAbsoluteDateTime()` Method

**Lines 236-241** - Replace:

```php
private function parseAbsoluteDateTime(string $text, Carbon $referenceTime): ?Carbon
{
    $text = mb_strtolower(trim($text));

    // Remove common prefixes
    $text = preg_replace('/^(seit|vor|ab|um|ca\.?|circa|etwa|ungefähr)\s*/u', '', $text);
```

**With**:

```php
private function parseAbsoluteDateTime(string $text, Carbon $referenceTime): ?Carbon
{
    $text = mb_strtolower(trim($text));

    // Remove common prefixes ITERATIVELY (handles "seit circa", "vor ungefähr", etc.)
    $prefixPattern = '/^(seit|vor|ab|um|ca\.?|circa|etwa|ungefähr|so)\s*/u';
    $maxIterations = 5; // Safety limit
    $iterations = 0;
    
    while ($iterations < $maxIterations && preg_match($prefixPattern, $text)) {
        $text = preg_replace($prefixPattern, '', $text);
        $iterations++;
    }
```

---

## Test Cases

### Test File: `tests/Unit/Services/RelativeTimeParserPrefixTest.php`

```php
<?php

namespace Tests\Unit\Services;

use App\Services\RelativeTimeParser;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RelativeTimeParser prefix stripping fix.
 * 
 * Bug: Single-pass preg_replace failed to strip multiple prefixes
 * like "seit circa", "vor ungefähr", etc.
 */
class RelativeTimeParserPrefixTest extends TestCase
{
    private RelativeTimeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RelativeTimeParser();
        Carbon::setTestNow('2026-01-08 14:21:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset
        parent::tearDown();
    }

    /**
     * Test single prefix (baseline - should already work)
     */
    public function test_single_prefix_seit_fuenfzehn_minuten(): void
    {
        $result = $this->parser->parse('seit fünfzehn Minuten');
        
        $this->assertNotNull($result['absolute']);
        $this->assertStringContainsString('14:06', $result['formatted']);
    }

    /**
     * Test double prefix "seit circa" - THE ACTUAL BUG
     */
    public function test_double_prefix_seit_circa_fuenfzehn_minuten(): void
    {
        $result = $this->parser->parse('seit circa fünfzehn Minuten');
        
        $this->assertNotNull($result['absolute'], 
            'Should parse "seit circa fünfzehn Minuten" - double prefix must be stripped');
        
        // 14:21 - 15 minutes = 14:06
        $expected = Carbon::parse('2026-01-08 14:06:00');
        $actual = Carbon::parse($result['absolute']);
        
        $this->assertEquals($expected->format('H:i'), $actual->format('H:i'));
    }

    /**
     * Test double prefix "vor ungefähr"
     */
    public function test_double_prefix_vor_ungefaehr_zehn_minuten(): void
    {
        $result = $this->parser->parse('vor ungefähr zehn Minuten');
        
        $this->assertNotNull($result['absolute'],
            'Should parse "vor ungefähr zehn Minuten" - double prefix must be stripped');
        
        // 14:21 - 10 minutes = 14:11
        $expected = Carbon::parse('2026-01-08 14:11:00');
        $actual = Carbon::parse($result['absolute']);
        
        $this->assertEquals($expected->format('H:i'), $actual->format('H:i'));
    }

    /**
     * Test triple prefix (edge case)
     */
    public function test_triple_prefix_seit_so_circa_zwanzig_minuten(): void
    {
        $result = $this->parser->parse('seit so circa zwanzig Minuten');
        
        $this->assertNotNull($result['absolute'],
            'Should parse triple prefix combination');
        
        // 14:21 - 20 minutes = 14:01
        $expected = Carbon::parse('2026-01-08 14:01:00');
        $actual = Carbon::parse($result['absolute']);
        
        $this->assertEquals($expected->format('H:i'), $actual->format('H:i'));
    }

    /**
     * Test double prefix with "ca."
     */
    public function test_double_prefix_seit_ca_fuenf_minuten(): void
    {
        $result = $this->parser->parse('seit ca. fünf Minuten');
        
        $this->assertNotNull($result['absolute']);
        
        // 14:21 - 5 minutes = 14:16
        $expected = Carbon::parse('2026-01-08 14:16:00');
        $actual = Carbon::parse($result['absolute']);
        
        $this->assertEquals($expected->format('H:i'), $actual->format('H:i'));
    }

    /**
     * Test absolute time with double prefix
     */
    public function test_double_prefix_absolute_time_seit_etwa_heute(): void
    {
        $result = $this->parser->parse('seit etwa heute mittag');
        
        $this->assertNotNull($result['absolute'],
            'Should parse absolute time with double prefix');
    }

    /**
     * Test double prefix with hours
     */
    public function test_double_prefix_vor_circa_einer_stunde(): void
    {
        $result = $this->parser->parse('vor circa einer Stunde');
        
        $this->assertNotNull($result['absolute'],
            'Should parse "vor circa einer Stunde"');
        
        // 14:21 - 60 minutes = 13:21
        $expected = Carbon::parse('2026-01-08 13:21:00');
        $actual = Carbon::parse($result['absolute']);
        
        $this->assertEquals($expected->format('H:i'), $actual->format('H:i'));
    }

    /**
     * Test that normal text without prefixes still works
     */
    public function test_no_prefix_still_works(): void
    {
        $result = $this->parser->parse('fünfzehn Minuten');
        
        $this->assertNotNull($result['absolute']);
    }

    /**
     * Test maximum iterations safety (shouldn't infinite loop)
     */
    public function test_max_iterations_safety(): void
    {
        // This pathological input shouldn't cause infinite loop
        $result = $this->parser->parse('seit seit seit seit seit seit fünfzehn Minuten');
        
        // Should complete without hanging (test timeout would catch infinite loop)
        $this->assertTrue(true, 'Parser completed without infinite loop');
    }
}
```

### Test for Category Classification

**File**: `tests/Unit/Services/ServiceDeskHandlerCategoryTest.php`

```php
<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\ServiceDeskHandler;
use App\Models\Company;
use App\Models\ServiceCaseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ReflectionClass;

/**
 * Test category classification in ServiceDeskHandler
 */
class ServiceDeskHandlerCategoryTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private ServiceDeskHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create(['id' => 1658]);
        $this->handler = app(ServiceDeskHandler::class);
    }

    /**
     * Helper to call private classifyCategory method
     */
    private function classifyCategory(string $description): ?int
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('classifyCategory');
        $method->setAccessible(true);
        
        return $method->invoke($this->handler, $description, $this->company->id);
    }

    /**
     * Test: "Monitor lässt sich nicht einschalten" should match Hardware category
     */
    public function test_monitor_einschalten_matches_hardware(): void
    {
        // Create Hardware category with expected keywords
        $hardware = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Hardware & Peripherals',
            'slug' => 'hardware-peripherals',
            'intent_keywords' => [
                'monitor', 'bildschirm', 'hardware', 'einschalten',
                'startet nicht', 'lässt sich nicht einschalten',
            ],
            'confidence_threshold' => 0.55,
            'is_active' => true,
        ]);

        // Create Network category (the wrong fallback)
        $network = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Network & Connectivity',
            'slug' => 'network-connectivity',
            'is_default' => true,
            'intent_keywords' => ['netzwerk', 'internet', 'verbindung'],
            'confidence_threshold' => 0.55,
            'is_active' => true,
        ]);

        $result = $this->classifyCategory('Monitor lässt sich nicht einschalten');

        $this->assertEquals($hardware->id, $result,
            'Should classify monitor issue to Hardware, not Network');
    }

    /**
     * Test: "Drucker druckt nicht" should match Hardware category
     */
    public function test_drucker_druckt_nicht_matches_hardware(): void
    {
        $hardware = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Hardware & Peripherals',
            'slug' => 'hardware-peripherals',
            'intent_keywords' => ['drucker', 'drucken', 'printer'],
            'confidence_threshold' => 0.55,
            'is_active' => true,
        ]);

        $result = $this->classifyCategory('Drucker druckt nicht');

        $this->assertEquals($hardware->id, $result);
    }

    /**
     * Test: "Bildschirm bleibt schwarz" should match Hardware category
     */
    public function test_bildschirm_schwarz_matches_hardware(): void
    {
        $hardware = ServiceCaseCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Hardware & Peripherals',
            'slug' => 'hardware-peripherals',
            'intent_keywords' => [
                'bildschirm', 'monitor', 'schwarzer bildschirm',
                'bleibt dunkel', 'kein bild',
            ],
            'confidence_threshold' => 0.55,
            'is_active' => true,
        ]);

        $result = $this->classifyCategory('Bildschirm bleibt schwarz');

        $this->assertEquals($hardware->id, $result);
    }
}
```

---

## Verification Commands

### After Category Fix

```bash
# Verify categories created for Company 1658
php artisan tinker --execute="
\$cats = \App\Models\ServiceCaseCategory::where('company_id', 1658)
    ->where('slug', 'LIKE', 'hardware%')
    ->orWhere('slug', 'LIKE', 'hw%')
    ->get(['id', 'name', 'slug', 'intent_keywords']);
echo 'Hardware categories for Company 1658:' . PHP_EOL;
foreach (\$cats as \$cat) {
    echo '- ' . \$cat->name . ' (ID: ' . \$cat->id . ')' . PHP_EOL;
}
"

# Test classification
php artisan tinker --execute="
\$handler = app(\App\Http\Controllers\ServiceDeskHandler::class);
\$ref = new ReflectionClass(\$handler);
\$method = \$ref->getMethod('classifyCategory');
\$method->setAccessible(true);

\$tests = [
    'Monitor lässt sich nicht einschalten',
    'Drucker druckt nicht',
    'Bildschirm bleibt schwarz',
    'Laptop startet nicht',
];

foreach (\$tests as \$input) {
    \$catId = \$method->invoke(\$handler, \$input, 1658);
    \$cat = \App\Models\ServiceCaseCategory::find(\$catId);
    echo \"'\$input' -> \" . (\$cat ? \$cat->name : 'NULL') . PHP_EOL;
}
"
```

### After Parser Fix

```bash
# Run unit tests
php artisan test --filter=RelativeTimeParserPrefixTest

# Manual verification via tinker
php artisan tinker --execute="
\$parser = new \App\Services\RelativeTimeParser();

\$tests = [
    'seit fünfzehn Minuten',
    'seit circa fünfzehn Minuten',
    'vor ungefähr zehn Minuten',
    'seit so circa zwanzig Minuten',
];

foreach (\$tests as \$input) {
    \$result = \$parser->parse(\$input);
    echo \"'\$input':\" . PHP_EOL;
    echo '  absolute: ' . (\$result['absolute'] ?? 'NULL') . PHP_EOL;
    echo '  formatted: ' . \$result['formatted'] . PHP_EOL . PHP_EOL;
}
"
```

---

## Risk Analysis

### Bug 1: Category Keywords

| Risk | Probability | Impact | Mitigation |
|------|------------|--------|------------|
| Keyword conflicts with existing categories | Low | Medium | Test classification before/after; use high-specificity keywords |
| Output config not found | Low | Low | Fallback to network config which uses same infrastructure output |
| Breaking existing categorization | Low | Medium | Run classification tests on sample data first |
| Seeder creates duplicates on re-run | Low | Low | Seeder uses `create()` not `firstOrCreate()` - only run once per company |

**Rollback**: Delete the 4 new categories via Tinker if issues arise.

### Bug 2: RelativeTimeParser

| Risk | Probability | Impact | Mitigation |
|------|------------|--------|------------|
| Infinite loop on pathological input | Very Low | High | `maxIterations = 5` safety limit prevents this |
| Breaking existing parsing | Very Low | High | Unit tests cover all existing patterns |
| Performance degradation | Very Low | Low | Max 5 iterations adds negligible overhead |
| Edge case: legitimate text starting with prefix word | Low | Low | Pattern requires whitespace after prefix |

**Rollback**: Revert to single `preg_replace` if issues arise. The code change is minimal and isolated.

---

## Implementation Sequence

1. **Phase 1: Parser Fix** (Lower risk, quick win)
   - Edit `RelativeTimeParser.php` (2 locations)
   - Create `RelativeTimeParserPrefixTest.php`
   - Run tests: `php artisan test --filter=RelativeTimeParser`

2. **Phase 2: Category Fix** (Requires DB changes)
   - Run Tinker commands for Company 1658
   - Update `ThomasIncidentCategoriesSeeder.php`
   - Create `ServiceDeskHandlerCategoryTest.php`
   - Verify via classification tests

3. **Phase 3: Verification**
   - Manual testing with sample inputs
   - Monitor logs for categorization issues
   - Check ServiceCase assignments in Filament admin

---

## Critical Files for Implementation

| File | Purpose |
|------|---------|
| `/var/www/api-gateway/app/Services/RelativeTimeParser.php` | Fix prefix stripping logic (lines 241, 392) |
| `/var/www/api-gateway/database/seeders/ThomasIncidentCategoriesSeeder.php` | Add `createHardwareCategories()` method |
| `/var/www/api-gateway/app/Http/Controllers/ServiceDeskHandler.php` | Reference for category classification logic |
| `/var/www/api-gateway/app/Models/ServiceCaseCategory.php` | Model structure reference |
| `/var/www/api-gateway/tests/Unit/Services/DateTimeParserFixTest.php` | Test pattern to follow |
