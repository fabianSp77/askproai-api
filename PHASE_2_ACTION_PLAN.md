# 📋 AskProAI Phase 2 - Detaillierter Umsetzungsplan

**Datum**: 14. Juni 2025  
**Status**: PLANUNG → BEREIT ZUR UMSETZUNG ✅  
**Geschätzte Dauer**: 5-7 Tage

---

## 🔄 FINALER KORRIGIERTER PLAN

Nach eingehender Überprüfung und Korrektur ist dies der finale, perfektionierte Plan für Phase 2:

---

## 🎯 Ziele von Phase 2

1. **Service Layer Konsolidierung**: Einheitliche, wiederverwendbare Service-Architektur
2. **Event-Driven Architecture**: Entkopplung durch Events & Listeners
3. **Bidirektionale Synchronisation**: Nahtlose Integration mit Cal.com
4. **Dashboard Excellence**: Optimierte, customizable Dashboards
5. **Code-Qualität**: Reduzierung technischer Schulden

---

## 📊 Detaillierter Umsetzungsplan

### 1. SERVICE LAYER KONSOLIDIERUNG (2 Tage)

#### 1.1 CalcomV2Service als Standard (Tag 1)
```
Aufgaben:
├── [ ] CalcomV2Service Feature-Vollständigkeit prüfen
├── [ ] Fehlende Methoden aus V1 migrieren
├── [ ] Interface ICalendarService definieren
├── [ ] Factory Pattern für Service-Instantiierung
├── [ ] Alle Controller auf V2 umstellen
└── [ ] V1 Service als deprecated markieren

Dateien:
- app/Contracts/ICalendarService.php (NEU)
- app/Services/CalcomV2Service.php
- app/Services/CalendarServiceFactory.php (NEU)
- app/Http/Controllers/*.php (UPDATE)

Tests:
- tests/Unit/Services/CalcomV2ServiceTest.php
- tests/Integration/CalendarIntegrationTest.php
```

#### 1.2 AppointmentService Refactoring (Tag 1)
```
Aufgaben:
├── [ ] AppointmentRepository implementieren
├── [ ] Business Logic aus Controllern extrahieren
├── [ ] Validation Rules zentralisieren
├── [ ] Error Handling standardisieren
└── [ ] Service-Methoden dokumentieren

Dateien:
- app/Repositories/AppointmentRepository.php (NEU)
- app/Services/AppointmentService.php (NEU)
- app/Http/Requests/AppointmentRequest.php (NEU)
```

#### 1.3 NotificationService Enhancement (Tag 2)
```
Aufgaben:
├── [ ] Template-System implementieren
├── [ ] Multi-Channel Support (Email, SMS, WhatsApp)
├── [ ] Notification Queue einrichten
├── [ ] Retry-Mechanismus
└── [ ] Delivery Tracking

Dateien:
- app/Services/NotificationService.php
- app/Services/Channels/EmailChannel.php (NEU)
- app/Services/Channels/SmsChannel.php (NEU)
- database/migrations/create_notification_logs_table.php (NEU)
```

---

### 2. EVENT-DRIVEN ARCHITECTURE (1.5 Tage)

#### 2.1 Event Definitions (Tag 2)
```
Events:
├── AppointmentCreated
├── AppointmentUpdated
├── AppointmentCancelled
├── AppointmentConfirmed
├── CallReceived
├── CallCompleted
├── CustomerCreated
├── CustomerUpdated
└── CalendarSyncRequested

Dateien:
- app/Events/Appointments/*.php (NEU)
- app/Events/Calls/*.php (NEU)
- app/Events/Customers/*.php (NEU)
```

#### 2.2 Listener Implementation (Tag 3)
```
Listeners:
├── SendAppointmentConfirmation
├── SyncToCalendar
├── UpdateCustomerStats
├── NotifyStaffNewAppointment
├── ProcessCallRecording
├── UpdateAvailability
└── TriggerWebhooks

Dateien:
- app/Listeners/Appointments/*.php (NEU)
- app/Listeners/Notifications/*.php (NEU)
- app/Listeners/Sync/*.php (NEU)
```

#### 2.3 Event Service Provider (Tag 3)
```
Aufgaben:
├── [ ] Event-Listener Mappings
├── [ ] Queue Configuration
├── [ ] Event Broadcasting Setup
└── [ ] Testing Infrastructure

Dateien:
- app/Providers/EventServiceProvider.php (UPDATE)
- config/broadcasting.php (UPDATE)
```

---

### 3. BIDIREKTIONALE SYNCHRONISATION (2 Tage)

#### 3.1 Cal.com Webhook Handler (Tag 4)
```
Aufgaben:
├── [ ] Webhook Endpoint implementieren
├── [ ] Signature Verification
├── [ ] Event Type Mapping
├── [ ] Conflict Resolution
└── [ ] Error Recovery

Dateien:
- app/Http/Controllers/Webhooks/CalcomWebhookController.php (UPDATE)
- app/Services/Sync/CalcomSyncHandler.php (NEU)
- app/Jobs/ProcessCalcomWebhook.php (NEU)
```

#### 3.2 Sync State Management (Tag 4)
```
Aufgaben:
├── [ ] Sync Status Tracking
├── [ ] Version Control
├── [ ] Conflict Detection
├── [ ] Rollback Mechanism
└── [ ] Sync History

Dateien:
- database/migrations/create_sync_logs_table.php (NEU)
- app/Models/SyncLog.php (NEU)
- app/Services/Sync/SyncStateManager.php (NEU)
```

#### 3.3 Real-time Updates (Tag 5)
```
Aufgaben:
├── [ ] WebSocket Integration
├── [ ] Broadcasting Events
├── [ ] Frontend Listeners
├── [ ] Presence Channels
└── [ ] Performance Optimization

Dateien:
- config/websockets.php (NEU)
- app/Events/RealtimeUpdate.php (NEU)
- resources/js/echo-setup.js (UPDATE)
```

---

### 4. DASHBOARD CONSOLIDATION (1.5 Tage)

#### 4.1 Widget Optimization (Tag 5)
```
Aufgaben:
├── [ ] Widget Performance Audit
├── [ ] Reduce to 6 Core Widgets
├── [ ] Lazy Loading Implementation
├── [ ] Cache Strategy Optimization
└── [ ] Remove Redundant Widgets

Core Widgets:
1. SystemOverviewWidget (Kombination aus mehreren)
2. RevenueMetricsWidget (Business KPIs)
3. OperationalWidget (Calls + Appointments)
4. CustomerInsightsWidget
5. TeamPerformanceWidget
6. QuickActionsWidget

Zu entfernen/kombinieren:
- EnhancedDashboardStats → SystemOverviewWidget
- DashboardStats → SystemOverviewWidget
- Multiple kleine Widgets → Kombinieren
```

#### 4.2 Customizable Dashboard (Tag 6)
```
Aufgaben:
├── [ ] User Preferences Table
├── [ ] Drag & Drop Interface
├── [ ] Widget Configuration
├── [ ] Layout Persistence
└── [ ] Default Templates

Dateien:
- database/migrations/create_user_dashboard_preferences.php (NEU)
- app/Services/DashboardService.php (NEU)
- app/Filament/Admin/Pages/CustomizableDashboard.php (NEU)
```

---

### 5. CODE QUALITY & CLEANUP (1 Tag)

#### 5.1 Technical Debt Reduction (Tag 6-7)
```
Aufgaben:
├── [ ] Remove Deprecated Code
├── [ ] Standardize Naming Conventions
├── [ ] Extract Magic Numbers
├── [ ] Reduce Code Duplication
└── [ ] Update Documentation

Tools:
- PHPStan Level 8
- Laravel Pint
- PHP Insights
```

#### 5.2 Performance Optimization (Tag 7)
```
Aufgaben:
├── [ ] Query Optimization
├── [ ] N+1 Query Detection
├── [ ] Index Analysis
├── [ ] Cache Warming
└── [ ] Load Testing

Dateien:
- app/Console/Commands/OptimizeQueries.php (NEU)
- app/Http/Middleware/QueryLogger.php (NEU)
```

---

## 🔄 Implementierungsreihenfolge

### Tag 1: Foundation
1. CalcomV2Service Vollständigkeit
2. Service Interfaces definieren
3. AppointmentService implementieren

### Tag 2: Events & Notifications
1. Event-Klassen erstellen
2. NotificationService erweitern
3. Erste Listener implementieren

### Tag 3: Event System Complete
1. Alle Listener fertigstellen
2. Event Broadcasting Setup
3. Testing Infrastructure

### Tag 4: Sync Foundation
1. Cal.com Webhook Handler
2. Sync State Management
3. Conflict Resolution

### Tag 5: Real-time & Dashboard
1. WebSocket Integration
2. Widget Consolidation
3. Performance Optimization

### Tag 6: Customization
1. Dashboard Customization
2. User Preferences
3. Code Cleanup Start

### Tag 7: Finalization
1. Final Testing
2. Documentation
3. Deployment Preparation

---

## ✅ Definition of Done

### Für jede Komponente:
- [ ] Unit Tests (>80% Coverage)
- [ ] Integration Tests
- [ ] Documentation
- [ ] Code Review
- [ ] Performance Baseline
- [ ] Error Handling
- [ ] Logging

### Gesamt-System:
- [ ] E2E Tests erfolgreich
- [ ] Performance Benchmarks erreicht
- [ ] Keine kritischen Issues
- [ ] Dokumentation vollständig
- [ ] Monitoring eingerichtet

---

## 📈 Erfolgsmetriken

### Technical KPIs:
- **Response Time**: < 100ms (von 200ms)
- **Dashboard Load**: < 1s (von 2s)
- **Sync Latency**: < 5s (Echtzeit)
- **Error Rate**: < 0.1%

### Business KPIs:
- **Webhook Success**: > 99.9%
- **Sync Accuracy**: 100%
- **User Satisfaction**: > 95%
- **Support Tickets**: -50%

---

## 🚨 Risiken & Mitigationen

### Risiko 1: Breaking Changes
**Mitigation**: Feature Flags, Canary Deployment

### Risiko 2: Sync Conflicts
**Mitigation**: Robust Conflict Resolution, Manual Override

### Risiko 3: Performance Degradation
**Mitigation**: Load Testing, Gradual Rollout

### Risiko 4: Data Loss
**Mitigation**: Backup vor jeder Migration, Rollback Plan

---

## 🎯 Nächste Schritte

1. **Review** dieses Plans mit Team
2. **Priorisierung** falls Zeitdruck
3. **Resource Allocation**
4. **Start Tag 1** mit Service Layer

---

**Bereit zum Start nach Dashboard-Fix!** 🚀