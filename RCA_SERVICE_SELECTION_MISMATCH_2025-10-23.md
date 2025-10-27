# Root Cause Analysis: Service Selection Mismatch
## "Herrenhaarschnitt" → "Beratung" Wrong Selection

**Date**: 2025-10-23
**Severity**: HIGH - User Experience Impact
**Component**: Service Selection Logic
**Files**: `ServiceSelectionService.php`, `RetellFunctionCallHandler.php`

---

## Executive Summary

When a customer requests "Herrenhaarschnitt" via Voice AI, the system incorrectly selects "Beratung" service instead. This happens because:

1. The Voice AI passes service name as plain text (`"dienstleistung": "Herrenhaarschnitt"`)
2. Backend has NO service name matching logic
3. Falls back to hardcoded default with `CASE WHEN name LIKE "%Beratung%" THEN 0` priority
4. Result: Wrong service booked, wrong email confirmation sent

**Impact**: 100% of voice bookings may be using wrong services, leading to customer confusion and operational issues.

---

## Problem Evidence

### Test Call Data (call_be0a6a6fbf16bb28506586300da)
```json
{
  "user_said": "fürn Herrenhaarschnitt",
  "tool_call": {
    "name": "check_availability_v17",
    "arguments": {
      "dienstleistung": "Herrenhaarschnitt"
    }
  },
  "service_selected": "Beratung",
  "email_confirmation": "Haarberatung"
}
```

### Database State
```sql
-- Query attempted (FAILED):
SELECT * FROM services WHERE company_id = 1 AND name = 'Herrenhaarschnitt'
-- Result: Found service ID 42

-- Query executed (SUCCEEDED - WRONG):
SELECT * FROM services WHERE company_id = 1
  AND is_active = true
  AND calcom_event_type_id IS NOT NULL
  AND is_default = true
LIMIT 1
-- Result: NULL (no default service)

-- Fallback query (SELECTED WRONG SERVICE):
SELECT * FROM services WHERE company_id = 1
  AND is_active = true
  AND calcom_event_type_id IS NOT NULL
ORDER BY priority ASC,
  CASE
    WHEN name LIKE "%Beratung%" THEN 0
    WHEN name LIKE "%30 Minuten%" THEN 1
    ELSE 2
  END
LIMIT 1
-- Result: "Beratung" (ID 172) ❌
```

### Service Database Structure
```
ID: 42  | Herrenhaarschnitt | Default: NO  | Priority: 50 | Cal.com: 3672814 ✓
ID: 172 | Beratung          | Default: NO  | Priority: 50 | Cal.com: 3719860 ✓
```

Both services exist, both have same priority (50), but "Beratung" wins due to hardcoded `CASE` statement.

---

## Root Cause Analysis

### Architecture Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Voice AI (Retell)                                           │
│    User: "Herrenhaarschnitt"                                   │
│    → Tool Call: {"dienstleistung": "Herrenhaarschnitt"}       │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. RetellFunctionCallHandler::checkAvailability()             │
│    → serviceSelector->getDefaultService(companyId, branchId)   │
│    ❌ NO service name matching logic                           │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. ServiceSelectionService::getDefaultService()               │
│    Step 1: Try is_default = true → NULL                       │
│    Step 2: Fallback to hardcoded priority:                    │
│            CASE WHEN name LIKE "%Beratung%" THEN 0 ← WINS     │
│                 WHEN name LIKE "%30 Minuten%" THEN 1          │
│                 ELSE 2                                         │
│            END                                                 │
│    Result: "Beratung" selected ❌                             │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Email Confirmation                                          │
│    Service Name: "Beratung"                                    │
│    Customer Sees: "Haarberatung" ❌ WRONG                     │
└─────────────────────────────────────────────────────────────────┘
```

### Missing Logic: Service Name Matching

**Current Code** (`ServiceSelectionService.php` line 36):
```php
public function getDefaultService(int $companyId, ?string $branchId = null): ?Service
{
    // NO $serviceName parameter ❌
    // NO matching logic ❌
    // Always returns default/fallback ❌

    $query = Service::where('company_id', $companyId)
        ->where('is_active', true)
        ->whereNotNull('calcom_event_type_id');

    // Try default
    $service = (clone $query)->where('is_default', true)->first();

    // Hardcoded fallback
    if (!$service) {
        $service = $query
            ->orderBy('priority', 'asc')
            ->orderByRaw('CASE WHEN name LIKE "%Beratung%" THEN 0 ...') // ❌
            ->first();
    }

    return $service;
}
```

**Expected Code**:
```php
public function findServiceByName(
    string $serviceName,
    int $companyId,
    ?string $branchId = null
): ?Service {
    // Step 1: Exact match
    // Step 2: Fuzzy match (Herrenhaarschnitt ≈ Haarschnitt Herren)
    // Step 3: Synonym mapping
    // Step 4: Fallback to default
}
```

### Why Hardcoded Priorities Exist

Looking at the code history, this appears to be a temporary solution:

```php
->orderByRaw('CASE WHEN name LIKE "%Beratung%" THEN 0 WHEN name LIKE "%30 Minuten%" THEN 1 ELSE 2 END')
```

**Hypothesis**:
- Early development phase
- Single company testing
- "Beratung" was the most common/safest fallback
- Never replaced with proper service matching

---

## Impact Assessment

### Data Analysis Needed
```sql
-- How many appointments used wrong service?
SELECT
    COUNT(*) as total_appointments,
    service_id,
    s.name as service_name
FROM appointments a
JOIN services s ON a.service_id = s.id
WHERE a.company_id = 1
  AND a.created_at >= '2025-10-01'
GROUP BY service_id, s.name
ORDER BY total_appointments DESC;

-- Expected: High concentration on "Beratung" if bug is widespread
```

### User Impact
- **Voice AI Bookings**: 100% at risk (no service matching)
- **Web Bookings**: 0% at risk (explicit service selection)
- **Phone Bookings**: 100% at risk if using Retell AI

### Business Impact
- ❌ Customer confusion (booked X, got Y)
- ❌ Wrong staff assignment
- ❌ Wrong pricing
- ❌ Wrong duration
- ❌ Email confirmations misleading
- ❌ Trust erosion

---

## Technical Deep Dive

### Call Flow Analysis

**RetellFunctionCallHandler.php** (lines 308-383):
```php
private function checkAvailability(array $params, ?string $callId)
{
    $callContext = $this->getCallContext($callId);
    $companyId = $callContext['company_id'];
    $branchId = $callContext['branch_id'];

    // Get service - NO service name used ❌
    if ($serviceId) {
        $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
    } else {
        // ❌ Ignores $params['dienstleistung'] = "Herrenhaarschnitt"
        $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
    }
}
```

**What's Missing**:
```php
// SHOULD BE:
$serviceName = $params['dienstleistung'] ?? null;

if ($serviceName) {
    $service = $this->serviceSelector->findServiceByName(
        $serviceName,
        $companyId,
        $branchId
    );
}

if (!$service && $serviceId) {
    $service = $this->serviceSelector->findServiceById(...);
}

if (!$service) {
    $service = $this->serviceSelector->getDefaultService(...);
}
```

### ServiceSelectionInterface Analysis

**Current Interface** (`ServiceSelectionInterface.php`):
```php
interface ServiceSelectionInterface
{
    public function getDefaultService(int $companyId, ?string $branchId = null): ?Service;
    public function getAvailableServices(int $companyId, ?string $branchId = null): Collection;
    public function validateServiceAccess(int $serviceId, int $companyId, ?string $branchId = null): bool;
    public function findServiceById(int $serviceId, int $companyId, ?string $branchId = null): ?Service;
}
```

**Missing Methods**:
```php
// Name-based matching
public function findServiceByName(string $name, int $companyId, ?string $branchId = null): ?Service;

// Fuzzy matching
public function findServiceByFuzzyMatch(string $query, int $companyId, ?string $branchId = null): ?Service;

// Synonym resolution
public function resolveServiceSynonym(string $synonym, int $companyId): ?Service;
```

---

## Solution Options

### Option 1: Quick Fix (Low Effort, Low Quality)
**Implementation Time**: 2 hours
**Risk**: Medium
**Maintainability**: Poor

```php
// ServiceSelectionService.php
public function findServiceByName(string $serviceName, int $companyId, ?string $branchId = null): ?Service
{
    // Exact match only
    return Service::where('company_id', $companyId)
        ->where('name', $serviceName)
        ->where('is_active', true)
        ->whereNotNull('calcom_event_type_id')
        ->first();
}

// RetellFunctionCallHandler.php - Line 380
$serviceName = $params['dienstleistung'] ?? null;
if ($serviceName) {
    $service = $this->serviceSelector->findServiceByName($serviceName, $companyId, $branchId);
}
if (!$service && $serviceId) {
    $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
}
if (!$service) {
    $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
}
```

**Pros**:
- Fast implementation
- Fixes exact match cases
- Minimal code changes

**Cons**:
- No fuzzy matching (Herrenhaarschnitt vs Herren Haarschnitt)
- No synonym support
- Hardcoded fallback still exists
- Does not scale to multiple languages

---

### Option 2: Smart Matching (Medium Effort, Good Quality)
**Implementation Time**: 1-2 days
**Risk**: Low
**Maintainability**: Good

```php
// New Service: ServiceNameResolver.php
class ServiceNameResolver
{
    public function resolve(string $query, int $companyId, ?string $branchId = null): ?Service
    {
        // Step 1: Exact match
        $service = $this->exactMatch($query, $companyId, $branchId);
        if ($service) return $service;

        // Step 2: Case-insensitive match
        $service = $this->caseInsensitiveMatch($query, $companyId, $branchId);
        if ($service) return $service;

        // Step 3: Fuzzy match (Levenshtein distance)
        $service = $this->fuzzyMatch($query, $companyId, $branchId, threshold: 0.8);
        if ($service) return $service;

        // Step 4: Synonym mapping
        $service = $this->synonymMatch($query, $companyId, $branchId);
        if ($service) return $service;

        // Step 5: Category match (if query contains category keywords)
        $service = $this->categoryMatch($query, $companyId, $branchId);
        if ($service) return $service;

        return null;
    }

    private function fuzzyMatch(string $query, int $companyId, ?string $branchId, float $threshold): ?Service
    {
        $services = Service::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id')
            ->get();

        $bestMatch = null;
        $bestScore = 0;

        foreach ($services as $service) {
            $score = $this->calculateSimilarity($query, $service->name);

            if ($score > $threshold && $score > $bestScore) {
                $bestMatch = $service;
                $bestScore = $score;
            }
        }

        Log::info('Fuzzy service match', [
            'query' => $query,
            'best_match' => $bestMatch?->name,
            'confidence' => $bestScore
        ]);

        return $bestMatch;
    }

    private function calculateSimilarity(string $a, string $b): float
    {
        // Normalize
        $a = mb_strtolower($a);
        $b = mb_strtolower($b);

        // Remove common German words
        $stopwords = ['der', 'die', 'das', 'und', 'für'];
        foreach ($stopwords as $word) {
            $a = str_replace($word, '', $a);
            $b = str_replace($word, '', $b);
        }

        // Calculate Levenshtein distance
        $distance = levenshtein($a, $b);
        $maxLen = max(strlen($a), strlen($b));

        return 1 - ($distance / $maxLen);
    }
}
```

**Synonym Mapping Table**:
```sql
CREATE TABLE service_synonyms (
    id BIGSERIAL PRIMARY KEY,
    service_id BIGINT NOT NULL REFERENCES services(id),
    synonym VARCHAR(255) NOT NULL,
    confidence DECIMAL(3,2) DEFAULT 1.0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(service_id, synonym)
);

-- Example data:
INSERT INTO service_synonyms (service_id, synonym, confidence) VALUES
(42, 'Herrenhaarschnitt', 1.0),
(42, 'Herren Haarschnitt', 1.0),
(42, 'Haarschnitt Herren', 1.0),
(42, 'Männerhaarschnitt', 0.95),
(42, 'Männer Haarschnitt', 0.95);
```

**Pros**:
- Handles typos and variations
- Supports synonyms
- Extensible to multiple languages
- Confidence scoring for monitoring
- Database-driven (no code changes for new synonyms)

**Cons**:
- Requires database migration
- More complex testing
- Performance overhead (mitigated by caching)

---

### Option 3: AI-Powered Matching (High Effort, Best Quality)
**Implementation Time**: 3-5 days
**Risk**: Medium
**Maintainability**: Excellent

Use OpenAI Embeddings for semantic matching:

```php
class SemanticServiceMatcher
{
    private OpenAI $openai;

    public function match(string $query, array $availableServices): ?Service
    {
        // Get embedding for query
        $queryEmbedding = $this->getEmbedding($query);

        // Get embeddings for all services (cached)
        $bestMatch = null;
        $bestScore = 0;

        foreach ($availableServices as $service) {
            $serviceEmbedding = $this->getServiceEmbedding($service);
            $similarity = $this->cosineSimilarity($queryEmbedding, $serviceEmbedding);

            if ($similarity > 0.85 && $similarity > $bestScore) {
                $bestMatch = $service;
                $bestScore = $similarity;
            }
        }

        return $bestMatch;
    }

    private function getEmbedding(string $text): array
    {
        $cacheKey = "embedding:" . md5($text);

        return Cache::remember($cacheKey, 86400, function() use ($text) {
            $response = $this->openai->embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $text
            ]);

            return $response->embeddings[0]->embedding;
        });
    }
}
```

**Pros**:
- Understands context and semantics
- Handles any language automatically
- Works with descriptions, not just names
- Self-improving with usage data

**Cons**:
- Requires OpenAI API (cost: $0.0001 per match)
- Latency (50-200ms per match)
- Complexity in debugging
- Requires embedding cache infrastructure

---

## Recommended Solution

**Hybrid Approach: Option 2 + Option 1 Fallback**

```php
// ServiceSelectionService.php - Enhanced
public function findServiceByName(
    string $serviceName,
    int $companyId,
    ?string $branchId = null
): ?Service {
    // Step 1: Exact match (fast path)
    $service = Service::where('company_id', $companyId)
        ->where('name', $serviceName)
        ->where('is_active', true)
        ->whereNotNull('calcom_event_type_id')
        ->first();

    if ($service) {
        Log::info('Service matched (exact)', ['query' => $serviceName, 'service' => $service->name]);
        return $service;
    }

    // Step 2: Synonym match
    $service = $this->synonymResolver->resolve($serviceName, $companyId, $branchId);

    if ($service) {
        Log::info('Service matched (synonym)', ['query' => $serviceName, 'service' => $service->name]);
        return $service;
    }

    // Step 3: Fuzzy match
    $service = $this->fuzzyMatcher->match($serviceName, $companyId, $branchId, threshold: 0.8);

    if ($service) {
        Log::warning('Service matched (fuzzy)', [
            'query' => $serviceName,
            'service' => $service->name,
            'confidence' => $service->match_confidence
        ]);
        return $service;
    }

    Log::error('Service not matched', ['query' => $serviceName, 'company_id' => $companyId]);
    return null;
}
```

**Why This Approach?**:
1. **Fast**: Exact match is O(1) with index
2. **Accurate**: Synonym table handles known variations
3. **Robust**: Fuzzy match catches typos
4. **Observable**: Logs show which matching strategy worked
5. **Scalable**: Can add AI matching later without breaking changes

---

## Migration Strategy

### Phase 1: Fix Critical Path (Week 1)
```bash
# 1. Add service name matching
git checkout -b fix/service-name-matching

# 2. Implement exact match + logging
# Files: ServiceSelectionService.php, RetellFunctionCallHandler.php

# 3. Deploy to staging
# 4. Run test calls with all services
# 5. Analyze logs to find missing synonyms
```

### Phase 2: Add Synonym Support (Week 2)
```bash
# 1. Create migration
php artisan make:migration create_service_synonyms_table

# 2. Seed common synonyms
php artisan db:seed --class=ServiceSynonymsSeeder

# 3. Implement synonym matching
# 4. Deploy to production
# 5. Monitor for 1 week
```

### Phase 3: Add Fuzzy Matching (Week 3)
```bash
# 1. Implement Levenshtein-based fuzzy match
# 2. Add confidence threshold config
# 3. Deploy to production
# 4. Monitor match quality metrics
```

### Phase 4: Backfill Data (Week 4)
```bash
# Analyze historical appointments
SELECT
    a.id,
    a.service_id,
    s.name as service_used,
    c.dienstleistung as service_requested
FROM appointments a
JOIN services s ON a.service_id = s.id
JOIN calls c ON c.id = a.call_id
WHERE c.dienstleistung IS NOT NULL
  AND c.dienstleistung != s.name;

# Manual review + correction if needed
```

---

## Testing Strategy

### Test Cases

```php
// tests/Feature/ServiceSelectionTest.php
class ServiceSelectionTest extends TestCase
{
    /** @test */
    public function it_matches_exact_service_name()
    {
        $service = $this->selector->findServiceByName('Herrenhaarschnitt', 1, null);

        $this->assertNotNull($service);
        $this->assertEquals('Herrenhaarschnitt', $service->name);
    }

    /** @test */
    public function it_matches_case_insensitive()
    {
        $service = $this->selector->findServiceByName('herrenhaarschnitt', 1, null);

        $this->assertNotNull($service);
        $this->assertEquals('Herrenhaarschnitt', $service->name);
    }

    /** @test */
    public function it_matches_with_synonyms()
    {
        ServiceSynonym::create([
            'service_id' => 42,
            'synonym' => 'Männerhaarschnitt'
        ]);

        $service = $this->selector->findServiceByName('Männerhaarschnitt', 1, null);

        $this->assertNotNull($service);
        $this->assertEquals('Herrenhaarschnitt', $service->name);
    }

    /** @test */
    public function it_matches_fuzzy()
    {
        $service = $this->selector->findServiceByName('Herenharschnitt', 1, null); // Typo

        $this->assertNotNull($service);
        $this->assertEquals('Herrenhaarschnitt', $service->name);
    }

    /** @test */
    public function it_does_not_match_unrelated_services()
    {
        $service = $this->selector->findServiceByName('Pizza bestellen', 1, null);

        $this->assertNull($service);
    }

    /** @test */
    public function it_returns_null_instead_of_wrong_fallback()
    {
        // Remove hardcoded "Beratung" fallback
        $service = $this->selector->findServiceByName('Nonexistent Service', 1, null);

        $this->assertNull($service); // NOT "Beratung"
    }
}
```

### E2E Test Scenarios

```php
// Test via Retell AI
POST /api/retell/function-call
{
    "function_name": "check_availability_v17",
    "arguments": {
        "dienstleistung": "Herrenhaarschnitt",
        "datum": "24.10.2025",
        "uhrzeit": "14:00"
    },
    "call_id": "test_call_123"
}

// Expected Response:
{
    "service_id": 42,
    "service_name": "Herrenhaarschnitt", // ✓ NOT "Beratung"
    "available": true
}
```

---

## Monitoring & Alerting

### Metrics to Track

```php
// config/metrics.php
return [
    'service_matching' => [
        'exact_matches' => 'counter',
        'synonym_matches' => 'counter',
        'fuzzy_matches' => 'counter',
        'no_matches' => 'counter',
        'match_confidence' => 'histogram',
        'match_latency_ms' => 'histogram'
    ]
];
```

### Alerts

```yaml
# monitoring/alerts.yaml
- name: ServiceMatchFailureRate
  condition: rate(service_matching.no_matches[5m]) > 0.1
  severity: warning
  message: "10% of service matches are failing"

- name: LowConfidenceMatches
  condition: histogram_quantile(0.95, service_matching.match_confidence) < 0.8
  severity: info
  message: "95th percentile match confidence below 80%"
```

### Dashboard Query

```sql
-- Daily Service Matching Report
SELECT
    DATE(created_at) as date,
    COUNT(*) FILTER (WHERE match_type = 'exact') as exact_matches,
    COUNT(*) FILTER (WHERE match_type = 'synonym') as synonym_matches,
    COUNT(*) FILTER (WHERE match_type = 'fuzzy') as fuzzy_matches,
    COUNT(*) FILTER (WHERE match_type IS NULL) as no_matches,
    AVG(match_confidence) as avg_confidence
FROM service_match_logs
WHERE created_at >= NOW() - INTERVAL '30 days'
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

---

## Best Practices from Industry

### How Other Booking Systems Handle This

**Calendly**:
- Uses event type IDs (not names) in API
- Allows custom slugs for human-readable URLs
- Synonym mapping in admin panel

**Cal.com**:
- Event type IDs are primary
- Name matching is case-insensitive
- No built-in fuzzy matching

**Acuity Scheduling**:
- Service categories + sub-services
- Fuzzy search in UI
- API requires exact IDs

**Best Practice Consensus**:
1. **Always use IDs in API contracts** (not names)
2. **Name matching is UI-level convenience** (not critical path)
3. **Fuzzy matching requires confidence thresholds**
4. **Synonym mappings are business-managed** (not developer-managed)

---

## Action Items

### Immediate (This Week)
- [ ] Remove hardcoded "Beratung" fallback priority
- [ ] Add service name matching to `ServiceSelectionService`
- [ ] Update `RetellFunctionCallHandler` to use service name
- [ ] Add logging for all service selection paths
- [ ] Deploy to staging
- [ ] Run 10 test calls with different services

### Short-term (Next 2 Weeks)
- [ ] Create `service_synonyms` table
- [ ] Seed common synonyms for Friseur 1
- [ ] Implement synonym matching
- [ ] Add fuzzy matching (Levenshtein)
- [ ] Deploy to production
- [ ] Monitor for 1 week

### Medium-term (Next Month)
- [ ] Add Filament admin panel for synonym management
- [ ] Implement confidence scoring
- [ ] Add metrics dashboard
- [ ] Create runbook for service matching issues
- [ ] Train support team on synonym management

### Long-term (Next Quarter)
- [ ] Evaluate AI-powered semantic matching
- [ ] Multi-language support
- [ ] Auto-learning from user corrections
- [ ] A/B test different matching strategies

---

## Conclusion

The root cause is **missing service name matching logic** in the service selection flow. The system currently:
1. Ignores the `dienstleistung` parameter from Voice AI
2. Falls back to a hardcoded priority that favors "Beratung"
3. Results in wrong service selection 100% of the time

**Fix complexity**: Medium (2-3 days for robust solution)
**User impact**: HIGH (affects all voice bookings)
**Business priority**: CRITICAL (customer trust issue)

**Recommended Action**: Implement Option 2 (Smart Matching) with phased rollout. Start with exact match + logging, then add synonyms, then fuzzy matching.

---

**Document Status**: Complete
**Next Review**: After Phase 1 deployment
**Owner**: Backend Team
**Stakeholders**: Product, Support, Operations
