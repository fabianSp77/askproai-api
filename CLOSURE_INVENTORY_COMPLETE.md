# Complete Closure Inventory - Filament Components

**Date**: 2025-10-22
**Scope**: All PHP closures in CustomerResource that cannot serialize
**Serialization Issue**: When `activeRelationManager` state changes, Livewire attempts to serialize page state, including header actions with closures, causing snapshot corruption

---

## Quick Reference

**Total Closures Found**: 8
**Status**: 5 require fixing, 3 already using arrow functions

| Count | Component | File | Status |
|-------|-----------|------|--------|
| 3 | ViewCustomer (Header) | Pages/ViewCustomer.php | ❌ CRITICAL |
| 1 | AppointmentsRelationManager | RelationManagers/AppointmentsRelationManager.php | ❌ HIGH |
| 2 | CustomerRiskAlerts | Widgets/CustomerRiskAlerts.php | ❌ HIGH |
| 2 | AppointmentsRelationManager (arrow fn) | RelationManagers/AppointmentsRelationManager.php | ✅ SAFE |

---

## DetailedInventory

### 1. ViewCustomer.php - Header Actions (CRITICAL)

#### 1.1 Add Email Action (Line 56)
```php
Actions\Action::make('addEmail')
    ->label('E-Mail hinzufügen')
    ->icon('heroicon-o-envelope')
    ->form([
        \Filament\Forms\Components\TextInput::make('email')
            ->label('E-Mail-Adresse')
            ->email()
            ->required(),
    ])
    ->action(function (array $data) {  // ← CLOSURE (Line 56)
        $this->record->update(['email' => $data['email']]);
        Notification::success()
            ->title('E-Mail hinzugefügt')
            ->body('E-Mail-Adresse wurde erfolgreich gespeichert.')
            ->send();
    });
```

**Problem**:
- Closes over `$this` (the page)
- When Livewire serializes, this closure can't be serialized
- Results in corrupted component state

**Current Location**: Lines 46-62
**Type**: Form action closure
**Captured Variables**: `$this` (implicit)
**Impact**: CRITICAL - Part of page header state

---

#### 1.2 Add Note Action (Line 77)
```php
Actions\Action::make('addNote')
    ->label('Notiz hinzufügen')
    ->icon('heroicon-o-pencil-square')
    ->form([
        \Filament\Forms\Components\TextInput::make('subject')
            ->label('Betreff')
            ->required(),
        \Filament\Forms\Components\Textarea::make('content')
            ->label('Inhalt')
            ->required()
            ->rows(3),
    ])
    ->action(function (array $data) {  // ← CLOSURE (Line 77)
        $this->record->notes()->create([
            'subject' => $data['subject'],
            'content' => $data['content'],
            'type' => 'general',
            'created_by' => auth()->id(),
        ]);

        Notification::success()
            ->title('Notiz hinzugefügt')
            ->send();
    });
```

**Problem**: Same as above
**Current Location**: Lines 65-88
**Type**: Form action closure
**Captured Variables**: `$this` (implicit)
**Impact**: CRITICAL - Part of page header state

---

#### 1.3 Merge Modal Description (Line 120)
```php
foreach ($duplicates->take(3) as $duplicate) {
    $duplicateActions[] = Actions\Action::make('merge_' . $duplicate->id)
        ->label('Mit #' . $duplicate->id . ' zusammenführen')
        ->icon('heroicon-o-arrow-path')
        ->requiresConfirmation()
        ->modalHeading('Kunden zusammenführen?')
        ->modalDescription(function () use ($duplicate) {  // ← CLOSURE with USE (Line 120)
            $service = new \App\Services\Customer\CustomerMergeService();
            $preview = $service->previewMerge($this->record, $duplicate);

            return "Kunde #{$duplicate->id} ({$duplicate->name}) wird mit diesem Kunden zusammengeführt.\n\n" .
                   "Übertragen werden:\n" .
                   "• {$preview['duplicate']['calls']} Anruf(e)\n" .
                   "• {$preview['duplicate']['appointments']} Termin(e)\n" .
                   "• €" . number_format($preview['duplicate']['revenue'], 2) . " Umsatz\n\n" .
                   "Dieser Vorgang kann nicht rückgängig gemacht werden!";
        })
```

**Problem**:
- Captures `$duplicate` (a Customer model) with `use($duplicate)`
- Models cannot serialize
- Creates non-serializable closure
- This is in a loop - multiple instances

**Current Location**: Lines 114-130
**Type**: Modal description closure
**Captured Variables**: `$duplicate` (Customer model)
**Impact**: CRITICAL - Blocks serialization

---

#### 1.4 Merge Action (Line 132)
```php
        ->modalSubmitActionLabel('Jetzt zusammenführen')
        ->action(function () use ($duplicate) {  // ← CLOSURE with USE (Line 132)
            $service = new \App\Services\Customer\CustomerMergeService();
            $stats = $service->merge($this->record, $duplicate);

            Notification::success()
                ->title('Kunden erfolgreich zusammengeführt')
                ->body("Übertragen: {$stats['calls_transferred']} Anrufe, {$stats['appointments_transferred']} Termine")
                ->send();

            redirect()->to(route('filament.admin.resources.customers.view', ['record' => $this->record->id]));
        });
```

**Problem**: Same as 1.3 above
**Current Location**: Lines 114-142
**Type**: Action closure
**Captured Variables**: `$duplicate` (Customer model), implicit `$this`
**Impact**: CRITICAL - Multiple instances in loop

---

### 2. AppointmentsRelationManager.php

#### 2.1 viewFailedCalls Action (Line 264)
```php
Tables\Actions\Action::make('viewFailedCalls')
    ->label('Fehlgeschlagene Anrufe anzeigen')
    ->icon('heroicon-o-phone-x-mark')
    ->color('warning')
    ->visible(fn () => $this->ownerRecord->calls()
        ->where('appointment_made', 1)
        ->whereNull('converted_appointment_id')
        ->count() > 0)
    ->action(function () {  // ← CLOSURE (Line 264)
        // Scroll to calls relation manager
        $this->dispatch('scrollToRelation', relation: 'calls');
    }),
```

**Problem**:
- Regular closure (not arrow function)
- Closes over `$this` (the relation manager)
- Relation managers serialize when parent state changes
- Triggers serialization attempt

**Current Location**: Line 264
**Type**: Action closure
**Captured Variables**: `$this` (implicit)
**Impact**: HIGH - Blocks relation manager serialization

---

#### 2.2 Confirm Action (Line 186)
```php
Tables\Actions\Action::make('confirm')
    ->label('Bestätigen')
    ->icon('heroicon-o-check')
    ->color('success')
    ->visible(fn ($record) => $record->status === 'scheduled')
    ->action(fn ($record) => $record->update(['status' => 'confirmed']))  // ← ARROW FUNCTION ✅
    ->requiresConfirmation(),
```

**Status**: ✅ SAFE - Arrow function, not a closure
**Note**: Arrow functions are shorthand and don't have the same serialization issues

---

#### 2.3 Cancel Action (Line 193)
```php
Tables\Actions\Action::make('cancel')
    ->label('Absagen')
    ->icon('heroicon-o-x-mark')
    ->color('danger')
    ->visible(fn ($record) => in_array($record->status, ['scheduled', 'confirmed']))
    ->action(fn ($record) => $record->update(['status' => 'cancelled']))  // ← ARROW FUNCTION ✅
    ->requiresConfirmation(),
```

**Status**: ✅ SAFE - Arrow function

---

#### 2.4 Send Reminders Bulk Action (Line 203)
```php
Tables\Actions\BulkAction::make('sendReminders')
    ->label('Erinnerungen senden')
    ->icon('heroicon-o-bell')
    ->action(fn ($records) => $records->each->update(['reminder_24h_sent_at' => now()]))  // ← ARROW FUNCTION ✅
    ->deselectRecordsAfterCompletion()
    ->requiresConfirmation(),
```

**Status**: ✅ SAFE - Arrow function

---

### 3. CustomerRiskAlerts.php - Widget Actions

#### 3.1 Contact Action (Line 118)
```php
Tables\Actions\Action::make('contact')
    ->label('Kontaktieren')
    ->icon('heroicon-m-phone')
    ->color('primary')
    ->form([
        \Filament\Forms\Components\Select::make('contact_type')
            ->label('Kontaktart')
            ->options([
                'call' => '📞 Anrufen',
                'sms' => '💬 SMS senden',
                'email' => '📧 E-Mail senden',
                'special_offer' => '🎁 Sonderangebot',
            ])
            ->required(),
        \Filament\Forms\Components\Textarea::make('notes')
            ->label('Notiz')
            ->rows(2),
    ])
    ->action(function ($record, array $data) {  // ← CLOSURE (Line 118)
        // Log contact attempt
        $record->update([
            'last_contact_at' => now(),
            'notes' => ($record->notes ?? '') . "\n[" . now()->format('d.m.Y') . "] Kontakt: " . $data['contact_type'] . " - " . ($data['notes'] ?? ''),
        ]);

        \Filament\Notifications\Notification::make()
            ->title('Kunde kontaktiert')
            ->body("Kontakt zu {$record->name} wurde dokumentiert.")
            ->success()
            ->send();
    }),
```

**Problem**:
- Regular closure in widget action
- Widget has `#[Reactive]` attribute (CustomerRiskAlerts inherits from TableWidget)
- When parent state changes, widget re-renders
- Closure can't serialize during re-render

**Current Location**: Line 118
**Type**: Form action closure
**Captured Variables**: `$this` (implicit)
**Impact**: HIGH - Widget action serialization

---

#### 3.2 Win-back Action (Line 136)
```php
Tables\Actions\Action::make('win_back')
    ->label('Rückgewinnung')
    ->icon('heroicon-m-gift')
    ->color('success')
    ->action(function ($record) {  // ← CLOSURE (Line 136)
        $record->update([
            'journey_status' => 'prospect',
            'engagement_score' => min(100, $record->engagement_score + 20),
        ]);

        \Filament\Notifications\Notification::make()
            ->title('Rückgewinnungskampagne gestartet')
            ->body("Kunde wurde für Rückgewinnung markiert.")
            ->success()
            ->send();
    }),
```

**Problem**: Same as 3.1 above
**Current Location**: Line 136
**Type**: Action closure
**Captured Variables**: `$this` (implicit)
**Impact**: HIGH - Widget action serialization

---

## Closure Categories

### Category 1: Header Action Closures (ViewCustomer)
- **Count**: 3 closures (addEmail, addNote, merge x2)
- **Type**: Form action, Modal description
- **Serialization**: Required during relation manager tab switch
- **Severity**: CRITICAL
- **Fix Difficulty**: HIGH (need to refactor form handling)

### Category 2: Relation Manager Action Closures
- **Count**: 1 closure (viewFailedCalls in AppointmentsRelationManager)
- **Type**: Table action with dispatch
- **Serialization**: Required during relation manager render
- **Severity**: HIGH
- **Fix Difficulty**: MEDIUM (simple dispatch, can move to method)

### Category 3: Widget Action Closures (CustomerRiskAlerts)
- **Count**: 2 closures (contact, win_back)
- **Type**: Form action, Simple action
- **Serialization**: Required during widget reactive update
- **Severity**: HIGH
- **Fix Difficulty**: MEDIUM (simple updates, can move to methods)

### Category 4: Arrow Functions (Safe)
- **Count**: 3 arrow functions
- **Type**: Query filters, state updates
- **Serialization**: Not an issue
- **Severity**: NONE
- **Status**: ✅ LEAVE AS IS

---

## Why Closures Fail Serialization

```php
// Regular closure - cannot serialize
function () use ($model) {
    $model->update([...]);
}
// PHP Error: Object of class Closure could not be converted to string

// Arrow function - simplified syntax, different handling
fn ($model) => $model->update([...])
// Generally safer, but context-dependent

// Static method - always safe
public static function handleAction($record) {
    $record->update([...]);
}

// Livewire callback method - always safe
#[On('actionName')]
public function handleAction($record) {
    $record->update([...]);
}
```

---

## Fix Mapping

| Closure | File | Line | Fix Type | Difficulty |
|---------|------|------|----------|------------|
| addEmail action | ViewCustomer | 56 | Extract to method | HARD |
| addNote action | ViewCustomer | 77 | Extract to method | HARD |
| merge modalDescription | ViewCustomer | 120 | Pass as string, build in method | HARD |
| merge action | ViewCustomer | 132 | Use Livewire action/dispatch | HARD |
| viewFailedCalls action | AppointmentsRelationManager | 264 | Extract to method | EASY |
| contact action | CustomerRiskAlerts | 118 | Extract to method | EASY |
| win_back action | CustomerRiskAlerts | 136 | Extract to method | EASY |

---

## Testing Strategy

**Before Fixes**:
1. Navigate to customer view page ✅ Works
2. Click relation manager tab ❌ Fails with snapshot error

**After Each Fix**:
1. Clear browser cache
2. Open DevTools console
3. Navigate to customer view
4. Click each relation manager tab
5. Verify no "Snapshot missing" errors
6. Verify footer widgets render
7. Click action buttons
8. Verify form submissions work

---

**Created**: 2025-10-22
**Purpose**: Complete inventory of non-serializable closures before implementation
