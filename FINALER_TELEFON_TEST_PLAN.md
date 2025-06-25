# 🚀 FINALER TELEFON TEST PLAN - AskProAI

## 📊 Aktuelle Analyse-Ergebnisse

### ✅ Was funktioniert bereits:
1. **Telefonnummern-Zuordnung**: Telefonnummern sind korrekt Filialen zugeordnet
2. **Webhook-Endpunkte**: Alle Retell Webhooks sind aktiv und erreichbar
3. **System-Services**: Redis, MySQL, Horizon laufen einwandfrei
4. **Retell Agent Konfiguration**: Agent hat korrekte `collect_appointment_data` Funktion
5. **Sprache**: Deutsch (de-DE) ist konfiguriert

### ❌ Was fehlt noch:
1. **Retell Agents nicht synchronisiert**: Agents existieren in Retell, aber nicht in unserer DB
2. **Cal.com Event Types falsch**: Filialen verweisen auf nicht-existierende Event Type IDs
3. **UI zeigt nicht alle Felder**: Voice-Einstellungen, Funktionen etc. werden nicht angezeigt

### 📝 Wichtige Erkenntnisse:
- **Alle Retell-Felder werden gespeichert** - im `configuration` JSON-Feld
- **Kritische Felder für Anrufe sind vorhanden**: voice_id, language, functions
- **Die Sortierung/Darstellung** unterscheidet sich von Retell (Gruppierung nach Basis-Namen)

## 🔧 SOFORT-MAßNAHMEN (vor Telefontests)

### 1️⃣ Retell Agents synchronisieren (5 Minuten)
```bash
# Agents von Retell API importieren
php artisan retell:sync-configurations --company=1 --force

# Prüfen ob Agents importiert wurden
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db \
  -e "SELECT agent_id, name, is_active, sync_status FROM retell_agents WHERE company_id = 1;"
```

### 2️⃣ Cal.com Event Types korrigieren (10 Minuten)
```bash
# Verfügbare Event Types prüfen
php artisan tinker --execute="
\$eventTypes = \App\Models\CalcomEventType::where('company_id', 1)->get();
foreach(\$eventTypes as \$et) {
    echo 'ID: ' . \$et->id . ' - ' . \$et->title . ' (Slug: ' . \$et->slug . ')' . PHP_EOL;
}
"

# Filialen mit korrektem Event Type verknüpfen
php artisan tinker --execute="
\$branches = \App\Models\Branch::where('company_id', 1)->get();
\$correctEventTypeId = 1; // ANPASSEN basierend auf obiger Ausgabe!

foreach(\$branches as \$branch) {
    \$branch->calcom_event_type_id = \$correctEventTypeId;
    \$branch->save();
    echo 'Updated: ' . \$branch->name . PHP_EOL;
}
"
```

### 3️⃣ Telefonnummern-Agent Zuordnung prüfen (5 Minuten)
```bash
# Sicherstellen dass Telefonnummern den richtigen Agent haben
php artisan tinker --execute="
\$phones = \App\Models\PhoneNumber::where('company_id', 1)->get();
foreach(\$phones as \$phone) {
    echo \$phone->number . ' → Agent: ' . \$phone->retell_agent_id . PHP_EOL;
    if (!\$phone->retell_agent_id) {
        \$phone->retell_agent_id = 'agent_9a8202a740cd3120d96fcfda1e';
        \$phone->save();
        echo '  → UPDATED!' . PHP_EOL;
    }
}
"
```

### 4️⃣ Validierung ausführen (2 Minuten)
```bash
# Validierungsskript ausführen
php validate-phone-config.php

# Alle Checks sollten grün sein!
```

## 📞 TELEFON TEST-SZENARIEN

### Test 1: Basis-Terminbuchung 
**Telefonnummer:** +49 30 837 93 369

**Ablauf:**
1. Anrufen und auf Begrüßung warten
2. Sagen: "Ich möchte einen Termin buchen"
3. Angeben:
   - Name: "Test Kunde"
   - Service: "Beratung" 
   - Datum: "morgen um 15 Uhr"
   - Email: "test@example.com"
4. Termin bestätigen
5. Auflegen

**Monitoring während des Anrufs:**
```bash
# Terminal 1: Webhook-Logs
tail -f storage/logs/laravel.log | grep -E "RETELL|collect_appointment|WEBHOOK"

# Terminal 2: Cache beobachten
watch -n 1 'redis-cli --scan --pattern "*retell_appointment*" | xargs -I {} redis-cli get {}'

# Terminal 3: Datenbank prüfen
watch -n 2 'mysql -u askproai_user -p"lkZ57Dju9EDjrMxn" askproai_db -e "SELECT * FROM calls ORDER BY created_at DESC LIMIT 1\G"'
```

### Test 2: Keine Verfügbarkeit
**Szenario:** Nach einem Termin außerhalb der Öffnungszeiten fragen

### Test 3: Unterdrückte Nummer
**Szenario:** Mit unterdrückter Nummer anrufen, Telefonnummer manuell angeben

### Test 4: Abbruch
**Szenario:** Mitten im Gespräch auflegen, prüfen ob Daten korrekt gespeichert werden

## 🔍 DATENFLUSS-ÜBERPRÜFUNG

### Erwarteter Datenfluss:
```
1. Anruf → Retell Agent antwortet
2. collect_appointment_data wird aufgerufen
   → Daten in Redis Cache gespeichert
3. Anruf beendet → call_ended Webhook
   → ProcessRetellCallEndedJob 
4. Job verarbeitet Appointment-Daten
   → Kunde anlegen/finden
   → Termin in DB speichern
   → Cal.com Buchung erstellen
5. Bestätigungs-Email (wenn gewünscht)
```

### Nach jedem Test prüfen:
```sql
-- Letzter Anruf
SELECT * FROM calls ORDER BY created_at DESC LIMIT 1\G

-- Webhook Events
SELECT * FROM webhook_events 
WHERE provider = 'retell' 
ORDER BY created_at DESC LIMIT 5;

-- Erstellte Termine
SELECT a.*, c.name as customer_name 
FROM appointments a 
JOIN customers c ON a.customer_id = c.id 
WHERE a.source = 'phone' 
ORDER BY a.created_at DESC LIMIT 1\G

-- Cal.com Buchung
SELECT calcom_booking_id, calcom_event_type_id, status 
FROM appointments 
WHERE source = 'phone' 
ORDER BY created_at DESC LIMIT 1;
```

## 🎯 ERFOLGS-KRITERIEN

### Anruf-Handling ✓
- [ ] Agent antwortet innerhalb 3 Sekunden
- [ ] Korrekte deutsche Begrüßung
- [ ] Natürlicher Gesprächsfluss
- [ ] Alle Daten werden erfasst
- [ ] Klare Terminbestätigung

### Datenverarbeitung ✓
- [ ] Webhook wird empfangen
- [ ] Call-Record wird erstellt
- [ ] Kunde wird angelegt/gefunden
- [ ] Appointment mit allen Feldern erstellt
- [ ] Cal.com Buchung erfolgt

### Performance ✓
- [ ] Webhook-Verarbeitung < 500ms
- [ ] Termin-Erstellung < 2s
- [ ] Cal.com Sync < 3s
- [ ] Gesamt Ende-zu-Ende < 5s nach Anrufende

## 🚨 TROUBLESHOOTING

### Problem: "Agent antwortet nicht"
```bash
# Prüfe ob Agent aktiv
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db \
  -e "SELECT * FROM retell_agents WHERE agent_id = 'agent_9a8202a740cd3120d96fcfda1e'\G"

# Prüfe Telefonnummer-Zuordnung
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db \
  -e "SELECT * FROM phone_numbers WHERE number = '+49 30 837 93 369'\G"
```

### Problem: "Termin wird nicht erstellt"
```bash
# Cache prüfen
redis-cli keys "*retell_appointment*"

# Webhook-Logs prüfen
tail -100 storage/logs/laravel.log | grep "ProcessRetellCallEndedJob"

# Queue-Status
php artisan queue:monitor webhooks
```

### Problem: "Cal.com Fehler"
```bash
# Circuit Breaker prüfen
php artisan circuit-breaker:status

# Event Type prüfen
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db \
  -e "SELECT b.name, b.calcom_event_type_id, c.title 
      FROM branches b 
      LEFT JOIN calcom_event_types c ON b.calcom_event_type_id = c.id 
      WHERE b.company_id = 1;"
```

## 📋 UI/UX VERBESSERUNGS-PLAN (nach Tests)

### Phase 1: Kritische Felder anzeigen (1-2 Tage)
- Voice-Einstellungen (voice_id, language, speed)
- Funktionen-Liste mit Details
- Response Engine Info (Model, Temperature)

### Phase 2: Performance-Tracking (3-4 Tage)
- Anruf-Statistiken pro Agent
- Erfolgsquote
- Durchschnittliche Gesprächsdauer
- Fehler-Tracking

### Phase 3: Erweiterte Features (1 Woche)
- Funktions-Editor
- Voice-Preview
- A/B Testing Support
- Multi-Language Konfiguration

## ✅ CHECKLISTE VOR TELEFONTEST

- [ ] Retell Agents synchronisiert
- [ ] Cal.com Event Types korrekt
- [ ] Telefonnummern haben Agent ID
- [ ] Validierungsskript zeigt alles grün
- [ ] Monitoring-Terminals geöffnet
- [ ] Test-Telefon bereit
- [ ] Notizen-Template vorbereitet

## 🎉 LOS GEHT'S!

Nach Abschluss der Sofort-Maßnahmen (ca. 20 Minuten) ist das System bereit für Telefontests. 

**Erste Test-Nummer: +49 30 837 93 369**

Viel Erfolg! 🚀