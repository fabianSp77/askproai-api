# Phase 5: Service Architecture Refactoring Plan

**Date**: 2025-10-18
**Status**: Architecture Analysis & Planning
**Goal**: Transform 130+ fragmented services into coherent domain-driven service boundaries

---

## 🔍 Current State Analysis

### Service Distribution

```
Total Services: 130+

By Category:
├─ Retell AI Integration: 20+ services
├─ Appointment Management: 15+ services
├─ Notifications: 10+ services
├─ Resilience/Circuit Breaker: 8+ services
├─ Caching & Performance: 8+ services
├─ Cal.com Integration: 12+ services
├─ Data Integrity: 6+ services
├─ Policies & Rules: 5+ services
├─ Communication: 4+ services
├─ Monitoring: 5+ services
├─ Tracing & Auditing: 5+ services
├─ Saga/Orchestration: 8+ services
├─ Strategies/Patterns: 10+ services
└─ Other/Miscellaneous: 30+ services
```

### Current Problems

**1. Service Fragmentation**
- Services scattered across 20+ subdirectories
- Multiple services doing similar things (e.g., 3 different "BookingService" classes)
- No clear domain boundaries
- Unclear responsibilities

**2. Tight Coupling**
- Services directly calling other services
- No event-based communication
- Large service classes (100-500+ lines)
- Complex dependency chains

**3. Missing Orchestration**
- No central event bus
- Ad-hoc service chaining
- Difficult to understand appointment flow
- Hard to add new event listeners

**4. Duplicate Functionality**
- Multiple implementations of similar concepts
- Inconsistent patterns
- Replicated validation logic

---

## 📐 Proposed Architecture

### Domain-Driven Service Organization

```
app/Domains/
├── Appointments/
│   ├── Services/
│   │   ├── AppointmentCreationService
│   │   ├── AppointmentUpdateService
│   │   ├── AppointmentCancellationService
│   │   └── AppointmentQueryService
│   ├── Events/
│   │   ├── AppointmentCreatedEvent
│   │   ├── AppointmentUpdatedEvent
│   │   ├── AppointmentCancelledEvent
│   │   └── AppointmentConfirmedEvent
│   ├── Contracts/
│   │   └── AppointmentServiceInterface
│   └── Policies/
│
├── Availability/
│   ├── Services/
│   │   ├── AvailabilityCalculationService
│   │   ├── AvailabilityCheckService
│   │   └── AvailabilityUpdateService
│   ├── Events/
│   │   ├── AvailabilityChangedEvent
│   │   └── SlotBookedEvent
│   └── Contracts/
│
├── Customers/
│   ├── Services/
│   │   ├── CustomerCreationService
│   │   ├── CustomerResolutionService
│   │   └── CustomerValidationService
│   ├── Events/
│   │   ├── CustomerCreatedEvent
│   │   ├── CustomerIdentifiedEvent
│   │   └── CustomerPhoneNormalizedEvent
│   └── Contracts/
│
├── Notifications/
│   ├── Services/
│   │   ├── NotificationService
│   │   ├── NotificationTemplateService
│   │   ├── DeliveryChannelService
│   │   └── NotificationQueueService
│   ├── Events/
│   │   ├── NotificationScheduledEvent
│   │   ├── NotificationSentEvent
│   │   └── NotificationFailedEvent
│   ├── Channels/
│   │   ├── EmailChannel
│   │   ├── SmsChannel
│   │   └── WhatsAppChannel
│   └── Contracts/
│
├── VoiceAI/
│   ├── Services/
│   │   ├── RetellCallManagementService
│   │   ├── FunctionCallHandlerService
│   │   └── CallTranscriptionService
│   ├── Events/
│   │   ├── CallStartedEvent
│   │   ├── CallEndedEvent
│   │   ├── AppointmentBookedViaCallEvent
│   │   └── CallFailedEvent
│   └── Contracts/
│
├── CalendarSync/
│   ├── Services/
│   │   ├── CalcomSyncService
│   │   ├── CalcomWebhookHandlerService
│   │   └── BidirectionalSyncService
│   ├── Events/
│   │   ├── CalcomBookingCreatedEvent
│   │   ├── CalcomBookingUpdatedEvent
│   │   └── SyncCompletedEvent
│   └── Contracts/
│
├── Resilience/
│   ├── Services/
│   │   ├── CircuitBreakerService
│   │   ├── RetryPolicyService
│   │   ├── FailureDetectorService
│   │   └── HealthCheckService
│   ├── Events/
│   │   ├── CircuitBreakerOpenedEvent
│   │   └── ServiceRecoveredEvent
│   └── Contracts/
│
└── Shared/
    ├── Events/
    │   ├── EventBus
    │   ├── EventDispatcher
    │   ├── EventListener
    │   └── Event (abstract)
    ├── Services/
    │   ├── CacheService
    │   ├── AuditService
    │   └── TracingService
    └── Contracts/
        └── DomainEventInterface
```

---

## 🔄 Event-Driven Architecture

### Core Event System

```php
// Base event class
abstract class DomainEvent {
    public string $eventId;
    public \DateTime $occurredAt;
    public string $aggregateId;
    public string $aggregateType;
    public array $metadata = [];
}

// Event bus for publishing
class EventBus {
    public function publish(DomainEvent $event): void
    public function subscribe(string $eventClass, callable $listener): void
}

// Event dispatcher (async)
class EventDispatcher {
    public function dispatch(DomainEvent $event): void
    public function dispatchAsync(DomainEvent $event): void
}
```

### Key Domain Events

**Appointment Domain**:
- `AppointmentCreatedEvent` → Triggers notifications, Cal.com sync, analytics
- `AppointmentConfirmedEvent` → Triggers SMS reminder, email confirmation
- `AppointmentCancelledEvent` → Triggers refund, slot release, cancellation notification
- `AppointmentRescheduleRequestedEvent` → Triggers availability check, customer notification

**Customer Domain**:
- `CustomerIdentifiedEvent` → Updates customer profile, triggers follow-up
- `CustomerPhoneNormalizedEvent` → Updates phone format, improves matching

**Availability Domain**:
- `AvailabilityChangedEvent` → Invalidates caches, triggers recalculation
- `SlotBookedEvent` → Updates availability, triggers sync to Cal.com

**VoiceAI Domain**:
- `CallStartedEvent` → Logs call, starts tracking
- `AppointmentBookedViaCallEvent` → Triggers appointment domain events
- `CallEndedEvent` → Finalizes call record, triggers analytics

---

## 🏗️ Refactoring Roadmap

### Phase 5.1: Event Infrastructure (2-3 hours)

```
1. Create abstract DomainEvent base class
2. Create EventBus service
3. Create EventDispatcher service
4. Create EventListener interface
5. Create in-memory event store
6. Add event publishing to AppServiceProvider
```

### Phase 5.2: Domain Restructuring (3-4 hours)

```
1. Create Domains/ directory structure
2. Move appointment services to Appointments domain
3. Move availability services to Availability domain
4. Move customer services to Customers domain
5. Move notification services to Notifications domain
6. Move Retell services to VoiceAI domain
7. Move Cal.com services to CalendarSync domain
```

### Phase 5.3: Event Emission (2-3 hours)

```
1. Add event publishing to AppointmentCreationService
2. Add event publishing to AppointmentCancellationService
3. Add event publishing to AppointmentUpdateService
4. Add event publishing to CustomerCreationService
5. Add event publishing to AvailabilityChangeService
```

### Phase 5.4: Event Listeners (2-3 hours)

```
1. Create NotificationEventListener
   - Subscribes to AppointmentCreatedEvent
   - Triggers SMS/email notifications

2. Create CalcomSyncListener
   - Subscribes to AppointmentCreatedEvent
   - Triggers Cal.com booking

3. Create CacheInvalidationListener
   - Subscribes to AvailabilityChangedEvent
   - Clears relevant caches

4. Create AnalyticsEventListener
   - Subscribes to AppointmentCreatedEvent
   - Logs analytics data

5. Create AuditEventListener
   - Subscribes to all events
   - Creates audit trail
```

### Phase 5.5: Testing & Verification (2-3 hours)

```
1. Create event tests
2. Create domain service tests
3. Create event listener tests
4. Create integration tests
5. Performance verification
```

---

## 🎯 Benefits of Refactoring

### Before (Current State)
```
- 130+ services scattered in 20+ directories
- Service → Service → Service chains
- Tight coupling between domains
- Hard to add new features
- Difficult to understand flow
- Impossible to test independently
```

### After (Refactored)
```
- Organized by domain (7 domains)
- Services publish events
- Listeners handle consequences
- Loose coupling via events
- Easy to add listeners
- Clear, understandable flow
- Fully testable domains
```

### Specific Improvements

**Modularity**: Each domain is independent
**Extensibility**: Add features by creating event listeners
**Testability**: Test each domain in isolation
**Maintainability**: Clear responsibilities and organization
**Observability**: Events create audit trail
**Scalability**: Easy to move event handlers to queues/workers

---

## 📊 Expected Outcomes

### Code Organization
- From 130+ services in 20 directories
- To 50-60 services in 7 organized domains
- Clear boundaries between concerns

### Coupling Reduction
- From tightly coupled service chains
- To loosely coupled via event bus
- Easier to modify without side effects

### Feature Development Speed
- Instead of modifying multiple services
- Just add an event listener
- No need to understand entire flow

### Testing
- Test domains independently
- Mock event bus for unit tests
- Full integration tests for flows

---

## 🚨 Risks & Mitigation

**Risk 1: Event Processing Order**
- Mitigation: Database transaction ensures consistency
- Backup: Saga pattern for complex flows

**Risk 2: Event Loop/Infinite Loops**
- Mitigation: Careful listener registration
- Guard: Max retry counts on events

**Risk 3: Performance with Events**
- Mitigation: Use async/queue for non-critical events
- Backup: Keep synchronous for critical path

**Risk 4: Migration Complexity**
- Mitigation: Incremental migration (service by service)
- Backup: Keep old services until fully migrated

---

## 📋 Migration Strategy

### Strategy: Gradual Migration

**Stage 1: Infrastructure** (Phase 5.1)
- Create event system
- Existing services unchanged
- Event bus ready to use

**Stage 2: New Code** (Phase 5.2-5.3)
- New services use domain structure
- Old services run alongside
- Gradual migration of services

**Stage 3: Migration** (Phase 5.4-5.5)
- Move existing services to domains
- Add event publishing
- Add event listeners

**Stage 4: Cleanup** (Future phases)
- Remove old service paths
- Delete duplicate services
- Consolidate patterns

---

## 🔧 Implementation Details

### Directory Structure After Refactoring

```
app/
├── Domains/
│   ├── Appointments/
│   │   ├── Services/
│   │   │   ├── AppointmentCreationService.php
│   │   │   ├── AppointmentUpdateService.php
│   │   │   ├── AppointmentCancellationService.php
│   │   │   └── AppointmentQueryService.php
│   │   ├── Events/
│   │   │   ├── AppointmentCreatedEvent.php
│   │   │   ├── AppointmentUpdatedEvent.php
│   │   │   ├── AppointmentCancelledEvent.php
│   │   │   └── AppointmentConfirmedEvent.php
│   │   ├── Listeners/
│   │   │   ├── ValidateAppointmentListener.php
│   │   │   └── LogAppointmentListener.php
│   │   ├── Repositories/
│   │   │   └── AppointmentRepository.php
│   │   └── Contracts/
│   │       ├── AppointmentServiceInterface.php
│   │       └── AppointmentRepositoryInterface.php
│   │
│   ├── Notifications/
│   │   ├── Services/
│   │   │   ├── NotificationService.php
│   │   │   └── DeliveryChannelService.php
│   │   ├── Listeners/
│   │   │   ├── SendConfirmationListener.php
│   │   │   ├── SendReminderListener.php
│   │   │   └── SendCancellationListener.php
│   │   ├── Channels/
│   │   │   ├── EmailChannel.php
│   │   │   ├── SmsChannel.php
│   │   │   └── WhatsAppChannel.php
│   │   └── Contracts/
│   │       ├── NotificationChannelInterface.php
│   │       └── NotificationServiceInterface.php
│   │
│   └── ...other domains...
│
├── Shared/
│   ├── Events/
│   │   ├── DomainEvent.php (abstract)
│   │   ├── EventBus.php
│   │   ├── EventDispatcher.php
│   │   └── EventListener.php (interface)
│   └── Services/
│       ├── CacheService.php
│       └── AuditService.php
│
└── Http/
    ├── Controllers/
    ├── Requests/
    └── Resources/
```

---

## ✅ Success Criteria

Phase 5 is successful when:

- ✅ Event bus system created and operational
- ✅ All domains organized in new structure
- ✅ Key domain events defined and implemented
- ✅ Critical event listeners working
- ✅ Performance maintained (no degradation)
- ✅ Tests passing for new structure
- ✅ Documentation complete
- ✅ No breaking changes to API

---

## 📞 Next Steps

**Immediate** (next session):
1. Create event infrastructure
2. Define all domain events
3. Set up domain directory structure
4. Create base domain services

**Then**:
5. Migrate appointment services
6. Add event publishing
7. Create event listeners
8. Verify and test

---

**Phase 5 Planning**: ✅ Complete

**Status**: Ready to begin implementation

**Estimated Duration**: 10-12 hours total

**Expected Impact**: Significantly improved maintainability and extensibility
