# Eager Loading Optimization System

This document describes the comprehensive eager loading and query optimization system implemented in the AskProAI platform to prevent N+1 queries and optimize database performance.

## Overview

The system provides multiple layers of optimization:
1. **SmartLoader Trait** - Automatic relationship management for models
2. **Loading Profiles** - Predefined relationship loading strategies
3. **EagerLoadingAnalyzer** - N+1 query detection service
4. **Optimized Repositories** - Strategic eager loading in data access layer
5. **Middleware** - Automatic optimization based on routes
6. **Console Command** - Detect N+1 queries across the codebase

## Components

### 1. SmartLoader Trait (`app/Models/Traits/SmartLoader.php`)

Provides intelligent relationship loading capabilities:

```php
// Usage in models
class Appointment extends Model
{
    use SmartLoader, HasLoadingProfiles;
}

// Query with profiles
$appointments = Appointment::withProfile('standard')->get();
$appointment = Appointment::withFull()->find($id);
$list = Appointment::forListView()->paginate();
```

**Key Methods:**
- `withProfile($profile)` - Load a specific profile
- `withMinimal()` - Load minimal data only
- `withStandard()` - Load common relationships
- `withFull()` - Load all relationships
- `withCounts()` - Load relationship counts only
- `loadMissing($relations)` - Load only unloaded relations
- `forApi($includes)` - Optimized for API responses

### 2. Loading Profiles

Define different loading strategies for various use cases:

```php
protected static function defineLoadingProfiles(): void
{
    // Minimal - just the model data
    static::defineLoadingProfile('minimal', []);
    
    // Standard - common relationships
    static::defineLoadingProfile('standard', [
        'customer:id,name,email,phone',
        'staff:id,name',
        'service:id,name,duration,price',
    ]);
    
    // Full - all relationships
    static::defineLoadingProfile('full', [
        'customer',
        'staff',
        'branch',
        'service',
        'company',
        'calcomBooking',
    ]);
    
    // Counts - for listings
    static::defineLoadingProfile('counts', [
        'appointments',
        'customers',
    ]);
}
```

### 3. EagerLoadingAnalyzer Service

Detects N+1 queries in real-time:

```php
$analyzer = app(EagerLoadingAnalyzer::class);
$analyzer->startAnalysis();

// Run your code...

$results = $analyzer->stopAnalysis();
// Returns:
// - total_queries
// - suspicious_patterns
// - recommendations
// - query_breakdown
```

### 4. Optimized BaseRepository

Enhanced repository with loading profiles:

```php
class AppointmentRepository extends BaseRepository
{
    // Get minimal data for lists
    public function forList(): Collection
    {
        return $this->minimal()
            ->withCount(['customers'])
            ->all();
    }
    
    // Get full data for detail views
    public function forDetail(int $id): ?Model
    {
        return $this->full()->find($id);
    }
    
    // Optimized for API
    public function forApi(array $includes = []): Collection
    {
        return $this->standard()
            ->with($includes)
            ->all();
    }
}
```

### 5. Eager Loading Middleware

Automatically optimizes queries based on routes:

```php
// In routes/api.php
Route::middleware(['api', 'eager.loading'])->group(function () {
    Route::apiResource('appointments', AppointmentController::class);
});

// Middleware automatically:
// - Detects route patterns
// - Applies optimal loading profiles
// - Monitors for N+1 queries in debug mode
// - Adds performance headers
```

### 6. Detection Command

Scan codebase for N+1 query problems:

```bash
# Analyze entire codebase
php artisan optimize:detect-n1

# Analyze specific model
php artisan optimize:detect-n1 --model=Appointment

# Analyze specific path
php artisan optimize:detect-n1 --path=app/Http/Controllers

# Generate fixes
php artisan optimize:detect-n1 --fix

# Create detailed report
php artisan optimize:detect-n1 --report
```

## Usage Examples

### In Controllers

```php
class AppointmentController extends BaseApiController
{
    protected array $defaultIncludes = ['customer', 'staff'];
    protected array $availableIncludes = ['customer', 'staff', 'branch', 'service'];
    
    public function index(Request $request)
    {
        // Automatically optimized based on request
        return $this->respondWithCollection(
            Appointment::query(),
            $request
        );
    }
    
    public function show(Appointment $appointment)
    {
        // Load full profile for detail view
        $appointment->loadForDetailView();
        return response()->json($appointment);
    }
}
```

### In Filament Resources

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(fn ($query) => $query->with([
            'customer',
            'staff',
            'branch',
            'service'
        ]))
        ->columns([/* ... */]);
}
```

### In Repositories

```php
public function getByDateRange(Carbon $start, Carbon $end): Collection
{
    return $this->pushCriteria(function ($query) use ($start, $end) {
        $query->whereBetween('starts_at', [$start, $end]);
    })
    ->standard()  // Use standard loading profile
    ->all();
}
```

## Best Practices

### 1. Always Define Loading Profiles

```php
// In your model
protected static function defineLoadingProfiles(): void
{
    static::defineLoadingProfile('minimal', []);
    static::defineLoadingProfile('standard', ['essential_relations']);
    static::defineLoadingProfile('full', ['all_relations']);
}
```

### 2. Use Relationship Counting

Instead of loading full collections:
```php
// Bad
$company->appointments->count();

// Good
$company->appointments_count; // Use withCount()
```

### 3. Select Only Needed Fields

```php
// Load only specific fields
Appointment::withProfile('standard')
    ->select(['id', 'starts_at', 'status'])
    ->with(['customer:id,name'])
    ->get();
```

### 4. Use Appropriate Profiles

- **List Views**: Use `minimal` or `standard` with counts
- **Detail Views**: Use `full` profile
- **API Responses**: Use `forApi()` with requested includes
- **Exports**: Use `minimal` to reduce memory usage

### 5. Monitor in Development

Enable query logging to catch issues early:
```php
if (config('app.debug')) {
    DB::enableQueryLog();
    // Your code...
    $queries = DB::getQueryLog();
}
```

## Configuration

### Route-Based Optimization

Configure automatic loading in the middleware:

```php
// app/Http/Middleware/EagerLoadingMiddleware.php
protected array $routeLoadingProfiles = [
    'api/appointments' => [
        'model' => Appointment::class,
        'profile' => 'standard',
        'relations' => ['customer', 'staff'],
    ],
    'api/appointments/*' => [
        'model' => Appointment::class,
        'profile' => 'full',
    ],
];
```

### Model Configuration

```php
// Remove default eager loading
protected $with = []; // Don't use this!

// Define what can be loaded via API
protected function getAllowedIncludes(): array
{
    return ['customer', 'staff', 'branch'];
}

// Define countable relationships
protected function getCountableRelations(): array
{
    return ['appointments', 'customers'];
}
```

## Performance Tips

1. **Chunking with Profiles**
   ```php
   Appointment::smartChunk(1000, function ($appointments) {
       // Process with optimal loading
   }, 'minimal');
   ```

2. **Conditional Loading**
   ```php
   $appointment->loadWhen($request->has('include_history'), 'history');
   ```

3. **Limited Loading**
   ```php
   $customer->loadLimited('appointments', 10);
   $customer->loadRecent('appointments', days: 30);
   ```

4. **API Optimization**
   ```php
   // Support sparse fieldsets
   GET /api/appointments?fields[appointment]=id,starts_at&fields[customer]=name
   ```

## Troubleshooting

### Detecting N+1 Queries

1. Run the detection command:
   ```bash
   php artisan optimize:detect-n1 --report
   ```

2. Check Laravel Debugbar in development

3. Monitor slow query log

4. Use the analyzer in tests:
   ```php
   $this->expectNoN1Queries(function () {
       // Your test code
   });
   ```

### Common Issues

**Issue**: Still seeing N+1 queries
- Check if model uses SmartLoader trait
- Verify loading profile is defined
- Ensure repository uses profiles

**Issue**: Over-fetching data
- Use minimal profile for lists
- Select only needed fields
- Use relationship counting

**Issue**: Memory issues with large datasets
- Use chunking with minimal profile
- Implement pagination
- Consider using cursor() for large exports

## Monitoring

Add to your monitoring dashboard:
- Query count per request
- N+1 query warnings
- Average query time
- Memory usage per endpoint

Headers added in debug mode:
- `X-Query-Count`: Total queries executed
- `X-Query-Time`: Total query time
- `X-N1-Warning`: Number of potential N+1 queries
- `X-Query-Analysis`: Detailed analysis JSON