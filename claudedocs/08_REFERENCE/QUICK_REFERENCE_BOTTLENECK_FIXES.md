# Quick Reference - Performance Bottleneck Fixes
**Created**: 2025-10-18

---

## The Three Critical Bottlenecks

### 1. Agent Name Verification (100s of 144s total)
**File**: `/var/www/api-gateway/app/Services/CustomerIdentification/PhoneticMatcher.php`

**Status**: Not yet implemented from spec

**What's Missing**:
- [ ] Phonetic columns on staff table (`phonetic_name_soundex`, `phonetic_name_metaphone`)
- [ ] Phonetic indexes
- [ ] Cached agent resolution

**Impact**: 100s → <5s (95 seconds saved)

---

### 2. N+1 Query Patterns (12ms wasted per endpoint)

#### 2a: Call Lookup Not Eager Loading
**File**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php`  
**Line**: 67

```php
// WRONG
$call = Call::where('retell_call_id', $callId)->first();

// RIGHT
$call = Call::with(['customer', 'company', 'branch', 'phoneNumber'])
    ->where('retell_call_id', $callId)
    ->first();
```

#### 2b: Customer Lookup Not Cached
**File**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php`  
**Lines**: 82-96

```php
// WRONG
$customer = Customer::where(...)->first();  // Every time

// RIGHT
$cacheKey = "customer:phone:" . md5($normalizedPhone) . ":company:{$companyId}";
$customer = Cache::remember($cacheKey, 300, function() use ($normalizedPhone, $companyId) {
    return Customer::where('company_id', $companyId)
        ->where('phone', $normalizedPhone)
        ->first();
});
```

#### 2c: Appointment Queries Not Using Eager Loading
**File**: `/var/www/api-gateway/app/Services/Retell/AppointmentQueryService.php`  
**Line**: 157

```php
// WRONG
return $query->orderBy('starts_at', 'asc')->get();

// RIGHT
return $query
    ->with(['service:id,name', 'staff:id,name', 'customer:id,name'])
    ->orderBy('starts_at', 'asc')
    ->get();
```

---

## Implementation Quick Start

### Step 1: Enable Performance Monitoring (2 minutes)
**File**: `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    \App\Services\Monitoring\DatabasePerformanceMonitor::enable();  // Add this
}
```

**Purpose**: Real-time N+1 detection - access via `/admin/performance-report`

---

### Step 2: Fix Call Lookups (5 minutes)
**File**: `app/Http/Controllers/Api/RetellApiController.php`

Find all `Call::where('retell_call_id'...` occurrences (3 places):
- Line 67 in `checkCustomer()`
- Line 487 in `cancelAppointment()` 
- Line 960+ in `rescheduleAppointment()`

Add eager loading:
```php
Call::with(['customer', 'company', 'branch', 'phoneNumber'])
    ->where('retell_call_id', $callId)
    ->first()
```

**Impact**: 2-3ms saved per endpoint

---

### Step 3: Cache Customer Lookups (10 minutes)
**File**: `app/Http/Controllers/Api/RetellApiController.php`  
**Lines**: 82-96

Replace entire customer lookup section with:
```php
$customer = null;
if ($phoneNumber && $phoneNumber !== 'anonymous') {
    $normalizedPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);
    
    $cacheKey = "customer:phone:" . md5($normalizedPhone) . ":company:{$companyId}";
    $customer = Cache::remember($cacheKey, 300, function() use ($normalizedPhone, $companyId) {
        return Customer::where('company_id', $companyId)
            ->where('phone', $normalizedPhone)
            ->first();
    });
}
```

**Impact**: 1-2ms saved per call (first time), cache hits next request

---

### Step 4: Fix Appointment Query N+1 (5 minutes)
**File**: `app/Services/Retell/AppointmentQueryService.php`  
**Line**: 157

```php
// BEFORE
return $query->orderBy('starts_at', 'asc')->get();

// AFTER
return $query
    ->with(['service:id,name', 'staff:id,name', 'customer:id,name,email,phone'])
    ->orderBy('starts_at', 'asc')
    ->get();
```

**Impact**: 80% reduction on appointment list queries

---

### Step 5: Agent Verification Fix (4-6 hours) [CRITICAL]

This is the big one - 100 seconds saved.

#### 5a: Create Migration
```bash
php artisan make:migration add_phonetic_columns_to_staff_table
```

```php
Schema::table('staff', function (Blueprint $table) {
    $table->string('phonetic_name_soundex')->nullable();
    $table->string('phonetic_name_metaphone')->nullable();
    
    $table->index(['phonetic_name_soundex', 'company_id']);
    $table->index(['phonetic_name_metaphone', 'company_id']);
});
```

#### 5b: Populate Phonetic Columns
```bash
php artisan tinker

// In tinker:
Staff::all()->each(function($staff) {
    $staff->update([
        'phonetic_name_soundex' => soundex($staff->name),
        'phonetic_name_metaphone' => metaphone($staff->name)
    ]);
});
```

#### 5c: Update PhoneticMatcher Service
**File**: `app/Services/CustomerIdentification/PhoneticMatcher.php`

Add caching layer:
```php
public function matches(string $name1, string $name2, int $threshold = 80): bool
{
    $cacheKey = "phonetic:match:" . md5($name1 . $name2) . ":" . $threshold;
    
    return Cache::remember($cacheKey, 3600, function() use ($name1, $name2, $threshold) {
        // Existing matching logic
        $lev = levenshtein(strtolower($name1), strtolower($name2));
        $maxLen = max(strlen($name1), strlen($name2));
        $similarity = (1 - $lev / $maxLen) * 100;
        return $similarity >= $threshold;
    });
}
```

---

## Validation Checklist

- [ ] Run `DatabasePerformanceMonitor::getReport()` - should show 0 N+1 patterns
- [ ] Verify call lookups execute 1 query instead of 3-5
- [ ] Verify customer lookups hit Redis cache (check redis-cli for `customer:phone:*` keys)
- [ ] Verify appointment lists load relationships in single query
- [ ] Load test with 50 concurrent calls - should see <45s duration

---

## Performance Targets After Implementation

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Agent Verification | 100s | <5s | 95s saved |
| Call Lookup Queries | 3-5 | 1 | 4 queries saved |
| Customer Lookup | Uncached | Cached 300s TTL | ~50% hits |
| Appointment Query N+1 | 1+2N | 3 | 80-95% reduction |
| **Total Call Duration** | **144s** | **<45s** | **~99s saved** |

---

## Quick Debugging

### Check if customer lookup is cached
```php
// In tinker or controller
Redis::client()->keys('customer:phone:*');  // Should see growing list
```

### Check for remaining N+1 patterns
```php
$report = DatabasePerformanceMonitor::getReport();
dd($report['n_plus_one_candidates']);  // Should be empty
```

### Verify eager loading worked
```php
$appointments = Appointment::with(['service', 'staff'])->get();
// DB::getQueryLog() should show 3 queries, not 1+2N
```

---

**Total Effort**: ~7 hours  
**Expected Result**: 144s → <45s (69% reduction)  
**ROI**: ~12 seconds saved per appointment booking
