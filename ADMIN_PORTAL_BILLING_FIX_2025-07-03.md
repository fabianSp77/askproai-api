# Admin Portal Billing Fix - Company Access

## Date: 2025-07-03

## Issue
`Attempt to read property "company" on null` error in billing views when accessing as admin.

## Root Cause
The billing views were trying to access `Auth::guard('portal')->user()->company`, but when admin is viewing, there is no authenticated portal user.

## Solution

### 1. Updated BillingController to pass company to views
```php
// In topup() method
return view('portal.billing.topup', compact(
    'suggestedAmounts',
    'selectedAmount',
    'company'  // Added
));

// In index() method
return view('portal.billing.index', compact(
    'company',  // Added
    'balanceStatus',
    // ... other variables
));
```

### 2. Updated topup.blade.php to use passed company
```php
// Before
$balanceStatus = app(\App\Services\BalanceMonitoringService::class)
    ->getBalanceStatus(Auth::guard('portal')->user()->company);

// After
$balanceStatus = app(\App\Services\BalanceMonitoringService::class)
    ->getBalanceStatus($company);
```

### 3. Added admin warning to topup page
Shows a warning message when admin is viewing that payments cannot be processed in admin mode.

## Result
Admin can now view billing pages without authentication errors. The pages show:
- Current balance information
- Transaction history
- Topup options (view only for admin)

## Security Note
The processTopup method already has protection against admin processing payments:
```php
if (session('is_admin_viewing')) {
    return back()->with('error', 'Als Administrator können Sie keine Zahlungen für Kunden durchführen...');
}
```