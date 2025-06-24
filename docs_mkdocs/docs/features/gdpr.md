# GDPR Compliance

## Overview

AskProAI is designed with GDPR (General Data Protection Regulation) compliance at its core, ensuring that all personal data is handled according to European privacy laws.

## Data Protection Principles

### Lawfulness, Fairness, and Transparency
- Clear privacy policies
- Explicit consent mechanisms
- Transparent data processing

### Purpose Limitation
- Data collected only for appointment booking
- No secondary use without consent
- Clear data retention policies

### Data Minimization
- Only essential data collected
- No excessive information storage
- Regular data cleanup

## Personal Data Handling

### Types of Personal Data
```php
// Personal data fields
protected $personalData = [
    'name',           // Customer name
    'phone',          // Phone number
    'email',          // Email address
    'date_of_birth',  // For age verification
    'notes',          // Appointment notes
    'call_recordings' // Retell.ai recordings
];
```

### Encryption
```php
// Automatic encryption for sensitive fields
class Customer extends Model
{
    protected $encrypted = [
        'phone',
        'email',
        'date_of_birth'
    ];
}
```

## Data Subject Rights

### Right to Access
```php
// Export customer data
Route::get('/gdpr/export', function (Request $request) {
    $customer = Customer::findByPhone($request->phone);
    
    return response()->json([
        'personal_data' => $customer->exportPersonalData(),
        'appointments' => $customer->appointments,
        'calls' => $customer->calls
    ]);
});
```

### Right to Erasure
```php
// Delete customer data
Route::delete('/gdpr/delete', function (Request $request) {
    $customer = Customer::findByPhone($request->phone);
    
    // Anonymize instead of hard delete
    $customer->anonymize();
    
    return response()->json(['status' => 'data_anonymized']);
});
```

### Right to Rectification
```php
// Update personal data
Route::put('/gdpr/update', function (Request $request) {
    $customer = Customer::findByPhone($request->phone);
    $customer->updatePersonalData($request->validated());
    
    return response()->json(['status' => 'data_updated']);
});
```

## Consent Management

### Obtaining Consent
```php
// Record consent
$consent = new Consent([
    'customer_id' => $customer->id,
    'type' => 'marketing_communications',
    'granted' => true,
    'granted_at' => now(),
    'ip_address' => $request->ip()
]);
$consent->save();
```

### Consent Types
- Appointment booking (legitimate interest)
- Marketing communications (explicit consent)
- Call recording (explicit consent)
- Data sharing with third parties (explicit consent)

## Data Retention

### Retention Periods
```yaml
appointment_data: 2 years
call_recordings: 90 days
customer_data: 5 years (or until deletion requested)
logs: 30 days
backups: 90 days
```

### Automated Cleanup
```php
// Scheduled cleanup job
class GDPRCleanupJob extends Job
{
    public function handle()
    {
        // Delete old call recordings
        CallRecording::where('created_at', '<', now()->subDays(90))
            ->delete();
        
        // Anonymize old appointments
        Appointment::where('created_at', '<', now()->subYears(2))
            ->anonymize();
    }
}
```

## Data Processing Agreements

### Third-Party Processors
- **Retell.ai**: Call processing and recording
- **Cal.com**: Calendar management
- **AWS/Google Cloud**: Infrastructure
- **Stripe**: Payment processing

### Security Measures
```php
// Data processor audit log
class ProcessorAuditLog extends Model
{
    protected $fillable = [
        'processor_name',
        'data_type',
        'operation',
        'timestamp',
        'purpose'
    ];
}
```

## Privacy by Design

### Technical Measures
```php
// Pseudonymization
class CustomerService
{
    public function pseudonymize($customer)
    {
        return hash('sha256', $customer->phone . config('app.key'));
    }
}

// Access logging
class GDPRMiddleware
{
    public function handle($request, $next)
    {
        if ($request->routeIs('gdpr.*')) {
            AccessLog::create([
                'user_id' => auth()->id(),
                'action' => $request->route()->getName(),
                'data_subject' => $request->phone,
                'timestamp' => now()
            ]);
        }
        
        return $next($request);
    }
}
```

## Breach Notification

### Detection System
```php
// Breach detection
class BreachDetector
{
    public function detectAnomalies()
    {
        // Monitor for unusual access patterns
        $suspiciousActivity = AccessLog::suspicious()->get();
        
        if ($suspiciousActivity->isNotEmpty()) {
            $this->notifyDataProtectionOfficer($suspiciousActivity);
        }
    }
}
```

### Notification Process
1. Detect breach within 24 hours
2. Assess impact and risk
3. Notify authorities within 72 hours
4. Notify affected individuals if high risk

## Documentation

### Required Documentation
- Privacy Policy
- Cookie Policy
- Data Processing Records
- Consent Records
- Breach Register

### Compliance Dashboard
```php
// GDPR compliance status
class GDPRDashboard
{
    public function getComplianceStatus()
    {
        return [
            'encryption_enabled' => true,
            'consent_management' => true,
            'data_retention_policy' => true,
            'breach_notification' => true,
            'dpo_appointed' => true,
            'privacy_policy_updated' => '2025-06-01'
        ];
    }
}
```

## Related Documentation
- [Security Configuration](../configuration/security.md)
- [Data Retention Policies](../operations/maintenance.md)
- [API Authentication](../api/authentication.md)