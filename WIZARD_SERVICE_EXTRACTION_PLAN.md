# QuickSetupWizard Service Extraction Plan

## Current State Analysis

The `completeSetup()` method in `QuickSetupWizard.php` is a monolithic method (230+ lines) that handles multiple responsibilities:

1. **Company Management** (Create/Update)
2. **Branch Management** (Create/Update multiple branches)
3. **Phone Number Configuration** (Direct numbers, hotlines, routing)
4. **Cal.com Integration** (API key setup, event type import)
5. **Service Creation** (Template-based services)
6. **Staff Creation** (Initial staff member)
7. **Retell Agent Provisioning** (AI agent setup)
8. **Transaction Management** (DB transactions, error handling)
9. **Progress Tracking** (UI updates)
10. **Notifications** (Success/error messages)

## Proposed Service Architecture

### 1. Core Services

#### `CompanySetupService`
**Responsibility**: Orchestrates the entire setup process
```php
class CompanySetupService {
    public function __construct(
        private CompanyService $companyService,
        private BranchSetupService $branchService,
        private PhoneSetupService $phoneService,
        private CalcomSetupService $calcomService,
        private ServiceTemplateService $serviceTemplateService,
        private StaffSetupService $staffService,
        private RetellSetupService $retellService,
        private SetupProgressTracker $progressTracker
    ) {}
    
    public function setup(SetupRequest $request): SetupResult
    public function update(UpdateRequest $request): SetupResult
}
```

#### `SetupRequest` / `UpdateRequest` DTOs
**Purpose**: Encapsulate all wizard form data
```php
class SetupRequest {
    public string $companyName;
    public string $industry;
    public ?string $logo;
    public array $branches;
    public PhoneConfiguration $phoneConfig;
    public CalcomConfiguration $calcomConfig;
    public array $services;
    public array $staff;
    public RetellConfiguration $retellConfig;
}
```

### 2. Domain-Specific Services

#### `BranchSetupService`
**Responsibility**: Handle branch creation/updates
```php
class BranchSetupService {
    public function createBranches(Company $company, array $branchData): Collection
    public function updateBranch(Branch $branch, array $data): Branch
    public function setupBusinessHours(Branch $branch, string $industry): void
}
```

#### `PhoneSetupService`
**Responsibility**: Configure phone numbers and routing
```php
class PhoneSetupService {
    public function setupForCompany(Company $company, PhoneConfiguration $config): void
    public function configureDirectNumbers(array $branches, array $phoneNumbers): void
    public function setupHotline(Company $company, HotlineConfig $config): void
}
```

#### `CalcomSetupService`
**Responsibility**: Handle Cal.com integration
```php
class CalcomSetupService {
    public function configure(Company $company, CalcomConfiguration $config): void
    public function importEventTypes(Company $company, Branch $branch): void
    public function testConnection(string $apiKey): ConnectionResult
}
```

#### `ServiceTemplateService`
**Responsibility**: Create services from industry templates
```php
class ServiceTemplateService {
    public function createFromTemplate(Company $company, string $industry): Collection
    public function getTemplateServices(string $industry): array
}
```

#### `StaffSetupService`
**Responsibility**: Handle staff creation
```php
class StaffSetupService {
    public function createInitialStaff(Company $company, Branch $branch, array $staffData): Collection
    public function assignToServices(Staff $staff, Company $company): void
}
```

#### `RetellSetupService`
**Responsibility**: Provision Retell AI agents
```php
class RetellSetupService {
    public function setupForBranch(Branch $branch, RetellConfiguration $config): AgentResult
    public function scheduleTestCall(Branch $branch): void
}
```

### 3. Supporting Services

#### `SetupProgressTracker`
**Responsibility**: Track and report setup progress
```php
class SetupProgressTracker {
    public function start(): void
    public function update(string $message, int $percentage): void
    public function complete(): void
    public function failed(string $error): void
}
```

#### `SetupValidator`
**Responsibility**: Validate setup data before processing
```php
class SetupValidator {
    public function validateSetupRequest(SetupRequest $request): ValidationResult
    public function validatePhoneNumbers(array $phoneNumbers): ValidationResult
}
```

## Implementation Strategy

### Phase 1: Create DTOs and Contracts (1 hour)
1. Create `SetupRequest` and related DTOs
2. Define service contracts/interfaces
3. Create `SetupResult` response objects

### Phase 2: Extract Services (3 hours)
1. Start with `ServiceTemplateService` (simplest)
2. Extract `PhoneSetupService`
3. Extract `BranchSetupService`
4. Extract `StaffSetupService`
5. Extract `CalcomSetupService`
6. Extract `RetellSetupService`

### Phase 3: Create Orchestrator (2 hours)
1. Implement `CompanySetupService`
2. Add transaction management
3. Implement progress tracking
4. Add error handling and rollback

### Phase 4: Refactor Wizard (1 hour)
1. Update `QuickSetupWizard` to use services
2. Simplify `completeSetup()` method
3. Update tests

### Phase 5: Testing (2 hours)
1. Unit tests for each service
2. Integration tests for orchestrator
3. E2E test for complete flow

## Benefits

1. **Separation of Concerns**: Each service has a single responsibility
2. **Testability**: Each service can be unit tested independently
3. **Reusability**: Services can be used outside the wizard
4. **Maintainability**: Easier to modify individual aspects
5. **Error Handling**: Better error isolation and recovery
6. **Progress Tracking**: Centralized progress management

## Migration Path

1. Create services alongside existing code
2. Gradually move logic from wizard to services
3. Keep wizard working during migration
4. Final step: replace monolithic method with service calls

## Example: Refactored completeSetup()

```php
public function completeSetup(): void
{
    $validator = app(SetupValidator::class);
    $setupService = app(CompanySetupService::class);
    
    try {
        // Build request from form data
        $request = SetupRequest::fromArray($this->data);
        
        // Validate
        $validation = $validator->validateSetupRequest($request);
        if (!$validation->isValid()) {
            throw new ValidationException($validation->getErrors());
        }
        
        // Execute setup
        $result = $this->editMode 
            ? $setupService->update($this->editingCompany, $request)
            : $setupService->setup($request);
            
        // Handle result
        if ($result->isSuccessful()) {
            $this->handleSuccess($result);
        } else {
            $this->handleFailure($result);
        }
        
    } catch (\Exception $e) {
        $this->handleException($e);
    }
}
```

## Next Steps

1. **Review** this plan with the team
2. **Prioritize** which services to extract first
3. **Create** service provider for dependency injection
4. **Implement** services incrementally
5. **Test** thoroughly at each step