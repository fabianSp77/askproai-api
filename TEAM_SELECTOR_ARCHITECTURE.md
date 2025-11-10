# Team-Selector Backend Architecture Design

**Version**: 1.0
**Date**: 2025-11-10
**Status**: Design Phase

---

## Executive Summary

Design specification for extending Cal.com integration to support multi-team selection per company. Enables branch-specific booking flows with proper multi-tenant isolation and caching.

**Key Features**:
- Company can have multiple Cal.com teams (1:N)
- Branch-level team assignment (optional)
- Multi-tenant security via RLS
- Redis caching with event-driven invalidation
- Backward compatible with existing `branches.calcom_team_id`

---

## 1. Database Schema Design

### 1.1 Primary Table: `calcom_teams`

Stores Cal.com team information at company level with optional branch association.

```php
// Migration: database/migrations/2025_11_10_000000_create_calcom_teams_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Team-Selector Feature: Multi-Team Cal.com Integration
     *
     * Enables companies to manage multiple Cal.com teams for different branches
     * or organizational units, with proper multi-tenant isolation.
     */
    public function up(): void
    {
        Schema::create('calcom_teams', function (Blueprint $table) {
            $table->id();

            // Multi-tenant isolation (CRITICAL)
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            // Optional branch association (if team is branch-specific)
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Cal.com Team Identification
            $table->unsignedBigInteger('calcom_team_id')
                ->comment('Cal.com API Team ID (numeric)');
            $table->string('calcom_team_slug', 100)
                ->comment('Cal.com team slug (workspace identifier)');

            // Display Information
            $table->string('name')
                ->comment('Display name for UI (e.g., "Friseur Mitte")');
            $table->text('description')->nullable();

            // Configuration
            $table->string('timezone', 50)
                ->default('Europe/Berlin');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false)
                ->comment('Default team for new bookings');

            // Sync Tracking
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status', 20)
                ->default('pending')
                ->comment('pending|synced|error');
            $table->text('sync_error')->nullable();

            // Metadata
            $table->json('metadata')->nullable()
                ->comment('Cal.com team metadata (logo, color, settings)');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'is_active'], 'idx_company_active');
            $table->index(['branch_id'], 'idx_branch');
            $table->unique(['company_id', 'calcom_team_id'], 'unique_company_team');
            $table->unique(['company_id', 'calcom_team_slug'], 'unique_company_slug');

            // Ensure only one default team per company
            $table->index(['company_id', 'is_default'], 'idx_company_default');
        });

        // Add comment for multi-tenant security review
        DB::statement("COMMENT ON TABLE calcom_teams IS 'Multi-tenant isolated: All queries MUST be scoped by company_id via BelongsToCompany trait'");
    }

    public function down(): void
    {
        Schema::dropIfExists('calcom_teams');
    }
};
```

### 1.2 Junction Table: `calcom_team_event_types`

Maps Cal.com event types (services) to teams. Enables cross-team service availability.

```php
// Migration: database/migrations/2025_11_10_000001_create_calcom_team_event_types_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maps Cal.com Event Types to Teams
     *
     * Replaces hardcoded team_id in config with dynamic team-service relationships.
     * Enables: Service X available on Team A and Team B.
     */
    public function up(): void
    {
        Schema::create('calcom_team_event_types', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('calcom_team_id')
                ->constrained('calcom_teams')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('calcom_event_type_id')
                ->comment('Cal.com API Event Type ID');

            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete()
                ->comment('Link to internal service (if mapped)');

            // Event Type Metadata (cached from Cal.com)
            $table->string('event_type_title');
            $table->string('event_type_slug');
            $table->integer('duration_minutes')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);

            // Sync Tracking
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['calcom_team_id', 'calcom_event_type_id'], 'unique_team_event');
            $table->index('service_id', 'idx_service');
            $table->index(['calcom_team_id', 'is_active'], 'idx_team_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calcom_team_event_types');
    }
};
```

### 1.3 Data Migration: Backfill from `branches.calcom_team_id`

Migrate existing branch team assignments to new structure.

```php
// Migration: database/migrations/2025_11_10_000002_backfill_calcom_teams_from_branches.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\CalcomTeam;

return new class extends Migration
{
    /**
     * Backfill calcom_teams table from existing branches.calcom_team_id
     *
     * Ensures backward compatibility with existing data structure.
     */
    public function up(): void
    {
        // Get all branches with calcom_team_id set
        $branches = DB::table('branches')
            ->whereNotNull('calcom_team_id')
            ->get();

        foreach ($branches as $branch) {
            // Check if team already exists for this company
            $existingTeam = DB::table('calcom_teams')
                ->where('company_id', $branch->company_id)
                ->where('calcom_team_id', $branch->calcom_team_id)
                ->first();

            if (!$existingTeam) {
                // Create new team record
                DB::table('calcom_teams')->insert([
                    'company_id' => $branch->company_id,
                    'branch_id' => $branch->id,
                    'calcom_team_id' => $branch->calcom_team_id,
                    'calcom_team_slug' => config('calcom.team_slug') ?? 'default',
                    'name' => $branch->name . ' Team',
                    'timezone' => $branch->timezone ?? 'Europe/Berlin',
                    'is_active' => true,
                    'is_default' => true, // First team is default
                    'sync_status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('Backfilled Cal.com team from branch', [
                    'company_id' => $branch->company_id,
                    'branch_id' => $branch->id,
                    'calcom_team_id' => $branch->calcom_team_id
                ]);
            }
        }
    }

    public function down(): void
    {
        // Rollback: Delete teams that were backfilled
        DB::table('calcom_teams')
            ->whereNotNull('branch_id')
            ->delete();
    }
};
```

---

## 2. Eloquent Model Design

### 2.1 CalcomTeam Model

```php
// app/Models/CalcomTeam.php

<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * CalcomTeam Model
 *
 * Represents a Cal.com team within a company's context.
 *
 * SECURITY: Multi-tenant isolated via BelongsToCompany trait
 * All queries automatically scoped to current company
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $branch_id
 * @property int $calcom_team_id
 * @property string $calcom_team_slug
 * @property string $name
 * @property string|null $description
 * @property string $timezone
 * @property bool $is_active
 * @property bool $is_default
 * @property \Carbon\Carbon|null $last_synced_at
 * @property string $sync_status
 * @property string|null $sync_error
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Company $company
 * @property-read Branch|null $branch
 * @property-read \Illuminate\Database\Eloquent\Collection|CalcomTeamEventType[] $eventTypes
 */
class CalcomTeam extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'calcom_team_id',
        'calcom_team_slug',
        'name',
        'description',
        'timezone',
        'is_active',
        'is_default',
        'last_synced_at',
        'sync_status',
        'sync_error',
        'metadata',
    ];

    protected $casts = [
        'calcom_team_id' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_synced_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_default' => false,
        'sync_status' => 'pending',
        'timezone' => 'Europe/Berlin',
    ];

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function eventTypes(): HasMany
    {
        return $this->hasMany(CalcomTeamEventType::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'calcom_team_id', 'calcom_team_id')
            ->where('company_id', $this->company_id);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Accessors & Mutators

    /**
     * Ensure only one default team per company
     */
    protected function isDefault(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if ($value) {
                    // Unset other defaults for this company
                    static::where('company_id', $this->company_id)
                        ->where('id', '!=', $this->id)
                        ->update(['is_default' => false]);
                }
                return $value;
            }
        );
    }

    // Helper Methods

    /**
     * Check if team has been synced successfully
     */
    public function isSynced(): bool
    {
        return $this->sync_status === 'synced'
            && $this->last_synced_at !== null;
    }

    /**
     * Mark team as synced
     */
    public function markAsSynced(): void
    {
        $this->update([
            'sync_status' => 'synced',
            'last_synced_at' => now(),
            'sync_error' => null,
        ]);
    }

    /**
     * Mark team sync as failed
     */
    public function markAsSyncFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'error',
            'sync_error' => $error,
        ]);
    }

    /**
     * Get cache key for this team's data
     */
    public function getCacheKey(string $suffix = ''): string
    {
        return sprintf(
            'company:%d:calcom_team:%d%s',
            $this->company_id,
            $this->id,
            $suffix ? ':' . $suffix : ''
        );
    }
}
```

### 2.2 CalcomTeamEventType Model

```php
// app/Models/CalcomTeamEventType.php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CalcomTeamEventType Model
 *
 * Junction table for Team ↔ EventType relationships
 * Caches Cal.com event type metadata per team
 *
 * @property int $id
 * @property int $calcom_team_id
 * @property int $calcom_event_type_id
 * @property int|null $service_id
 * @property string $event_type_title
 * @property string $event_type_slug
 * @property int|null $duration_minutes
 * @property float|null $price
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CalcomTeamEventType extends Model
{
    protected $fillable = [
        'calcom_team_id',
        'calcom_event_type_id',
        'service_id',
        'event_type_title',
        'event_type_slug',
        'duration_minutes',
        'price',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'calcom_team_id' => 'integer',
        'calcom_event_type_id' => 'integer',
        'duration_minutes' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    // Relationships

    public function calcomTeam(): BelongsTo
    {
        return $this->belongsTo(CalcomTeam::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

---

## 3. Service Layer Architecture

### 3.1 CalcomTeamService

```php
// app/Services/Calcom/CalcomTeamService.php

<?php

namespace App\Services\Calcom;

use App\Models\CalcomTeam;
use App\Models\CalcomTeamEventType;
use App\Services\CalcomService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * CalcomTeamService
 *
 * Manages Cal.com team operations with caching and multi-tenant isolation
 *
 * Responsibilities:
 * - Fetch teams from Cal.com API
 * - Sync team data with local database
 * - Cache team information (Redis)
 * - Manage team event types
 */
class CalcomTeamService
{
    protected CalcomService $calcomService;

    public function __construct(CalcomService $calcomService)
    {
        $this->calcomService = $calcomService;
    }

    /**
     * Get all teams for a company (cached)
     *
     * @param int $companyId Company ID
     * @param bool $activeOnly Filter to active teams only
     * @return Collection<CalcomTeam>
     */
    public function getTeamsForCompany(int $companyId, bool $activeOnly = true): Collection
    {
        $cacheKey = sprintf('company:%d:calcom_teams:all', $companyId);

        return Cache::remember($cacheKey, 3600, function () use ($companyId, $activeOnly) {
            $query = CalcomTeam::where('company_id', $companyId);

            if ($activeOnly) {
                $query->active();
            }

            return $query->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get teams for a specific branch
     *
     * @param int $branchId Branch ID
     * @return Collection<CalcomTeam>
     */
    public function getTeamsForBranch(int $branchId): Collection
    {
        $cacheKey = sprintf('branch:%d:calcom_teams', $branchId);

        return Cache::remember($cacheKey, 3600, function () use ($branchId) {
            return CalcomTeam::forBranch($branchId)
                ->active()
                ->get();
        });
    }

    /**
     * Get default team for a company
     *
     * @param int $companyId Company ID
     * @return CalcomTeam|null
     */
    public function getDefaultTeam(int $companyId): ?CalcomTeam
    {
        $cacheKey = sprintf('company:%d:calcom_teams:default', $companyId);

        return Cache::remember($cacheKey, 3600, function () use ($companyId) {
            return CalcomTeam::where('company_id', $companyId)
                ->default()
                ->active()
                ->first();
        });
    }

    /**
     * Fetch all teams from Cal.com API
     *
     * Uses Cal.com v2 API: GET /teams
     *
     * @return array Cal.com teams data
     * @throws \Exception
     */
    public function fetchTeamsFromCalcom(): array
    {
        try {
            $response = $this->calcomService->fetchTeams();

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch teams from Cal.com: ' . $response->body());
            }

            $data = $response->json();

            // V2 API returns teams in 'data' field
            $teams = $data['data'] ?? [];

            Log::info('Fetched teams from Cal.com', [
                'count' => count($teams)
            ]);

            return $teams;

        } catch (\Exception $e) {
            Log::error('Failed to fetch teams from Cal.com', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sync teams from Cal.com to database
     *
     * @param int $companyId Company ID
     * @return array Sync results
     */
    public function syncTeamsForCompany(int $companyId): array
    {
        try {
            $teams = $this->fetchTeamsFromCalcom();

            $synced = 0;
            $errors = [];

            foreach ($teams as $teamData) {
                try {
                    $this->createOrUpdateTeam($companyId, $teamData);
                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'team_id' => $teamData['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Invalidate cache
            $this->invalidateTeamCache($companyId);

            return [
                'success' => true,
                'synced' => $synced,
                'errors' => $errors,
                'total' => count($teams)
            ];

        } catch (\Exception $e) {
            Log::error('Team sync failed for company', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create or update team from Cal.com data
     *
     * @param int $companyId Company ID
     * @param array $teamData Cal.com team data
     * @return CalcomTeam
     */
    protected function createOrUpdateTeam(int $companyId, array $teamData): CalcomTeam
    {
        $team = CalcomTeam::updateOrCreate(
            [
                'company_id' => $companyId,
                'calcom_team_id' => $teamData['id']
            ],
            [
                'calcom_team_slug' => $teamData['slug'] ?? $teamData['username'] ?? 'default',
                'name' => $teamData['name'] ?? 'Team ' . $teamData['id'],
                'timezone' => $teamData['timeZone'] ?? 'Europe/Berlin',
                'is_active' => true,
                'metadata' => [
                    'logo' => $teamData['logo'] ?? null,
                    'bio' => $teamData['bio'] ?? null,
                    'calcom_data' => $teamData
                ],
            ]
        );

        $team->markAsSynced();

        Log::info('Synced Cal.com team', [
            'company_id' => $companyId,
            'team_id' => $team->id,
            'calcom_team_id' => $team->calcom_team_id
        ]);

        return $team;
    }

    /**
     * Fetch event types for a specific team
     *
     * @param CalcomTeam $team
     * @return array Event types data
     */
    public function fetchEventTypesForTeam(CalcomTeam $team): array
    {
        try {
            $response = $this->calcomService->fetchTeamEventTypes($team->calcom_team_id);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch event types: ' . $response->body());
            }

            $data = $response->json();
            $eventTypes = $data['data'] ?? [];

            Log::info('Fetched event types for team', [
                'team_id' => $team->id,
                'calcom_team_id' => $team->calcom_team_id,
                'count' => count($eventTypes)
            ]);

            return $eventTypes;

        } catch (\Exception $e) {
            Log::error('Failed to fetch event types for team', [
                'team_id' => $team->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sync event types for a team
     *
     * @param CalcomTeam $team
     * @return array Sync results
     */
    public function syncEventTypesForTeam(CalcomTeam $team): array
    {
        try {
            $eventTypes = $this->fetchEventTypesForTeam($team);

            $synced = 0;

            foreach ($eventTypes as $eventTypeData) {
                CalcomTeamEventType::updateOrCreate(
                    [
                        'calcom_team_id' => $team->id,
                        'calcom_event_type_id' => $eventTypeData['id']
                    ],
                    [
                        'event_type_title' => $eventTypeData['title'],
                        'event_type_slug' => $eventTypeData['slug'],
                        'duration_minutes' => $eventTypeData['length'] ?? null,
                        'price' => $eventTypeData['price'] ?? null,
                        'is_active' => !($eventTypeData['hidden'] ?? false),
                        'last_synced_at' => now(),
                    ]
                );

                $synced++;
            }

            // Invalidate event types cache
            Cache::forget($team->getCacheKey('event_types'));

            return [
                'success' => true,
                'synced' => $synced,
                'total' => count($eventTypes)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Invalidate all team-related caches for a company
     *
     * @param int $companyId Company ID
     */
    public function invalidateTeamCache(int $companyId): void
    {
        $keys = [
            sprintf('company:%d:calcom_teams:all', $companyId),
            sprintf('company:%d:calcom_teams:default', $companyId),
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Log::debug('Invalidated team cache', [
            'company_id' => $companyId,
            'keys_cleared' => count($keys)
        ]);
    }
}
```

### 3.2 Extension to CalcomService

Add team-related methods to existing `CalcomService`:

```php
// Add to: app/Services/CalcomService.php

/**
 * Fetch all teams from Cal.com
 *
 * Uses Cal.com v2 API: GET /teams
 *
 * @return Response
 */
public function fetchTeams(): Response
{
    $fullUrl = $this->baseUrl . '/teams';

    $resp = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey,
        'cal-api-version' => config('services.calcom.api_version', '2024-08-13')
    ])->acceptJson()->timeout(5)->get($fullUrl);

    Log::channel('calcom')->debug('[Cal.com] Fetch Teams Response:', [
        'status' => $resp->status(),
        'count' => count($resp->json()['data'] ?? [])
    ]);

    return $resp;
}

/**
 * Fetch event types for a specific team
 *
 * Uses Cal.com v2 API: GET /teams/{teamId}/event-types
 *
 * @param int $teamId Cal.com Team ID
 * @return Response
 */
public function fetchTeamEventTypes(int $teamId): Response
{
    $fullUrl = $this->baseUrl . '/teams/' . $teamId . '/event-types';

    $resp = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey,
        'cal-api-version' => config('services.calcom.api_version', '2024-08-13')
    ])->acceptJson()->timeout(5)->get($fullUrl);

    Log::channel('calcom')->debug('[Cal.com] Fetch Team Event Types Response:', [
        'team_id' => $teamId,
        'status' => $resp->status(),
        'count' => count($resp->json()['data'] ?? [])
    ]);

    return $resp;
}
```

---

## 4. API Controller Design

### 4.1 CalcomTeamController

```php
// app/Http/Controllers/Api/CalcomTeamController.php

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Calcom\CalcomTeamService;
use App\Models\CalcomTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CalcomTeamController
 *
 * API endpoints for Cal.com team management
 *
 * SECURITY: All endpoints enforce company-scoped access
 * Users can only access teams belonging to their company
 */
class CalcomTeamController extends Controller
{
    protected CalcomTeamService $teamService;

    public function __construct(CalcomTeamService $teamService)
    {
        $this->teamService = $teamService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all teams for current user's company
     *
     * GET /api/calcom/teams
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Filter by branch if provided
        if ($request->has('branch_id')) {
            $teams = $this->teamService->getTeamsForBranch($request->branch_id);
        } else {
            $teams = $this->teamService->getTeamsForCompany($companyId);
        }

        return response()->json([
            'success' => true,
            'data' => $teams->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'slug' => $team->calcom_team_slug,
                    'calcom_team_id' => $team->calcom_team_id,
                    'branch_id' => $team->branch_id,
                    'branch_name' => $team->branch?->name,
                    'is_active' => $team->is_active,
                    'is_default' => $team->is_default,
                    'timezone' => $team->timezone,
                    'synced_at' => $team->last_synced_at?->toIso8601String(),
                ];
            })
        ]);
    }

    /**
     * Get default team for current user's company
     *
     * GET /api/calcom/teams/default
     *
     * @return JsonResponse
     */
    public function getDefault(): JsonResponse
    {
        $user = Auth::user();
        $team = $this->teamService->getDefaultTeam($user->company_id);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'No default team configured'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->calcom_team_slug,
                'calcom_team_id' => $team->calcom_team_id,
                'timezone' => $team->timezone,
            ]
        ]);
    }

    /**
     * Get event types for a specific team
     *
     * GET /api/calcom/teams/{team}/event-types
     *
     * @param CalcomTeam $team
     * @return JsonResponse
     */
    public function getEventTypes(CalcomTeam $team): JsonResponse
    {
        // Security: Verify team belongs to user's company
        if ($team->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to team');
        }

        $eventTypes = $team->eventTypes()->active()->get();

        return response()->json([
            'success' => true,
            'data' => $eventTypes->map(function ($eventType) {
                return [
                    'id' => $eventType->calcom_event_type_id,
                    'title' => $eventType->event_type_title,
                    'slug' => $eventType->event_type_slug,
                    'duration' => $eventType->duration_minutes,
                    'price' => $eventType->price,
                    'service_id' => $eventType->service_id,
                ];
            })
        ]);
    }

    /**
     * Sync teams from Cal.com
     *
     * POST /api/calcom/teams/sync
     *
     * @return JsonResponse
     */
    public function sync(): JsonResponse
    {
        $user = Auth::user();

        // Authorization: Admin only
        if (!$user->hasRole('admin')) {
            abort(403, 'Only administrators can sync teams');
        }

        $result = $this->teamService->syncTeamsForCompany($user->company_id);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Sync event types for a team
     *
     * POST /api/calcom/teams/{team}/sync-event-types
     *
     * @param CalcomTeam $team
     * @return JsonResponse
     */
    public function syncEventTypes(CalcomTeam $team): JsonResponse
    {
        // Security: Verify team belongs to user's company
        if ($team->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to team');
        }

        // Authorization: Admin only
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Only administrators can sync event types');
        }

        $result = $this->teamService->syncEventTypesForTeam($team);

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
```

### 4.2 Route Registration

```php
// Add to: routes/api.php

use App\Http\Controllers\Api\CalcomTeamController;

Route::middleware('auth:sanctum')->prefix('calcom')->group(function () {
    // Team Management
    Route::get('/teams', [CalcomTeamController::class, 'index']);
    Route::get('/teams/default', [CalcomTeamController::class, 'default']);
    Route::get('/teams/{team}/event-types', [CalcomTeamController::class, 'getEventTypes']);

    // Admin Operations
    Route::middleware('role:admin')->group(function () {
        Route::post('/teams/sync', [CalcomTeamController::class, 'sync']);
        Route::post('/teams/{team}/sync-event-types', [CalcomTeamController::class, 'syncEventTypes']);
    });
});
```

---

## 5. Caching Strategy

### 5.1 Cache Key Structure

```
Pattern: company:{company_id}:calcom_team:{team_id}:{resource}

Examples:
- company:1:calcom_teams:all                  // All teams for company
- company:1:calcom_teams:default              // Default team
- company:1:calcom_team:5:event_types         // Event types for team 5
- branch:3:calcom_teams                       // Teams for branch 3
```

### 5.2 TTL Strategy

| Cache Type | TTL | Invalidation Trigger |
|-----------|-----|----------------------|
| Team List | 1 hour | Team sync, team CRUD |
| Default Team | 1 hour | Team is_default change |
| Event Types | 5 minutes | Event type sync, service update |
| Branch Teams | 1 hour | Branch-team assignment change |

### 5.3 Cache Invalidation Events

```php
// app/Observers/CalcomTeamObserver.php

<?php

namespace App\Observers;

use App\Models\CalcomTeam;
use Illuminate\Support\Facades\Cache;

class CalcomTeamObserver
{
    /**
     * Handle the CalcomTeam "saved" event.
     */
    public function saved(CalcomTeam $team): void
    {
        $this->invalidateCache($team);
    }

    /**
     * Handle the CalcomTeam "deleted" event.
     */
    public function deleted(CalcomTeam $team): void
    {
        $this->invalidateCache($team);
    }

    /**
     * Invalidate all related cache keys
     */
    protected function invalidateCache(CalcomTeam $team): void
    {
        $keys = [
            sprintf('company:%d:calcom_teams:all', $team->company_id),
            sprintf('company:%d:calcom_teams:default', $team->company_id),
            $team->getCacheKey('event_types'),
        ];

        if ($team->branch_id) {
            $keys[] = sprintf('branch:%d:calcom_teams', $team->branch_id);
        }

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
```

Register observer in `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php

use App\Models\CalcomTeam;
use App\Observers\CalcomTeamObserver;

public function boot(): void
{
    CalcomTeam::observe(CalcomTeamObserver::class);
}
```

---

## 6. Multi-Tenant Security

### 6.1 Row-Level Security (RLS)

**Strategy**: All team queries automatically scoped by `company_id` via `BelongsToCompany` trait.

```php
// Trait already applied in CalcomTeam model
use BelongsToCompany;

// Automatic query scoping:
CalcomTeam::all();  // SELECT * FROM calcom_teams WHERE company_id = ?
```

### 6.2 API Route Protection

```php
// Middleware Stack
Route::middleware([
    'auth:sanctum',        // Authentication required
    'companyscope',        // Multi-tenant isolation
])->group(function () {
    // Team endpoints
});
```

### 6.3 Explicit Company Checks

```php
// In controller methods:
if ($team->company_id !== Auth::user()->company_id) {
    abort(403, 'Unauthorized access to team');
}
```

### 6.4 Cache Isolation

All cache keys include `company_id` to prevent cross-tenant data leakage:

```php
// SECURE: Company-scoped cache key
$cacheKey = sprintf('company:%d:calcom_teams:all', $companyId);

// INSECURE: Global cache key (DO NOT USE)
$cacheKey = 'calcom_teams:all';  // ❌ BAD: Cross-tenant leak
```

---

## 7. Cal.com API Integration Plan

### 7.1 Required Endpoints

| Endpoint | Method | Purpose | Auth |
|----------|--------|---------|------|
| `/teams` | GET | Fetch all teams | Bearer Token |
| `/teams/{id}` | GET | Fetch single team | Bearer Token |
| `/teams/{id}/event-types` | GET | Fetch team event types | Bearer Token |

### 7.2 API Request Examples

**Fetch All Teams:**
```bash
curl -X GET "https://api.cal.com/v2/teams" \
  -H "Authorization: Bearer $CALCOM_API_KEY" \
  -H "cal-api-version: 2024-08-13"
```

**Fetch Team Event Types:**
```bash
curl -X GET "https://api.cal.com/v2/teams/34209/event-types" \
  -H "Authorization: Bearer $CALCOM_API_KEY" \
  -H "cal-api-version: 2024-08-13"
```

### 7.3 Response Structures

**Teams Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 34209,
      "name": "Friseur Team",
      "slug": "friseur",
      "username": "friseur",
      "bio": "Professional hair styling",
      "logo": "https://...",
      "timeZone": "Europe/Berlin"
    }
  ]
}
```

**Event Types Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 123456,
      "title": "Herrenhaarschnitt",
      "slug": "herrenhaarschnitt",
      "length": 30,
      "price": 25.00,
      "currency": "EUR",
      "hidden": false
    }
  ]
}
```

### 7.4 Error Handling

```php
try {
    $response = Http::get($fullUrl);

    if (!$response->successful()) {
        throw CalcomApiException::fromResponse($response, $fullUrl, [], 'GET');
    }

    return $response->json();

} catch (\Illuminate\Http\Client\ConnectionException $e) {
    // Network error: timeout, DNS, connection refused
    Log::error('Cal.com API network error', [
        'endpoint' => $fullUrl,
        'error' => $e->getMessage()
    ]);
    throw CalcomApiException::networkError($fullUrl, [], $e);

} catch (CalcomApiException $e) {
    // Cal.com API error: 4xx, 5xx
    Log::error('Cal.com API error', [
        'endpoint' => $fullUrl,
        'status' => $e->getCode(),
        'error' => $e->getMessage()
    ]);
    throw $e;
}
```

---

## 8. Backward Compatibility

### 8.1 Existing `branches.calcom_team_id` Column

**Strategy**: Keep column, use as fallback if no team mapping exists.

```php
// In CalcomTeamService

public function getTeamForBranch(Branch $branch): ?CalcomTeam
{
    // Priority 1: Explicit team mapping
    $team = CalcomTeam::forBranch($branch->id)->active()->first();

    if ($team) {
        return $team;
    }

    // Priority 2: Fallback to branch.calcom_team_id
    if ($branch->calcom_team_id) {
        return CalcomTeam::where('company_id', $branch->company_id)
            ->where('calcom_team_id', $branch->calcom_team_id)
            ->active()
            ->first();
    }

    // Priority 3: Default team
    return $this->getDefaultTeam($branch->company_id);
}
```

### 8.2 Config Migration

Existing `config/calcom.php` values used as defaults during data migration:

```php
// Migration uses config values
$team->calcom_team_slug = config('calcom.team_slug') ?? 'default';
$team->calcom_team_id = config('calcom.team_id') ?? 34209;
```

---

## 9. Testing Strategy

### 9.1 Unit Tests

```php
// tests/Unit/Services/CalcomTeamServiceTest.php

public function test_get_teams_for_company_with_cache()
{
    $company = Company::factory()->create();
    $teams = CalcomTeam::factory()->count(3)->create(['company_id' => $company->id]);

    // First call: Cache miss
    $result1 = $this->teamService->getTeamsForCompany($company->id);
    $this->assertCount(3, $result1);

    // Second call: Cache hit
    $result2 = $this->teamService->getTeamsForCompany($company->id);
    $this->assertEquals($result1, $result2);

    // Verify cache key exists
    $cacheKey = sprintf('company:%d:calcom_teams:all', $company->id);
    $this->assertTrue(Cache::has($cacheKey));
}

public function test_multi_tenant_isolation()
{
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $team1 = CalcomTeam::factory()->create(['company_id' => $company1->id]);
    $team2 = CalcomTeam::factory()->create(['company_id' => $company2->id]);

    $teams1 = $this->teamService->getTeamsForCompany($company1->id);
    $teams2 = $this->teamService->getTeamsForCompany($company2->id);

    // Company 1 should only see their team
    $this->assertCount(1, $teams1);
    $this->assertEquals($team1->id, $teams1->first()->id);

    // Company 2 should only see their team
    $this->assertCount(1, $teams2);
    $this->assertEquals($team2->id, $teams2->first()->id);
}
```

### 9.2 Integration Tests

```php
// tests/Feature/Api/CalcomTeamControllerTest.php

public function test_get_teams_requires_authentication()
{
    $response = $this->getJson('/api/calcom/teams');
    $response->assertStatus(401);
}

public function test_user_can_only_access_their_company_teams()
{
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $user = User::factory()->create(['company_id' => $company1->id]);

    $team1 = CalcomTeam::factory()->create(['company_id' => $company1->id]);
    $team2 = CalcomTeam::factory()->create(['company_id' => $company2->id]);

    $response = $this->actingAs($user)->getJson('/api/calcom/teams');

    $response->assertStatus(200);
    $data = $response->json('data');

    // Should only see company1 team
    $this->assertCount(1, $data);
    $this->assertEquals($team1->id, $data[0]['id']);
}
```

---

## 10. Deployment Checklist

### 10.1 Pre-Deployment

- [ ] Run migrations on staging:
  ```bash
  php artisan migrate --path=database/migrations/2025_11_10_000000_create_calcom_teams_table.php
  php artisan migrate --path=database/migrations/2025_11_10_000001_create_calcom_team_event_types_table.php
  php artisan migrate --path=database/migrations/2025_11_10_000002_backfill_calcom_teams_from_branches.php
  ```

- [ ] Verify backfill data:
  ```sql
  SELECT company_id, COUNT(*) FROM calcom_teams GROUP BY company_id;
  ```

- [ ] Test API endpoints on staging
- [ ] Verify multi-tenant isolation
- [ ] Load test caching behavior

### 10.2 Deployment

- [ ] Deploy code to production
- [ ] Run migrations:
  ```bash
  php artisan migrate --force
  ```

- [ ] Clear caches:
  ```bash
  php artisan cache:clear
  php artisan config:clear
  ```

- [ ] Verify backfill:
  ```bash
  php artisan tinker
  >>> CalcomTeam::count()
  >>> CalcomTeam::with('company')->get()
  ```

### 10.3 Post-Deployment

- [ ] Smoke test API endpoints
- [ ] Monitor logs for errors
- [ ] Verify cache hit rates
- [ ] Test team sync for each company

---

## 11. File Summary

### New Files to Create

```
database/migrations/
  2025_11_10_000000_create_calcom_teams_table.php
  2025_11_10_000001_create_calcom_team_event_types_table.php
  2025_11_10_000002_backfill_calcom_teams_from_branches.php

app/Models/
  CalcomTeam.php
  CalcomTeamEventType.php

app/Services/Calcom/
  CalcomTeamService.php

app/Http/Controllers/Api/
  CalcomTeamController.php

app/Observers/
  CalcomTeamObserver.php

tests/Unit/Services/
  CalcomTeamServiceTest.php

tests/Feature/Api/
  CalcomTeamControllerTest.php
```

### Files to Modify

```
app/Services/CalcomService.php
  + fetchTeams()
  + fetchTeamEventTypes()

routes/api.php
  + Team management routes

app/Providers/AppServiceProvider.php
  + CalcomTeam::observe(CalcomTeamObserver::class)

config/services.php (optional)
  + team_selector config
```

---

## 12. Performance Considerations

### 12.1 Query Optimization

- Indexed columns: `company_id`, `is_active`, `branch_id`, `calcom_team_id`
- Eager loading: `$teams->load('branch', 'eventTypes')`
- Cache warm-up: Pre-populate cache during sync

### 12.2 API Rate Limiting

Cal.com API rate limits:
- **Free**: 30 requests/minute
- **Pro**: 300 requests/minute

**Mitigation**:
- Cache team data (1 hour TTL)
- Batch sync operations
- Use request coalescing for concurrent requests

### 12.3 Monitoring

Track metrics:
- Team sync success rate
- Cache hit ratio (target: >95%)
- API response time (target: <100ms)
- Multi-tenant query performance

---

## 13. Security Audit Checklist

- [x] All models use `BelongsToCompany` trait
- [x] API routes enforce `auth:sanctum` middleware
- [x] Cache keys include `company_id` for isolation
- [x] Controller methods verify company ownership
- [x] Database foreign keys enforce cascade deletes
- [x] No global team queries without company scope
- [x] Admin operations require role check
- [x] Soft deletes prevent accidental data loss

---

## Appendix: Quick Reference

### Key Commands

```bash
# Sync teams for a company
$teamService->syncTeamsForCompany($companyId);

# Get default team
$team = $teamService->getDefaultTeam($companyId);

# Get teams for branch
$teams = $teamService->getTeamsForBranch($branchId);

# Sync event types for team
$teamService->syncEventTypesForTeam($team);

# Invalidate cache
$teamService->invalidateTeamCache($companyId);
```

### API Endpoints

```
GET    /api/calcom/teams                        # List all teams
GET    /api/calcom/teams/default                # Get default team
GET    /api/calcom/teams/{team}/event-types     # Get team event types
POST   /api/calcom/teams/sync                   # Sync teams from Cal.com (admin)
POST   /api/calcom/teams/{team}/sync-event-types # Sync event types (admin)
```

---

**End of Architecture Design**
