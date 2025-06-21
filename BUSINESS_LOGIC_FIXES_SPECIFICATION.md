# Business Logic Fixes - Technische Spezifikation

## Übersicht
Diese Spezifikation definiert Verbesserungen für kritische Business Logic Komponenten mit Fokus auf Resilience, User Experience und Datenintegrität.

---

## 1. RetellAgentProvisioner Validation

### 1.1 Aktueller Provisioning Flow Analyse

**Probleme im aktuellen Flow:**
- Keine Pre-Provisioning Validation der Branch-Daten
- Fehlende Service-Requirements Prüfung
- Unzureichendes Error Handling bei API-Fehlern
- Kein Rollback bei teilweisem Provisioning-Fehler
- Fehlende User-Feedback Mechanismen

### 1.2 Pre-Provisioning Validation Definition

```php
interface ProvisioningValidator {
    public function validateBranchRequirements(Branch $branch): ValidationResult;
    public function validateServiceRequirements(Branch $branch): ValidationResult;
    public function validateApiConnectivity(): ValidationResult;
}

class ValidationResult {
    public bool $isValid;
    public array $errors = [];
    public array $warnings = [];
    public array $requiredActions = [];
}
```

**Validierungsschritte:**
1. **Branch Validation**
   - Branch muss aktiv sein (`is_active = true`)
   - Telefonnummer muss vorhanden und gültig sein
   - Mindestens ein Service muss zugeordnet sein
   - Geschäftszeiten müssen definiert sein

2. **Service Validation**
   - Mindestens ein aktiver Service erforderlich
   - Services müssen Dauer und Preis haben
   - Optional: Staff-Service Zuordnungen prüfen

3. **API Connectivity**
   - Retell API Key vorhanden und gültig
   - API Endpoint erreichbar
   - Ausreichendes API-Guthaben/Limits

### 1.3 Service Requirement Handling

```php
class ServiceRequirementChecker {
    private const REQUIRED_SERVICES = [
        'medical' => ['Erstberatung', 'Behandlung'],
        'beauty' => ['Haarschnitt', 'Beratung'],
        'handwerk' => ['Kostenvoranschlag', 'Vor-Ort-Termin']
    ];
    
    public function checkRequirements(Branch $branch): array {
        $industry = $branch->company->industry;
        $requiredServices = self::REQUIRED_SERVICES[$industry] ?? [];
        $missingServices = [];
        
        foreach ($requiredServices as $serviceName) {
            if (!$branch->services()->where('name', 'LIKE', "%{$serviceName}%")->exists()) {
                $missingServices[] = $serviceName;
            }
        }
        
        return [
            'has_minimum_services' => $branch->services()->active()->count() >= 1,
            'missing_required_services' => $missingServices,
            'can_provision' => empty($missingServices) || $branch->services()->active()->count() >= 1
        ];
    }
}
```

### 1.4 Error Messages und User Feedback

```php
class ProvisioningFeedback {
    const ERROR_MESSAGES = [
        'no_phone' => 'Bitte hinterlegen Sie eine Telefonnummer für diese Filiale.',
        'no_services' => 'Mindestens ein Service muss angelegt sein. Möchten Sie jetzt Services hinzufügen?',
        'no_hours' => 'Geschäftszeiten sind nicht definiert. Standard-Zeiten verwenden?',
        'api_error' => 'Verbindung zu Retell.ai fehlgeschlagen. Bitte API-Key prüfen.',
        'insufficient_credits' => 'Nicht genügend Retell.ai Guthaben. Bitte aufladen.'
    ];
    
    public function generateUserFeedback(ValidationResult $result): array {
        $feedback = [
            'canProceed' => $result->isValid,
            'messages' => [],
            'actions' => []
        ];
        
        foreach ($result->errors as $error) {
            $feedback['messages'][] = [
                'type' => 'error',
                'text' => self::ERROR_MESSAGES[$error] ?? $error,
                'field' => $this->getFieldForError($error)
            ];
        }
        
        // Suggest quick fixes
        if (in_array('no_services', $result->errors)) {
            $feedback['actions'][] = [
                'label' => 'Services hinzufügen',
                'action' => 'openServiceWizard',
                'style' => 'primary'
            ];
        }
        
        return $feedback;
    }
}
```

### 1.5 Rollback bei Provisioning Failure

```php
class ProvisioningTransaction {
    private array $completedSteps = [];
    private array $rollbackHandlers = [];
    
    public function execute(array $steps): Result {
        DB::beginTransaction();
        
        try {
            foreach ($steps as $step) {
                $result = $step->execute();
                
                if ($result->success) {
                    $this->completedSteps[] = $step;
                    
                    if ($step->hasRollback()) {
                        $this->rollbackHandlers[$step->getName()] = $step->getRollbackHandler();
                    }
                } else {
                    throw new ProvisioningException($result->error, $step);
                }
            }
            
            DB::commit();
            return new Result(true, $this->completedSteps);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->executeRollbacks();
            
            return new Result(false, null, [
                'error' => $e->getMessage(),
                'failed_step' => $e instanceof ProvisioningException ? $e->getStep() : null,
                'rolled_back' => true
            ]);
        }
    }
    
    private function executeRollbacks(): void {
        foreach (array_reverse($this->rollbackHandlers) as $handler) {
            try {
                $handler->rollback();
            } catch (\Exception $e) {
                Log::error('Rollback failed', ['handler' => get_class($handler), 'error' => $e->getMessage()]);
            }
        }
    }
}
```

### 1.6 Alternative Lösungsansätze

**Option A: Lazy Provisioning**
- Agent wird erst bei erstem Anruf erstellt
- Vorteile: Keine unnötigen Agents, schnelleres Onboarding
- Nachteile: Erster Anruf könnte fehlschlagen

**Option B: Background Provisioning**
- Agent-Erstellung im Hintergrund nach Setup
- Vorteile: Kein Blocking des Users
- Nachteile: Status-Tracking erforderlich

**Option C: Template-basierte Provisioning**
- Vorgefertigte Agent-Templates pro Industrie
- Vorteile: Schnell und zuverlässig
- Nachteile: Weniger Flexibilität

**Empfehlung:** Kombination aus Option B + C

---

## 2. Booking Flow Resilience

### 2.1 Transaction Management

```php
trait BookingTransactionManager {
    protected function executeBookingTransaction(callable $operation, array $context = []): BookingResult {
        $transactionId = Str::uuid();
        $startTime = microtime(true);
        
        // Start distributed transaction tracking
        $this->trackTransaction($transactionId, 'started', $context);
        
        $retries = 0;
        $maxRetries = 3;
        
        while ($retries < $maxRetries) {
            try {
                DB::beginTransaction();
                
                // Set transaction isolation level for booking operations
                DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
                
                $result = $operation($transactionId);
                
                DB::commit();
                
                $this->trackTransaction($transactionId, 'completed', [
                    'duration_ms' => (microtime(true) - $startTime) * 1000,
                    'retries' => $retries
                ]);
                
                return $result;
                
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                
                if ($this->isDeadlock($e) && $retries < $maxRetries - 1) {
                    $retries++;
                    $backoff = pow(2, $retries) * 100000; // Exponential backoff in microseconds
                    usleep($backoff);
                    continue;
                }
                
                $this->trackTransaction($transactionId, 'failed', [
                    'error' => $e->getMessage(),
                    'retries' => $retries
                ]);
                
                throw new BookingTransactionException('Transaction failed', 0, $e);
            }
        }
        
        throw new BookingTransactionException('Max retries exceeded');
    }
    
    private function isDeadlock(\Exception $e): bool {
        return str_contains($e->getMessage(), '1213') || // MySQL deadlock
               str_contains($e->getMessage(), 'deadlock');
    }
}
```

### 2.2 Partial Failure Handling

```php
class PartialBookingHandler {
    private array $completedSteps = [];
    private array $failedSteps = [];
    
    public function handlePartialFailure(BookingContext $context, \Exception $exception): BookingResult {
        // Determine what was completed
        $this->analyzeCompletedSteps($context);
        
        // Categorize the failure
        $failureType = $this->categorizeFailure($exception);
        
        switch ($failureType) {
            case 'calendar_sync_failed':
                // Booking succeeded locally, calendar sync failed
                return $this->handleCalendarSyncFailure($context);
                
            case 'notification_failed':
                // Booking succeeded, notifications failed
                return $this->handleNotificationFailure($context);
                
            case 'payment_failed':
                // If payment required, handle gracefully
                return $this->handlePaymentFailure($context);
                
            case 'critical_failure':
                // Complete failure, initiate full rollback
                return $this->handleCriticalFailure($context);
                
            default:
                return $this->handleUnknownFailure($context, $exception);
        }
    }
    
    private function handleCalendarSyncFailure(BookingContext $context): BookingResult {
        // Queue for retry
        CalendarSyncRetryJob::dispatch($context->appointment)
            ->delay(now()->addMinutes(5))
            ->onQueue('calendar-sync-retry');
            
        // Mark appointment with sync pending status
        $context->appointment->update([
            'sync_status' => 'pending',
            'metadata' => array_merge($context->appointment->metadata ?? [], [
                'sync_retry_scheduled' => now()->toIso8601String()
            ])
        ]);
        
        return new BookingResult(
            success: true,
            appointment: $context->appointment,
            warnings: ['Kalender-Synchronisation wird in Kürze durchgeführt.']
        );
    }
}
```

### 2.3 Saga Pattern Implementation

```php
abstract class BookingSaga {
    protected array $steps = [];
    protected array $compensations = [];
    protected SagaLog $log;
    
    public function execute(BookingContext $context): SagaResult {
        $this->log = new SagaLog($context);
        
        foreach ($this->steps as $index => $step) {
            try {
                $this->log->stepStarted($step);
                
                $result = $step->execute($context);
                
                $this->log->stepCompleted($step, $result);
                
                // Register compensation if step is successful
                if ($step->hasCompensation()) {
                    $this->compensations[] = [
                        'step' => $step,
                        'data' => $result->getCompensationData()
                    ];
                }
                
            } catch (\Exception $e) {
                $this->log->stepFailed($step, $e);
                
                // Execute compensations in reverse order
                $this->compensate($index - 1);
                
                return new SagaResult(false, $this->log, $e);
            }
        }
        
        return new SagaResult(true, $this->log);
    }
    
    protected function compensate(int $fromIndex): void {
        for ($i = $fromIndex; $i >= 0; $i--) {
            if (isset($this->compensations[$i])) {
                try {
                    $compensation = $this->compensations[$i];
                    $compensation['step']->compensate($compensation['data']);
                    
                    $this->log->compensationCompleted($compensation['step']);
                } catch (\Exception $e) {
                    $this->log->compensationFailed($compensation['step'], $e);
                    // Continue with other compensations
                }
            }
        }
    }
}

class PhoneBookingSaga extends BookingSaga {
    protected function defineSteps(): void {
        $this->steps = [
            new ValidateCustomerStep(),
            new ReserveTimeSlotStep(),
            new CreateAppointmentStep(),
            new SyncToCalendarStep(),
            new SendNotificationsStep(),
        ];
    }
}
```

### 2.4 Compensation Logic

```php
interface CompensatableStep {
    public function execute(BookingContext $context): StepResult;
    public function compensate(CompensationData $data): void;
    public function hasCompensation(): bool;
}

class ReserveTimeSlotStep implements CompensatableStep {
    private TimeSlotLockManager $lockManager;
    
    public function execute(BookingContext $context): StepResult {
        $lock = $this->lockManager->acquireLock(
            $context->branch->id,
            $context->staff->id,
            $context->startTime,
            $context->endTime,
            10 // 10 minutes timeout
        );
        
        if (!$lock) {
            throw new TimeSlotUnavailableException();
        }
        
        return new StepResult(true, ['lock_token' => $lock]);
    }
    
    public function compensate(CompensationData $data): void {
        if ($data->has('lock_token')) {
            $this->lockManager->releaseLock($data->get('lock_token'));
        }
    }
    
    public function hasCompensation(): bool {
        return true;
    }
}
```

### 2.5 State Management

```php
class BookingStateMachine {
    const STATES = [
        'initialized' => ['validating'],
        'validating' => ['reserving', 'failed'],
        'reserving' => ['creating', 'failed'],
        'creating' => ['syncing', 'completed_with_warnings'],
        'syncing' => ['notifying', 'completed_with_warnings'],
        'notifying' => ['completed', 'completed_with_warnings'],
        'completed' => [],
        'completed_with_warnings' => [],
        'failed' => []
    ];
    
    private string $currentState = 'initialized';
    private array $stateHistory = [];
    
    public function transition(string $toState, array $metadata = []): void {
        if (!$this->canTransition($toState)) {
            throw new InvalidStateTransitionException(
                "Cannot transition from {$this->currentState} to {$toState}"
            );
        }
        
        $this->stateHistory[] = [
            'from' => $this->currentState,
            'to' => $toState,
            'timestamp' => now(),
            'metadata' => $metadata
        ];
        
        $this->currentState = $toState;
        
        event(new BookingStateChanged($this->currentState, $metadata));
    }
    
    private function canTransition(string $toState): bool {
        return in_array($toState, self::STATES[$this->currentState] ?? []);
    }
}
```

---

## 3. Customer Onboarding

### 3.1 Quick Setup Wizard Fixes

```php
class EnhancedQuickSetupWizard {
    private WizardStateManager $stateManager;
    private ValidationEngine $validator;
    private DefaultValueProvider $defaults;
    
    public function processStep(int $step, array $data): StepResult {
        // Save progress after each step
        $this->stateManager->saveProgress($step, $data);
        
        // Validate step data
        $validation = $this->validator->validateStep($step, $data);
        
        if (!$validation->passes()) {
            return new StepResult(false, $validation->errors(), [
                'suggestions' => $this->generateSuggestions($validation->errors())
            ]);
        }
        
        // Apply smart defaults for next step
        $nextStepDefaults = $this->defaults->getForStep($step + 1, $data);
        
        return new StepResult(true, [], [
            'next_step_defaults' => $nextStepDefaults,
            'progress_percentage' => ($step / 4) * 100
        ]);
    }
}
```

### 3.2 Validation Requirements

```php
class WizardValidationRules {
    public static function getStepRules(int $step): array {
        return match($step) {
            1 => [ // Company & Branch
                'company_name' => ['required', 'string', 'max:255', 'unique:companies,name'],
                'industry' => ['required', 'in:medical,beauty,handwerk,legal,other'],
                'branch_name' => ['required', 'string', 'max:255'],
                'branch_city' => ['required', 'string', 'max:100'],
                'branch_phone' => ['nullable', 'phone:DE,AT,CH', 'unique:branches,phone_number'],
            ],
            2 => [ // Calendar Connection
                'calcom_connection_type' => ['required', 'in:oauth,api_key'],
                'calcom_api_key' => ['required_if:calcom_connection_type,api_key', 'string'],
                'calcom_team_slug' => ['nullable', 'string', 'regex:/^[a-z0-9-]+$/'],
            ],
            3 => [ // AI Phone Setup
                'phone_setup' => ['required', 'in:new,existing,skip'],
                'ai_voice' => ['required', 'in:sarah,matt,custom'],
                'custom_greeting' => ['required_if:use_template_greeting,false', 'string', 'max:500'],
            ],
            4 => [ // Services & Hours
                'use_template_services' => ['required', 'boolean'],
                'use_template_hours' => ['required', 'boolean'],
                'confirm_setup' => ['required', 'accepted'],
            ],
            default => []
        };
    }
    
    public static function getCustomMessages(): array {
        return [
            'company_name.unique' => 'Dieser Firmenname ist bereits vergeben.',
            'branch_phone.phone' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
            'calcom_team_slug.regex' => 'Team-Slug darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten.',
        ];
    }
}
```

### 3.3 Default Values Strategy

```php
class IntelligentDefaultProvider {
    private array $industryDefaults;
    
    public function getDefaults(string $context, array $previousData = []): array {
        return match($context) {
            'branch_setup' => $this->getBranchDefaults($previousData),
            'calendar_setup' => $this->getCalendarDefaults($previousData),
            'ai_setup' => $this->getAIDefaults($previousData),
            'service_setup' => $this->getServiceDefaults($previousData),
            default => []
        };
    }
    
    private function getBranchDefaults(array $data): array {
        $defaults = [
            'branch_name' => 'Hauptfiliale',
            'branch_country' => 'DE',
        ];
        
        // Intelligent city suggestion based on phone area code
        if (!empty($data['branch_phone'])) {
            $areaCode = $this->extractAreaCode($data['branch_phone']);
            $defaults['branch_city'] = $this->getCityFromAreaCode($areaCode);
        }
        
        return $defaults;
    }
    
    private function getAIDefaults(array $data): array {
        $industry = $data['industry'] ?? 'general';
        
        return [
            'ai_voice' => $this->getRecommendedVoice($industry),
            'greeting_template' => $this->getGreetingTemplate($industry),
            'response_style' => $this->getResponseStyle($industry),
            'language' => 'de-DE',
            'interruption_sensitivity' => $industry === 'medical' ? 0.3 : 0.5,
        ];
    }
}
```

### 3.4 Progressive Disclosure Pattern

```php
class ProgressiveSetupFlow {
    private array $steps = [
        'basic' => ['company_name', 'industry', 'branch_city'],
        'extended' => ['branch_phone', 'branch_address', 'logo'],
        'advanced' => ['custom_settings', 'integrations', 'api_keys']
    ];
    
    public function getCurrentFields(array $completedData): array {
        $requiredFields = $this->steps['basic'];
        $optionalFields = [];
        
        // Show extended fields if basic is complete
        if ($this->isStepComplete('basic', $completedData)) {
            $requiredFields = array_merge($requiredFields, $this->steps['extended']);
        }
        
        // Show advanced as optional
        if ($this->isStepComplete('extended', $completedData)) {
            $optionalFields = $this->steps['advanced'];
        }
        
        return [
            'required' => $requiredFields,
            'optional' => $optionalFields,
            'hidden' => $this->getHiddenFields($completedData)
        ];
    }
    
    private function isStepComplete(string $step, array $data): bool {
        foreach ($this->steps[$step] as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        return true;
    }
}
```

### 3.5 Error Recovery & Assistance

```php
class SetupAssistant {
    public function provideContextualHelp(string $field, $error = null): array {
        $help = [
            'company_name' => [
                'hint' => 'Der Name Ihrer Firma, wie er Kunden angezeigt wird.',
                'example' => 'Zahnarztpraxis Dr. Schmidt',
                'common_errors' => [
                    'unique' => 'Dieser Name ist bereits vergeben. Versuchen Sie, Ihren Standort hinzuzufügen.',
                ]
            ],
            'branch_phone' => [
                'hint' => 'Die Haupttelefonnummer für eingehende Anrufe.',
                'example' => '+49 30 12345678',
                'format_help' => 'Internationale Vorwahl (+49) oder lokale Nummer (030...)',
                'validation' => 'phone:DE,AT,CH'
            ],
            'calcom_api_key' => [
                'hint' => 'Ihr persönlicher API-Schlüssel von Cal.com.',
                'help_link' => 'https://cal.com/settings/developer/api-keys',
                'security_note' => 'Wird verschlüsselt gespeichert.',
                'test_button' => true
            ]
        ];
        
        $response = $help[$field] ?? ['hint' => 'Keine Hilfe verfügbar.'];
        
        // Add error-specific help
        if ($error && isset($response['common_errors'][$error])) {
            $response['error_help'] = $response['common_errors'][$error];
        }
        
        return $response;
    }
    
    public function suggestFixes(array $errors): array {
        $suggestions = [];
        
        foreach ($errors as $field => $error) {
            $suggestions[$field] = match($field) {
                'company_name' => $this->suggestUniqueCompanyName($error),
                'branch_phone' => $this->formatPhoneNumber($error),
                'calcom_api_key' => $this->validateApiKey($error),
                default => null
            };
        }
        
        return array_filter($suggestions);
    }
}
```

---

## Implementation Timeline

### Phase 1: RetellAgentProvisioner (1 Woche)
- Pre-Validation Framework
- Error Handling & Feedback
- Rollback Mechanism

### Phase 2: Booking Flow (2 Wochen)
- Transaction Management
- Saga Pattern Implementation
- State Machine

### Phase 3: Customer Onboarding (1 Woche)
- Wizard Enhancements
- Validation & Defaults
- Progressive Disclosure

### Phase 4: Testing & Refinement (1 Woche)
- Integration Tests
- Performance Optimization
- User Acceptance Testing

---

## Success Metrics

1. **Provisioning Success Rate**: > 95%
2. **Booking Transaction Success**: > 99%
3. **Onboarding Completion Rate**: > 80%
4. **Error Recovery Rate**: > 90%
5. **User Satisfaction Score**: > 4.5/5

---

## Risk Mitigation

1. **Database Deadlocks**: Implementierung von Retry-Logic mit exponential backoff
2. **API Failures**: Circuit Breaker Pattern für externe Services
3. **Data Consistency**: Event Sourcing für kritische Operationen
4. **Performance**: Caching und Async Processing wo möglich
5. **User Experience**: Graceful Degradation bei Feature-Ausfall