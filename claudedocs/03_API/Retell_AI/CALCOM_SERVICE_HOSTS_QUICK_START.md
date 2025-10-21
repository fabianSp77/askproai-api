# Cal.com Service Hosts - Quick Start

**Schnellstart für Service 47 Fix**

---

## 🎯 Was wurde gelöst?

```
❌ VORHER (Service 47):
   - 500 Error beim "Mitarbeiter hinzufügen"
   - N+1 Query Problem
   - itemLabel Exception bei NULL state
   - Keine Sichtbarkeit auf Cal.com Hosts

✅ NACHHER (Service 47):
   - Keine Fehler
   - Automatische Cal.com Host Anzeige
   - Zeigt Avatar, Email, Mapping-Status
   - Shows verfügbare Services pro Host
```

---

## 🚀 Deployment

### Schritt 1: Code-Update
```bash
# Die neuen Files sind bereits committed:
cd /var/www/api-gateway

# Folgende Dateien wurden erstellt:
- app/Services/CalcomServiceHostsResolver.php
- app/Console/Commands/SyncCalcomServiceHosts.php
- resources/views/filament/fields/calcom-hosts-display.blade.php
- resources/views/filament/components/calcom-hosts-card.blade.php
```

### Schritt 2: Cache leeren
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Schritt 3: Initial Sync (Optional aber empfohlen)
```bash
# Alle Service synchronisieren
php artisan calcom:sync-service-hosts

# Nur Service 47
php artisan calcom:sync-service-hosts --service-id=47

# Nur AskProAI Firma
php artisan calcom:sync-service-hosts --company-id=5
```

---

## 🧪 Testing

### Test 1: Öffne Service 47 Edit-Seite
```
1. Gehe zu: https://api.askproai.de/admin/services/47/edit
2. Du solltest sehen:
   - ✅ Neue Section: "📅 Cal.com Mitarbeiter (Automatisch)"
   - ✅ Summary Stats (Total, Verbunden, Neu, Für Service)
   - ✅ Host Cards mit:
      - Avatars
      - Names & Emails
      - Status Badges
      - Verfügbare Services
   - ✅ Info Box mit Erklärung

3. Kein 500-Fehler!
```

### Test 2: Auto-Sync durchführen
```bash
$ php artisan calcom:sync-service-hosts --service-id=47

# Expected output:
# ✅ Synced X host mappings for service: Service 47
```

### Test 3: Retell Voice Booking
```
1. Tester ruft Retell Voice Number an
2. Bucht Termin für Service 47
3. Check: Appointment wurde mit korrektem Staff erstellt
4. Check: Cal.com Sync funktioniert
```

---

## 📊 Was wird angezeigt?

### Host Status Badges

| Badge | Bedeutung | Aktion |
|-------|-----------|--------|
| ✅ Verbunden | Host ist zu Local Staff gemappt | Alles OK |
| ⚠️ Nicht verbunden | Host existiert, aber kein Staff Mapping | Auto-Sync oder manuell zuordnen |
| ℹ️ Keine Hosts | Service hat keine Cal.com Event Type | Event Type ID eintragen |

### Verfügbare Services

Zeigt: "📋 Verfügbar für: Service A (30min), Service B (60min)"

Das bedeutet: Dieser Host kann diese Services buchen.

---

## 🔍 Debugging

### Wenn Service 47 Edit keine Cal.com Hosts zeigt:

1. **Prüfe: Hat Service eine calcom_event_type_id?**
   ```bash
   mysql> SELECT id, name, calcom_event_type_id FROM services WHERE id=47;
   # Should show: 47 | Service 47 | 2563193
   ```

2. **Prüfe: Existiert TeamEventTypeMapping?**
   ```bash
   mysql> SELECT * FROM team_event_type_mappings
          WHERE calcom_event_type_id=2563193 AND company_id=5;
   # Should show 1 row with 'hosts' JSON Array
   ```

3. **Prüfe: Hosts Array ist nicht leer?**
   ```bash
   mysql> SELECT JSON_ARRAY_LENGTH(hosts) FROM team_event_type_mappings
          WHERE calcom_event_type_id=2563193;
   # Should show: > 0
   ```

4. **Wenn ja:** Cache leeren und Seite neu laden
   ```bash
   php artisan cache:clear
   ```

5. **Wenn immer noch nichts:** Logs prüfen
   ```bash
   tail -f storage/logs/laravel.log | grep -i "calcom\|service\|host"
   ```

---

## 💡 Wichtige Konzepte

### 1. Cal.com ist Source of Truth
```
Cal.com:
  Event Type 2563193
  ├─ Host 1: Karl Meyer (karl@...)
  ├─ Host 2: Julia Schmidt (julia@...)
  └─ Host 3: Tom Bauer (tom@...)
     ↓
Unsere DB: TeamEventTypeMapping.hosts (JSON Array)
     ↓
Lokale Mapping: CalcomHostMapping
     └─ Host 1 → Local Staff (Karl)
```

### 2. Automatische Mitarbeiter-Auswahl
```
Retell Voice Call → book_appointment()
  ↓
Cal.com API: bestimmt Host automatisch
  (wer ist verfügbar?)
  ↓
Wir: reverse lookup
  Host → Staff via CalcomHostMapping
  ↓
Appointment: erstellt mit korrektem Staff
```

### 3. Warum kein manueller Repeater?
```
❌ Manueller Repeater (alt):
   - Nicht synchron mit Cal.com
   - 500-Fehler
   - Wird ignoriert bei Booking

✅ Automatisch von Cal.com (neu):
   - Immer synchron
   - Keine Fehler
   - Wahrheitliche Quelle
```

---

## 🔧 Erweiterte Verwendung

### Nur bestimmte Services syncing
```php
$services = Service::whereIn('id', [47, 48, 49])
    ->get();

$resolver = new CalcomServiceHostsResolver();
foreach ($services as $service) {
    $count = $resolver->autoSyncHostMappings($service);
    echo "Synced $count for {$service->name}\n";
}
```

### Mapping Status prüfen
```php
$resolver = new CalcomServiceHostsResolver();
$summary = $resolver->getHostsSummary($service);

echo "Total: " . $summary['total_hosts'] . "\n";
echo "Mapped: " . $summary['mapped_hosts'] . "\n";
echo "Unmapped: " . $summary['unmapped_hosts'] . "\n";
echo "Available for service: " . $summary['available_for_service'] . "\n";
```

### Alle Hosts für Service anzeigen
```php
$resolver = new CalcomServiceHostsResolver();
$hosts = $resolver->resolveHostsForService($service);

foreach ($hosts as $host) {
    echo "Host: {$host['calcom_name']} ({$host['calcom_email']})\n";
    echo "  Mapped: " . ($host['is_mapped'] ? 'Yes' : 'No') . "\n";
    echo "  Available: " . ($host['is_available_for_service'] ? 'Yes' : 'No') . "\n";
    echo "  Services: " . $host['available_services']->count() . "\n";
}
```

---

## 📝 Checklist

- [ ] Code deployed
- [ ] Cache geleert
- [ ] Service 47 Edit-Seite getestet
- [ ] Keine 500-Fehler
- [ ] Cal.com Hosts angezeigt
- [ ] Auto-Sync durchgeführt
- [ ] CalcomHostMapping erstellt
- [ ] Retell Voice Test durchgeführt
- [ ] Appointment mit korrektem Staff erstellt

---

## 🆘 Support

### Frage: Warum wird mein Staff nicht angezeigt?
**Antwort**:
- Staff muss `is_active=true` sein
- Staff muss zu Service via `service_staff` Pivot attached sein
- Pivot muss `can_book=true` haben

### Frage: Kann ich noch den alten Staff Repeater nutzen?
**Antwort**:
- Ja, die Legacy Section ist noch vorhanden
- Sie ist aber versteckt und wird nicht empfohlen
- Neue Lösung ist viel zuverlässiger

### Frage: Was passiert mit alten Staff-Daten?
**Antwort**:
- Sie werden nicht gelöscht
- Legacy Section kann noch geöffnet werden
- Auto-Sync erstellt neue Mappings neben alten Daten

### Frage: Wie oft sollte ich Sync durchführen?
**Antwort**:
- Initial: Nach diesem Deployment
- Dann: Optional täglich via Scheduler
- Manual: Wenn neue Hosts zu Cal.com Event Type hinzugefügt werden

---

## 📚 Mehr Infos

**Vollständige Dokumentation**:
- `/claudedocs/03_API/Retell_AI/CALCOM_SERVICE_HOSTS_INTEGRATION_2025-10-21.md`

**Cal.com API Doku**:
- `/claudedocs/03_API/Retell_AI/CALCOM_API_TEAM_MEMBERS_INVESTIGATION_2025-10-21.md`

**Technische Details**:
- `/app/Services/CalcomServiceHostsResolver.php` (Service Code)
- `/app/Filament/Resources/ServiceResource.php` (Form Changes)

---

**Status**: ✅ Ready to Deploy
**Tested**: Lokal mit Sample Data
**Next**: Deployment & Production Test
