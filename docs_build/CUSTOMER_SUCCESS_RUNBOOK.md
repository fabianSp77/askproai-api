# 🎯 Customer Success Runbook - Top 10 Probleme & Instant-Lösungen

> **Ziel**: Jedes Kundenproblem in < 2 Minuten lösen!

## 🔍 Quick-Diagnose Tool
```bash
# Ein Befehl für Gesamt-Diagnose
php artisan askpro:diagnose --company-id=X
```

---

## 🚨 PROBLEM #1: "AI versteht unseren Dialekt/Akzent nicht"

### Symptome:
- Kunden beschweren sich über Verständnisprobleme
- AI fragt ständig nach
- Hohe Abbruchrate

### 🔧 SOFORT-LÖSUNG:
```bash
# 1. Dialekt-optimierten Prompt aktivieren
php artisan retell:update-prompt --company-id=X --template=dialect-robust

# 2. In Retell Dashboard anpassen:
```
```
VERSTEHEN: Höre geduldig zu, auch bei Dialekt.
Bei Unklarheiten sage: "Entschuldigung, könnten Sie das bitte wiederholen?"
DIALEKT-WÖRTER:
- "Samstag" = "Sonnabend" 
- "Viertel drei" = "14:15 Uhr"
- "Heuer" = "Dieses Jahr"
```

### ✅ Erfolgskontrolle:
- Testanruf mit Dialekt-Sprecher
- Completion Rate > 80%

---

## 🚨 PROBLEM #2: "Termine werden doppelt gebucht"

### Symptome:
- Zwei Kunden zur gleichen Zeit
- Cal.com zeigt andere Termine als DB

### 🔧 SOFORT-LÖSUNG:
```bash
# 1. Webhook-Deduplication prüfen
php artisan webhook:check-duplicates --last-hour

# 2. Force-Sync durchführen
php artisan calcom:force-sync --company-id=X

# 3. Deduplication verstärken
php artisan config:set webhook.deduplication_window=10
php artisan cache:clear
```

### 🚨 NOTFALL-FIX:
```bash
# Sofort alle Webhooks pausieren
php artisan webhook:pause --duration=5

# Duplicates bereinigen
php artisan appointments:remove-duplicates --dry-run
php artisan appointments:remove-duplicates --confirm
```

---

## 🚨 PROBLEM #3: "Kunde bekommt keine Bestätigungs-Email"

### Symptome:
- Termin wird erstellt
- Keine Email im Postfach (auch nicht Spam)

### 🔧 SOFORT-LÖSUNG:
```bash
# 1. Email-Queue Status
php artisan queue:monitor emails

# 2. Stuck Emails neu senden
php artisan email:retry-failed --last-day

# 3. Test-Email senden
php artisan email:test --to=kunde@example.de

# 4. SMTP prüfen
php artisan email:verify-smtp
```

### 📧 Alternative Email-Provider aktivieren:
```bash
# Auf Backup-SMTP umschalten
php artisan config:set mail.mailer=backup_smtp
php artisan queue:restart
```

---

## 🚨 PROBLEM #4: "Anrufe werden nicht erfasst/angezeigt"

### Symptome:
- Kunde ruft an, aber kein Eintrag
- Retell Dashboard zeigt Calls

### 🔧 SOFORT-LÖSUNG:
```bash
# 1. Horizon läuft?
php artisan horizon:status
# Wenn nicht: 
sudo supervisorctl start horizon

# 2. Manueller Import
php artisan retell:import-calls --last-hour

# 3. Webhook-URL prüfen
php artisan retell:verify-webhook
```

### 🔄 Auto-Import aktivieren:
```bash
# Cron für automatischen Import
php artisan schedule:run-now --command=retell:import-calls
```

---

## 🚨 PROBLEM #5: "Falscher Mitarbeiter wird zugewiesen"

### Symptome:
- Termin bei Mitarbeiter A statt B
- Branch-Zuordnung falsch

### 🔧 SOFORT-LÖSUNG:
```bash
# 1. Staff-Assignment prüfen
php artisan staff:show-assignments --branch-id=X

# 2. Neu-Mapping
php artisan staff:reassign --from=A --to=B --future-only

# 3. Service-Zuordnung fixen
php artisan services:sync-staff --branch-id=X
```

---

## 🚨 PROBLEM #6: "AI spricht zu schnell/langsam"

### Symptome:
- Ältere Kunden verstehen AI nicht
- Beschwerden über Sprechtempo

### 🔧 SOFORT-LÖSUNG:
```json
// In Retell Agent Settings:
{
  "voice_settings": {
    "speed": 0.9,  // Langsamer (Standard: 1.0)
    "pitch": 1.0,
    "stability": 0.8
  }
}
```

### Copy-Paste Prompt-Ergänzung:
```
SPRECHWEISE: Spreche langsam und deutlich.
Mache Pausen zwischen Sätzen.
Wiederhole wichtige Informationen.
```

---

## 🚨 PROBLEM #7: "Kunde kann Termin nicht stornieren"

### Symptome:
- Anrufer will absagen
- AI versteht Stornierung nicht

### 🔧 SOFORT-LÖSUNG:
```
# Prompt-Ergänzung für Stornierung:
STORNIERUNG: 
- Bei "absagen", "stornieren", "verschieben" → 
- Frage: "Möchten Sie den Termin absagen oder verschieben?"
- Bei Absage: "Ihr Termin wird storniert. Sie erhalten eine Bestätigung."
- Leite zu Webhook: cancel_appointment
```

### Backend-Handler aktivieren:
```bash
php artisan config:set retell.enable_cancellation=true
php artisan cache:clear
```

---

## 🚨 PROBLEM #8: "Keine Termine in den nächsten Tagen verfügbar"

### Symptome:
- Cal.com zeigt freie Slots
- AI sagt "keine Termine"

### 🔧 SOFORT-LÖSUNG:
```bash
# 1. Availability-Cache leeren
php artisan cache:forget availability:*

# 2. Timezone-Check
php artisan timezone:verify --company-id=X

# 3. Working Hours prüfen
php artisan branch:show-hours --branch-id=X

# 4. Force Refresh
php artisan calcom:refresh-availability --next-days=7
```

---

## 🚨 PROBLEM #9: "AI legt mittendrin auf"

### Symptome:
- Gespräch bricht ab
- Timeout-Fehler in Logs

### 🔧 SOFORT-LÖSUNG:
```bash
# 1. Timeout erhöhen
php artisan config:set retell.call_timeout=300

# 2. Webhook-Response-Zeit prüfen
php artisan webhook:measure-performance

# 3. Memory-Limit Check
php artisan system:check-resources
```

### Retell Settings anpassen:
```json
{
  "max_call_duration": 600,  // 10 Minuten
  "idle_timeout": 30         // 30 Sekunden Stille
}
```

---

## 🚨 PROBLEM #10: "Kunde versteht AI-Bestätigung nicht"

### Symptome:
- Kunde unsicher ob Termin gebucht
- Nachfragen per Email/Telefon

### 🔧 SOFORT-LÖSUNG:
```
# Klarere Bestätigung im Prompt:
BESTÄTIGUNG: Wiederhole IMMER:
"Ich bestätige Ihren Termin am [TAG], den [DATUM] um [UHRZEIT] Uhr 
bei [MITARBEITER] für [SERVICE]. 
Sie erhalten gleich eine Bestätigung per Email an [EMAIL]."
```

### SMS-Bestätigung aktivieren:
```bash
php artisan config:set notifications.sms.enabled=true
php artisan config:set notifications.sms.provider=twilio
```

---

## 📊 Success Metrics Dashboard

```bash
# Customer Success Score anzeigen
php artisan metrics:customer-success --company-id=X

# Ausgabe:
📊 Customer Success Metrics:
- Call Completion Rate: 87%
- Email Delivery Rate: 98%
- No-Show Rate: 12%
- Customer Satisfaction: 4.2/5
- Avg Resolution Time: 1.8 min
```

---

## 🚀 Proaktive Maßnahmen

### Täglicher Health-Check:
```bash
# Als Cron-Job einrichten
0 9 * * * php artisan askpro:daily-health-check --notify-slack
```

### Wöchentliches Review:
```bash
# Automatischer Report
php artisan report:customer-success --week --email=support@company.de
```

### Monatliches Update:
```bash
# AI-Prompts optimieren basierend auf Daten
php artisan ai:optimize-prompts --based-on-last-month
```

---

## 🆘 Eskalation

**Problem nicht in Liste?**
1. Tier 1: Support Chat (2 Min Response)
2. Tier 2: Phone Support (+49 30 123456)
3. Tier 3: Remote Access Session
4. Tier 4: On-Site Support (wenn kritisch)

> 💡 **Remember**: Ein glücklicher Kunde = Ein erfolgreicher Kunde!