# 🔧 Service Konsolidierungsplan

## 📊 Aktuelle Situation: 36 Services (viel zu viele!)

### Cal.com Services (10 → 1)
**Behalten:**
- ✅ `CalcomV2Service.php` - Neueste Version, vollständig

**Löschen:**
- ❌ `CalcomService.php` - Alte v1 API
- ❌ `CalcomDebugService.php` - Nur für Debugging
- ❌ `CalcomEventSyncService.php` - In V2 integriert
- ❌ `CalcomEventTypeImportService.php` - In V2 integriert
- ❌ `CalcomEventTypeSyncService.php` - Redundant
- ❌ `CalcomImportService.php` - Alte Version
- ❌ `CalcomSyncService.php` - Redundant
- ❌ `CalcomUnifiedService.php` - Experiment, nicht verwendet
- ❌ `CalcomV2MigrationService.php` - Migration abgeschlossen

### Retell Services (5 → 1)
**Behalten:**
- ✅ `RetellV2Service.php` - Neueste Version

**Löschen:**
- ❌ `RetellAIService.php` - Alte Version
- ❌ `RetellAgentService.php` - In V2 integriert
- ❌ `RetellService.php` - Alte v1
- ❌ `RetellV1Service.php` - Explizit alte Version

### Andere redundante Services
**Löschen:**
- ❌ `AppointmentService.php` - Wird durch SmartBookingService ersetzt
- ❌ `BookingService.php` - Redundant
- ❌ `CallService.php` - In SmartBookingService integrieren
- ❌ `CustomerService.php` - Zu komplex für MVP

### Neue konsolidierte Services (3 Total)
1. **SmartBookingService** - Alles rund um Terminbuchung
2. **IntegrationService** - Wrapper für CalcomV2 + RetellV2
3. **NotificationService** - Email, später SMS

## 🎯 Ergebnis: Von 36 auf ~15 Services (-58%)