# 🚀 AskProAI MCP System - Komplette Übersicht

## 📋 Was sind MCPs und warum brauchen wir sie?

MCP (Model Context Protocol) ermöglicht es Claude, direkt mit Ihrer Anwendung zu kommunizieren. Statt manuell Logs zu durchsuchen oder SQL-Queries zu schreiben, können Sie Claude einfach fragen: "Warum ist die Buchung fehlgeschlagen?"

## 🎯 Verfügbare MCP Server

### 1. **Database MCP** 🗄️
**Zweck**: Datenbank-Analyse ohne SQL-Kenntnisse

**Claude kann für Sie:**
- "Zeige alle fehlgeschlagenen Termine heute"
- "Wie viele Kunden haben wir in Berlin?"
- "Suche nach Kunde mit Telefonnummer 0170..."

**Endpoints:**
- `GET /api/mcp/database/schema` - Datenbankstruktur
- `POST /api/mcp/database/query` - SQL ausführen
- `POST /api/mcp/database/search` - Textsuche
- `GET /api/mcp/database/failed-appointments` - Fehlerhafte Termine
- `GET /api/mcp/database/call-stats` - Anrufstatistiken

### 2. **Cal.com MCP** 📅
**Zweck**: Kalender-System verwalten

**Claude kann für Sie:**
- "Synchronisiere alle Event Types"
- "Welche Termine sind morgen frei?"
- "Zeige mir die Buchungen dieser Woche"
- "Welcher Mitarbeiter kann welchen Service?"

**Endpoints:**
- `GET /api/mcp/calcom/event-types` - Event Types anzeigen
- `POST /api/mcp/calcom/availability` - Verfügbarkeit prüfen
- `GET /api/mcp/calcom/bookings` - Buchungen abrufen
- `POST /api/mcp/calcom/sync` - Synchronisieren
- `GET /api/mcp/calcom/test/{id}` - Verbindung testen

### 3. **Retell.ai MCP** 📞
**Zweck**: Telefon-AI System überwachen

**Claude kann für Sie:**
- "Wie viele Anrufe hatten wir heute?"
- "Zeige Details zum letzten Anruf"
- "Welche Telefonnummern sind aktiv?"
- "Analysiere die Anrufqualität"

**Endpoints:**
- `GET /api/mcp/retell/agent/{id}` - Agent-Details
- `GET /api/mcp/retell/call-stats` - Anrufstatistiken
- `GET /api/mcp/retell/recent-calls` - Letzte Anrufe
- `GET /api/mcp/retell/phone-numbers/{id}` - Telefonnummern

### 4. **Sentry MCP** 🐛
**Zweck**: Fehler analysieren und beheben

**Claude kann für Sie:**
- "Welche Fehler treten häufig auf?"
- "Zeige mir den Stack Trace für Fehler XYZ"
- "Gibt es Performance-Probleme?"

**Endpoints:**
- `GET /api/mcp/sentry/issues` - Aktuelle Fehler
- `GET /api/mcp/sentry/issues/{id}` - Fehlerdetails
- `GET /api/mcp/sentry/performance` - Performance-Daten

### 5. **Queue MCP** 📋
**Zweck**: Queue-System und Jobs überwachen

**Claude kann für Sie:**
- "Wie viele Jobs sind fehlgeschlagen?"
- "Zeige mir die letzten Webhook-Jobs"
- "Warum ist Job XYZ fehlgeschlagen?"
- "Wie ist die Queue-Performance?"
- "Starte fehlgeschlagenen Job neu"

**Endpoints:**
- `GET /api/mcp/queue/overview` - Queue-Übersicht
- `GET /api/mcp/queue/failed-jobs` - Fehlgeschlagene Jobs
- `GET /api/mcp/queue/recent-jobs` - Letzte Jobs
- `GET /api/mcp/queue/job/{id}` - Job-Details
- `POST /api/mcp/queue/job/{id}/retry` - Job neu starten
- `GET /api/mcp/queue/metrics` - Performance-Metriken
- `GET /api/mcp/queue/workers` - Worker-Status
- `POST /api/mcp/queue/search` - Jobs suchen

### 6. **Laravel Loop** 🔄
**Zweck**: Direkte Laravel-Kontrolle

**Claude kann für Sie:**
- "Führe php artisan queue:monitor aus"
- "Zeige alle Routes"
- "Lösche den Cache"
- "Welche Jobs sind in der Queue?"

## 🎨 Typische Anwendungsfälle

### 🔍 **Fehlersuche bei gescheiterten Buchungen**
```
Sie: "Claude, warum ist die Buchung für Kunde Schmidt heute fehlgeschlagen?"

Claude nutzt automatisch:
1. Database MCP → Sucht nach Kunde Schmidt
2. Database MCP → Findet fehlerhafte Appointments
3. Retell MCP → Prüft zugehörige Anrufe
4. Sentry MCP → Sucht nach Fehlern im Zeitraum
5. Cal.com MCP → Prüft Kalenderstatus

Antwort: "Die Buchung ist fehlgeschlagen, weil..."
```

### 📊 **Täglicher Status-Check**
```
Sie: "Claude, gib mir einen Überblick über heute"

Claude nutzt automatisch:
1. Database MCP → Appointments & Calls heute
2. Retell MCP → Anrufstatistiken
3. Cal.com MCP → Buchungsstatus
4. Sentry MCP → Neue Fehler

Antwort: "Heute: 47 Anrufe, 23 Buchungen, 2 Fehler..."
```

### 🛠️ **System-Wartung**
```
Sie: "Claude, optimiere das System"

Claude nutzt automatisch:
1. Laravel Loop → Cache leeren
2. Queue MCP → Queue-Status und Failed Jobs prüfen
3. Database MCP → Langsame Queries finden
4. Sentry MCP → Performance-Bottlenecks
5. Queue MCP → Worker-Status überprüfen

Antwort: "Optimierungen durchgeführt: ..."
```

### 🔄 **Webhook-Probleme debuggen**
```
Sie: "Claude, warum werden Webhooks nicht verarbeitet?"

Claude nutzt automatisch:
1. Queue MCP → Failed Jobs suchen
2. Queue MCP → Job Details abrufen
3. Sentry MCP → Fehler analysieren
4. Database MCP → Webhook Events prüfen
5. Queue MCP → Jobs neu starten

Antwort: "Die Webhooks schlagen fehl wegen..."
```

## 🚦 MCP Auswahl-Matrix

| Problem | Primärer MCP | Sekundäre MCPs |
|---------|--------------|----------------|
| Buchung fehlgeschlagen | Database | Retell, Cal.com, Sentry, Queue |
| Keine Anrufe kommen an | Retell | Database, Sentry, Queue |
| Kalender nicht synchron | Cal.com | Database, Queue |
| System langsam | Laravel Loop | Sentry, Database, Queue |
| Kunde beschwert sich | Database | Retell, Cal.com |
| Fehler im Admin Panel | Sentry | Laravel Loop |
| Webhook nicht verarbeitet | Queue | Database, Sentry |
| Jobs schlagen fehl | Queue | Sentry, Database |
| Horizon läuft nicht | Queue | Laravel Loop |

## 🔧 Fehlende MCPs (Roadmap)

### 1. **Customer Intelligence MCP** (PRIORITÄT: HOCH)
- Kundenverhalten analysieren
- Duplikate finden
- Kommunikationshistorie

### 3. **Business Analytics MCP**
- Umsatzanalyse
- Conversion-Trichter
- ROI-Berechnungen

### 4. **Integration Health MCP**
- Alle APIs überwachen
- Circuit Breaker Status
- Webhook-Verarbeitung

## 📚 Quick Reference

### MCP Token erstellen:
```bash
php artisan mcp:create-token admin@askproai.de
```

### Verbindung testen:
```bash
php artisan mcp:test YOUR_TOKEN
```

### Laravel Loop starten:
```bash
php artisan loop:mcp:start
```

### Cache leeren:
```bash
curl -X POST -H "Authorization: Bearer TOKEN" \
  https://api.askproai.de/api/mcp/database/cache/clear
```

## 🎯 Best Practices

1. **Immer Company Context angeben**
   - ✅ "Fehler für Firma ABC"
   - ❌ "Alle Fehler"

2. **Zeiträume begrenzen**
   - ✅ "Anrufe der letzten 24 Stunden"
   - ❌ "Alle Anrufe"

3. **Spezifisch fragen**
   - ✅ "Warum ist Buchung #123 fehlgeschlagen?"
   - ❌ "Was läuft falsch?"

4. **MCPs kombinieren**
   - Bei Buchungsproblemen: Database + Cal.com + Retell + Queue
   - Bei Performance: Laravel Loop + Sentry + Queue
   - Bei Kundenproblemen: Database + Retell
   - Bei Webhook-Problemen: Queue + Database + Sentry

## 🚨 Wichtige Befehle für Notfälle

```bash
# System-Status prüfen
php artisan mcp:test TOKEN --endpoint=all

# Alle Caches leeren
php artisan optimize:clear

# Queue neustarten
php artisan queue:restart

# Fehler der letzten Stunde
curl -H "Authorization: Bearer TOKEN" \
  "https://api.askproai.de/api/mcp/sentry/issues?query=created_at:>1h"

# Queue-Status prüfen
curl -H "Authorization: Bearer TOKEN" \
  https://api.askproai.de/api/mcp/queue/overview

# Fehlgeschlagene Jobs anzeigen
curl -H "Authorization: Bearer TOKEN" \
  https://api.askproai.de/api/mcp/queue/failed-jobs

# Job neu starten
curl -X POST -H "Authorization: Bearer TOKEN" \
  https://api.askproai.de/api/mcp/queue/job/JOB_ID/retry
```

## 📈 Monitoring

Überwachen Sie die MCP-Nutzung:
```sql
-- Top MCP Endpoints
SELECT endpoint, COUNT(*) as calls, AVG(response_time_ms) as avg_ms
FROM api_call_logs
WHERE endpoint LIKE '/api/mcp/%'
AND created_at > NOW() - INTERVAL 24 HOUR
GROUP BY endpoint
ORDER BY calls DESC;
```

---

**Tipp**: Speichern Sie diese Übersicht als Lesezeichen. Bei Problemen können Sie Claude einfach sagen: "Nutze die MCP Übersicht und löse Problem X"