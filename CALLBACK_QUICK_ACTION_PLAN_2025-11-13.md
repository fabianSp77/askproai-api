# Callback System - Quick Action Plan

**Date**: 2025-11-13
**Purpose**: Prioritized implementation checklist with effort estimates

---

## Phase 1: Critical Fixes (Week 1) - ~12 hours

### P0-1: Re-enable SoftDeletes (30 min)

**Problem:** Callbacks can't be restored if accidentally deleted

**Files:**
- `/app/Models/CallbackRequest.php` (line 46)

**Tasks:**
```bash
# 1. Check if deleted_at column exists
php artisan tinker
>>> Schema::hasColumn('callback_requests', 'deleted_at');

# 2. If false, create migration
php artisan make:migration add_soft_deletes_to_callback_requests_table

# 3. Add in migration:
$table->softDeletes();

# 4. Run migration
php artisan migrate

# 5. Update Model
use HasFactory, BelongsToCompany, SoftDeletes;
```

**Validation:**
```php
$callback = CallbackRequest::first();
$callback->delete(); // Soft delete
CallbackRequest::withTrashed()->find($callback->id); // Should exist
$callback->restore(); // Should work
```

---

### P0-2: Move Cache Invalidation to Observer (1 hour)

**Problem:** Cache logic tightly coupled in Model::boot()

**Files:**
- `/app/Models/CallbackRequest.php` (lines 321-333)
- `/app/Observers/CallbackRequestObserver.php`

**Implementation:**

```php
// app/Observers/CallbackRequestObserver.php
public function saved(CallbackRequest $callback): void
{
    if ($callback->wasChanged('status')) {
        // Use cache tags for granular invalidation
        Cache::tags(['callbacks', 'navigation'])->flush();

        // Fire event for listeners
        event(new CallbackStatusChanged($callback));
    }
}

public function deleted(CallbackRequest $callback): void
{
    Cache::tags(['callbacks', 'navigation'])->flush();
}

// Remove from CallbackRequest::boot()
// Delete lines 321-333
```

---

### P0-3: Create NotifyManagers Listener (2 hours)

**Problem:** Managers not notified when callbacks escalated

**Files to Create:**
- `/app/Listeners/Callbacks/NotifyManagers.php`
- `/app/Notifications/CallbackEscalatedNotification.php`

**Implementation:**

```php
// app/Listeners/Callbacks/NotifyManagers.php
namespace App\Listeners\Callbacks;

use App\Events\Appointments\CallbackEscalated;
use App\Models\Staff;
use App\Notifications\CallbackEscalatedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyManagers
{
    public function handle(CallbackEscalated $event): void
    {
        $managers = Staff::where('role', 'manager')
            ->where('branch_id', $event->callbackRequest->branch_id)
            ->where('is_active', true)
            ->get();

        if ($managers->isEmpty()) {
            \Log::warning('No managers found for escalation', [
                'callback_id' => $event->callbackRequest->id,
                'branch_id' => $event->callbackRequest->branch_id,
            ]);
            return;
        }

        Notification::send($managers, new CallbackEscalatedNotification($event->callbackRequest));
    }
}

// app/Notifications/CallbackEscalatedNotification.php
namespace App\Notifications;

use App\Models\CallbackRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CallbackEscalatedNotification extends Notification
{
    public function __construct(
        public readonly CallbackRequest $callback
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🚨 Callback Request Escalated')
            ->line('A callback request has been escalated and requires your attention.')
            ->line('Customer: ' . $this->callback->customer_name)
            ->line('Phone: ' . $this->callback->phone_number)
            ->line('Priority: ' . $this->callback->priority)
            ->line('Overdue by: ' . $this->callback->expires_at->diffForHumans())
            ->action('View Callback', url('/admin/callback-requests/' . $this->callback->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'callback_id' => $this->callback->id,
            'customer_name' => $this->callback->customer_name,
            'priority' => $this->callback->priority,
            'expires_at' => $this->callback->expires_at,
        ];
    }
}

// Register in app/Providers/EventServiceProvider.php
CallbackEscalated::class => [
    NotifyManagers::class,
],
```

**Test:**
```php
// Manually trigger escalation
$callback = CallbackRequest::overdue()->first();
event(new CallbackEscalated($callback, 'sla_breach', 'auto'));

// Check logs
tail -f storage/logs/laravel.log | grep -i "escalat"
```

---

### P0-4: Add CallbackStatsWidget (3 hours)

**Files to Create:**
- `/app/Filament/Widgets/CallbackStatsOverview.php`

**Implementation:**

```php
namespace App\Filament\Widgets;

use App\Models\CallbackRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CallbackStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s'; // Auto-refresh

    protected function getStats(): array
    {
        $stats = Cache::remember('callback_stats_overview', 300, function() {
            $pending = CallbackRequest::where('status', 'pending')->count();
            $overdue = CallbackRequest::overdue()->count();
            $today = CallbackRequest::whereDate('created_at', today())->count();
            $completionRate = $this->calculateCompletionRate();

            return compact('pending', 'overdue', 'today', 'completionRate');
        });

        return [
            Stat::make('Pending Callbacks', $stats['pending'])
                ->description('Awaiting assignment or contact')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart($this->getPendingTrend()),

            Stat::make('Overdue', $stats['overdue'])
                ->description('Exceeded SLA deadline')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Today', $stats['today'])
                ->description('Callbacks created today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Completion Rate', $stats['completionRate'] . '%')
                ->description('Last 24 hours')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($stats['completionRate'] >= 90 ? 'success' : 'warning'),
        ];
    }

    protected function calculateCompletionRate(): float
    {
        $completed = CallbackRequest::where('status', 'completed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $total = CallbackRequest::where('created_at', '>=', now()->subDay())
            ->count();

        return $total > 0 ? round(($completed / $total) * 100, 1) : 0;
    }

    protected function getPendingTrend(): array
    {
        // Last 7 days pending count
        return collect(range(6, 0))
            ->map(fn($days) => CallbackRequest::where('status', 'pending')
                ->whereDate('created_at', today()->subDays($days))
                ->count())
            ->toArray();
    }
}
```

**Register in Dashboard:**
```php
// app/Filament/Pages/Dashboard.php
protected function getHeaderWidgets(): array
{
    return [
        CallbackStatsOverview::class,
    ];
}
```

---

### P0-5: SLA Pre-Breach Alerts (4 hours)

**Files to Create:**
- `/app/Jobs/Callbacks/CheckCallbackSlaJob.php`
- `/app/Events/Callbacks/CallbackSlaApproaching.php`
- `/app/Listeners/Callbacks/NotifyStaffSlaApproaching.php`

**Implementation:**

```php
// app/Jobs/Callbacks/CheckCallbackSlaJob.php
namespace App\Jobs\Callbacks;

use App\Events\Callbacks\CallbackSlaApproaching;
use App\Models\CallbackRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CheckCallbackSlaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        // Find callbacks approaching SLA breach (within 1 hour)
        $approaching = CallbackRequest::whereIn('status', ['pending', 'assigned', 'contacted'])
            ->whereBetween('expires_at', [now(), now()->addHour()])
            ->get();

        foreach ($approaching as $callback) {
            $minutesRemaining = now()->diffInMinutes($callback->expires_at, false);

            if ($minutesRemaining > 0) {
                event(new CallbackSlaApproaching($callback, $minutesRemaining));
            }
        }

        Log::info('SLA check completed', [
            'approaching_count' => $approaching->count(),
        ]);
    }
}

// app/Events/Callbacks/CallbackSlaApproaching.php
namespace App\Events\Callbacks;

use App\Models\CallbackRequest;
use Illuminate\Foundation\Events\Dispatchable;

class CallbackSlaApproaching
{
    use Dispatchable;

    public function __construct(
        public CallbackRequest $callback,
        public int $minutesRemaining
    ) {}
}

// app/Listeners/Callbacks/NotifyStaffSlaApproaching.php
namespace App\Listeners\Callbacks;

use App\Events\Callbacks\CallbackSlaApproaching;
use Filament\Notifications\Notification;

class NotifyStaffSlaApproaching
{
    public function handle(CallbackSlaApproaching $event): void
    {
        if (!$event->callback->assignedTo) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('SLA Deadline Approaching')
            ->body("Callback for {$event->callback->customer_name} expires in {$event->minutesRemaining} minutes")
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->button()
                    ->url(route('filament.admin.resources.callback-requests.view', $event->callback)),
            ])
            ->sendToDatabase($event->callback->assignedTo);
    }
}

// Register in EventServiceProvider.php
CallbackSlaApproaching::class => [
    NotifyStaffSlaApproaching::class,
],

// Schedule in app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->job(new CheckCallbackSlaJob)->everyFifteenMinutes();
}
```

---

### P0-6: Structured Logging (2 hours)

**Files to Update:**
- `/app/Services/Callbacks/CallbackAssignmentService.php`
- `/app/Services/Appointments/CallbackManagementService.php`

**Standard Log Format:**

```php
// Replace existing Log::info() calls with structured format
Log::info('[CALLBACK] Created', [
    'callback_id' => $callback->id,
    'customer_id' => $callback->customer_id,
    'customer_name' => $callback->customer_name,
    'phone' => $callback->phone_number,
    'priority' => $callback->priority,
    'branch_id' => $callback->branch_id,
    'expires_at' => $callback->expires_at,
    'source' => $callback->metadata['source'] ?? 'manual',
]);

Log::info('[CALLBACK] Assigned', [
    'callback_id' => $callback->id,
    'staff_id' => $staff->id,
    'staff_name' => $staff->name,
    'strategy' => $strategy,
    'assignment_time_ms' => round(microtime(true) - $startTime) * 1000,
]);

Log::warning('[CALLBACK] SLA Breach', [
    'callback_id' => $callback->id,
    'customer_name' => $callback->customer_name,
    'priority' => $callback->priority,
    'overdue_hours' => $callback->expires_at->diffInHours(now(), false),
    'assigned_to' => $callback->assigned_to,
]);
```

**Log Parsing (for metrics):**
```bash
# Count callbacks created today
grep '\[CALLBACK\] Created' storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l

# Find slow assignments (>5s)
grep '\[CALLBACK\] Assigned' storage/logs/laravel-*.log | grep 'assignment_time_ms":[5-9][0-9][0-9][0-9]'
```

---

## Phase 2: Automation Enhancements (Week 3-4) - ~24 hours

### P1-1: Auto-Priority Calculation (3 hours)

```php
// app/Observers/CallbackRequestObserver.php
public function creating(CallbackRequest $callback): void
{
    // Existing validation...

    // Auto-calculate priority if not set
    if (!$callback->priority) {
        $callback->priority = $this->calculatePriority($callback);
    }
}

private function calculatePriority(CallbackRequest $callback): string
{
    // 1. Check if VIP customer
    if ($callback->customer?->is_vip) {
        return CallbackRequest::PRIORITY_URGENT;
    }

    // 2. Check failed appointment history
    if ($callback->customer_id) {
        $failedCount = \App\Models\Appointment::where('customer_id', $callback->customer_id)
            ->where('status', 'cancelled')
            ->where('created_at', '>', now()->subMonths(3))
            ->count();

        if ($failedCount >= 2) {
            return CallbackRequest::PRIORITY_HIGH;
        }
    }

    // 3. Check for urgency keywords
    $urgentKeywords = ['urgent', 'asap', 'sofort', 'dringend', 'notfall'];
    $notes = strtolower($callback->notes ?? '');

    foreach ($urgentKeywords as $keyword) {
        if (str_contains($notes, $keyword)) {
            return CallbackRequest::PRIORITY_HIGH;
        }
    }

    // 4. Check metadata from Retell AI
    if (isset($callback->metadata['urgency_detected']) && $callback->metadata['urgency_detected']) {
        return CallbackRequest::PRIORITY_HIGH;
    }

    return CallbackRequest::PRIORITY_NORMAL;
}
```

**Test Cases:**
```php
// VIP customer → urgent
$callback = CallbackRequest::factory()->create(['customer_id' => $vipCustomer->id]);
$this->assertEquals('urgent', $callback->priority);

// Urgency keyword → high
$callback = CallbackRequest::factory()->create(['notes' => 'Need appointment ASAP']);
$this->assertEquals('high', $callback->priority);
```

---

### P1-2: Link to Appointment System (4 hours)

```php
// app/Observers/AppointmentObserver.php
public function created(Appointment $appointment): void
{
    // Check if this appointment originated from a callback request
    if (isset($appointment->metadata['callback_request_id'])) {
        $callbackId = $appointment->metadata['callback_request_id'];
        $callback = CallbackRequest::find($callbackId);

        if ($callback && $callback->status !== CallbackRequest::STATUS_COMPLETED) {
            $callback->update([
                'status' => CallbackRequest::STATUS_COMPLETED,
                'completed_at' => now(),
                'notes' => ($callback->notes ?? '') . "\n\nAppointment booked: {$appointment->id}",
            ]);

            Log::info('[CALLBACK] Auto-completed via appointment', [
                'callback_id' => $callback->id,
                'appointment_id' => $appointment->id,
            ]);
        }
    }
}

// When creating appointment from callback
// app/Filament/Resources/CallbackRequestResource.php
Tables\Actions\Action::make('createAppointment')
    ->label('Create Appointment')
    ->icon('heroicon-o-calendar-plus')
    ->visible(fn($record) => $record->status !== 'completed')
    ->url(fn($record) => route('filament.admin.resources.appointments.create', [
        'customer_id' => $record->customer_id,
        'staff_id' => $record->assigned_to,
        'service_id' => $record->service_id,
        'callback_request_id' => $record->id, // Pass callback ID
    ]))
```

---

### P1-3: Webhook System (8 hours)

**Database Migration:**
```php
Schema::create('webhook_endpoints', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('url');
    $table->json('events'); // ["callback.created", "callback.completed"]
    $table->string('secret');
    $table->boolean('is_active')->default(true);
    $table->integer('retry_attempts')->default(3);
    $table->integer('timeout_seconds')->default(10);
    $table->timestamps();
});

Schema::create('webhook_deliveries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('webhook_endpoint_id')->constrained()->cascadeOnDelete();
    $table->string('event');
    $table->json('payload');
    $table->integer('response_code')->nullable();
    $table->text('response_body')->nullable();
    $table->integer('attempt')->default(1);
    $table->timestamp('delivered_at')->nullable();
    $table->timestamps();
});
```

**Service Implementation:**
```php
// app/Services/Webhooks/CallbackWebhookService.php
namespace App\Services\Webhooks;

use App\Jobs\DispatchWebhookJob;
use App\Models\CallbackRequest;
use App\Models\WebhookEndpoint;

class CallbackWebhookService
{
    public function dispatch(CallbackRequest $callback, string $event): void
    {
        $webhooks = WebhookEndpoint::where('company_id', $callback->company_id)
            ->where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhooks as $webhook) {
            DispatchWebhookJob::dispatch($webhook, $event, [
                'id' => $callback->id,
                'customer_name' => $callback->customer_name,
                'phone' => $callback->phone_number,
                'status' => $callback->status,
                'priority' => $callback->priority,
                'created_at' => $callback->created_at->toIso8601String(),
                'expires_at' => $callback->expires_at?->toIso8601String(),
            ]);
        }
    }
}

// Fire in Observer
public function created(CallbackRequest $callback): void
{
    app(CallbackWebhookService::class)->dispatch($callback, 'callback.created');
}
```

---

## Phase 3: Observability (Week 5-6) - ~25 hours

### P1-4: Metrics Collection (8 hours)

**Install Prometheus Client:**
```bash
composer require promphp/prometheus_client_php
```

**Metrics Service:**
```php
// app/Services/Metrics/CallbackMetricsService.php
namespace App\Services\Metrics;

use App\Models\CallbackRequest;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

class CallbackMetricsService
{
    private CollectorRegistry $registry;

    public function __construct()
    {
        $this->registry = new CollectorRegistry(new Redis([
            'host' => config('database.redis.default.host'),
            'port' => config('database.redis.default.port'),
        ]));
    }

    public function recordCreated(CallbackRequest $callback): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'callbacks',
            'created_total',
            'Total callbacks created',
            ['priority', 'branch']
        );

        $counter->inc([
            $callback->priority,
            $callback->branch->name ?? 'unknown',
        ]);
    }

    public function recordAssignmentTime(CallbackRequest $callback): void
    {
        if (!$callback->assigned_at) return;

        $seconds = $callback->assigned_at->diffInSeconds($callback->created_at);

        $histogram = $this->registry->getOrRegisterHistogram(
            'callbacks',
            'assignment_duration_seconds',
            'Time to assign callback',
            ['priority'],
            [1, 5, 10, 30, 60, 300, 600, 1800] // Buckets: 1s, 5s, 10s, 30s, 1m, 5m, 10m, 30m
        );

        $histogram->observe($seconds, [$callback->priority]);
    }

    public function recordSlaBreach(CallbackRequest $callback): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'callbacks',
            'sla_breach_total',
            'Total SLA breaches',
            ['priority', 'branch']
        );

        $counter->inc([
            $callback->priority,
            $callback->branch->name ?? 'unknown',
        ]);
    }

    public function getMetrics(): string
    {
        $renderer = new \Prometheus\RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }
}

// Expose metrics endpoint
// routes/web.php
Route::get('/metrics', function() {
    return response(app(\App\Services\Metrics\CallbackMetricsService::class)->getMetrics())
        ->header('Content-Type', 'text/plain; version=0.0.4');
})->middleware('auth:sanctum');
```

---

## Quick Reference Commands

### Cache Management
```bash
# Clear all callback caches
php artisan tinker
>>> Cache::tags(['callbacks'])->flush();

# Warm dashboard cache
php artisan schedule:run --force-run=WarmCallbackCacheJob
```

### Queue Management
```bash
# Start queue workers
php artisan horizon

# Monitor queue
php artisan queue:monitor callbacks --max=100

# Retry failed jobs
php artisan queue:retry all
```

### Testing
```bash
# Run callback tests
php artisan test --filter=Callback

# Load test (100 concurrent creations)
php artisan test --filter=CallbackLoadTest
```

### Debugging
```bash
# Watch logs for callbacks
tail -f storage/logs/laravel.log | grep '\[CALLBACK\]'

# Count pending callbacks
php artisan tinker
>>> CallbackRequest::where('status', 'pending')->count();

# Find overdue
>>> CallbackRequest::overdue()->get();
```

---

## Success Criteria

**Phase 1 Complete When:**
- ✅ SoftDeletes enabled and tested
- ✅ Cache invalidation moved to Observer
- ✅ Managers receive escalation emails
- ✅ Dashboard shows stats widget with auto-refresh
- ✅ SLA alerts fire before breach
- ✅ All logs follow structured format

**Phase 2 Complete When:**
- ✅ Priority auto-calculated based on customer/keywords
- ✅ Appointments auto-complete callbacks
- ✅ Webhook system operational with 1+ integration
- ✅ API endpoints functional

**Phase 3 Complete When:**
- ✅ Prometheus metrics exposed at /metrics
- ✅ Grafana dashboard configured
- ✅ Alerts configured in Prometheus
- ✅ SLA compliance reports generated

---

**Document Version**: 1.0
**Companion To**:
- CALLBACK_SYSTEM_ARCHITECTURE_ANALYSIS_2025-11-13.md
- CALLBACK_ARCHITECTURE_DIAGRAMS_2025-11-13.md
**Last Updated**: 2025-11-13
