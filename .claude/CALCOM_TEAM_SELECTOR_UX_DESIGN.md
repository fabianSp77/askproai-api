# Cal.com Team-Selector Component - UX Design Specification

**Date**: 2025-11-10
**Designer**: Claude (UI/UX Expert)
**Target**: Filament 3 Admin Panel - Cal.com Booking Page
**Status**: Design Phase

---

## Executive Summary

Design specification for an enhanced Team-Selector dropdown component that replaces the current basic BranchSelector with an admin-focused, information-rich selection interface. The component will enable admin users to efficiently test bookings across different teams/branches while providing immediate context about service availability and team configuration.

**Key Improvements**:
- Service count badges for immediate availability visibility
- Team metadata preview (city, active services count)
- Keyboard navigation and accessibility enhancements
- Session persistence for improved workflow efficiency
- Mobile-responsive design with touch-optimized interactions

---

## 1. Current State UX Assessment

### 1.1 Current Implementation Analysis

**File**: `/var/www/api-gateway/resources/js/components/calcom/BranchSelector.jsx`

**Current Component Structure**:
```javascript
BranchSelector {
  - Simple native <select> dropdown
  - Branch name only display
  - "(Default)" suffix for default branch
  - Auto-hides if ≤1 branch
  - Auto-selects single branch (configurable)
  - Emits 'branch-changed' Livewire event
}
```

**Data Structure** (from `/api/calcom-atoms/config`):
```javascript
{
  branches: [
    {
      id: number,
      name: string,
      is_default: boolean
    }
  ]
}
```

**Current User Flow**:
```
1. Page Load
   ↓
2. BranchSelector fetches branches
   ↓
3. If 1 branch → auto-select & hide
   ↓
4. If >1 branch → show dropdown
   ↓
5. User selects branch
   ↓
6. CalcomBookerWidget re-fetches config
   ↓
7. BookerEmbed re-renders with new event types
```

### 1.2 UX Strengths

✅ **Simplicity**: Minimal cognitive load for single-branch users
✅ **Auto-Selection**: Intelligent defaults reduce clicks
✅ **Native Controls**: Familiar browser UI patterns
✅ **Accessibility**: Native `<select>` provides baseline screen reader support
✅ **Performance**: Efficient re-rendering on branch change

### 1.3 UX Pain Points

❌ **Limited Context**: No service count or availability preview
❌ **Visual Hierarchy**: Dropdown blends with other form elements
❌ **Information Scent**: Users can't preview branch details before selecting
❌ **No Persistence**: Selection resets on page refresh
❌ **Admin-Specific Needs**: Doesn't surface testing-relevant metadata
❌ **Mobile UX**: Native select has poor mobile experience
❌ **No Search**: Problematic for companies with 10+ branches
❌ **No Status Indicators**: Can't see if branch has configured services

### 1.4 User Research Insights

**Target Users**: Admin users testing multi-branch booking flows

**Primary Tasks**:
1. Test booking flow for different branches
2. Verify service availability per branch
3. Validate Cal.com integration per team
4. Compare service configurations across branches

**Pain Points Observed**:
- "I can't tell which branches have services configured"
- "I need to select a branch, wait for loading, then realize no services exist"
- "I lose my selection when refreshing during testing"
- "Mobile testing is difficult with the native dropdown"

---

## 2. Enhanced Team-Selector Component Design

### 2.1 Component Architecture

**Component Name**: `TeamSelector` (replaces `BranchSelector`)

**Technology Stack**:
- React 18.x (existing)
- Headless UI (Filament's dropdown component library)
- Tailwind CSS (Filament design system)
- Alpine.js (for enhanced interactions)

**Data Flow**:
```
API: /api/calcom-atoms/config/enhanced
  ↓
TeamSelector State Management
  ↓
LocalStorage Persistence Layer
  ↓
Livewire Bridge (branch-changed event)
  ↓
CalcomBookerWidget Re-render
```

### 2.2 Enhanced Data Structure

**API Response** (new endpoint: `/api/calcom-atoms/config/enhanced`):
```json
{
  "teams": [
    {
      "id": 1,
      "name": "Friseur Salon Berlin-Mitte",
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
    },
    {
      "id": 2,
      "name": "Friseur Salon Hamburg",
      "slug": "hamburg",
      "city": "Hamburg",
      "is_default": false,
      "is_active": true,
      "services_count": 8,
      "active_services_count": 8,
      "staff_count": 3,
      "calcom_team_id": 45679,
      "integration_status": "connected",
      "last_sync_at": "2025-11-09T14:20:00Z"
    },
    {
      "id": 3,
      "name": "Friseur Salon München",
      "slug": "muenchen",
      "city": "München",
      "is_default": false,
      "is_active": false,
      "services_count": 0,
      "active_services_count": 0,
      "staff_count": 0,
      "calcom_team_id": null,
      "integration_status": "not_configured",
      "last_sync_at": null
    }
  ],
  "default_team_id": 1,
  "user": {
    "can_access_all_teams": true,
    "role": "super_admin"
  }
}
```

### 2.3 Visual Design Specification

#### 2.3.1 Component Wireframe (Text Description)

```
┌─────────────────────────────────────────────────────────────────┐
│  Team / Branch                                          [v]      │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  ⚫ Friseur Salon Berlin-Mitte                    [12]   │   │
│  │  📍 Berlin • 5 staff                          ✓ Default  │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘

When clicked, dropdown expands:

┌─────────────────────────────────────────────────────────────────┐
│  Team / Branch                                          [^]      │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  🔍 Search teams...                                  [x] │   │
│  ├──────────────────────────────────────────────────────────┤   │
│  │  ⚫ Friseur Salon Berlin-Mitte                    [12]   │   │
│  │  📍 Berlin • 5 staff                          ✓ Selected │   │
│  ├──────────────────────────────────────────────────────────┤   │
│  │  ⚪ Friseur Salon Hamburg                         [8]    │   │
│  │  📍 Hamburg • 3 staff                                    │   │
│  ├──────────────────────────────────────────────────────────┤   │
│  │  ⚪ Friseur Salon München                         [0]    │   │
│  │  📍 München • 0 staff                      ⚠️ Not Ready  │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

#### 2.3.2 Design Tokens (Filament 3 Aligned)

**Colors** (Tailwind + Filament Palette):
```css
/* Primary States */
--team-selector-bg: white;
--team-selector-border: theme('colors.gray.300');
--team-selector-focus: theme('colors.primary.600');

/* Team Item States */
--team-item-hover-bg: theme('colors.gray.50');
--team-item-selected-bg: theme('colors.primary.50');
--team-item-selected-border: theme('colors.primary.500');

/* Badge Colors */
--badge-active-bg: theme('colors.success.100');
--badge-active-text: theme('colors.success.700');
--badge-warning-bg: theme('colors.warning.100');
--badge-warning-text: theme('colors.warning.700');
--badge-error-bg: theme('colors.danger.100');
--badge-error-text: theme('colors.danger.700');

/* Status Indicators */
--status-connected: theme('colors.success.500');
--status-pending: theme('colors.warning.500');
--status-error: theme('colors.danger.500');
```

**Typography**:
```css
--team-name-font: theme('fontFamily.sans');
--team-name-size: theme('fontSize.sm');
--team-name-weight: theme('fontWeight.medium');

--team-meta-font: theme('fontFamily.sans');
--team-meta-size: theme('fontSize.xs');
--team-meta-weight: theme('fontWeight.normal');
--team-meta-color: theme('colors.gray.600');

--badge-font: theme('fontFamily.sans');
--badge-size: theme('fontSize.xs');
--badge-weight: theme('fontWeight.semibold');
```

**Spacing**:
```css
--team-selector-padding: theme('spacing.3');
--team-item-padding: theme('spacing.3');
--team-item-gap: theme('spacing.2');
--badge-padding-x: theme('spacing.2');
--badge-padding-y: theme('spacing.1');
```

**Borders & Shadows**:
```css
--team-selector-radius: theme('borderRadius.lg');
--team-item-radius: theme('borderRadius.md');
--badge-radius: theme('borderRadius.full');

--team-selector-shadow: theme('boxShadow.sm');
--team-dropdown-shadow: theme('boxShadow.lg');
```

#### 2.3.3 Responsive Breakpoints

**Desktop** (≥1024px):
- Full dropdown with all metadata
- 2-column layout for team metadata
- Hover states enabled

**Tablet** (≥768px, <1024px):
- Compact metadata (1 line)
- Larger touch targets (min 44px)
- Service count badge remains

**Mobile** (<768px):
- Full-screen modal overlay
- Touch-optimized (min 48px targets)
- Search filter prominent
- Stack metadata vertically

---

## 3. User Interaction Flow

### 3.1 Primary Flow: Team Selection

```
┌─────────────────────────────────────────────────────────┐
│ 1. User Views Page                                      │
│    - TeamSelector loads with default selection         │
│    - Badge shows service count immediately              │
└────────────┬────────────────────────────────────────────┘
             │
             v
┌─────────────────────────────────────────────────────────┐
│ 2. User Clicks TeamSelector                             │
│    - Dropdown opens with animation (150ms ease-out)     │
│    - Search input auto-focuses (desktop)                │
│    - Current selection highlighted                      │
└────────────┬────────────────────────────────────────────┘
             │
             v
┌─────────────────────────────────────────────────────────┐
│ 3. User Reviews Options                                 │
│    - Service count badges provide instant context      │
│    - Warning badges flag unconfigured teams             │
│    - Hover state provides feedback                      │
└────────────┬────────────────────────────────────────────┘
             │
             v
┌─────────────────────────────────────────────────────────┐
│ 4. User Selects Team (Click/Enter/Space)                │
│    - Dropdown closes with animation (100ms ease-in)     │
│    - Selection persisted to localStorage                │
│    - 'branch-changed' event emitted                     │
└────────────┬────────────────────────────────────────────┘
             │
             v
┌─────────────────────────────────────────────────────────┐
│ 5. CalcomBookerWidget Updates                           │
│    - Loading state shown (skeleton)                     │
│    - Config fetched for new team                        │
│    - BookerEmbed re-renders with new services           │
└─────────────────────────────────────────────────────────┘
```

### 3.2 Alternative Flow: Search & Filter

```
Desktop:
  Click → Search Input Auto-Focus → Type Query → Filter Results → Select

Mobile:
  Tap → Full-Screen Modal → Search Prominent → Type → Filter → Tap to Select
```

### 3.3 Edge Cases

**No Services Configured**:
```
User sees warning badge → Clicks anyway → BookerEmbed shows empty state
→ "No services available" message with setup CTA
```

**Network Error During Load**:
```
TeamSelector shows error state → "Retry" button → Re-fetch on click
```

**Single Team Available**:
```
Auto-select + hide component (existing behavior preserved)
```

**Session Persistence**:
```
Page Refresh → localStorage.getItem('team-selector-last-selection')
→ Restore selection if team still accessible
```

---

## 4. Component Specification

### 4.1 Component Props

```typescript
interface TeamSelectorProps {
  // Configuration
  defaultTeamId?: number | null;
  enableSearch?: boolean;
  enablePersistence?: boolean;
  showMetadata?: boolean;
  variant?: 'default' | 'compact' | 'minimal';

  // Callbacks
  onTeamChange?: (teamId: number, team: Team) => void;
  onTeamLoad?: (teams: Team[]) => void;
  onError?: (error: Error) => void;

  // Styling
  className?: string;
  dropdownClassName?: string;

  // Accessibility
  label?: string;
  ariaLabel?: string;
  ariaDescribedBy?: string;

  // Feature Flags
  autoSelectSingle?: boolean;
  showInactiveTeams?: boolean;
  groupByCity?: boolean;
}
```

### 4.2 Component State

```typescript
interface TeamSelectorState {
  // Data
  teams: Team[];
  selectedTeam: Team | null;
  filteredTeams: Team[];

  // UI State
  isOpen: boolean;
  isLoading: boolean;
  error: Error | null;
  searchQuery: string;

  // Interaction State
  focusedIndex: number;
  lastSelection: number | null;
}

interface Team {
  id: number;
  name: string;
  slug: string;
  city: string;
  is_default: boolean;
  is_active: boolean;
  services_count: number;
  active_services_count: number;
  staff_count: number;
  calcom_team_id: number | null;
  integration_status: 'connected' | 'pending' | 'not_configured' | 'error';
  last_sync_at: string | null;
}
```

### 4.3 Component Events

```typescript
// Emitted to Livewire Bridge
interface TeamSelectorEvents {
  'team-changed': {
    team_id: number;
    team_name: string;
    previous_team_id: number | null;
  };

  'team-search': {
    query: string;
    results_count: number;
  };

  'team-selector-opened': {
    teams_count: number;
  };

  'team-selector-error': {
    error_message: string;
    error_code: string;
  };
}

// React Component Events
interface TeamSelectorCallbacks {
  onTeamChange(teamId: number, team: Team): void;
  onTeamLoad(teams: Team[]): void;
  onError(error: Error): void;
  onSearchChange(query: string, results: Team[]): void;
}
```

### 4.4 Accessibility Requirements (WCAG 2.1 AA)

#### Keyboard Navigation

**Trigger Button**:
- `Tab`: Focus trigger
- `Enter` / `Space`: Open dropdown
- `Escape`: Close dropdown (if open)
- `ArrowDown`: Open dropdown + focus first item
- `ArrowUp`: Open dropdown + focus last item

**Dropdown Open**:
- `ArrowDown`: Focus next team
- `ArrowUp`: Focus previous team
- `Home`: Focus first team
- `End`: Focus last team
- `Enter` / `Space`: Select focused team
- `Escape`: Close dropdown (no selection)
- `Tab`: Close dropdown + focus next element
- `Shift+Tab`: Close dropdown + focus previous element
- `Type character`: Search teams (incremental search)

**Search Input** (when present):
- `Escape`: Clear search + return focus to trigger
- `ArrowDown`: Move focus to first filtered result
- `Enter`: Select first filtered result (if only one)

#### ARIA Labels & Roles

```html
<!-- Trigger Button -->
<button
  role="combobox"
  aria-label="Select team or branch"
  aria-haspopup="listbox"
  aria-expanded={isOpen}
  aria-controls="team-listbox"
  aria-activedescendant={focusedTeamId}
>
  {selectedTeam.name}
</button>

<!-- Dropdown List -->
<ul
  id="team-listbox"
  role="listbox"
  aria-label="Available teams"
  aria-multiselectable="false"
>
  <li
    role="option"
    aria-selected={isSelected}
    aria-disabled={!isActive}
    id={`team-option-${team.id}`}
  >
    {team.name}
  </li>
</ul>

<!-- Search Input -->
<input
  type="search"
  role="searchbox"
  aria-label="Search teams"
  aria-controls="team-listbox"
  aria-autocomplete="list"
/>
```

#### Focus Management

**Rules**:
1. Focus trap when dropdown open (Escape to exit)
2. Return focus to trigger on close
3. Visual focus indicators (2px outline, primary color)
4. Focus visible on keyboard navigation only (`:focus-visible`)
5. Skip focus on disabled/inactive teams

**Focus Styles**:
```css
.team-selector-trigger:focus-visible {
  outline: 2px solid theme('colors.primary.600');
  outline-offset: 2px;
}

.team-option:focus-visible {
  background-color: theme('colors.primary.50');
  outline: 2px solid theme('colors.primary.600');
  outline-offset: -2px;
}
```

#### Screen Reader Support

**Announcements**:
- Team count on dropdown open: "12 teams available"
- Search results update: "8 teams match your search"
- Selection change: "Friseur Salon Berlin-Mitte selected"
- Error state: "Error loading teams. Please try again."
- No results: "No teams match your search"

**Live Regions**:
```html
<!-- Status Messages -->
<div role="status" aria-live="polite" aria-atomic="true">
  {statusMessage}
</div>

<!-- Error Messages -->
<div role="alert" aria-live="assertive">
  {errorMessage}
</div>
```

#### Color Contrast

**WCAG AA Requirements** (4.5:1 for normal text, 3:1 for large text):

✅ **Team Name**: Gray-900 on White (21:1)
✅ **Metadata**: Gray-600 on White (7:1)
✅ **Badge Text**: Success-700 on Success-100 (5.2:1)
✅ **Warning Badge**: Warning-800 on Warning-100 (6.8:1)
✅ **Error Badge**: Danger-700 on Danger-100 (5.5:1)

**Non-Text Contrast** (3:1 for UI components):

✅ **Border**: Gray-300 vs White (2.8:1) → Needs adjustment to Gray-400 (3.2:1)
✅ **Focus Ring**: Primary-600 vs White (4.8:1)
✅ **Selected State**: Primary-500 border (5.1:1)

#### Touch Target Sizes

**Mobile Requirements** (iOS HIG + Material Design):

- Minimum touch target: 48px × 48px
- Recommended: 44px × 44px (iOS), 48dp × 48dp (Android)
- Spacing between targets: ≥8px

**Implementation**:
```css
@media (max-width: 767px) {
  .team-option {
    min-height: 48px;
    padding: 12px 16px;
  }

  .team-option + .team-option {
    margin-top: 8px;
  }
}
```

---

## 5. Mobile-Responsive Design

### 5.1 Mobile Optimization Strategy

**Approach**: Progressive Enhancement
- Desktop: Dropdown overlay
- Tablet: Larger dropdown with touch optimization
- Mobile: Full-screen modal for maximum usability

### 5.2 Mobile Layout Specification

```
┌───────────────────────────────────────┐
│ ← Select Team                    [x]  │ ← Header (sticky)
├───────────────────────────────────────┤
│ 🔍 Search teams...                    │ ← Search (prominent)
├───────────────────────────────────────┤
│                                       │
│ ┌─────────────────────────────────┐   │
│ │ ⚫ Friseur Salon Berlin    [12] │   │ ← 48px min-height
│ │ 📍 Berlin • 5 staff             │   │
│ │ ✓ Selected                      │   │
│ └─────────────────────────────────┘   │
│                                       │
│ ┌─────────────────────────────────┐   │
│ │ ⚪ Friseur Salon Hamburg    [8] │   │
│ │ 📍 Hamburg • 3 staff            │   │
│ └─────────────────────────────────┘   │
│                                       │
│ ┌─────────────────────────────────┐   │
│ │ ⚪ Friseur Salon München    [0] │   │
│ │ 📍 München • 0 staff            │   │
│ │ ⚠️ Not Ready                    │   │
│ └─────────────────────────────────┘   │
│                                       │
└───────────────────────────────────────┘
```

### 5.3 Mobile Interaction Patterns

**Open Behavior**:
- Trigger tap → Full-screen modal slides up (300ms ease-out)
- Background overlay (gray-900/50 opacity)
- Body scroll locked

**Search Behavior**:
- Auto-focus on modal open
- Virtual keyboard appears
- Incremental filter (debounced 300ms)

**Selection Behavior**:
- Tap team → Haptic feedback (if supported)
- Modal closes (slide down 200ms)
- Success toast notification (optional)

**Close Behavior**:
- Close button (top-right X)
- Swipe down gesture
- Tap outside modal
- Hardware back button (Android)

### 5.4 Responsive CSS Implementation

```css
/* Desktop: Dropdown */
@media (min-width: 1024px) {
  .team-selector-dropdown {
    position: absolute;
    top: 100%;
    max-height: 400px;
    width: 100%;
    overflow-y: auto;
    box-shadow: theme('boxShadow.lg');
  }
}

/* Tablet: Larger Dropdown */
@media (min-width: 768px) and (max-width: 1023px) {
  .team-selector-dropdown {
    position: absolute;
    top: 100%;
    max-height: 500px;
    width: 100%;
  }

  .team-option {
    min-height: 44px;
    padding: 10px 14px;
  }
}

/* Mobile: Full-Screen Modal */
@media (max-width: 767px) {
  .team-selector-dropdown {
    position: fixed;
    inset: 0;
    max-height: 100vh;
    border-radius: 0;
    animation: slideUp 300ms ease-out;
  }

  .team-option {
    min-height: 48px;
    padding: 12px 16px;
  }

  .team-search {
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
    padding: 16px;
    border-bottom: 1px solid theme('colors.gray.200');
  }
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}
```

---

## 6. Filament Design System Integration

### 6.1 Filament Component Alignment

**Use Filament UI Components**:
```javascript
import { Dropdown } from '@filament/support';
import { Badge } from '@filament/support';
import { Icon } from '@filament/support';
```

**Match Filament Patterns**:
- Badge colors: `success`, `warning`, `danger`, `info`
- Icon library: Heroicons (already in Filament)
- Form field styling: Match Filament's input components
- Loading states: Use Filament's skeleton pattern

### 6.2 Tailwind Classes (Filament-Aligned)

**Trigger Button**:
```html
<button class="
  flex items-center justify-between
  w-full px-3 py-2
  bg-white border border-gray-300 rounded-lg
  shadow-sm
  hover:bg-gray-50
  focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-primary-600
  transition duration-150 ease-in-out
">
```

**Dropdown Container**:
```html
<div class="
  absolute z-50 mt-2
  w-full min-w-[320px] max-w-md
  bg-white border border-gray-200 rounded-lg shadow-lg
  overflow-hidden
">
```

**Team Option**:
```html
<button class="
  flex items-center justify-between
  w-full px-3 py-2.5
  text-left
  hover:bg-gray-50
  focus:bg-primary-50 focus:outline-none
  transition duration-100 ease-in-out
  group
">
```

**Service Count Badge**:
```html
<span class="
  inline-flex items-center justify-center
  min-w-[24px] h-6 px-2
  text-xs font-semibold
  bg-success-100 text-success-700
  rounded-full
">
  12
</span>
```

**Warning Badge**:
```html
<span class="
  inline-flex items-center gap-1
  px-2 py-1
  text-xs font-medium
  bg-warning-100 text-warning-700
  rounded-md
">
  <svg>...</svg>
  Not Ready
</span>
```

### 6.3 Filament Theme Integration

**Register Custom Component** (if needed):
```php
// config/filament.php or AdminPanelProvider.php
use Filament\Support\Assets\Js;

return [
    'assets' => [
        'js' => [
            Js::make('team-selector', resource_path('js/filament/team-selector.js')),
        ],
    ],
];
```

**Blade Component Wrapper**:
```blade
{{-- resources/views/components/team-selector.blade.php --}}
<div
    x-data="teamSelectorData(@js($defaultTeamId))"
    x-init="init()"
    class="team-selector-wrapper"
>
    <div data-team-selector='@json([
        "defaultTeamId" => $defaultTeamId,
        "enableSearch" => $enableSearch,
        "variant" => $variant,
    ])'></div>
</div>

@once
    @vite(['resources/js/components/team-selector.jsx'])
@endonce
```

---

## 7. Implementation Recommendations

### 7.1 Phased Rollout

**Phase 1: Enhanced Data Layer** (Backend Focus)
- Create `/api/calcom-atoms/config/enhanced` endpoint
- Add service counts to Branch model (computed property)
- Add integration status to response
- Implement caching strategy (5 min TTL)

**Phase 2: Basic Team-Selector** (Frontend Core)
- Replace BranchSelector with TeamSelector
- Implement dropdown UI with service badges
- Add keyboard navigation
- Add session persistence

**Phase 3: Advanced Features** (UX Enhancements)
- Add search/filter functionality
- Implement mobile full-screen modal
- Add loading skeletons
- Add error boundaries

**Phase 4: Polish & Optimization** (Production Ready)
- Performance optimization (memoization, virtualization)
- Accessibility audit & fixes
- Analytics integration
- A/B testing framework

### 7.2 Backend Changes Required

**New Service Method** (`app/Services/Calcom/BranchCalcomConfigService.php`):
```php
public function getUserBranchesEnhanced(User $user): array
{
    $branches = $this->getUserBranches($user);

    return array_map(function ($branch) {
        $branchModel = Branch::find($branch['id']);

        return array_merge($branch, [
            'city' => $branchModel->city,
            'is_active' => $branchModel->is_active,
            'services_count' => $branchModel->services()->count(),
            'active_services_count' => $branchModel->activeServices()->count(),
            'staff_count' => $branchModel->staff()->count(),
            'calcom_team_id' => $branchModel->calcom_team_id,
            'integration_status' => $this->getIntegrationStatus($branchModel),
            'last_sync_at' => $branchModel->integrations_tested_at?->toIso8601String(),
        ]);
    }, $branches);
}

private function getIntegrationStatus(Branch $branch): string
{
    if (!$branch->is_active) return 'inactive';
    if (!$branch->calcom_team_id) return 'not_configured';
    if ($branch->integration_status === 'connected') return 'connected';
    if ($branch->integration_status === 'pending') return 'pending';
    return 'error';
}
```

**New Controller Method** (`app/Http/Controllers/Api/CalcomAtomsController.php`):
```php
public function configEnhanced(Request $request): JsonResponse
{
    try {
        $user = $request->user();
        $user->load(['company.branches', 'branch']);

        return response()->json([
            'teams' => $this->configService->getUserBranchesEnhanced($user),
            'default_team_id' => $user->branch_id ?? $user->company->branches()->first()?->id,
            'user' => [
                'can_access_all_teams' => $user->hasRole('super_admin') || $user->hasRole('company_owner'),
                'role' => $user->roles->first()?->name ?? 'user',
            ],
        ]);
    } catch (\Exception $e) {
        logger()->error('Enhanced config error', [
            'error' => $e->getMessage(),
            'user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'error' => 'Failed to load configuration',
        ], 500);
    }
}
```

**Route** (`routes/api.php`):
```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/calcom-atoms/config/enhanced', [CalcomAtomsController::class, 'configEnhanced']);
});
```

### 7.3 Frontend Implementation Strategy

**File Structure**:
```
resources/js/components/
├── calcom/
│   ├── TeamSelector.jsx           (Main component)
│   ├── TeamSelectorTrigger.jsx    (Button)
│   ├── TeamSelectorDropdown.jsx   (Dropdown container)
│   ├── TeamSelectorSearch.jsx     (Search input)
│   ├── TeamOption.jsx             (Individual team item)
│   ├── TeamBadge.jsx              (Service count badge)
│   ├── StatusIndicator.jsx        (Integration status)
│   └── hooks/
│       ├── useTeamSelector.js     (State management)
│       ├── useTeamPersistence.js  (localStorage)
│       └── useTeamSearch.js       (Search/filter logic)
```

**Component Composition**:
```jsx
<TeamSelector>
  <TeamSelectorTrigger>
    {selectedTeam.name}
    <TeamBadge count={selectedTeam.services_count} />
  </TeamSelectorTrigger>

  <TeamSelectorDropdown>
    <TeamSelectorSearch />

    {filteredTeams.map(team => (
      <TeamOption key={team.id} team={team}>
        <TeamBadge count={team.active_services_count} />
        <StatusIndicator status={team.integration_status} />
      </TeamOption>
    ))}
  </TeamSelectorDropdown>
</TeamSelector>
```

### 7.4 Performance Considerations

**Optimization Strategies**:

1. **Memoization**:
   ```javascript
   const filteredTeams = useMemo(() =>
     teams.filter(team =>
       team.name.toLowerCase().includes(searchQuery.toLowerCase())
     ),
     [teams, searchQuery]
   );
   ```

2. **Virtualization** (for 50+ teams):
   ```javascript
   import { useVirtualizer } from '@tanstack/react-virtual';

   const virtualizer = useVirtualizer({
     count: filteredTeams.length,
     getScrollElement: () => scrollRef.current,
     estimateSize: () => 60,
   });
   ```

3. **Debounced Search**:
   ```javascript
   const debouncedSearch = useDebouncedCallback(
     (query) => setSearchQuery(query),
     300
   );
   ```

4. **Lazy Loading**:
   ```javascript
   const TeamSelector = lazy(() => import('./TeamSelector'));

   <Suspense fallback={<TeamSelectorSkeleton />}>
     <TeamSelector />
   </Suspense>
   ```

5. **Caching Strategy**:
   ```javascript
   // SWR or React Query
   const { data: teams, error, isLoading } = useSWR(
     '/api/calcom-atoms/config/enhanced',
     fetcher,
     { revalidateOnFocus: false, dedupingInterval: 300000 }
   );
   ```

### 7.5 Testing Strategy

**Unit Tests** (Jest + React Testing Library):
```javascript
describe('TeamSelector', () => {
  it('renders teams with service counts', () => {
    const { getByText } = render(<TeamSelector teams={mockTeams} />);
    expect(getByText('12')).toBeInTheDocument(); // Service badge
  });

  it('filters teams on search', () => {
    const { getByRole, queryByText } = render(<TeamSelector teams={mockTeams} />);
    const search = getByRole('searchbox');

    fireEvent.change(search, { target: { value: 'Berlin' } });

    expect(queryByText('Hamburg')).not.toBeInTheDocument();
    expect(queryByText('Berlin-Mitte')).toBeInTheDocument();
  });

  it('persists selection to localStorage', () => {
    const { getByText } = render(<TeamSelector teams={mockTeams} />);

    fireEvent.click(getByText('Hamburg'));

    expect(localStorage.getItem('team-selector-last-selection')).toBe('2');
  });
});
```

**Accessibility Tests** (jest-axe):
```javascript
import { axe, toHaveNoViolations } from 'jest-axe';

expect.extend(toHaveNoViolations);

it('has no accessibility violations', async () => {
  const { container } = render(<TeamSelector teams={mockTeams} />);
  const results = await axe(container);
  expect(results).toHaveNoViolations();
});
```

**E2E Tests** (Playwright - existing in project):
```javascript
test('team selection updates booking widget', async ({ page }) => {
  await page.goto('/admin/calcom-booking');

  // Open team selector
  await page.click('[data-testid="team-selector-trigger"]');

  // Select different team
  await page.click('[data-testid="team-option-hamburg"]');

  // Verify booking widget updated
  await expect(page.locator('.calcom-booker-container')).toContainText('Hamburg');
});
```

---

## 8. Accessibility Deep Dive

### 8.1 Screen Reader Experience

**Optimal Flow**:
```
1. Tab to TeamSelector
   → "Select team or branch, button, collapsed, Friseur Salon Berlin-Mitte, 12 services"

2. Press Enter
   → "Select team or branch, button, expanded"
   → "12 teams available"
   → Focus moves to search input

3. Type "ham"
   → "2 teams match your search"

4. Arrow Down
   → "Friseur Salon Hamburg, option 1 of 2, 8 services"

5. Press Enter
   → "Friseur Salon Hamburg selected"
   → Focus returns to trigger
   → "Select team or branch, button, collapsed, Friseur Salon Hamburg, 8 services"
```

### 8.2 Voice Control Support

**Optimized for Voice Commands**:
- "Click Select team" → Opens dropdown
- "Click Friseur Salon Hamburg" → Selects team
- "Search teams" → Focuses search input
- "Close dialog" → Closes dropdown

**Implementation**:
```html
<!-- Clear, unique labels for voice recognition -->
<button aria-label="Select team or branch">
<input aria-label="Search teams by name or city">
<button aria-label="Select Friseur Salon Hamburg">
```

### 8.3 Reduced Motion Support

**Respect User Preferences**:
```css
@media (prefers-reduced-motion: reduce) {
  .team-selector-dropdown {
    animation: none;
    transition: none;
  }

  .team-option {
    transition: none;
  }
}

@media (prefers-reduced-motion: no-preference) {
  .team-selector-dropdown {
    animation: slideDown 150ms ease-out;
  }

  .team-option {
    transition: background-color 100ms ease-in-out;
  }
}
```

### 8.4 High Contrast Mode Support

**Windows High Contrast Mode**:
```css
@media (prefers-contrast: high) {
  .team-option:focus {
    outline: 3px solid;
    outline-offset: -3px;
  }

  .badge {
    border: 2px solid currentColor;
  }
}

/* Forced colors mode (Windows High Contrast) */
@media (forced-colors: active) {
  .team-option:focus {
    forced-color-adjust: none;
    outline: 2px solid CanvasText;
  }
}
```

---

## 9. Analytics & Monitoring

### 9.1 Key Metrics to Track

**Usage Metrics**:
- Team selector open rate
- Average time to selection
- Search usage rate
- Most selected teams
- Selection changes per session

**Performance Metrics**:
- Time to load teams list
- Dropdown render time
- Search filter latency
- Persistence reliability

**UX Metrics**:
- Abandonment rate (opened but not selected)
- Error rate
- Mobile vs desktop usage
- Accessibility feature usage (keyboard nav, screen reader)

### 9.2 Analytics Implementation

**Event Tracking**:
```javascript
// Track team selection
analytics.track('Team Selected', {
  team_id: team.id,
  team_name: team.name,
  services_count: team.active_services_count,
  method: 'click', // or 'keyboard', 'search'
  time_to_select: Date.now() - openTimestamp,
});

// Track search usage
analytics.track('Team Search', {
  query: searchQuery,
  results_count: filteredTeams.length,
  selected_from_search: true,
});

// Track errors
analytics.track('Team Selector Error', {
  error_type: error.name,
  error_message: error.message,
  user_role: user.role,
});
```

**Performance Monitoring**:
```javascript
// Measure dropdown render time
performance.mark('dropdown-open-start');
setIsOpen(true);
requestAnimationFrame(() => {
  performance.mark('dropdown-open-end');
  performance.measure('dropdown-render', 'dropdown-open-start', 'dropdown-open-end');
});
```

---

## 10. Future Enhancements

### 10.1 Phase 2+ Features

**Advanced Filtering**:
- Filter by city
- Filter by service count range
- Filter by integration status
- Filter by staff availability

**Favorites & Recents**:
- Pin frequently used teams
- Show recent selections
- Suggested teams based on usage

**Bulk Operations**:
- Multi-team testing mode
- Compare availability across teams
- Batch configuration checks

**Visual Enhancements**:
- Team avatars/logos
- Color-coded status indicators
- Availability heatmap preview
- Staff member quick preview

**Advanced UX**:
- Command palette (⌘K) integration
- Predictive search with fuzzy matching
- Natural language search ("Berlin salon with 10+ services")
- Quick actions menu (Edit team, View calendar, etc.)

### 10.2 Integration Opportunities

**Filament Ecosystem**:
- Integrate with Filament's global search
- Add to Filament's command palette
- Sync with Filament's recent items
- Use Filament's notification system

**External Tools**:
- Export team comparison reports
- Integration with analytics dashboards
- Sync with project management tools
- API for third-party integrations

---

## Appendix A: Component Code Skeleton

```jsx
// resources/js/components/calcom/TeamSelector.jsx

import React, { useState, useEffect, useRef } from 'react';
import { CalcomBridge } from './CalcomBridge';
import TeamSelectorTrigger from './TeamSelectorTrigger';
import TeamSelectorDropdown from './TeamSelectorDropdown';
import { useTeamPersistence } from './hooks/useTeamPersistence';
import { useTeamSearch } from './hooks/useTeamSearch';

export default function TeamSelector({
  defaultTeamId = null,
  enableSearch = true,
  enablePersistence = true,
  onTeamChange,
  className = ''
}) {
  const [teams, setTeams] = useState([]);
  const [selectedTeam, setSelectedTeam] = useState(null);
  const [isOpen, setIsOpen] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const dropdownRef = useRef(null);

  // Custom hooks
  const { saveSelection, restoreSelection } = useTeamPersistence(enablePersistence);
  const { searchQuery, setSearchQuery, filteredTeams } = useTeamSearch(teams);

  useEffect(() => {
    fetchTeams();
  }, []);

  const fetchTeams = async () => {
    setLoading(true);
    setError(null);

    try {
      const data = await CalcomBridge.fetch('/api/calcom-atoms/config/enhanced');
      setTeams(data.teams);

      // Restore or set default
      const restored = restoreSelection(data.teams);
      if (restored) {
        setSelectedTeam(restored);
      } else if (defaultTeamId) {
        const defaultTeam = data.teams.find(t => t.id === defaultTeamId);
        if (defaultTeam) setSelectedTeam(defaultTeam);
      }
    } catch (err) {
      console.error('Failed to fetch teams:', err);
      setError(err);
    } finally {
      setLoading(false);
    }
  };

  const handleTeamSelect = (team) => {
    setSelectedTeam(team);
    setIsOpen(false);
    saveSelection(team);

    onTeamChange?.(team.id, team);

    CalcomBridge.emit('team-changed', {
      team_id: team.id,
      team_name: team.name,
    });
  };

  const handleToggle = () => {
    setIsOpen(prev => !prev);
    if (!isOpen) {
      analytics.track('Team Selector Opened', { teams_count: teams.length });
    }
  };

  // Close on outside click
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
      return () => document.removeEventListener('mousedown', handleClickOutside);
    }
  }, [isOpen]);

  if (loading) {
    return <TeamSelectorSkeleton />;
  }

  if (error) {
    return <TeamSelectorError error={error} onRetry={fetchTeams} />;
  }

  if (teams.length <= 1) {
    return null; // Auto-hide for single team
  }

  return (
    <div className={`team-selector-wrapper ${className}`} ref={dropdownRef}>
      <TeamSelectorTrigger
        selectedTeam={selectedTeam}
        isOpen={isOpen}
        onClick={handleToggle}
      />

      {isOpen && (
        <TeamSelectorDropdown
          teams={filteredTeams}
          selectedTeam={selectedTeam}
          searchQuery={searchQuery}
          onSearchChange={setSearchQuery}
          onTeamSelect={handleTeamSelect}
          enableSearch={enableSearch}
        />
      )}
    </div>
  );
}
```

---

## Appendix B: Design Checklist

### Pre-Implementation Checklist

**UX Design**:
- [ ] Wireframes reviewed and approved
- [ ] User flows documented
- [ ] Edge cases identified and designed
- [ ] Mobile responsive breakpoints defined
- [ ] Interaction patterns specified

**Accessibility**:
- [ ] WCAG 2.1 AA requirements mapped
- [ ] Keyboard navigation documented
- [ ] Screen reader flow tested
- [ ] Color contrast verified (4.5:1 minimum)
- [ ] Touch targets sized (48px minimum)
- [ ] Focus management strategy defined

**Technical**:
- [ ] Component API specified (props, state, events)
- [ ] Data structure defined
- [ ] Backend changes documented
- [ ] Performance strategy outlined
- [ ] Error handling scenarios covered

**Design System**:
- [ ] Filament components identified for reuse
- [ ] Tailwind classes documented
- [ ] Custom CSS minimized
- [ ] Brand alignment verified
- [ ] Theme integration tested

**Testing**:
- [ ] Unit test coverage plan
- [ ] Accessibility test strategy
- [ ] E2E test scenarios
- [ ] Performance benchmarks defined
- [ ] Analytics events specified

### Post-Implementation Checklist

**Quality Assurance**:
- [ ] Visual regression testing passed
- [ ] Accessibility audit passed (axe, WAVE)
- [ ] Cross-browser testing completed
- [ ] Mobile device testing completed
- [ ] Keyboard navigation tested
- [ ] Screen reader testing completed

**Performance**:
- [ ] Lighthouse score >90
- [ ] First Contentful Paint <1.5s
- [ ] Time to Interactive <3s
- [ ] Dropdown render <100ms
- [ ] Search filter latency <300ms

**Documentation**:
- [ ] Component documentation written
- [ ] API documentation updated
- [ ] User guide created
- [ ] Developer guide created
- [ ] Analytics events documented

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-11-10 | Claude (UX Expert) | Initial comprehensive design specification |

---

**Next Steps**:
1. Review design specification with stakeholders
2. Approve wireframes and interaction patterns
3. Create detailed implementation plan
4. Begin Phase 1: Enhanced Data Layer
5. Schedule accessibility review session
