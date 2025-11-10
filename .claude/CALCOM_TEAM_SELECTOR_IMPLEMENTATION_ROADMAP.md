# Team-Selector Implementation Roadmap

**Date**: 2025-11-10
**Status**: Design Complete - Ready for Implementation
**Related Docs**:
- Design Spec: `CALCOM_TEAM_SELECTOR_UX_DESIGN.md`
- Visual Flows: `CALCOM_TEAM_SELECTOR_VISUAL_FLOWS.md`

---

## Executive Summary

This roadmap provides a step-by-step implementation guide for the enhanced Team-Selector component, broken into manageable phases with clear deliverables, testing requirements, and success criteria.

**Timeline Estimate**: 3-4 weeks (with proper testing)
**Complexity**: Medium
**Risk Level**: Low (backward compatible, progressive enhancement)

---

## Phase 1: Enhanced Data Layer (Backend)

**Duration**: 3-5 days
**Focus**: API enhancement without breaking existing functionality

### 1.1 Database Query Optimization

**Files to Modify**:
- `/var/www/api-gateway/app/Services/Calcom/BranchCalcomConfigService.php`

**Tasks**:

```php
// 1. Add getUserBranchesEnhanced() method
public function getUserBranchesEnhanced(User $user): array
{
    // If user has specific branch (company_manager)
    if ($user->branch_id && $user->branch) {
        return $this->formatBranchesEnhanced(collect([$user->branch]));
    }

    // If company owner/admin, get all branches
    if ($user->company) {
        $branches = $user->company->branches()
            ->with(['services', 'staff']) // Eager load to prevent N+1
            ->orderBy('name')
            ->get();

        return $this->formatBranchesEnhanced($branches);
    }

    return [];
}

// 2. Create formatBranchesEnhanced() helper
private function formatBranchesEnhanced(Collection $branches): array
{
    return $branches->map(function (Branch $branch) {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
            'city' => $branch->city,
            'is_default' => false, // Will be set by caller
            'is_active' => $branch->is_active,
            'services_count' => $branch->services()->count(),
            'active_services_count' => $branch->activeServices()->count(),
            'staff_count' => $branch->staff()->count(),
            'calcom_team_id' => $branch->calcom_team_id,
            'integration_status' => $this->getIntegrationStatus($branch),
            'last_sync_at' => $branch->integrations_tested_at?->toIso8601String(),
        ];
    })->toArray();
}

// 3. Add getIntegrationStatus() helper
private function getIntegrationStatus(Branch $branch): string
{
    if (!$branch->is_active) {
        return 'inactive';
    }

    if (!$branch->calcom_team_id) {
        return 'not_configured';
    }

    // Check if services exist and are synced
    $hasActiveServices = $branch->activeServices()->exists();
    if (!$hasActiveServices) {
        return 'pending';
    }

    // If integration_status field exists, use it
    if ($branch->integration_status === 'connected') {
        return 'connected';
    }

    if ($branch->integration_status === 'error') {
        return 'error';
    }

    return 'pending';
}
```

**Testing**:
```bash
# Unit test
php artisan test --filter=BranchCalcomConfigServiceTest::test_enhanced_config

# Manual test via Tinker
php artisan tinker
>>> $user = User::find(1);
>>> $service = app(BranchCalcomConfigService::class);
>>> $service->getUserBranchesEnhanced($user);
```

### 1.2 New API Endpoint

**Files to Modify**:
- `/var/www/api-gateway/app/Http/Controllers/Api/CalcomAtomsController.php`
- `/var/www/api-gateway/routes/api.php`

**Tasks**:

```php
// Controller: Add configEnhanced() method
public function configEnhanced(Request $request): JsonResponse
{
    try {
        $user = $request->user();

        // Eager load to prevent N+1
        $user->load(['company.branches', 'branch']);

        $branches = $this->configService->getUserBranchesEnhanced($user);

        // Mark default branch
        $defaultBranchId = $user->branch_id ?? $user->company?->branches()->first()?->id;
        foreach ($branches as &$branch) {
            if ($branch['id'] === $defaultBranchId) {
                $branch['is_default'] = true;
            }
        }

        return response()->json([
            'teams' => $branches,
            'default_team_id' => $defaultBranchId,
            'user' => [
                'can_access_all_teams' => $user->hasRole(['super_admin', 'company_owner']),
                'role' => $user->roles->first()?->name ?? 'user',
            ],
        ]);
    } catch (\Exception $e) {
        logger()->error('Enhanced config error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'error' => 'Failed to load configuration',
            'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
        ], 500);
    }
}

// Route: Add new endpoint
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/calcom-atoms/config/enhanced', [CalcomAtomsController::class, 'configEnhanced']);
});
```

**Testing**:
```bash
# API test with authentication
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost/api/calcom-atoms/config/enhanced | jq

# Expected response structure:
{
  "teams": [
    {
      "id": 1,
      "name": "Friseur Salon Berlin",
      "city": "Berlin",
      "services_count": 12,
      "active_services_count": 10,
      "staff_count": 5,
      "integration_status": "connected"
    }
  ],
  "default_team_id": 1,
  "user": {
    "can_access_all_teams": true,
    "role": "super_admin"
  }
}
```

### 1.3 Caching Strategy

**Files to Create**:
- `/var/www/api-gateway/app/Services/Calcom/BranchConfigCache.php`

**Tasks**:

```php
namespace App\Services\Calcom;

use Illuminate\Support\Facades\Cache;
use App\Models\User;

class BranchConfigCache
{
    private const TTL = 300; // 5 minutes
    private const PREFIX = 'branch_config_enhanced';

    public function get(User $user): ?array
    {
        $key = $this->getCacheKey($user);
        return Cache::get($key);
    }

    public function put(User $user, array $config): void
    {
        $key = $this->getCacheKey($user);
        Cache::put($key, $config, self::TTL);
    }

    public function forget(User $user): void
    {
        $key = $this->getCacheKey($user);
        Cache::forget($key);
    }

    public function forgetAll(int $companyId): void
    {
        // Invalidate all user caches for this company
        Cache::tags([self::PREFIX, "company:{$companyId}"])->flush();
    }

    private function getCacheKey(User $user): string
    {
        return sprintf(
            '%s:user:%d:company:%d',
            self::PREFIX,
            $user->id,
            $user->company_id
        );
    }
}
```

**Integration**:
```php
// In CalcomAtomsController::configEnhanced()
public function configEnhanced(Request $request): JsonResponse
{
    $user = $request->user();

    // Try cache first
    $cached = $this->cache->get($user);
    if ($cached) {
        return response()->json($cached);
    }

    // Fetch fresh data
    $data = [
        'teams' => $this->configService->getUserBranchesEnhanced($user),
        // ... rest of response
    ];

    // Cache for next request
    $this->cache->put($user, $data);

    return response()->json($data);
}
```

### Phase 1 Deliverables

- [ ] `getUserBranchesEnhanced()` method implemented
- [ ] `configEnhanced()` API endpoint created
- [ ] Route registered in `routes/api.php`
- [ ] Caching layer implemented
- [ ] Unit tests written (min 80% coverage)
- [ ] API documented in Postman/Swagger
- [ ] Performance benchmarked (<500ms response)

### Phase 1 Success Criteria

✅ API returns correct data structure
✅ N+1 queries prevented (verified via Laravel Debugbar)
✅ Cache hit rate >80% after warmup
✅ Response time <500ms (P95)
✅ Backward compatibility maintained (existing endpoint unchanged)

---

## Phase 2: Core Team-Selector Component (Frontend)

**Duration**: 5-7 days
**Focus**: Replace BranchSelector with enhanced TeamSelector

### 2.1 Component Structure

**Files to Create**:
```
resources/js/components/calcom/
├── TeamSelector.jsx              (Main container)
├── TeamSelectorTrigger.jsx       (Dropdown button)
├── TeamSelectorDropdown.jsx      (Dropdown overlay)
├── TeamOption.jsx                (Individual team item)
├── TeamBadge.jsx                 (Service count badge)
├── StatusIndicator.jsx           (Integration status dot)
└── hooks/
    ├── useTeamSelector.js        (State management)
    └── useTeamPersistence.js     (localStorage)
```

### 2.2 Implementation Steps

**Step 1: Create Core Hook** (`useTeamSelector.js`)

```javascript
import { useState, useEffect, useCallback } from 'react';
import { CalcomBridge } from '../CalcomBridge';

export function useTeamSelector({ defaultTeamId, onTeamChange }) {
  const [teams, setTeams] = useState([]);
  const [selectedTeam, setSelectedTeam] = useState(null);
  const [isOpen, setIsOpen] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchTeams();
  }, []);

  const fetchTeams = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const data = await CalcomBridge.fetch('/api/calcom-atoms/config/enhanced');
      setTeams(data.teams);

      // Set default selection
      const defaultTeam = data.teams.find(t => t.id === (defaultTeamId || data.default_team_id));
      if (defaultTeam) {
        setSelectedTeam(defaultTeam);
      }
    } catch (err) {
      console.error('Failed to fetch teams:', err);
      setError(err);
    } finally {
      setLoading(false);
    }
  }, [defaultTeamId]);

  const handleTeamSelect = useCallback((team) => {
    setSelectedTeam(team);
    setIsOpen(false);
    onTeamChange?.(team.id, team);

    CalcomBridge.emit('team-changed', {
      team_id: team.id,
      team_name: team.name,
    });
  }, [onTeamChange]);

  return {
    teams,
    selectedTeam,
    isOpen,
    setIsOpen,
    loading,
    error,
    handleTeamSelect,
    refetch: fetchTeams,
  };
}
```

**Step 2: Create Main Component** (`TeamSelector.jsx`)

```javascript
import React, { useRef, useEffect } from 'react';
import TeamSelectorTrigger from './TeamSelectorTrigger';
import TeamSelectorDropdown from './TeamSelectorDropdown';
import { useTeamSelector } from './hooks/useTeamSelector';
import { useTeamPersistence } from './hooks/useTeamPersistence';
import LoadingState from './LoadingState';
import ErrorState from './ErrorState';

export default function TeamSelector({
  defaultTeamId = null,
  enableSearch = true,
  enablePersistence = true,
  onTeamChange,
  className = ''
}) {
  const dropdownRef = useRef(null);

  const {
    teams,
    selectedTeam,
    isOpen,
    setIsOpen,
    loading,
    error,
    handleTeamSelect,
    refetch,
  } = useTeamSelector({ defaultTeamId, onTeamChange });

  // Persistence layer
  useTeamPersistence(selectedTeam, teams, enablePersistence);

  // Close on outside click
  useEffect(() => {
    if (!isOpen) return;

    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [isOpen, setIsOpen]);

  // Loading state
  if (loading) {
    return (
      <div className={`team-selector-loading ${className}`}>
        <LoadingState message="Loading teams..." />
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <ErrorState
        message="Failed to load teams"
        onRetry={refetch}
      />
    );
  }

  // Hide if single team (auto-selected)
  if (teams.length <= 1) {
    return null;
  }

  return (
    <div
      ref={dropdownRef}
      className={`team-selector-wrapper relative ${className}`}
    >
      <TeamSelectorTrigger
        selectedTeam={selectedTeam}
        isOpen={isOpen}
        onClick={() => setIsOpen(!isOpen)}
      />

      {isOpen && (
        <TeamSelectorDropdown
          teams={teams}
          selectedTeam={selectedTeam}
          onTeamSelect={handleTeamSelect}
          enableSearch={enableSearch}
          onClose={() => setIsOpen(false)}
        />
      )}
    </div>
  );
}
```

**Step 3: Create Trigger Component** (`TeamSelectorTrigger.jsx`)

```javascript
import React from 'react';
import TeamBadge from './TeamBadge';
import StatusIndicator from './StatusIndicator';

export default function TeamSelectorTrigger({ selectedTeam, isOpen, onClick }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className="
        flex items-center justify-between
        w-full px-3 py-2
        bg-white border border-gray-300 rounded-lg
        shadow-sm
        hover:bg-gray-50
        focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-primary-600
        transition duration-150 ease-in-out
      "
      aria-haspopup="listbox"
      aria-expanded={isOpen}
      aria-label="Select team or branch"
    >
      <div className="flex items-center gap-3 min-w-0 flex-1">
        {selectedTeam && (
          <>
            <StatusIndicator status={selectedTeam.integration_status} />

            <div className="min-w-0 flex-1">
              <div className="font-medium text-sm text-gray-900 truncate">
                {selectedTeam.name}
              </div>
              <div className="text-xs text-gray-600 truncate">
                {selectedTeam.city} • {selectedTeam.staff_count} staff
              </div>
            </div>

            <TeamBadge count={selectedTeam.active_services_count} />
          </>
        )}

        {!selectedTeam && (
          <span className="text-sm text-gray-500">Select a team...</span>
        )}
      </div>

      <svg
        className={`ml-2 h-5 w-5 text-gray-400 transition-transform duration-150 ${
          isOpen ? 'transform rotate-180' : ''
        }`}
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
      </svg>
    </button>
  );
}
```

**Step 4: Create Badge Component** (`TeamBadge.jsx`)

```javascript
import React from 'react';

export default function TeamBadge({ count }) {
  // Determine badge color based on service count
  const getBadgeColor = (count) => {
    if (count === 0) return 'bg-danger-100 text-danger-700';
    if (count < 5) return 'bg-warning-100 text-warning-700';
    return 'bg-success-100 text-success-700';
  };

  return (
    <span
      className={`
        inline-flex items-center justify-center
        min-w-[24px] h-6 px-2
        text-xs font-semibold
        rounded-full
        ${getBadgeColor(count)}
      `}
      aria-label={`${count} active services`}
    >
      {count}
    </span>
  );
}
```

**Step 5: Create Status Indicator** (`StatusIndicator.jsx`)

```javascript
import React from 'react';

const statusColors = {
  connected: 'bg-success-500',
  pending: 'bg-warning-500',
  not_configured: 'bg-gray-400',
  inactive: 'bg-gray-300',
  error: 'bg-danger-500',
};

const statusLabels = {
  connected: 'Connected to Cal.com',
  pending: 'Setup pending',
  not_configured: 'Not configured',
  inactive: 'Inactive',
  error: 'Connection error',
};

export default function StatusIndicator({ status }) {
  return (
    <span
      className={`
        inline-block
        w-2 h-2
        rounded-full
        ${statusColors[status] || statusColors.not_configured}
      `}
      aria-label={statusLabels[status]}
      title={statusLabels[status]}
    />
  );
}
```

### 2.3 Integration with CalcomBookerWidget

**File to Modify**: `/var/www/api-gateway/resources/js/components/calcom/CalcomBookerWidget.jsx`

**Changes**:

```javascript
// Replace import
- import BranchSelector from './BranchSelector';
+ import TeamSelector from './TeamSelector';

// Replace component usage (lines 172-178)
{enableBranchSelector && (
  <div className="mb-3 md:mb-4">
-   <BranchSelector
+   <TeamSelector
      defaultBranchId={branchId}
      onBranchChange={setBranchId}
+     enableSearch={true}
+     enablePersistence={true}
    />
  </div>
)}
```

### Phase 2 Deliverables

- [ ] `TeamSelector` component implemented
- [ ] `TeamSelectorTrigger` component implemented
- [ ] `TeamBadge` component implemented
- [ ] `StatusIndicator` component implemented
- [ ] `useTeamSelector` hook implemented
- [ ] Integration with CalcomBookerWidget complete
- [ ] Component renders without errors
- [ ] Service count badges display correctly
- [ ] Team selection updates booking widget

### Phase 2 Success Criteria

✅ Component renders on page load
✅ Teams fetched from enhanced API
✅ Service count badges display correct numbers
✅ Status indicators show correct colors
✅ Clicking team updates CalcomBookerWidget
✅ No console errors or warnings
✅ Backward compatible (falls back to single team if needed)

---

## Phase 3: Advanced Features

**Duration**: 4-6 days
**Focus**: Search, keyboard navigation, mobile optimization

### 3.1 Search Functionality

**Files to Create**:
- `resources/js/components/calcom/TeamSelectorSearch.jsx`
- `resources/js/components/calcom/hooks/useTeamSearch.js`

**Implementation**:

```javascript
// useTeamSearch.js
import { useState, useMemo } from 'react';
import { useDebouncedCallback } from 'use-debounce';

export function useTeamSearch(teams) {
  const [searchQuery, setSearchQuery] = useState('');

  const filteredTeams = useMemo(() => {
    if (!searchQuery.trim()) return teams;

    const query = searchQuery.toLowerCase();

    return teams.filter(team =>
      team.name.toLowerCase().includes(query) ||
      team.city.toLowerCase().includes(query) ||
      team.slug.toLowerCase().includes(query)
    );
  }, [teams, searchQuery]);

  const debouncedSetQuery = useDebouncedCallback(
    (value) => setSearchQuery(value),
    300
  );

  return {
    searchQuery,
    setSearchQuery: debouncedSetQuery,
    filteredTeams,
    hasResults: filteredTeams.length > 0,
  };
}
```

### 3.2 Keyboard Navigation

**File to Create**: `resources/js/components/calcom/hooks/useKeyboardNavigation.js`

```javascript
import { useState, useCallback, useEffect } from 'react';

export function useKeyboardNavigation(items, onSelect, isOpen) {
  const [focusedIndex, setFocusedIndex] = useState(0);

  useEffect(() => {
    if (!isOpen) {
      setFocusedIndex(0);
    }
  }, [isOpen]);

  const handleKeyDown = useCallback((event) => {
    if (!isOpen || items.length === 0) return;

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        setFocusedIndex((prev) => (prev + 1) % items.length);
        break;

      case 'ArrowUp':
        event.preventDefault();
        setFocusedIndex((prev) => (prev - 1 + items.length) % items.length);
        break;

      case 'Home':
        event.preventDefault();
        setFocusedIndex(0);
        break;

      case 'End':
        event.preventDefault();
        setFocusedIndex(items.length - 1);
        break;

      case 'Enter':
      case ' ':
        event.preventDefault();
        if (items[focusedIndex]) {
          onSelect(items[focusedIndex]);
        }
        break;

      default:
        break;
    }
  }, [isOpen, items, focusedIndex, onSelect]);

  useEffect(() => {
    if (isOpen) {
      document.addEventListener('keydown', handleKeyDown);
      return () => document.removeEventListener('keydown', handleKeyDown);
    }
  }, [isOpen, handleKeyDown]);

  return { focusedIndex, setFocusedIndex };
}
```

### 3.3 Mobile Full-Screen Modal

**File to Create**: `resources/js/components/calcom/TeamSelectorModal.jsx`

```javascript
import React, { useEffect } from 'react';
import { createPortal } from 'react-dom';
import TeamSelectorSearch from './TeamSelectorSearch';
import TeamOption from './TeamOption';

export default function TeamSelectorModal({
  teams,
  selectedTeam,
  onTeamSelect,
  onClose,
}) {
  // Lock body scroll
  useEffect(() => {
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = '';
    };
  }, []);

  // Close on back button (mobile)
  useEffect(() => {
    const handlePopState = () => {
      onClose();
    };

    window.addEventListener('popstate', handlePopState);
    return () => window.removeEventListener('popstate', handlePopState);
  }, [onClose]);

  return createPortal(
    <div className="fixed inset-0 z-50 md:hidden">
      {/* Overlay */}
      <div
        className="absolute inset-0 bg-gray-900 bg-opacity-50"
        onClick={onClose}
      />

      {/* Modal */}
      <div className="relative h-full bg-white animate-slide-up">
        {/* Header */}
        <div className="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between z-10">
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700"
          >
            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <h2 className="text-lg font-semibold text-gray-900">
            Select Team
          </h2>

          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700"
          >
            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* Search */}
        <div className="sticky top-[57px] bg-white border-b border-gray-200 p-4 z-10">
          <TeamSelectorSearch />
        </div>

        {/* Team List */}
        <div className="overflow-y-auto pb-4">
          {teams.map((team) => (
            <TeamOption
              key={team.id}
              team={team}
              isSelected={selectedTeam?.id === team.id}
              onClick={() => {
                onTeamSelect(team);
                onClose();
              }}
              isMobile
            />
          ))}
        </div>
      </div>
    </div>,
    document.body
  );
}
```

### 3.4 Session Persistence

**File to Create**: `resources/js/components/calcom/hooks/useTeamPersistence.js`

```javascript
import { useEffect } from 'react';

const STORAGE_KEY = 'team-selector-last-selection';
const STORAGE_TIMESTAMP_KEY = 'team-selector-timestamp';
const MAX_AGE = 24 * 60 * 60 * 1000; // 24 hours

export function useTeamPersistence(selectedTeam, teams, enabled) {
  // Save selection to localStorage
  useEffect(() => {
    if (!enabled || !selectedTeam) return;

    try {
      localStorage.setItem(STORAGE_KEY, selectedTeam.id.toString());
      localStorage.setItem(STORAGE_TIMESTAMP_KEY, Date.now().toString());
    } catch (error) {
      console.warn('Failed to persist team selection:', error);
    }
  }, [selectedTeam, enabled]);

  // Restore selection helper
  const restoreSelection = (teams) => {
    if (!enabled || teams.length === 0) return null;

    try {
      const savedId = localStorage.getItem(STORAGE_KEY);
      const savedTimestamp = localStorage.getItem(STORAGE_TIMESTAMP_KEY);

      if (!savedId || !savedTimestamp) return null;

      // Check if selection is expired
      const age = Date.now() - parseInt(savedTimestamp, 10);
      if (age > MAX_AGE) {
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem(STORAGE_TIMESTAMP_KEY);
        return null;
      }

      // Find team in current list
      const team = teams.find(t => t.id === parseInt(savedId, 10));
      return team || null;
    } catch (error) {
      console.warn('Failed to restore team selection:', error);
      return null;
    }
  };

  return { restoreSelection };
}
```

### Phase 3 Deliverables

- [ ] Search input implemented with debounce
- [ ] Keyboard navigation working (arrows, home, end, enter)
- [ ] Mobile full-screen modal working
- [ ] Session persistence implemented
- [ ] Empty state shown when no search results
- [ ] Focus management working correctly
- [ ] Accessibility tested with screen reader

### Phase 3 Success Criteria

✅ Search filters teams in <300ms
✅ Keyboard navigation cycles through all items
✅ Mobile modal opens/closes smoothly
✅ Selection persists across page refreshes
✅ Focus returns to trigger on close
✅ Screen reader announces changes correctly
✅ No accessibility violations (axe DevTools)

---

## Phase 4: Polish & Production Readiness

**Duration**: 3-4 days
**Focus**: Testing, optimization, documentation

### 4.1 Performance Optimization

**Tasks**:

1. **Memoization**:
```javascript
// Memoize expensive computations
const sortedTeams = useMemo(() =>
  teams.sort((a, b) => a.name.localeCompare(b.name)),
  [teams]
);

const badgeColor = useMemo(() =>
  getBadgeColor(team.active_services_count),
  [team.active_services_count]
);
```

2. **Virtualization** (if >50 teams):
```javascript
import { useVirtualizer } from '@tanstack/react-virtual';

const virtualizer = useVirtualizer({
  count: filteredTeams.length,
  getScrollElement: () => scrollRef.current,
  estimateSize: () => 60,
  overscan: 5,
});
```

3. **Code Splitting**:
```javascript
// Lazy load mobile modal
const TeamSelectorModal = lazy(() => import('./TeamSelectorModal'));

{isMobile && isOpen && (
  <Suspense fallback={<LoadingState />}>
    <TeamSelectorModal />
  </Suspense>
)}
```

### 4.2 Testing Suite

**Unit Tests** (`tests/Unit/Components/TeamSelectorTest.php`):

```javascript
// Jest + React Testing Library
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import TeamSelector from '../TeamSelector';

describe('TeamSelector', () => {
  const mockTeams = [
    { id: 1, name: 'Berlin', city: 'Berlin', active_services_count: 12 },
    { id: 2, name: 'Hamburg', city: 'Hamburg', active_services_count: 8 },
  ];

  it('renders trigger with selected team', () => {
    render(<TeamSelector defaultTeamId={1} teams={mockTeams} />);
    expect(screen.getByText('Berlin')).toBeInTheDocument();
  });

  it('opens dropdown on click', async () => {
    render(<TeamSelector teams={mockTeams} />);
    const trigger = screen.getByRole('button');

    await userEvent.click(trigger);

    expect(screen.getByRole('listbox')).toBeVisible();
  });

  it('filters teams on search', async () => {
    render(<TeamSelector teams={mockTeams} enableSearch />);
    await userEvent.click(screen.getByRole('button'));

    const search = screen.getByRole('searchbox');
    await userEvent.type(search, 'Ham');

    expect(screen.getByText('Hamburg')).toBeVisible();
    expect(screen.queryByText('Berlin')).not.toBeInTheDocument();
  });

  it('persists selection to localStorage', async () => {
    render(<TeamSelector teams={mockTeams} enablePersistence />);

    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(screen.getByText('Hamburg'));

    expect(localStorage.getItem('team-selector-last-selection')).toBe('2');
  });
});
```

**Accessibility Tests**:

```javascript
import { axe, toHaveNoViolations } from 'jest-axe';

expect.extend(toHaveNoViolations);

it('has no accessibility violations', async () => {
  const { container } = render(<TeamSelector teams={mockTeams} />);
  const results = await axe(container);
  expect(results).toHaveNoViolations();
});
```

**E2E Tests** (`tests/Browser/TeamSelectorTest.php`):

```php
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TeamSelectorTest extends DuskTestCase
{
    public function test_team_selection_updates_booking_widget()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create())
                    ->visit('/admin/calcom-booking')
                    ->waitFor('[data-testid="team-selector"]')
                    ->click('[data-testid="team-selector-trigger"]')
                    ->waitFor('[data-testid="team-option-hamburg"]')
                    ->click('[data-testid="team-option-hamburg"]')
                    ->waitForText('Hamburg')
                    ->assertSee('Hamburg');
        });
    }

    public function test_mobile_modal_opens_and_closes()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone SE
                    ->loginAs(User::factory()->create())
                    ->visit('/admin/calcom-booking')
                    ->click('[data-testid="team-selector-trigger"]')
                    ->waitFor('[data-testid="team-selector-modal"]')
                    ->assertVisible('[data-testid="team-selector-modal"]')
                    ->click('[data-testid="modal-close"]')
                    ->waitUntilMissing('[data-testid="team-selector-modal"]')
                    ->assertMissing('[data-testid="team-selector-modal"]');
        });
    }
}
```

### 4.3 Documentation

**Files to Create**:

1. **Component README** (`resources/js/components/calcom/TeamSelector/README.md`):
```markdown
# TeamSelector Component

## Usage

\`\`\`javascript
import TeamSelector from './components/calcom/TeamSelector';

<TeamSelector
  defaultTeamId={1}
  enableSearch={true}
  enablePersistence={true}
  onTeamChange={(teamId, team) => console.log('Selected:', team)}
/>
\`\`\`

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| defaultTeamId | number\|null | null | Initial team selection |
| enableSearch | boolean | true | Show search input |
| enablePersistence | boolean | true | Save selection to localStorage |
| onTeamChange | function | undefined | Callback when team changes |

## Events

- `team-changed`: Emitted to Livewire when team selected
- `team-selector-opened`: Analytics event when dropdown opens
- `team-search`: Analytics event when search used

## Accessibility

- WCAG 2.1 AA compliant
- Full keyboard navigation
- Screen reader optimized
- Reduced motion support
```

2. **API Documentation** (`docs/api/calcom-atoms-enhanced.md`):
```markdown
# Cal.com Atoms Enhanced Config API

## Endpoint

`GET /api/calcom-atoms/config/enhanced`

## Authentication

Requires `auth:sanctum` middleware

## Response

\`\`\`json
{
  "teams": [
    {
      "id": 1,
      "name": "Friseur Salon Berlin",
      "slug": "berlin-mitte",
      "city": "Berlin",
      "is_default": true,
      "is_active": true,
      "services_count": 12,
      "active_services_count": 10,
      "staff_count": 5,
      "calcom_team_id": 45678,
      "integration_status": "connected",
      "last_sync_at": "2025-11-10T08:30:00Z"
    }
  ],
  "default_team_id": 1,
  "user": {
    "can_access_all_teams": true,
    "role": "super_admin"
  }
}
\`\`\`

## Caching

- TTL: 5 minutes
- Cache key: `branch_config_enhanced:user:{user_id}:company:{company_id}`
- Invalidation: On service/branch update
```

### Phase 4 Deliverables

- [ ] All components memoized appropriately
- [ ] Code splitting implemented
- [ ] Unit tests written (>80% coverage)
- [ ] Accessibility tests passing
- [ ] E2E tests passing
- [ ] Component README written
- [ ] API documentation written
- [ ] Performance benchmarked

### Phase 4 Success Criteria

✅ Lighthouse score >90
✅ Test coverage >80%
✅ Zero accessibility violations
✅ All E2E tests passing
✅ Documentation complete
✅ Performance budget met (<100ms render)
✅ Mobile tests passing on real devices

---

## Deployment Checklist

### Pre-Deployment

- [ ] All tests passing (unit, integration, E2E)
- [ ] Accessibility audit complete (no violations)
- [ ] Performance benchmarks met
- [ ] Code review approved
- [ ] Documentation reviewed and approved
- [ ] Changelog updated
- [ ] Migration plan reviewed

### Deployment Steps

**Step 1: Backend Deployment**
```bash
# 1. Deploy backend changes
git checkout develop
git pull origin develop

# 2. Run migrations (if any)
php artisan migrate

# 3. Clear cache
php artisan cache:clear
php artisan config:clear

# 4. Test API endpoint
curl -H "Authorization: Bearer TOKEN" \
     https://yourdomain.com/api/calcom-atoms/config/enhanced
```

**Step 2: Frontend Deployment**
```bash
# 1. Build assets
npm run build

# 2. Deploy to staging
# (Your deployment process)

# 3. Smoke test on staging
# - Open Cal.com Booking page
# - Verify TeamSelector renders
# - Test team selection
# - Test search functionality
# - Test mobile view

# 4. Deploy to production
# (Your deployment process)
```

**Step 3: Post-Deployment Verification**
```bash
# 1. Check error logs
tail -f storage/logs/laravel.log

# 2. Monitor performance
# - API response time < 500ms
# - Component render time < 100ms
# - No JavaScript errors in console

# 3. Verify analytics
# - 'team-changed' events firing
# - 'team-selector-opened' events firing

# 4. Test on real devices
# - iPhone (Safari)
# - Android (Chrome)
# - Desktop (Chrome, Firefox, Safari)
```

### Rollback Plan

If issues detected:

```bash
# 1. Identify issue severity
# - Critical: Blocks booking flow → Immediate rollback
# - Major: Degrades UX → Rollback within 1 hour
# - Minor: Cosmetic issues → Fix forward

# 2. Rollback frontend
git revert <commit-hash>
npm run build
# Deploy

# 3. Rollback backend (if needed)
git revert <commit-hash>
php artisan migrate:rollback

# 4. Notify stakeholders
# - Post-mortem within 24 hours
# - Action items for fix
```

---

## Post-Launch Monitoring

### Week 1: Close Monitoring

**Metrics to Track**:
- API error rate (<1%)
- Component render time (<100ms P95)
- Search usage rate
- Most selected teams
- Mobile vs desktop usage
- Accessibility feature usage

**Daily Tasks**:
- Review error logs
- Check performance metrics
- Monitor user feedback
- Test on different browsers

### Week 2-4: Optimization

**Based on Data**:
- Optimize slow queries
- Improve cache hit rate
- Refine search algorithm
- Enhance mobile UX
- Add missing features

### Month 2+: Feature Expansion

**Potential Enhancements**:
- Favorites/pinning
- Recent selections
- Bulk testing mode
- Team comparison view
- Advanced filtering

---

## Success Metrics

### Technical Metrics

✅ **Performance**:
- API response time: <500ms (P95)
- Component render: <100ms (P95)
- Search latency: <300ms
- Cache hit rate: >80%

✅ **Quality**:
- Test coverage: >80%
- Accessibility score: 100 (axe)
- Lighthouse score: >90
- Zero critical bugs

✅ **Reliability**:
- Uptime: 99.9%
- Error rate: <0.1%
- Successful renders: >99%

### User Experience Metrics

✅ **Adoption**:
- Daily active users: Baseline + 20%
- Search usage: >30% of selections
- Mobile usage: >40% of sessions

✅ **Efficiency**:
- Time to selection: <3 seconds
- Clicks to selection: <2 average
- Abandonment rate: <5%

✅ **Satisfaction**:
- User feedback: Positive >90%
- Support tickets: Decrease >20%
- Feature requests: >10 validated ideas

---

## Timeline Summary

```
Week 1: Phase 1 (Backend)
├─ Day 1-2: Enhanced data layer
├─ Day 3-4: API endpoint & caching
└─ Day 5:   Testing & documentation

Week 2: Phase 2 (Core Component)
├─ Day 1-2: Component structure & hooks
├─ Day 3-4: Integration with CalcomBookerWidget
└─ Day 5:   Testing & bug fixes

Week 3: Phase 3 (Advanced Features)
├─ Day 1-2: Search & keyboard navigation
├─ Day 3-4: Mobile optimization
└─ Day 5:   Session persistence & testing

Week 4: Phase 4 (Polish & Deploy)
├─ Day 1-2: Performance optimization
├─ Day 3:   Full testing suite
├─ Day 4:   Documentation
└─ Day 5:   Deployment & monitoring
```

**Total Duration**: 4 weeks (20 working days)

---

## Risk Mitigation

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| API performance degradation | Medium | High | Implement aggressive caching, optimize queries, add monitoring |
| Accessibility regressions | Low | High | Automated accessibility tests, manual testing, screen reader validation |
| Mobile UX issues | Medium | Medium | Test on real devices, implement progressive enhancement |
| Browser compatibility | Low | Medium | Use Filament's proven patterns, cross-browser testing |
| User adoption resistance | Low | Low | Training documentation, gradual rollout, feedback loop |

---

## Next Steps

1. **Review & Approval**:
   - [ ] Design specification approved
   - [ ] Implementation roadmap approved
   - [ ] Timeline confirmed
   - [ ] Resources allocated

2. **Environment Setup**:
   - [ ] Development environment ready
   - [ ] Staging environment configured
   - [ ] Testing tools installed
   - [ ] Analytics configured

3. **Kick-off**:
   - [ ] Team briefed
   - [ ] Roles assigned
   - [ ] Communication channels established
   - [ ] First sprint planned

4. **Start Phase 1**:
   - [ ] Create feature branch
   - [ ] Implement enhanced data layer
   - [ ] Begin testing

---

**Document Version**: 1.0
**Last Updated**: 2025-11-10
**Status**: Ready for Implementation

**Related Documents**:
- Design Specification: `CALCOM_TEAM_SELECTOR_UX_DESIGN.md`
- Visual Flows: `CALCOM_TEAM_SELECTOR_VISUAL_FLOWS.md`
- API Documentation: `docs/api/calcom-atoms-enhanced.md` (to be created)
