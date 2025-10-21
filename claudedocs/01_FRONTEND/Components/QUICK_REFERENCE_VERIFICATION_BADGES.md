# Quick Reference: Mobile-Friendly Verification Badges

**Last Updated**: 2025-10-20
**Component Location**: `/resources/views/components/`

---

## Quick Decision Matrix

| Requirement | Use Component | Why |
|-------------|---------------|-----|
| Minimal visual change | `mobile-verification-badge` | Keeps familiar tooltip UX |
| Touch-first design | `verification-badge-inline` | No floating elements |
| Limited table space | `mobile-verification-badge` | Compact icon only |
| Critical info display | `verification-badge-inline` | Always expandable |
| Desktop-only users | `mobile-verification-badge` | Better hover experience |
| Tablet/mobile majority | `verification-badge-inline` | Consistent tap behavior |

---

## Component 1: mobile-verification-badge.blade.php

### Quick Usage
```php
view('components.mobile-verification-badge', [
    'name' => 'John Doe',
    'verified' => true,
    'verificationSource' => 'customer_linked',
    'additionalInfo' => null,
    'phone' => '+49 123 456789',
])->render()
```

### Props Reference
```php
'name'               => string   // Customer name (required)
'verified'           => bool     // true = green ✓, false = orange !, null = no badge
'verificationSource' => string   // 'customer_linked', 'phone_verified', 'phonetic_match', 'ai_extracted'
'additionalInfo'     => string   // Extra info (e.g., "85% match")
'phone'              => string   // Customer phone (shown in tooltip)
```

### Visual Output
```
Desktop: [Name ✓] → hover shows tooltip
Mobile:  [Name ✓] → tap shows tooltip
```

---

## Component 2: verification-badge-inline.blade.php

### Quick Usage
```php
view('components.verification-badge-inline', [
    'name' => 'John Doe',
    'verified' => true,
    'verificationSource' => 'phone_verified',
    'phone' => '+49 123 456789',
])->render()
```

### Props Reference
Same as Component 1

### Visual Output
```
Collapsed: [Name ✓]
Expanded:  [Name ✓]
           └─ Details shown inline below
```

---

## Integration in Filament Tables

### Basic Pattern
```php
Tables\Columns\TextColumn::make('customer.name')
    ->html()
    ->getStateUsing(function ($record) {
        return view('components.mobile-verification-badge', [
            'name' => $record->customer->name,
            'verified' => $record->metadata['customer_verified'] ?? true,
            'verificationSource' => $record->metadata['verification_source'] ?? 'customer_linked',
            'phone' => $record->customer->phone,
        ])->render();
    })
```

### With Fallback
```php
->getStateUsing(function ($record) {
    if (!$record->customer) {
        return '<span class="text-gray-400">Kein Kunde</span>';
    }

    return view('components.mobile-verification-badge', [
        'name' => $record->customer->name,
        'verified' => true,
        'verificationSource' => 'customer_linked',
    ])->render();
})
```

---

## Verification Sources Quick Reference

| Source | Icon | Color | Tooltip Title | Meaning |
|--------|------|-------|---------------|---------|
| `customer_linked` | ✓ | Green | "Verifizierter Kunde" | Customer record exists (100%) |
| `phone_verified` | ✓ | Green | "Verifiziert via Telefon" | Phone number matched (99%) |
| `phonetic_match` | ✓ | Green | "Phonetische Übereinstimmung" | Name matched phonetically (80-95%) |
| `ai_extracted` | ! | Orange | "Unverifiziert" | Name from transcript (0-50%) |
| `null` | - | - | - | No badge shown |

---

## Common Customizations

### Change Icon Size
```php
// In component file, find:
$iconClass = 'w-4 h-4';

// Change to:
$iconClass = 'w-5 h-5';  // Medium
$iconClass = 'w-6 h-6';  // Large
```

### Change Colors
```php
// Verified (Green → Blue)
$iconColor = 'text-blue-600 dark:text-blue-400';

// Unverified (Orange → Red)
$iconColor = 'text-red-600 dark:text-red-400';
```

### Change Tooltip Position
```html
<!-- Below icon (default) -->
class="absolute z-50 left-0 top-full mt-2"

<!-- Above icon -->
class="absolute z-50 left-0 bottom-full mb-2"

<!-- Right side -->
class="absolute z-50 left-full ml-2 top-0"
```

---

## Testing Quick Checks

### Desktop
```bash
# Hover tooltip appears? ✓
# Tooltip disappears on unhover? ✓
# Click doesn't toggle (only hover)? ✓
```

### Mobile (Chrome DevTools)
```bash
# Toggle device toolbar (Cmd+Shift+M / Ctrl+Shift+M)
# Select iPhone/iPad
# Tap icon shows tooltip? ✓
# Tap outside closes tooltip? ✓
```

### Accessibility
```bash
# Tab to icon (keyboard navigation)? ✓
# Focus ring visible? ✓
# Screen reader announces status? ✓
```

---

## Troubleshooting Quick Fixes

### Tooltip Not Showing
```bash
# Check: Alpine.js loaded?
console.log(typeof Alpine)  # Should be "object", not "undefined"
```

### Tooltip Cut Off
```php
// Add to table config:
->extraAttributes(['style' => 'overflow-x: visible;'])
```

### Icons Broken
```bash
# Publish Filament assets
php artisan filament:assets
```

---

## Code Snippets

### Full Filament Column Implementation
```php
Tables\Columns\TextColumn::make('customer.name')
    ->label('Kunde')
    ->searchable()
    ->sortable()
    ->icon('heroicon-m-user')
    ->html()
    ->getStateUsing(function ($record) {
        if (!$record->customer) {
            return '<span class="text-gray-400">Kein Kunde</span>';
        }

        $verified = $record->metadata['customer_verified'] ?? true;
        $source = $record->metadata['verification_source'] ?? 'customer_linked';
        $info = $record->metadata['verification_info'] ?? null;

        return view('components.mobile-verification-badge', [
            'name' => $record->customer->name,
            'verified' => $verified,
            'verificationSource' => $source,
            'additionalInfo' => $info,
            'phone' => $record->customer->phone,
        ])->render();
    })
    ->description(fn ($record) => $record->customer?->phone)
```

### Adding Metadata to Appointments
```php
Appointment::create([
    'customer_id' => $customer->id,
    // ... other fields ...
    'metadata' => [
        'customer_verified' => true,
        'verification_source' => 'phone_verified',
        'verification_info' => '99% match',
        'verified_at' => now(),
    ],
]);
```

---

## Performance Notes

- **Caching**: Phonetic lookups cached for 1 hour
- **Indexes**: Ensure `phonetic_name_soundex` and `phonetic_name_metaphone` indexed
- **Transitions**: GPU-accelerated via Alpine.js (no jank)

---

**Files**:
- Component 1: `/resources/views/components/mobile-verification-badge.blade.php`
- Component 2: `/resources/views/components/verification-badge-inline.blade.php`
- Implementation: `/app/Filament/Resources/AppointmentResource.php` (line 628)
- Full Guide: `/claudedocs/01_FRONTEND/Components/MOBILE_VERIFICATION_BADGE_IMPLEMENTATION.md`
