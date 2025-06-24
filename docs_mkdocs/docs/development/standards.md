# Coding Standards

## Overview

This document outlines the coding standards and best practices for the AskProAI project. Following these standards ensures code consistency, maintainability, and quality across the entire codebase.

## PHP Standards

### PSR Standards
We follow PSR-12 for PHP code style and PSR-4 for autoloading.

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Exceptions\BookingException;
use Illuminate\Support\Collection;

class AppointmentService
{
    private const MAX_BOOKING_DAYS = 60;
    
    public function __construct(
        private readonly CalendarService $calendarService,
        private readonly NotificationService $notificationService
    ) {
    }
    
    public function createAppointment(array $data): Appointment
    {
        // Method implementation
    }
}
```

### Naming Conventions

#### Classes
- Use PascalCase for class names
- Be descriptive and specific
- Suffix with type (Controller, Service, Repository, etc.)

```php
// Good
class AppointmentBookingService
class CustomerController
class PaymentRepository

// Bad
class Appointment  // Too generic for a service
class custController  // Wrong case
class Process  // Not descriptive
```

#### Methods
- Use camelCase for method names
- Start with a verb
- Be descriptive about what the method does

```php
// Good
public function createAppointment(): Appointment
public function calculateTotalPrice(): float
public function hasAvailableSlots(): bool

// Bad
public function appointment()  // Not descriptive
public function total_price()  // Wrong case
public function process()  // Too vague
```

#### Variables
- Use camelCase for variables
- Use descriptive names
- Avoid abbreviations

```php
// Good
$appointmentDate = Carbon::parse($request->date);
$customerEmail = $customer->email;
$isAvailable = $this->checkAvailability($slot);

// Bad
$d = Carbon::parse($request->date);  // Too short
$e_mail = $customer->email;  // Wrong case
$flag = true;  // Not descriptive
```

### Type Declarations

Always use type declarations for parameters and return types:

```php
class CustomerService
{
    public function findByPhone(string $phone): ?Customer
    {
        return Customer::where('phone', $phone)->first();
    }
    
    public function createCustomer(array $data): Customer
    {
        return Customer::create($data);
    }
    
    public function getActiveCustomers(): Collection
    {
        return Customer::active()->get();
    }
}
```

### Class Organization

Organize class members in this order:
1. Constants
2. Properties
3. Constructor
4. Public methods
5. Protected methods
6. Private methods

```php
class ExampleService
{
    // 1. Constants
    private const CACHE_TTL = 3600;
    
    // 2. Properties
    private CacheManager $cache;
    private LoggerInterface $logger;
    
    // 3. Constructor
    public function __construct(
        CacheManager $cache,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
    }
    
    // 4. Public methods
    public function process(): void
    {
        // Implementation
    }
    
    // 5. Protected methods
    protected function validate(): bool
    {
        // Implementation
    }
    
    // 6. Private methods
    private function cleanup(): void
    {
        // Implementation
    }
}
```

## Laravel Conventions

### Eloquent Models

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'customer_id',
        'branch_id',
        'staff_id',
        'service_id',
        'date',
        'time',
        'duration',
        'status',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'time' => 'datetime',
        'duration' => 'integer',
        'metadata' => 'array',
    ];
    
    /**
     * Get the customer for the appointment.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    
    /**
     * Scope a query to only include upcoming appointments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('time');
    }
}
```

### Controllers

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointmentService
    ) {
    }
    
    /**
     * Store a newly created appointment.
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointmentService->createAppointment(
            $request->validated()
        );
        
        return response()->json([
            'data' => new AppointmentResource($appointment),
            'message' => 'Appointment created successfully',
        ], 201);
    }
}
```

### Form Requests

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^[+][0-9]{10,15}$/'],
            'service_id' => ['required', 'exists:services,id'],
            'date' => ['required', 'date', 'after:today'],
            'time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
    
    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Phone number must be in international format (e.g., +49...)',
            'date.after' => 'Appointment date must be in the future',
        ];
    }
}
```

## JavaScript/TypeScript Standards

### ES6+ Features

Always use modern JavaScript features:

```javascript
// Use const/let instead of var
const API_ENDPOINT = '/api/appointments';
let isLoading = false;

// Use arrow functions
const calculateTotal = (items) => {
    return items.reduce((sum, item) => sum + item.price, 0);
};

// Use destructuring
const { name, email, phone } = customer;

// Use template literals
const message = `Appointment confirmed for ${name} on ${date}`;

// Use async/await
const fetchAppointments = async () => {
    try {
        const response = await fetch(API_ENDPOINT);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Failed to fetch appointments:', error);
    }
};
```

### Vue.js Components

```vue
<template>
    <div class="appointment-form">
        <h2>{{ title }}</h2>
        
        <form @submit.prevent="handleSubmit">
            <div class="form-group">
                <label for="customer-name">Name</label>
                <input
                    id="customer-name"
                    v-model="form.customerName"
                    type="text"
                    required
                    @input="validateName"
                >
                <span v-if="errors.customerName" class="error">
                    {{ errors.customerName }}
                </span>
            </div>
            
            <button type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? 'Booking...' : 'Book Appointment' }}
            </button>
        </form>
    </div>
</template>

<script>
import { ref, computed } from 'vue';
import { useAppointmentStore } from '@/stores/appointment';

export default {
    name: 'AppointmentForm',
    
    props: {
        title: {
            type: String,
            default: 'Book an Appointment'
        }
    },
    
    setup() {
        const appointmentStore = useAppointmentStore();
        
        const form = ref({
            customerName: '',
            customerPhone: '',
            serviceId: null,
            date: '',
            time: ''
        });
        
        const errors = ref({});
        const isSubmitting = ref(false);
        
        const isFormValid = computed(() => {
            return Object.keys(errors.value).length === 0 
                && form.value.customerName 
                && form.value.customerPhone;
        });
        
        const validateName = () => {
            if (form.value.customerName.length < 2) {
                errors.value.customerName = 'Name must be at least 2 characters';
            } else {
                delete errors.value.customerName;
            }
        };
        
        const handleSubmit = async () => {
            if (!isFormValid.value) return;
            
            isSubmitting.value = true;
            
            try {
                await appointmentStore.createAppointment(form.value);
                // Handle success
            } catch (error) {
                // Handle error
            } finally {
                isSubmitting.value = false;
            }
        };
        
        return {
            form,
            errors,
            isSubmitting,
            validateName,
            handleSubmit
        };
    }
};
</script>

<style scoped>
.appointment-form {
    max-width: 500px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 1rem;
}

.error {
    color: #dc3545;
    font-size: 0.875rem;
}
</style>
```

## Database Standards

### Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('time');
            $table->integer('duration')->comment('Duration in minutes');
            $table->enum('status', ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])
                ->default('scheduled');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['company_id', 'date', 'status']);
            $table->index(['branch_id', 'date']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['staff_id', 'date']);
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
```

### Database Queries

Always optimize queries:

```php
// Good - Eager load relationships
$appointments = Appointment::with(['customer', 'service', 'staff'])
    ->where('branch_id', $branchId)
    ->where('date', $date)
    ->get();

// Bad - N+1 query problem
$appointments = Appointment::where('branch_id', $branchId)->get();
foreach ($appointments as $appointment) {
    echo $appointment->customer->name; // Triggers additional query
}

// Good - Use query scopes
$upcomingAppointments = Appointment::upcoming()
    ->forBranch($branchId)
    ->get();

// Good - Select only needed columns
$customers = Customer::select('id', 'name', 'phone')
    ->active()
    ->get();
```

## Documentation Standards

### PHPDoc Comments

```php
/**
 * Create a new appointment for the customer.
 *
 * @param array $data The appointment data
 * @param Customer|null $customer Optional customer instance
 * @return Appointment The created appointment
 * @throws BookingException If the time slot is not available
 * @throws ValidationException If the data is invalid
 */
public function createAppointment(array $data, ?Customer $customer = null): Appointment
{
    // Implementation
}
```

### Inline Comments

```php
// Use comments to explain "why", not "what"

// Bad
// Increment counter
$counter++;

// Good
// Increment retry counter to track failed attempts for rate limiting
$retryCounter++;

// Good - Explain complex business logic
// We need to check availability 15 minutes before and after
// the requested time to account for buffer time between appointments
$bufferTime = 15;
$startTime = $requestedTime->subMinutes($bufferTime);
$endTime = $requestedTime->addMinutes($bufferTime);
```

## Testing Standards

### Test Naming

```php
class AppointmentServiceTest extends TestCase
{
    /** @test */
    public function it_creates_appointment_with_valid_data()
    {
        // Test implementation
    }
    
    /** @test */
    public function it_throws_exception_when_time_slot_is_not_available()
    {
        // Test implementation
    }
    
    /** @test */
    public function it_sends_confirmation_email_after_booking()
    {
        // Test implementation
    }
}
```

### Test Structure

Follow the AAA pattern (Arrange, Act, Assert):

```php
/** @test */
public function it_calculates_appointment_end_time_correctly()
{
    // Arrange
    $appointment = Appointment::factory()->create([
        'time' => '14:00:00',
        'duration' => 30,
    ]);
    
    // Act
    $endTime = $appointment->getEndTime();
    
    // Assert
    $this->assertEquals('14:30:00', $endTime->format('H:i:s'));
}
```

## Git Commit Standards

### Commit Message Format

Follow the Conventional Commits specification:

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

### Examples

```bash
# Good commit messages
git commit -m "feat(appointments): add recurring appointment support"
git commit -m "fix(webhooks): handle missing signature header gracefully"
git commit -m "docs: update installation guide with Docker instructions"
git commit -m "refactor(services): extract notification logic to separate service"

# Bad commit messages
git commit -m "fix bug"
git commit -m "Update code"
git commit -m "WIP"
```

## Security Standards

### Input Validation

Always validate and sanitize user input:

```php
// Validate request data
$validated = $request->validate([
    'email' => ['required', 'email', 'max:255'],
    'phone' => ['required', 'regex:/^[+][0-9]{10,15}$/'],
    'date' => ['required', 'date', 'after:today', 'before:+60 days'],
]);

// Sanitize output
$safeHtml = e($userInput); // Laravel's escape helper
$cleaned = strip_tags($htmlContent);
```

### SQL Injection Prevention

```php
// Good - Use parameter binding
$appointments = DB::select(
    'SELECT * FROM appointments WHERE branch_id = ? AND date = ?',
    [$branchId, $date]
);

// Good - Use query builder
$appointments = DB::table('appointments')
    ->where('branch_id', $branchId)
    ->where('date', $date)
    ->get();

// Bad - Never concatenate user input
$appointments = DB::select(
    "SELECT * FROM appointments WHERE branch_id = {$branchId}"
);
```

## Performance Standards

### Caching

```php
// Cache expensive operations
public function getEventTypes(): Collection
{
    return Cache::remember('event-types', 300, function () {
        return EventType::with('services')->active()->get();
    });
}

// Use cache tags for easy invalidation
Cache::tags(['appointments', 'branch-' . $branchId])
    ->remember($key, 3600, function () {
        return $this->calculateAvailability();
    });
```

### Pagination

Always paginate large datasets:

```php
// Good
$appointments = Appointment::paginate(20);

// Better - with custom pagination
$appointments = Appointment::with(['customer', 'service'])
    ->latest()
    ->paginate(
        perPage: 20,
        columns: ['id', 'customer_id', 'service_id', 'date', 'time', 'status']
    );
```

## Code Review Checklist

Before submitting code for review, ensure:

- [ ] Code follows PSR-12 standards
- [ ] All methods have proper type declarations
- [ ] Complex logic is well-commented
- [ ] No hardcoded values (use constants or config)
- [ ] Proper error handling is implemented
- [ ] Security considerations are addressed
- [ ] Performance optimizations are applied
- [ ] Tests are written and passing
- [ ] Documentation is updated
- [ ] No console.log or dump statements

## Related Documentation

- [Testing Guide](testing.md)
- [Security Best Practices](../configuration/security.md)
- [Contributing Guide](contributing.md)
- [API Standards](../api/standards.md)