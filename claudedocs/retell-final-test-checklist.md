# Retell Agent - Finale Test-Checkliste

## ✅ Korrigierte Diskrepanzen

### 1. Parameter-Anpassungen (KRITISCH)
- [x] **check_customer**: Akzeptiert nur `call_id` (vorher: phone_number, customer_name)
- [x] **cancel_appointment**: Verwendet `appointment_date` statt `booking_id`
- [x] **reschedule_appointment**: Verwendet `old_date` statt `booking_id`
- [x] **book_appointment**: Verwendet `appointment_date/time` statt `date/time`
- [x] **book_appointment**: Akzeptiert `service_type` als String statt `service_id`

### 2. Response-Format (KRITISCH)
- [x] Alle Responses enthalten `success: true/false`
- [x] Alle Responses enthalten `message` für Agent-Ausgabe
- [x] Response-Variables matchen Agent-Erwartungen

### 3. Call ID Integration
- [x] Alle Funktionen nutzen `call_id` zur Telefonnummer-Ermittlung
- [x] Keine Funktion fragt mehr nach Telefonnummer
- [x] Automatische Kunden-Erkennung über Call-Record

## 🧪 Test-Szenarien für Live-Anruf

### Szenario 1: Neuer Kunde bucht Termin
1. Agent ruft automatisch `check_customer` mit `call_id` auf
2. System meldet "Neuer Kunde"
3. Kunde nennt Terminwunsch
4. Agent ruft `collect_appointment_data` auf
5. System prüft Verfügbarkeit und bucht

### Szenario 2: Bestandskunde storniert
1. Agent ruft `check_customer` auf → findet Kunde
2. Kunde will Termin am "Dienstag" stornieren
3. Agent ruft `cancel_appointment` mit `appointment_date: "2025-09-30"`
4. System findet und storniert Termin

### Szenario 3: Umbuchung
1. Kunde will Termin verschieben
2. Agent ruft `reschedule_appointment` mit:
   - `old_date`: "2025-09-30"
   - `new_date`: "2025-10-02"
   - `new_time`: "15:00"
3. System verschiebt Termin

## 🔴 Bekannte Probleme

### Cal.com Booking Error
```
Error: Undefined array key "startTime"
```
**Ursache**: CalcomService Response-Parsing
**Workaround**: Manuelle Buchung über Dashboard

## 📞 Test-Anruf Durchführung

### Vorbereitung:
1. ✅ Webhook URL konfiguriert: `https://api.askproai.de/webhooks/retell`
2. ✅ Alle API-Endpoints erreichbar unter `/api/retell/*`
3. ✅ Agent-Funktionen verwenden korrekte URLs
4. ⚠️ Cal.com Service mit Event-Type konfiguriert

### Test-Dialog:
```
Anrufer: "Guten Tag, ich möchte einen Termin buchen"
Agent: [check_customer wird aufgerufen]
Agent: "Gerne! Wie ist Ihr Name?"
Anrufer: "Max Mustermann"
Agent: "Wann hätten Sie denn Zeit?"
Anrufer: "Morgen um 14 Uhr"
Agent: [collect_appointment_data wird aufgerufen]
Agent: "Der Termin ist verfügbar..."
```

## 🎯 Validierungspunkte

### Must-Have:
- [ ] Agent fragt NICHT nach Telefonnummer
- [ ] Agent erkennt Bestandskunden automatisch
- [ ] Terminbuchung funktioniert mit deutschem Datum
- [ ] Alternativen werden bei Nichtverfügbarkeit angeboten

### Nice-to-Have:
- [ ] Post-Call-Analysis wird korrekt gefüllt
- [ ] SMS-Bestätigung wird versendet
- [ ] Kalendereintrag wird erstellt

## 📊 Monitoring

### Logs prüfen:
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "Retell|appointment|booking"
```

### Database prüfen:
```sql
SELECT * FROM calls WHERE retell_call_id IS NOT NULL ORDER BY created_at DESC;
SELECT * FROM calcom_bookings ORDER BY created_at DESC;
```

## 🚀 Go-Live Checkliste

1. [ ] Testanruf erfolgreich
2. [ ] Kunde wird erkannt/angelegt
3. [ ] Termin wird gebucht
4. [ ] Webhook-Events werden verarbeitet
5. [ ] Post-Call-Analysis kommt an

## Support-Kontakt

Bei Problemen mit:
- **Retell Agent**: Dashboard → Agent Settings
- **API-Endpoints**: Logs in `/var/www/api-gateway/storage/logs/`
- **Cal.com Integration**: CalcomService.php überprüfen