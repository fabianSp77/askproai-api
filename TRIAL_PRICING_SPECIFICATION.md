# Trial-Based Pricing Implementation Specification

## Executive Summary

This specification outlines a minimal, non-intrusive implementation of a trial-based pricing system for AskProAI that maximizes user success while maintaining simplicity. The approach avoids modifying the QuickSetupWizard and focuses on automatic trial activation with clear upgrade paths.

## 1. Default Trial Parameters

### Trial Configuration
```php
const TRIAL_DURATION_DAYS = 30;
const TRIAL_INCLUDED_MINUTES = 500;
const TRIAL_PRICE_PER_MINUTE = 0.00; // Free during trial
const TRIAL_OVERAGE_ALLOWED = false; // Hard stop at 500 minutes
const TRIAL_GRACE_PERIOD_DAYS = 7; // After trial ends
```

### Trial Features
- **Duration**: 30 days from company creation
- **Included Minutes**: 500 AI phone minutes
- **Price**: €0 (completely free)
- **Overage**: Not allowed (calls blocked after 500 minutes)
- **Grace Period**: 7 days to upgrade after trial ends

## 2. Auto-Applied Pricing Structure

### During Trial Period
```php
// Automatically created when company is registered
[
    'price_per_minute' => 0.00,
    'setup_fee' => 0.00,
    'monthly_base_fee' => 0.00,
    'included_minutes' => 500,
    'overage_price_per_minute' => null, // No overage allowed
    'valid_from' => $company->created_at,
    'valid_until' => $company->created_at->addDays(30),
    'notes' => 'Automatische Testphase - 30 Tage, 500 Minuten kostenlos'
]
```

### Post-Trial Default Pricing (Auto-Applied)
```php
// Automatically created to start after trial ends
[
    'price_per_minute' => 0.29,
    'setup_fee' => 0.00, // Waived for trial conversions
    'monthly_base_fee' => 49.00,
    'included_minutes' => 100,
    'overage_price_per_minute' => 0.39,
    'valid_from' => $company->created_at->addDays(31),
    'valid_until' => null,
    'notes' => 'Standard-Tarif nach Testphase'
]
```

### Available Pricing Plans (Shown in Dashboard)
1. **Starter** (Default after trial)
   - €49/Monat Grundgebühr
   - 100 Inklusivminuten
   - €0.39/Minute darüber

2. **Professional**
   - €149/Monat Grundgebühr
   - 500 Inklusivminuten
   - €0.29/Minute darüber

3. **Enterprise**
   - €349/Monat Grundgebühr
   - 1500 Inklusivminuten
   - €0.19/Minute darüber

## 3. Post-Trial Conversion Flow

### Automated Timeline
```
Day 0: Company created → Trial starts automatically
Day 20: First upgrade reminder (email + dashboard notice)
Day 25: Second reminder + usage report
Day 28: Final reminder + urgency messaging
Day 30: Trial ends → Grace period starts
Day 31-37: Grace period (limited features)
Day 38: Account suspended (data preserved)
```

### Grace Period Behavior
- Read-only access to dashboard
- No new calls accepted
- Existing appointments honored
- Data export available
- One-click reactivation

## 4. Implementation Steps

### Phase 1: Database Schema (Day 1)
1. Add trial fields to companies table
2. Create pricing_plans table
3. Create trial_notifications table
4. Add indexes for performance

### Phase 2: Core Services (Day 2-3)
1. Create TrialService
2. Extend PricingService
3. Create TrialNotificationService
4. Add middleware for trial enforcement

### Phase 3: Dashboard Integration (Day 4-5)
1. Create TrialStatusWidget
2. Add usage tracking widget
3. Create upgrade prompt component
4. Implement pricing plan selector

### Phase 4: Automation (Day 6)
1. Create trial monitoring commands
2. Set up notification queues
3. Implement usage alerts
4. Add conversion tracking

### Phase 5: Testing & Polish (Day 7-8)
1. End-to-end testing
2. German translations
3. Email template design
4. Documentation

## 5. Database Changes

### New Migration: add_trial_fields_to_companies
```php
Schema::table('companies', function (Blueprint $table) {
    $table->boolean('is_trial')->default(true);
    $table->datetime('trial_started_at')->nullable();
    $table->datetime('trial_ends_at')->nullable();
    $table->integer('trial_minutes_used')->default(0);
    $table->integer('trial_minutes_limit')->default(500);
    $table->enum('trial_status', ['active', 'expired', 'converted', 'suspended'])
          ->default('active');
    $table->datetime('trial_converted_at')->nullable();
    $table->string('selected_plan')->nullable();
    $table->datetime('last_trial_notification_at')->nullable();
    $table->string('trial_notification_stage')->nullable();
    
    // Indexes
    $table->index(['is_trial', 'trial_status']);
    $table->index(['trial_ends_at']);
});
```

### New Table: pricing_plans
```php
Schema::create('pricing_plans', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->decimal('monthly_base_fee', 10, 2);
    $table->integer('included_minutes');
    $table->decimal('price_per_minute', 10, 4);
    $table->decimal('overage_price_per_minute', 10, 4);
    $table->json('features'); // Feature list for display
    $table->boolean('is_popular')->default(false);
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### New Table: trial_notifications
```php
Schema::create('trial_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->string('type'); // day_20, day_25, day_28, expired, etc.
    $table->enum('channel', ['email', 'dashboard', 'both']);
    $table->json('metadata')->nullable();
    $table->boolean('was_read')->default(false);
    $table->datetime('sent_at');
    $table->timestamps();
    
    $table->index(['company_id', 'type']);
});
```

## 6. Service Modifications

### TrialService (New)
```php
class TrialService
{
    public function initializeTrial(Company $company): void;
    public function checkTrialStatus(Company $company): array;
    public function recordUsage(Company $company, int $minutes): void;
    public function canMakeCall(Company $company): bool;
    public function convertToPaid(Company $company, string $planSlug): bool;
    public function suspendAccount(Company $company): void;
    public function getTrialStats(Company $company): array;
    public function getRemainingMinutes(Company $company): int;
    public function getDaysRemaining(Company $company): int;
}
```

### PricingService (Extended)
```php
// Add to existing PricingService
public function isInTrial(Company $company): bool;
public function getTrialPricing(Company $company): ?CompanyPricing;
public function shouldBlockCall(Company $company): bool;
public function createPostTrialPricing(Company $company): void;
```

### NotificationService (Extended)
```php
// Add trial-specific notifications
public function sendTrialReminder(Company $company, string $stage): void;
public function sendTrialExpiredNotice(Company $company): void;
public function sendUsageAlert(Company $company, int $percentUsed): void;
public function sendConversionThankYou(Company $company): void;
```

## 7. UI Components

### TrialStatusWidget (New Dashboard Widget)
```php
// Shows in main dashboard
class TrialStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.trial-status';
    
    public function getTrialData(): array
    {
        return [
            'days_remaining' => $this->trialService->getDaysRemaining($company),
            'minutes_remaining' => $this->trialService->getRemainingMinutes($company),
            'usage_percentage' => $this->calculateUsagePercentage(),
            'show_urgency' => $this->shouldShowUrgency(),
        ];
    }
}
```

### Dashboard Modifications
1. **SystemStatsOverview**: Add trial status indicator
2. **CallKpiWidget**: Show minutes remaining
3. **Header**: Add persistent trial banner (last 7 days)
4. **Navigation**: Add "Upgrade" button with badge

### New Pages
1. **PricingPlansPage**: Compare and select plans
2. **UpgradeWizard**: Simple 3-step upgrade flow
3. **UsageReportPage**: Detailed usage analytics

## 8. Notification Templates

### Email Templates (German)
```blade
{{-- trial-reminder-day-20.blade.php --}}
Betreff: Noch 10 Tage in Ihrer kostenlosen Testphase! 🎯

Guten Tag {{ $company->name }},

Ihre kostenlose Testphase läuft in 10 Tagen ab. Bisher haben Sie:
- {{ $minutesUsed }} von 500 Minuten genutzt
- {{ $appointmentsBooked }} Termine gebucht
- {{ $hourssSaved }} Stunden gespart

Sichern Sie sich jetzt Ihren Wunschtarif:
[Tarif wählen]

{{-- trial-expired.blade.php --}}
Betreff: Ihre Testphase ist beendet - Jetzt upgraden! ⏰

Ihre 30-tägige Testphase ist abgelaufen. 
Sie haben noch 7 Tage Zeit, um Ihren Account zu aktivieren.

[Jetzt aktivieren]
```

### Dashboard Notifications
```php
// Shown as Filament notifications
Notification::make()
    ->title('Testphase läuft bald ab')
    ->body("Noch {$days} Tage und {$minutes} Minuten verfügbar")
    ->warning()
    ->persistent()
    ->actions([
        Action::make('upgrade')
            ->label('Jetzt upgraden')
            ->url(route('filament.admin.pages.pricing-plans'))
    ])
    ->send();
```

## 9. Middleware & Guards

### TrialEnforcementMiddleware
```php
class TrialEnforcementMiddleware
{
    public function handle($request, Closure $next)
    {
        $company = $request->user()->company;
        
        if ($this->trialService->isExpired($company)) {
            // Redirect to upgrade page for write operations
            if (!$request->isMethod('GET')) {
                return redirect()->route('filament.admin.pages.upgrade-wizard')
                    ->with('error', 'Bitte wählen Sie einen Tarif aus.');
            }
        }
        
        if ($this->trialService->isOverLimit($company)) {
            // Block call-related operations
            if ($request->is('api/retell/*') || $request->is('api/calls/*')) {
                return response()->json([
                    'error' => 'Minutenlimit erreicht. Bitte upgraden Sie Ihren Account.'
                ], 402);
            }
        }
        
        return $next($request);
    }
}
```

## 10. Monitoring & Analytics

### Trial Metrics to Track
```php
// Via TrialAnalyticsService
- Trial start rate (% of registrations)
- Usage patterns (minutes/day)
- Feature adoption (which features used)
- Conversion rate (trial → paid)
- Drop-off points
- Revenue per trial
- Time to conversion
- Churn after conversion
```

### Admin Dashboard Enhancements
```php
// New metrics in UltimateSystemCockpit
- Active trials count
- Trials expiring this week
- Trial conversion rate (30d)
- Average trial usage
- Trial revenue forecast
```

## 11. German Compliance

### Transparent Pricing Display
- All prices shown with "inkl. MwSt." (including VAT)
- Clear breakdown of included/overage minutes
- No hidden fees messaging
- Cancellation terms clearly stated

### GDPR Compliance
- Consent for marketing emails during trial
- Easy data export functionality
- Clear data retention policy
- Right to deletion honored

### Invoice Requirements
- Automatic invoice generation post-trial
- Kleinunternehmerregelung support
- EU VAT handling
- DATEV export compatibility

## 12. Implementation Timeline

### Week 1
- Day 1: Database migrations
- Day 2-3: Core services
- Day 4-5: Dashboard integration

### Week 2  
- Day 6: Automation & notifications
- Day 7-8: Testing & translations
- Day 9-10: Production deployment

### Success Metrics (First 30 days)
- 80% trial activation rate
- 30% trial-to-paid conversion
- <5% support tickets about pricing
- 90% trial users make at least 10 calls

## 13. Rollback Plan

If issues arise:
1. Disable trial enforcement middleware
2. Set all companies to non-trial status
3. Remove trial widgets from dashboard
4. Revert to manual pricing setup

Database changes are backwards compatible, so no data migration needed for rollback.

## 14. Future Enhancements

After successful launch:
1. A/B test different trial lengths (14 vs 30 days)
2. Dynamic pricing based on usage patterns
3. Referral program for trial extensions
4. Industry-specific trial packages
5. Partner integrations for extended trials