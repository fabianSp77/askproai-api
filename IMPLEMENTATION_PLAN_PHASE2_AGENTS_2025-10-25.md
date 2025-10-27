# ServiceResource Phase 2 - Agent Orchestration Plan

**Date:** 2025-10-25
**Mode:** --orchestrate + --delegate (Parallel Agent Execution)
**Target:** Phase 2 Operational Visibility (18h → 6h with parallelization)

---

## 🎯 Phase 2 Goals

**Focus:** Operational Visibility + Business Metrics

### Features to Implement:
1. **Staff Assignment Column** (List View) - 3h
2. **Staff Assignment Section** (Detail View) - 4h
3. **Enhanced Pricing Display** (List View) - 2h
4. **Enhanced Appointment Statistics** (List View) - 3h
5. **Booking Statistics Section** (Detail View) - 6h

**Total Sequential:** 18 hours
**With Parallelization:** ~6 hours (67% time savings)

---

## 📊 Agent Assignment Matrix

| Agent | Specialization | Task | Files | Est. Time | Parallel Group |
|-------|---------------|------|-------|-----------|----------------|
| **Agent 5** | frontend-developer | Staff Assignment Column | ServiceResource.php | 3h | Group A |
| **Agent 6** | frontend-developer | Staff Assignment Section | ViewService.php | 4h | Group A |
| **Agent 7** | frontend-developer | Enhanced Pricing Display | ServiceResource.php | 2h | Group B |
| **Agent 8** | frontend-developer | Enhanced Appointment Stats | ServiceResource.php | 3h | Group B |
| **Agent 9** | frontend-developer | Booking Statistics Section | ViewService.php | 6h | Solo |

**Parallelization Strategy:**
- Group A: Agents 5 & 6 (Staff Assignment) → 4h (max of 3h, 4h)
- Group B: Agents 7 & 8 (List enhancements) → 3h (max of 2h, 3h)
- Agent 9: Solo (Complex statistics) → 6h

**Sequential Execution:**
1. Launch Groups A & B parallel → 4h
2. Launch Agent 9 after Groups A & B → 6h
3. Total: 10h (vs 18h sequential = 44% savings)

**OR Aggressive Parallel:**
- All 5 agents at once → 6h (max of all)
- Total: 6h (67% savings)

---

## 🚀 Phase 2: Feature Specifications

### Agent 5: Staff Assignment Column (List View)
**Priority:** 🟡 Important
**Concurrency:** Parallel with Agent 6

**Task:**
Add new column to ServiceResource table showing staff assignment information.

**Location:** `app/Filament/Resources/ServiceResource.php` (after appointments_count column)

**Requirements:**

1. **Column displays:**
   - If `assignment_method = 'any'`: "👥 Alle verfügbaren"
   - If `assignment_method = 'specific'`: "👤 {count} zugewiesen"
   - If `assignment_method = 'preferred'`: "⭐ {preferred_name} (+{count} weitere)"

2. **Badge color:**
   - 'any' → gray
   - 'specific' or 'preferred' → info

3. **Tooltip shows:**
   - Assignment method (Any/Specific/Preferred)
   - List of assigned staff names (max 5)
   - If more than 5: "... und {x} weitere"

4. **Sortable** by staff count

**Implementation:**
```php
Tables\Columns\TextColumn::make('staff_assignment')
    ->label('Mitarbeiter')
    ->getStateUsing(function ($record) {
        $config = $record->policyConfiguration;
        $method = $config?->staff_assignment_method ?? 'any';

        if ($method === 'any') {
            return '👥 Alle verfügbaren';
        }

        $count = $record->allowedStaff()->count();

        if ($method === 'preferred' && $config?->preferred_staff_id) {
            $staff = \App\Models\Staff::find($config->preferred_staff_id);
            return $staff ? "⭐ {$staff->name} (+{$count})" : "👤 {$count} zugewiesen";
        }

        return "👤 {$count} zugewiesen";
    })
    ->badge()
    ->color(fn ($record) =>
        ($record->policyConfiguration?->staff_assignment_method ?? 'any') === 'any'
            ? 'gray'
            : 'info'
    )
    ->tooltip(function ($record) {
        $config = $record->policyConfiguration;
        if (!$config) return 'Keine Konfiguration';

        $method = $config->staff_assignment_method ?? 'any';
        $parts = ["Methode: " . match($method) {
            'any' => 'Alle verfügbaren',
            'specific' => 'Spezifische Auswahl',
            'preferred' => 'Bevorzugt',
            default => $method,
        }];

        if ($method !== 'any') {
            $staff = $record->allowedStaff()->pluck('name')->take(5);
            if ($staff->count() > 0) {
                $parts[] = "Mitarbeiter: " . $staff->join(', ');
                if ($record->allowedStaff()->count() > 5) {
                    $remaining = $record->allowedStaff()->count() - 5;
                    $parts[] = "... und {$remaining} weitere";
                }
            }
        }

        return implode("\n", $parts);
    })
    ->sortable(query: function ($query, $direction) {
        $query->withCount('allowedStaff')
            ->orderBy('allowed_staff_count', $direction);
    }),
```

**Expected Output:** Complete code block for new column

---

### Agent 6: Staff Assignment Section (Detail View)
**Priority:** 🟡 Important
**Concurrency:** Parallel with Agent 5

**Task:**
Add comprehensive Staff Assignment section to service detail view.

**Location:** `app/Filament/Resources/ServiceResource/Pages/ViewService.php` (after "Preise & Buchungsregeln", before "Cal.com Integration")

**Requirements:**

1. **Section shows:**
   - Assignment method badge
   - Preferred staff (if method = 'preferred')
   - List of allowed staff
   - Policy settings (auto-assign, double-booking, breaks)

2. **Grid layout:**
   - Row 1: Assignment method + Preferred staff
   - Row 2: Allowed staff list (full width)
   - Row 3: 3 policy toggles (auto-assign, double-booking, breaks)

**Implementation:**
```php
Section::make('Mitarbeiter & Zuweisungen')
    ->description('Welche Mitarbeiter können diesen Service ausführen')
    ->icon('heroicon-o-user-group')
    ->schema([
        Grid::make(2)->schema([
            TextEntry::make('assignment_method')
                ->label('Zuweisungsmethode')
                ->getStateUsing(fn ($record) =>
                    $record->policyConfiguration?->staff_assignment_method ?? 'any'
                )
                ->formatStateUsing(fn ($state) => match($state) {
                    'any' => '👥 Alle verfügbaren Mitarbeiter',
                    'specific' => '👤 Spezifische Mitarbeiter',
                    'preferred' => '⭐ Bevorzugter Mitarbeiter',
                    default => $state,
                })
                ->badge()
                ->color(fn ($state) => $state === 'any' ? 'gray' : 'info'),

            TextEntry::make('preferred_staff')
                ->label('Bevorzugter Mitarbeiter')
                ->getStateUsing(function ($record) {
                    $preferredId = $record->policyConfiguration?->preferred_staff_id;
                    if (!$preferredId) return null;

                    $staff = \App\Models\Staff::find($preferredId);
                    return $staff ? $staff->name : 'Nicht gefunden';
                })
                ->placeholder('Kein bevorzugter Mitarbeiter')
                ->visible(fn ($record) =>
                    ($record->policyConfiguration?->staff_assignment_method ?? 'any') === 'preferred'
                )
                ->icon('heroicon-m-star'),
        ]),

        TextEntry::make('allowed_staff')
            ->label('Zugelassene Mitarbeiter')
            ->getStateUsing(function ($record) {
                $method = $record->policyConfiguration?->staff_assignment_method ?? 'any';

                if ($method === 'any') {
                    $count = \App\Models\Staff::where('company_id', $record->company_id)
                        ->where('is_active', true)
                        ->count();
                    return "Alle aktiven Mitarbeiter ({$count})";
                }

                $staff = $record->allowedStaff;
                if ($staff->isEmpty()) {
                    return 'Keine Mitarbeiter zugewiesen';
                }

                return $staff->pluck('name')->join(', ');
            })
            ->badge()
            ->color('info')
            ->columnSpanFull(),

        Grid::make(3)->schema([
            IconEntry::make('policyConfiguration.auto_assign_staff')
                ->label('Auto-Zuweisung')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('gray'),

            IconEntry::make('policyConfiguration.allow_double_booking')
                ->label('Doppelbuchung erlaubt')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('gray'),

            IconEntry::make('policyConfiguration.respect_staff_breaks')
                ->label('Pausen respektieren')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('gray'),
        ]),
    ]),
```

**Expected Output:** Complete Section code block

---

### Agent 7: Enhanced Pricing Display (List View)
**Priority:** 🟡 Important
**Concurrency:** Parallel with Agent 8

**Task:**
Replace simple price column with enhanced pricing display including hourly rate and deposit indicator.

**Location:** `app/Filament/Resources/ServiceResource.php` (replace existing price column)

**Requirements:**

1. **Main display:**
   - Base price: "50.00 €"
   - Hourly rate: " (75.00 €/h)" if duration exists
   - Deposit icon: " 💰" if deposit required

2. **Description line:**
   - If deposit required: "Anzahlung: {amount} €"

3. **Tooltip:**
   - "Grundpreis: {price} €"
   - "Stundensatz: {hourly_rate} €/h" (if duration exists)
   - "Anzahlung erforderlich: {deposit} €" (if deposit required)

**Implementation:**
```php
Tables\Columns\TextColumn::make('pricing')
    ->label('Preis')
    ->getStateUsing(function ($record) {
        $price = number_format($record->price, 2) . ' €';

        if ($record->duration_minutes > 0) {
            $hourlyRate = number_format($record->price / ($record->duration_minutes / 60), 2);
            $price .= " ({$hourlyRate} €/h)";
        }

        if ($record->deposit_required) {
            $price .= " 💰";
        }

        return $price;
    })
    ->description(fn ($record) =>
        $record->deposit_required
            ? "Anzahlung: " . number_format($record->deposit_amount, 2) . " €"
            : null
    )
    ->tooltip(function ($record) {
        $parts = [
            "Grundpreis: " . number_format($record->price, 2) . " €",
        ];

        if ($record->duration_minutes > 0) {
            $hourlyRate = number_format($record->price / ($record->duration_minutes / 60), 2);
            $parts[] = "Stundensatz: {$hourlyRate} €/h";
        }

        if ($record->deposit_required) {
            $parts[] = "Anzahlung erforderlich: " . number_format($record->deposit_amount, 2) . " €";
        }

        return implode("\n", $parts);
    })
    ->sortable(query: function ($query, $direction) {
        $query->orderBy('price', $direction);
    }),
```

**Expected Output:** Complete column replacement code

---

### Agent 8: Enhanced Appointment Statistics (List View)
**Priority:** 🟡 Important
**Concurrency:** Parallel with Agent 7

**Task:**
Replace simple appointments_count column with enhanced statistics including revenue and recent activity.

**Location:** `app/Filament/Resources/ServiceResource.php` (replace appointments_count column)

**Requirements:**

1. **Main display:**
   - "{count} Termine • {revenue} €"
   - Example: "15 Termine • 750 €"

2. **Badge color:**
   - count > 10: success
   - count > 0: info
   - count = 0: gray

3. **Description:**
   - Recent activity: "📈 {recent_count} neue (30 Tage)"
   - Only show if recent_count > 0

4. **Tooltip:**
   - "Gesamt: {total}"
   - "Abgeschlossen: {completed}"
   - "Storniert: {cancelled}"
   - "Umsatz: {revenue} €"

**Implementation:**
```php
Tables\Columns\TextColumn::make('appointment_stats')
    ->label('Termine & Umsatz')
    ->getStateUsing(function ($record) {
        $count = $record->appointments()->count();
        $revenue = $record->appointments()
            ->where('status', 'completed')
            ->sum('price');

        return "{$count} Termine • " . number_format($revenue, 0) . " €";
    })
    ->badge()
    ->color(fn ($record) => {
        $count = $record->appointments()->count();
        return $count > 10 ? 'success' : ($count > 0 ? 'info' : 'gray');
    })
    ->description(function ($record) {
        $recent = $record->appointments()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return $recent > 0 ? "📈 {$recent} neue (30 Tage)" : null;
    })
    ->tooltip(function ($record) {
        $total = $record->appointments()->count();
        $completed = $record->appointments()->where('status', 'completed')->count();
        $cancelled = $record->appointments()->where('status', 'cancelled')->count();
        $revenue = $record->appointments()->where('status', 'completed')->sum('price');

        return implode("\n", [
            "Gesamt: {$total}",
            "Abgeschlossen: {$completed}",
            "Storniert: {$cancelled}",
            "Umsatz: " . number_format($revenue, 2) . " €",
        ]);
    })
    ->sortable(query: function ($query, $direction) {
        $query->withCount('appointments')
            ->orderBy('appointments_count', $direction);
    }),
```

**Expected Output:** Complete column replacement code

---

### Agent 9: Booking Statistics Section (Detail View)
**Priority:** 🟡 Important
**Concurrency:** After Groups A & B or Solo

**Task:**
Add comprehensive Booking Statistics section with business metrics and trends.

**Location:** `app/Filament/Resources/ServiceResource/Pages/ViewService.php` (after "Preise & Buchungsregeln", before "Cal.com Integration")

**Requirements:**

1. **Section collapsed by default**

2. **Grid 1 (4 columns):**
   - Total appointments
   - Completed appointments
   - Cancelled appointments
   - Total revenue

3. **Grid 2 (3 columns):**
   - This month count
   - Last month count
   - Last booking (relative time)

**Implementation:**
```php
Section::make('Buchungsstatistiken')
    ->description('Übersicht über Terminhistorie und Performance')
    ->icon('heroicon-o-chart-bar')
    ->collapsed()
    ->schema([
        Grid::make(4)->schema([
            TextEntry::make('stats.total')
                ->label('Gesamt')
                ->getStateUsing(fn ($record) => $record->appointments()->count())
                ->badge()
                ->color('gray')
                ->suffix(' Termine'),

            TextEntry::make('stats.completed')
                ->label('Abgeschlossen')
                ->getStateUsing(fn ($record) =>
                    $record->appointments()->where('status', 'completed')->count()
                )
                ->badge()
                ->color('success')
                ->suffix(' Termine'),

            TextEntry::make('stats.cancelled')
                ->label('Storniert')
                ->getStateUsing(fn ($record) =>
                    $record->appointments()->where('status', 'cancelled')->count()
                )
                ->badge()
                ->color('danger')
                ->suffix(' Termine'),

            TextEntry::make('stats.revenue')
                ->label('Umsatz')
                ->getStateUsing(fn ($record) =>
                    $record->appointments()
                        ->where('status', 'completed')
                        ->sum('price')
                )
                ->money('EUR')
                ->badge()
                ->color('success'),
        ]),

        Grid::make(3)->schema([
            TextEntry::make('stats.this_month')
                ->label('Diesen Monat')
                ->getStateUsing(fn ($record) =>
                    $record->appointments()
                        ->whereMonth('start_time', now()->month)
                        ->whereYear('start_time', now()->year)
                        ->count()
                )
                ->badge()
                ->color('info')
                ->suffix(' Termine'),

            TextEntry::make('stats.last_month')
                ->label('Letzter Monat')
                ->getStateUsing(fn ($record) =>
                    $record->appointments()
                        ->whereMonth('start_time', now()->subMonth()->month)
                        ->whereYear('start_time', now()->subMonth()->year)
                        ->count()
                )
                ->badge()
                ->color('gray')
                ->suffix(' Termine'),

            TextEntry::make('stats.last_booking')
                ->label('Letzte Buchung')
                ->getStateUsing(function ($record) {
                    $last = $record->appointments()
                        ->latest('created_at')
                        ->first();

                    return $last ? $last->created_at->diffForHumans() : 'Keine Buchungen';
                })
                ->badge()
                ->color('info'),
        ]),
    ]),
```

**Expected Output:** Complete Section code block

---

## ⏱️ Execution Timeline

### Aggressive Parallel (6 hours)
```
Hour 0-6: ALL AGENTS PARALLEL
├─ Agent 5: Staff Assignment Column (3h)
├─ Agent 6: Staff Assignment Section (4h)
├─ Agent 7: Enhanced Pricing Display (2h)
├─ Agent 8: Enhanced Appointment Stats (3h)
└─ Agent 9: Booking Statistics Section (6h)

Hour 6-7: CODE REVIEW
└─ Comprehensive review of all Phase 2 changes

Hour 7-8: TESTING
└─ Manual + Automated testing
```

**Total: 8 hours** (vs 18h sequential = 56% savings)

---

## 🎯 Success Criteria

### Must Have
- ✅ Staff assignment visible in list and detail
- ✅ Pricing shows hourly rate + deposits
- ✅ Appointment stats show revenue + trends
- ✅ Booking statistics section complete
- ✅ All features sortable/filterable where applicable
- ✅ No N+1 queries
- ✅ Code review passed

### Should Have
- ✅ Tooltips helpful and informative
- ✅ Visual hierarchy clear
- ✅ Performance acceptable
- ✅ Mobile-friendly

---

## 📊 Expected Impact

### Before Phase 2
- ❌ Staff assignments hidden (have to open each service)
- ❌ No hourly rate visible (hard to compare efficiency)
- ❌ No revenue metrics (only appointment count)
- ❌ No business trends (can't see growth)

### After Phase 2
- ✅ Staff assignments at a glance
- ✅ Pricing efficiency visible (hourly rate)
- ✅ Revenue metrics in list view
- ✅ Complete business dashboard in detail view
- ✅ Operational decisions 5x faster

---

**Status:** 📋 Plan Ready for Execution
**Next Action:** Launch all 5 agents in parallel
**Estimated Completion:** 8 hours from start
**Risk Level:** Low (no database changes, UI only)

Ready to execute? 🚀
