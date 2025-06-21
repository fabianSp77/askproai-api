# QuickSetupWizard Refactoring Specification

## Executive Summary
The QuickSetupWizard is a critical onboarding component that's currently **2,175 lines long** with multiple issues that prevent proper functionality. This specification outlines a complete refactoring approach to create a maintainable, testable, and functional wizard.

## Critical Issues Identified

### 1. **Blocking Issue: PromptTemplateService Missing Methods**
- `RetellAgentProvisioner` depends on `PromptTemplateService::renderPrompt()`
- The service exists but is incomplete (only 50 lines)
- This blocks all new customer onboarding

### 2. **Code Complexity**
- Single file with 2,175 lines
- Mixed concerns: UI, business logic, API calls, validation
- 7 wizard steps with complex interdependencies
- Inline HTML generation in PHP methods

### 3. **Error Handling**
- Generic try-catch blocks that hide specific errors
- No proper validation feedback to users
- Silent failures in API integrations

### 4. **State Management**
- Complex state transitions not properly managed
- Edit mode vs create mode handled inconsistently
- No proper state persistence between steps

## Refactored Architecture

### 1. **Component Separation**

```
app/Filament/Admin/Pages/QuickSetupWizard/
├── QuickSetupWizard.php                    # Main page (200 lines max)
├── Steps/
│   ├── CompanySetupStep.php                # Step 1: Company & Branch
│   ├── PhoneConfigurationStep.php          # Step 2: Phone Setup
│   ├── CalendarIntegrationStep.php         # Step 3: Cal.com
│   ├── AIAgentSetupStep.php                # Step 4: Retell.ai
│   ├── IntegrationCheckStep.php            # Step 5: Live Testing
│   ├── TeamServicesStep.php                # Step 6: Staff & Services
│   └── ReviewHealthCheckStep.php           # Step 7: Final Review
├── Actions/
│   ├── CreateCompanyAction.php
│   ├── SetupPhoneNumbersAction.php
│   ├── IntegrateCalcomAction.php
│   ├── ProvisionRetellAgentAction.php
│   └── RunHealthCheckAction.php
├── Forms/
│   ├── CompanyForm.php
│   ├── BranchForm.php
│   ├── PhoneStrategyForm.php
│   └── StaffServiceForm.php
└── Services/
    ├── WizardStateManager.php              # Handles wizard state
    ├── WizardValidator.php                 # Step validation
    └── WizardDataTransformer.php          # Data transformation
```

### 2. **Immediate Fix for Blocking Issue**

Create a minimal working PromptTemplateService:

```php
// app/Services/PromptTemplateService.php
<?php

namespace App\Services;

use App\Models\Branch;

class PromptTemplateService
{
    public function renderPrompt(Branch $branch, string $industry = 'generic', array $additionalData = []): string
    {
        $templates = [
            'medical' => $this->getMedicalTemplate(),
            'beauty' => $this->getBeautyTemplate(),
            'handwerk' => $this->getHandwerkTemplate(),
            'legal' => $this->getLegalTemplate(),
            'generic' => $this->getGenericTemplate(),
        ];
        
        $template = $templates[$industry] ?? $templates['generic'];
        
        return $this->replaceVariables($template, $branch, $additionalData);
    }
    
    private function getGenericTemplate(): string
    {
        return <<<PROMPT
Sie sind ein freundlicher KI-Assistent für {{company_name}}.
Ihre Aufgabe ist es, Anrufe entgegenzunehmen und Termine zu vereinbaren.

Firmendetails:
- Name: {{company_name}}
- Standort: {{branch_name}} in {{branch_city}}
- Telefon: {{branch_phone}}

Öffnungszeiten:
{{business_hours}}

Verfügbare Services:
{{services_list}}

Anweisungen:
1. Begrüßen Sie den Anrufer freundlich
2. Erfragen Sie den gewünschten Service
3. Finden Sie einen passenden Termin
4. Bestätigen Sie die Buchung
PROMPT;
    }
    
    private function replaceVariables(string $template, Branch $branch, array $additionalData): string
    {
        $variables = [
            '{{company_name}}' => $branch->company->name,
            '{{branch_name}}' => $branch->name,
            '{{branch_city}}' => $branch->city ?? 'Unknown',
            '{{branch_phone}}' => $branch->phone_number ?? '',
            '{{business_hours}}' => $this->formatBusinessHours($branch),
            '{{services_list}}' => $this->formatServices($branch),
        ];
        
        return str_replace(array_keys($variables), array_values($variables), $template);
    }
    
    // Additional helper methods...
}
```

### 3. **Simplified Main Wizard Class**

```php
// app/Filament/Admin/Pages/QuickSetupWizard.php
<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Filament\Admin\Pages\QuickSetupWizard\Steps\CompanySetupStep;
use App\Filament\Admin\Pages\QuickSetupWizard\Services\WizardStateManager;

class QuickSetupWizard extends Page
{
    protected static string $view = 'filament.admin.pages.quick-setup-wizard';
    
    protected WizardStateManager $stateManager;
    
    public function mount(): void
    {
        $this->stateManager = new WizardStateManager();
        $this->stateManager->initialize($this->getWizardId());
    }
    
    protected function getSteps(): array
    {
        return [
            CompanySetupStep::make($this->stateManager),
            PhoneConfigurationStep::make($this->stateManager),
            CalendarIntegrationStep::make($this->stateManager),
            AIAgentSetupStep::make($this->stateManager),
            IntegrationCheckStep::make($this->stateManager),
            TeamServicesStep::make($this->stateManager),
            ReviewHealthCheckStep::make($this->stateManager),
        ];
    }
    
    public function completeSetup(): void
    {
        $this->stateManager->complete();
        $this->redirect(route('admin.dashboard'));
    }
}
```

### 4. **Step Interface**

```php
// app/Filament/Admin/Pages/QuickSetupWizard/Contracts/WizardStep.php
<?php

namespace App\Filament\Admin\Pages\QuickSetupWizard\Contracts;

interface WizardStep
{
    public function getSchema(): array;
    public function validate(): bool;
    public function save(): void;
    public function canProceed(): bool;
    public function getValidationErrors(): array;
}
```

## Implementation Priority

### Phase 1: Critical Fixes (Day 1)
1. **Fix PromptTemplateService** - Unblock onboarding
2. **Extract validation logic** - Create WizardValidator service
3. **Simplify RetellAgentProvisioner** - Remove hard dependencies

### Phase 2: Core Refactoring (Days 2-3)
1. **Extract wizard steps** - Create separate step classes
2. **Implement state management** - WizardStateManager
3. **Create action classes** - Separate business logic

### Phase 3: Testing & Polish (Day 4)
1. **Unit tests** for each component
2. **Integration tests** for full wizard flow
3. **Error handling improvements**
4. **Loading states and progress indicators**

## Unit Test Specifications

### 1. **PromptTemplateService Tests**
```php
class PromptTemplateServiceTest extends TestCase
{
    /** @test */
    public function it_renders_generic_template_with_branch_data()
    {
        $branch = Branch::factory()->create();
        $service = new PromptTemplateService();
        
        $prompt = $service->renderPrompt($branch);
        
        $this->assertStringContainsString($branch->company->name, $prompt);
        $this->assertStringContainsString($branch->name, $prompt);
    }
    
    /** @test */
    public function it_uses_industry_specific_template()
    {
        $branch = Branch::factory()->create();
        $service = new PromptTemplateService();
        
        $prompt = $service->renderPrompt($branch, 'medical');
        
        $this->assertStringContainsString('Praxis', $prompt);
        $this->assertStringContainsString('Patient', $prompt);
    }
}
```

### 2. **WizardStateManager Tests**
```php
class WizardStateManagerTest extends TestCase
{
    /** @test */
    public function it_persists_state_between_steps()
    {
        $manager = new WizardStateManager();
        $manager->initialize('test-wizard');
        
        $manager->saveStepData('company', ['name' => 'Test Company']);
        $manager->moveToStep(2);
        
        $this->assertEquals(2, $manager->getCurrentStep());
        $this->assertEquals('Test Company', $manager->getStepData('company')['name']);
    }
}
```

### 3. **Integration Test**
```php
class QuickSetupWizardIntegrationTest extends TestCase
{
    /** @test */
    public function it_completes_full_wizard_flow()
    {
        $this->actingAs($this->createAdminUser());
        
        // Step 1: Company Setup
        Livewire::test(QuickSetupWizard::class)
            ->set('data.company_name', 'Test Salon')
            ->set('data.industry', 'beauty')
            ->call('nextStep');
            
        // Continue through all steps...
        
        $this->assertDatabaseHas('companies', ['name' => 'Test Salon']);
        $this->assertDatabaseHas('branches', ['company_id' => 1]);
    }
}
```

## Migration Strategy

### Step 1: Create New Structure (Non-Breaking)
1. Create new directory structure
2. Implement new classes alongside existing
3. Add feature flag for gradual rollout

### Step 2: Gradual Migration
1. Extract one step at a time
2. Test each extraction thoroughly
3. Keep old code as fallback

### Step 3: Cleanup
1. Remove old code
2. Update documentation
3. Train support team

## Performance Improvements

### 1. **Lazy Loading**
- Load step components only when needed
- Defer API calls until integration check step

### 2. **Caching**
- Cache industry templates
- Cache validation results
- Cache API responses

### 3. **Async Operations**
- Queue agent provisioning
- Background health checks
- Parallel API calls where possible

## Error Recovery

### 1. **Graceful Degradation**
- If Retell.ai fails, allow manual agent setup later
- If Cal.com fails, provide alternative calendar options
- Save progress at each step

### 2. **Clear Error Messages**
```php
class WizardErrorHandler
{
    public function handleApiError($service, $error)
    {
        return match($service) {
            'calcom' => 'Cal.com Verbindung fehlgeschlagen. Bitte prüfen Sie Ihren API-Key.',
            'retell' => 'Retell.ai ist momentan nicht erreichbar. Sie können den Agent später einrichten.',
            default => 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.'
        };
    }
}
```

## Success Metrics

1. **Wizard completion rate** > 80%
2. **Average completion time** < 5 minutes
3. **Error rate** < 5%
4. **Support tickets** reduced by 50%

## Next Steps

1. **Immediate**: Fix PromptTemplateService (30 minutes)
2. **Today**: Extract validation logic (2 hours)
3. **Tomorrow**: Begin step extraction (4 hours)
4. **This Week**: Complete refactoring (16 hours total)

## Conclusion

This refactoring will transform the QuickSetupWizard from a monolithic, error-prone component into a modular, testable, and maintainable system. The immediate fix for PromptTemplateService unblocks customer onboarding, while the broader refactoring ensures long-term stability and developer productivity.