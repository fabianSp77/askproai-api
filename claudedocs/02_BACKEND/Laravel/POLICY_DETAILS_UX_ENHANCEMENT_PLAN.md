# Policy Details UX Enhancement - Interactive Richtlinien-Anzeige

**Date**: 2025-10-11
**Status**: PLANNED
**User Request**: "Welche Richtlinien genau eingehalten/verletzt wurden, wie viele (3 von 3), on click/mouseover"

---

## 🎯 ZIEL

**Aktuell**: Badge zeigt nur "✅ Richtlinie eingehalten" oder "⚠️ Richtlinienverstoß"

**Gewünscht**:
- Welche Richtlinien wurden geprüft?
- Wie viele erfüllt? (z.B. "3 von 3")
- Details zu jeder Regel (erfüllt/nicht erfüllt)
- Interaktiv (Tooltip/Click)

---

## 📊 VERFÜGBARE POLICY-DATEN

### Aus AppointmentModification.metadata

**Beispiel Cancellation** (ID 31):
```json
{
  "call_id": "call_44797c95c05004cfd559fc39cc5",
  "hours_notice": 80.0,           ← Tatsächliche Vorwarnung
  "policy_required": 24,           ← Erforderliche Vorwarnung
  "cancelled_via": "retell_api"
}
```

**Beispiel Reschedule** (ID 30):
```json
{
  "call_id": "call_44797c95c05004cfd559fc39cc5",
  "hours_notice": 79.5,
  "original_time": "2025-10-14T15:00:00+02:00",
  "new_time": "2025-10-14T15:30:00+02:00",
  "rescheduled_via": "retell_api",
  "calcom_synced": true
}
```

### Aus AppointmentPolicyEngine

**PolicyResult Details** (für denied):
```php
details: [
    'hours_notice' => 12.5,
    'required_hours' => 24,
    'fee_if_forced' => 15.00,
]
```

**PolicyResult Details** (für allowed):
```php
details: [
    'hours_notice' => 80.0,
    'required_hours' => 24,
    'policy' => [...],  // Complete policy config
]
```

### Richtlinien die geprüft werden

**Für Cancellation**:
1. ✅ **Vorwarnung** (hours_before): Min. 24h
2. ✅ **Monatslimit** (max_cancellations_per_month): Max. 10/Monat
3. ✅ **Gebührenpflichtig** (fee_applicable): Ja/Nein

**Für Reschedule**:
1. ✅ **Vorwarnung** (hours_before): Min. 24h
2. ✅ **Pro-Termin-Limit** (max_reschedules_per_appointment): Max. 3/Termin
3. ✅ **Gebührenpflichtig** (fee_applicable): Ja/Nein

---

## 🎨 UX-KONZEPT

### Option 1: Tooltip mit Summary (EMPFOHLEN)

**Implementierung**: Filament Tooltip auf Badge

**Badge Display**:
```
✅ Richtlinie eingehalten
```

**Tooltip (on hover)**:
```
┌────────────────────────────────────┐
│ 3 von 3 Regeln erfüllt             │
├────────────────────────────────────┤
│ ✅ Vorwarnung: 80h (min. 24h)      │
│ ✅ Monatslimit: 2/10 verwendet     │
│ ✅ Gebühr: Keine (0,00 €)          │
└────────────────────────────────────┘
```

**Code**:
```php
// In Timeline Widget:
->tooltip(fn ($event) => $this->getPolicyTooltip($event))
```

---

### Option 2: Expandable Details unter Badge (ALTERNATIVE)

**Badge Display**:
```
✅ Richtlinie eingehalten [▼]
```

**On Click** (expand):
```
✅ Richtlinie eingehalten [▲]

Regelprüfung:
├─ ✅ Vorwarnung: 80,0 Stunden (erforderlich: 24h)
├─ ✅ Monatslimit: 2 von 10 Stornierungen
└─ ✅ Gebühr: Kostenlos (0,00 €)
```

**Implementierung**: Blade `<details>` Element

---

### Option 3: Modal mit vollständiger Policy-Info (MAXIMAL)

**Badge Display**:
```
✅ Richtlinie eingehalten [🔍]
```

**On Click** (Modal öffnet):
```
┌─────────────────────────────────────────────┐
│ Richtlinienprüfung - Details                │
├─────────────────────────────────────────────┤
│                                             │
│ Status: ✅ Alle Regeln erfüllt              │
│                                             │
│ Geprüfte Richtlinien: 3 von 3 erfüllt      │
│                                             │
│ 1. ✅ Vorwarnzeit                           │
│    Gegeben: 80,0 Stunden                   │
│    Erforderlich: 24 Stunden                │
│    Differenz: +56h (ausreichend)           │
│                                             │
│ 2. ✅ Monatliches Stornierungslimit        │
│    Verwendet: 2 von 10                     │
│    Verbleibend: 8 Stornierungen           │
│                                             │
│ 3. ✅ Gebührenregelung                     │
│    Status: Gebührenfrei                    │
│    Grund: >24h Vorwarnung                  │
│    Betrag: 0,00 €                          │
│                                             │
│ [Schließen]                                │
└─────────────────────────────────────────────┘
```

---

## 📋 IMPLEMENTATION PLAN

### PHASE A: Tooltip (Quick Win - 2h)

**1. Enhance AppointmentHistoryTimeline.php** (Widget)
- Add method: `getPolicyTooltipText($event)`
- Extract: hours_notice, policy_required, quota info
- Format: "3 von 3 erfüllt: Vorwarnung 80h (min. 24h)"

**2. Update timeline.blade.php** (View)
- Add tooltip to policy badge (line 97)
- Use Filament's built-in tooltip functionality

**Example Code**:
```php
// Widget method:
protected function getPolicyTooltipText(array $event): ?string
{
    if (!isset($event['metadata']['within_policy'])) {
        return null;
    }

    $metadata = $event['metadata']['details'] ?? [];
    $withinPolicy = $event['metadata']['within_policy'];

    $rules = [];

    // Rule 1: Hours notice
    if (isset($metadata['hours_notice']) && isset($metadata['policy_required'])) {
        $hours = round($metadata['hours_notice'], 1);
        $required = $metadata['policy_required'];
        $status = $hours >= $required ? '✅' : '❌';
        $rules[] = "{$status} Vorwarnung: {$hours}h (min. {$required}h)";
    }

    // Rule 2: Quota (if checked)
    if (isset($metadata['quota_used']) && isset($metadata['quota_max'])) {
        $used = $metadata['quota_used'];
        $max = $metadata['quota_max'];
        $status = $used <= $max ? '✅' : '❌';
        $rules[] = "{$status} Monatslimit: {$used}/{$max}";
    }

    // Rule 3: Fee
    $fee = $event['metadata']['fee_charged'] ?? 0;
    $rules[] = "✅ Gebühr: " . number_format($fee, 2) . " €";

    $passedCount = count(array_filter($rules, fn($r) => str_starts_with($r, '✅')));
    $totalCount = count($rules);

    $header = $withinPolicy
        ? "{$passedCount} von {$totalCount} Regeln erfüllt"
        : ($totalCount - $passedCount) . " von {$totalCount} Regeln verletzt";

    return $header . "\n" . implode("\n", $rules);
}
```

```blade
{{-- In timeline.blade.php --}}
@if(isset($event['metadata']['within_policy']))
    <span
        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ ... }}"
        x-tooltip="{!! $this->getPolicyTooltipText($event) !!}">
        {{ $event['metadata']['within_policy'] ? '✅ Richtlinie eingehalten' : '⚠️ Richtlinienverstoß' }}
    </span>
@endif
```

---

### PHASE B: Expandable Details (Enhanced - 3h)

**1. Add expandable policy section in timeline card**

```blade
{{-- Policy badge with expandable details --}}
@if(isset($event['metadata']['within_policy']))
    <details class="mt-2">
        <summary class="cursor-pointer text-xs font-medium text-primary-600 hover:text-primary-800">
            {{ $event['metadata']['within_policy'] ? '✅ Richtlinie eingehalten' : '⚠️ Richtlinienverstoß' }}
            (Details anzeigen)
        </summary>

        <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-900 rounded text-xs space-y-2">
            <div class="font-semibold text-gray-900 dark:text-white">
                Regelprüfung:
            </div>

            @php
                $policyDetails = $this->getPolicyDetails($event);
            @endphp

            @foreach($policyDetails as $rule)
                <div class="flex items-start gap-2">
                    <span>{{ $rule['icon'] }}</span>
                    <div>
                        <div class="font-medium">{{ $rule['name'] }}</div>
                        <div class="text-gray-600 dark:text-gray-400">{{ $rule['details'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </details>
@endif
```

---

### PHASE C: Modal with Full Policy Info (Maximum - 4h)

**1. Create PolicyDetailsModal.php** (Livewire Component)

**2. Add button to badge**:
```blade
<button
    wire:click="$emit('showPolicyDetails', {{ $event['id'] }})"
    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium">
    {{ $withinPolicy ? '✅ Richtlinie eingehalten' : '⚠️ Richtlinienverstoß' }}
    <x-heroicon-o-information-circle class="w-3 h-3"/>
</button>
```

**3. Modal shows**:
- Complete rule breakdown
- Visual indicators (progress bars, checkmarks)
- Historical context (previous violations)
- Fee calculation details

---

## 🎨 RECOMMENDED APPROACH

**Phase A (Tooltip) - QUICK WIN**:
- ✅ Effort: 2 hours
- ✅ Impact: HIGH (immediate clarity)
- ✅ Risk: LOW (simple addition)
- ✅ UX: Non-intrusive, discoverable

**Advantages**:
- Fast to implement
- Works on hover (no click needed)
- Doesn't clutter UI
- Filament built-in tooltip support

**Display Example**:
```
Hover over: "✅ Richtlinie eingehalten"

Tooltip shows:
┌──────────────────────────────────┐
│ 3 von 3 Regeln erfüllt           │
│                                  │
│ ✅ Vorwarnung: 80,0h (min. 24h)  │
│ ✅ Monatslimit: 2/10 verwendet   │
│ ✅ Gebühr: 0,00 €                │
└──────────────────────────────────┘
```

---

## 📋 IMPLEMENTATION DETAILS

### Data Structure Enhancement

**Current** (in AppointmentModification):
```json
{
  "within_policy": true,
  "hours_notice": 80.0,
  "policy_required": 24
}
```

**Enhanced** (what we can build from existing data):
```json
{
  "within_policy": true,
  "rules_checked": [
    {
      "name": "Vorwarnzeit",
      "required": "24 Stunden",
      "actual": "80,0 Stunden",
      "passed": true,
      "details": "+56h Puffer"
    },
    {
      "name": "Monatslimit",
      "required": "Max. 10/Monat",
      "actual": "2 verwendet",
      "passed": true,
      "details": "8 verbleibend"
    },
    {
      "name": "Gebührenregelung",
      "required": "Gebührenfrei bei >24h",
      "actual": "0,00 €",
      "passed": true,
      "details": "Ausreichend Vorwarnung"
    }
  ],
  "summary": {
    "total_rules": 3,
    "passed": 3,
    "failed": 0
  }
}
```

### Widget Method: getPolicyTooltip()

```php
/**
 * Get policy tooltip text for timeline event
 *
 * Shows which rules were checked and their results
 */
protected function getPolicyTooltip(array $event): ?string
{
    if (!isset($event['metadata']['within_policy'])) {
        return null;
    }

    $details = $event['metadata']['details'] ?? [];
    $withinPolicy = $event['metadata']['within_policy'];

    $rules = [];
    $passedCount = 0;
    $totalCount = 0;

    // Rule 1: Hours Notice (always checked)
    if (isset($details['hours_notice']) && isset($details['policy_required'])) {
        $totalCount++;
        $hours = round($details['hours_notice'], 1);
        $required = $details['policy_required'];

        if ($hours >= $required) {
            $passedCount++;
            $rules[] = "✅ Vorwarnung: {$hours}h (min. {$required}h)";
        } else {
            $rules[] = "❌ Vorwarnung: {$hours}h (min. {$required}h erforderlich)";
        }
    }

    // Rule 2: Quota (if checked)
    if (isset($details['quota_used']) && isset($details['quota_max'])) {
        $totalCount++;
        $used = $details['quota_used'];
        $max = $details['quota_max'];

        if ($used <= $max) {
            $passedCount++;
            $rules[] = "✅ Monatslimit: {$used}/{$max} verwendet";
        } else {
            $rules[] = "❌ Monatslimit: {$used}/{$max} (überschritten)";
        }
    }

    // Rule 3: Fee
    $totalCount++;
    $fee = $event['metadata']['fee_charged'] ?? 0;
    if ($fee == 0) {
        $passedCount++;
        $rules[] = "✅ Gebühr: Keine (0,00 €)";
    } else {
        $rules[] = "⚠️ Gebühr: " . number_format($fee, 2) . " €";
    }

    // Build tooltip
    $summary = $withinPolicy
        ? "{$passedCount} von {$totalCount} Regeln erfüllt"
        : ($totalCount - $passedCount) . " von {$totalCount} Regeln verletzt";

    return $summary . "\n\n" . implode("\n", $rules);
}
```

### Blade View Enhancement

```blade
{{-- Current badge (line 94-98) --}}
@if(isset($event['metadata']['within_policy']))
    <span
        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
            {{ $event['metadata']['within_policy'] ? 'bg-success-50 text-success-700' : 'bg-warning-50 text-warning-700' }}"
        title="{{ $this->getPolicyTooltip($event) }}"
        data-tooltip="true">
        {{ $event['metadata']['within_policy'] ? '✅ Richtlinie eingehalten' : '⚠️ Richtlinienverstoß' }}
    </span>
@endif

{{-- Alternative: Filament Tooltip Component --}}
<x-filament::tooltip>
    <x-slot name="trigger">
        <span class="...">
            {{ $event['metadata']['within_policy'] ? '✅ Richtlinie eingehalten' : '⚠️ Richtlinienverstoß' }}
        </span>
    </x-slot>

    <x-slot name="content">
        {!! nl2br(e($this->getPolicyTooltip($event))) !!}
    </x-slot>
</x-filament::tooltip>
```

---

## 💡 ENHANCED POLICY DISPLAY

### For Modification Details Modal

**Current**: Shows basic info
**Enhanced**: Add Policy Rules Section

```blade
{{-- In modification-details.blade.php --}}
@if($modification->within_policy !== null)
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
            📋 Richtlinienprüfung
        </h4>

        <div class="space-y-3">
            {{-- Summary --}}
            <div class="flex items-center gap-2">
                <span class="text-2xl">
                    {{ $modification->within_policy ? '✅' : '⚠️' }}
                </span>
                <div>
                    <div class="font-semibold">
                        {{ $modification->within_policy ? 'Alle Regeln erfüllt' : 'Regelverstoß' }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @php
                            $rules = $this->getPolicyRulesSummary($modification);
                        @endphp
                        {{ $rules['passed'] }} von {{ $rules['total'] }} Regeln eingehalten
                    </div>
                </div>
            </div>

            {{-- Detailed rules --}}
            <div class="space-y-2">
                @foreach($this->getPolicyRulesDetailed($modification) as $rule)
                    <div class="flex items-start gap-3 p-2 rounded
                        {{ $rule['passed'] ? 'bg-success-50' : 'bg-warning-50' }}">
                        <span class="text-lg">{{ $rule['icon'] }}</span>
                        <div class="flex-1">
                            <div class="font-medium text-sm">{{ $rule['name'] }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                {{ $rule['details'] }}
                            </div>
                        </div>
                        @if(!$rule['passed'] && isset($rule['fee']))
                            <span class="text-xs font-semibold text-danger-600">
                                {{ number_format($rule['fee'], 2) }} €
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
```

---

## 🎯 RECOMMENDED IMPLEMENTATION

**Start with**: **Phase A (Tooltip)** - Quick win, high impact

**Files to modify**:
1. `AppointmentHistoryTimeline.php` - Add getPolicyTooltip() method
2. `appointment-history-timeline.blade.php` - Add tooltip to badge
3. `ModificationsRelationManager.php` - Add tooltip to policy column
4. `modification-details.blade.php` - Add detailed policy section

**Estimated Effort**: 2-3 hours
**User Value**: HIGH (immediate clarity on policy compliance)
**Risk**: LOW (additive enhancement)

---

## 📊 EXAMPLE OUTPUTS

### Scenario 1: Within Policy (Cancellation)
```
Tooltip:
3 von 3 Regeln erfüllt

✅ Vorwarnung: 80,0h (min. 24h)
✅ Monatslimit: 2/10 verwendet
✅ Gebühr: 0,00 €
```

### Scenario 2: Outside Policy (Late Cancellation)
```
Tooltip:
1 von 3 Regeln verletzt

❌ Vorwarnung: 12h (min. 24h erforderlich)
✅ Monatslimit: 3/10 verwendet
⚠️ Gebühr: 15,00 € (Kurzfristige Stornierung)
```

### Scenario 3: Quota Exceeded
```
Tooltip:
1 von 3 Regeln verletzt

✅ Vorwarnung: 48h (min. 24h)
❌ Monatslimit: 11/10 (überschritten)
⚠️ Gebühr: 10,00 € (Quotenüberschreitung)
```

---

## 🔮 FUTURE ENHANCEMENTS

**Phase B+**: (Later)
- Visual progress bars for quotas
- Historical policy violation chart
- Customer policy compliance score
- Predictive warnings before booking

---

## ✅ ACCEPTANCE CRITERIA

After implementation:
- [ ] User sieht bei hover über "Richtlinie eingehalten" Details
- [ ] Tooltip zeigt "X von Y Regeln erfüllt"
- [ ] Jede Regel wird einzeln aufgelistet (✅/❌)
- [ ] Vorwarnung mit Stunden angezeigt
- [ ] Monatslimit wenn geprüft angezeigt
- [ ] Gebühr prominent angezeigt
- [ ] Funktioniert bei allen Modifications
- [ ] Dark mode compatible

---

**Recommendation**: Start with Phase A (Tooltip) - 2h effort, immediate user value

Soll ich das jetzt implementieren? 🎯
