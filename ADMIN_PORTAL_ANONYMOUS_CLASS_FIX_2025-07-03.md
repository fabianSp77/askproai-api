# Admin Portal Anonymous Class Fix

## Date: 2025-07-03

## Issue
`Call to undefined method stdClass::hasPermission()` when admin accesses portal.

## Root Cause
The dummy user object created for admin viewing was using `stdClass` with assigned function properties, but PHP doesn't allow calling methods this way on stdClass objects.

## Solution
Use PHP anonymous classes to create a proper object with actual methods:

```php
// Instead of stdClass with function properties:
$user = new \stdClass();
$user->hasPermission = function() { return true; };

// Use anonymous class with real methods:
$user = new class($company) {
    public $company;
    public $company_id;
    public $id = 'admin';
    
    public function __construct($company) {
        $this->company = $company;
        $this->company_id = $company->id;
    }
    
    public function hasPermission($permission) {
        return true; // Admin has all permissions
    }
    
    public function canViewBilling() {
        return true;
    }
    
    public function teamMembers() {
        return collect();
    }
};
```

## Result
The admin dummy user now has proper methods that can be called throughout the portal views and controllers.

## Query Log Analysis
From the error page queries, we can see:
- Company 15 (AskProAI) is being loaded correctly
- Phone numbers for company 15 are being fetched
- This confirms the correct company data is being loaded

The issue was only with the method call syntax, not with the company data.