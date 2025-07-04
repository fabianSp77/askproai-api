# 🏆 Top 10 Power-Befehle für AskProAI

> **Ziel**: Die 10 wichtigsten Befehle, die 95% deiner täglichen Aufgaben abdecken
> **Stand**: 2025-06-28
> **Tipp**: Speichere diese Datei als Bookmark!

## 1️⃣ **5-Minuten Komplett-Onboarding** 🚀
```bash
php artisan askpro:quick-setup \
  --company="Firma Name" \
  --phone="+49 30 12345678" \
  --email="info@firma.de" \
  --branch="Hauptfiliale"
```
**Was es macht**: 
- ✅ Erstellt Company & Branch
- ✅ Konfiguriert Phone Number
- ✅ Erstellt Retell AI Agent
- ✅ Verknüpft alles automatisch
- ✅ Sendet Willkommens-Email

**Wann nutzen**: Bei jedem neuen Kunden!

---

## 2️⃣ **Phone Resolution Debugger** 📞
```bash
php artisan phone:test-resolution "+49 30 12345678" --webhook
```
**Was es macht**:
- 🔍 Testet Telefonnummer → Company Zuordnung
- 🔍 Prüft Branch & Agent Mapping
- 🔍 Simuliert Webhook-Aufruf
- 🔍 Zeigt kompletten Data Flow

**Wann nutzen**: Wenn Anrufe nicht richtig zugeordnet werden

---

## 3️⃣ **Business KPI Dashboard** 📊
```bash
php artisan kpi:dashboard --company-id=1 --format=pretty

# Für alle Companies
php artisan kpi:dashboard --all --export=csv
```
**Zeigt**:
- 📈 Anrufe (heute/Woche/Monat)
- 📈 Termine gebucht
- 📈 Conversion Rate
- 📈 Durchschnittliche Anrufdauer
- 📈 Kosten & ROI

**Wann nutzen**: Täglich für Business Insights

---

## 4️⃣ **Smart Impact Analysis** 🔍
```bash
# Vor jedem Deployment
php artisan analyze:impact --git

# Für spezifische Komponente
php artisan analyze:component App\\Services\\BookingService
```
**Was es macht**:
- ⚠️ Findet Breaking Changes
- ⚠️ Zeigt betroffene Komponenten
- ⚠️ Empfiehlt Rollback-Strategie
- ⚠️ Bewertet Risiko-Level

**Wann nutzen**: VOR jedem Deployment!

---

## 5️⃣ **MCP Auto-Discovery** 🤖
```bash
# Aufgabe beschreiben
php artisan mcp:discover "kunde anlegen und termin buchen"

# Direkt ausführen
php artisan mcp:discover "import calls from retell" --execute
```
**Features**:
- 🎯 Findet besten MCP-Server automatisch
- 🎯 Zeigt Alternativen mit Confidence Score
- 🎯 Kann direkt ausführen
- 🎯 Lernt aus Nutzung

**Wann nutzen**: Bei jeder neuen Aufgabe

---

## 6️⃣ **Webhook Health Monitor** 🏥
```bash
# Live Monitoring starten
./monitor-webhooks.sh

# Oder analysieren
php artisan retell:analyze-webhooks --last-hour

# Webhook manuell testen
php artisan webhook:test --company-id=1
```
**Überwacht**:
- 🚨 Webhook Failures
- 🚨 Duplikate
- 🚨 Performance (Response Time)
- 🚨 Signature Errors

**Wann nutzen**: Bei Webhook-Problemen

---

## 7️⃣ **Emergency Fix Combo** 🚨
```bash
# Der "Fix Everything" Befehl
rm -f bootstrap/cache/config.php && \
php artisan config:cache && \
php artisan horizon:terminate && \
php artisan horizon && \
php artisan optimize:clear
```
**Behebt**:
- 🔧 "Access Denied" Errors
- 🔧 Cache-Probleme
- 🔧 Queue läuft nicht
- 🔧 Config nicht aktuell
- 🔧 90% aller Probleme!

**Wann nutzen**: Wenn etwas nicht funktioniert

---

## 8️⃣ **Complete System Health Check** ✅
```bash
# Alles auf einmal prüfen
php artisan health:check --all && \
php artisan mcp:health && \
php artisan performance:analyze && \
php artisan circuit-breaker:status
```
**Prüft**:
- ✅ Alle Services erreichbar
- ✅ Database Performance
- ✅ Queue Processing
- ✅ External APIs
- ✅ Circuit Breaker Status

**Wann nutzen**: Morgens & vor wichtigen Events

---

## 9️⃣ **Data Flow Debugger** 🔄
```bash
# Tracking starten
php artisan dataflow:start

# Letzte Flows anzeigen
php artisan dataflow:list --today

# Spezifischen Flow analysieren
php artisan dataflow:diagram <correlation-id>

# Flow nach Call ID suchen
php artisan dataflow:trace --call-id="call_xxx"
```
**Verfolgt**:
- 🔄 Retell → Webhook → Processing
- 🔄 Cal.com API Calls
- 🔄 Jeden externen API Call
- 🔄 Mit Timing & Errors

**Wann nutzen**: Für End-to-End Debugging

---

## 🔟 **Production-Ready Deployment** 🚀
```bash
# Der sichere Deployment-Prozess
php artisan test:production-readiness && \
php artisan analyze:impact --git && \
composer quality && \
php artisan backup:run --only-db && \
./deploy.sh production --safety-check
```
**Macht**:
- 🚀 Testet Production-Readiness
- 🚀 Analysiert Impact
- 🚀 Code Quality Check
- 🚀 Backup erstellen
- 🚀 Safe Deployment

**Wann nutzen**: Bei JEDEM Production Deploy

---

## 🎁 BONUS: Die 3 SQL-Queries die du wirklich brauchst

### 1. Letzte Anrufe mit allen Details
```sql
SELECT 
    c.id,
    c.created_at,
    c.duration_minutes,
    cu.name as customer_name,
    cu.phone,
    co.name as company,
    c.transcript_summary
FROM calls c 
LEFT JOIN customers cu ON c.customer_id = cu.id 
LEFT JOIN companies co ON c.company_id = co.id 
WHERE c.created_at > NOW() - INTERVAL 24 HOUR 
ORDER BY c.created_at DESC 
LIMIT 50;
```

### 2. Webhook Performance & Fehler
```sql
SELECT 
    provider,
    event_type,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
    ROUND(AVG(processing_time_ms)) as avg_ms,
    MAX(processing_time_ms) as max_ms
FROM webhook_events 
WHERE created_at > NOW() - INTERVAL 1 HOUR
GROUP BY provider, event_type
ORDER BY failed DESC, avg_ms DESC;
```

### 3. API Performance Bottlenecks
```sql
SELECT 
    service,
    endpoint,
    COUNT(*) as calls,
    ROUND(AVG(duration_ms)) as avg_ms,
    MAX(duration_ms) as max_ms,
    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors
FROM api_call_logs 
WHERE created_at >= NOW() - INTERVAL 1 HOUR
GROUP BY service, endpoint
HAVING avg_ms > 500 OR errors > 0
ORDER BY errors DESC, avg_ms DESC;
```

---

## 🚀 Quick Access Aliases

Füge diese zu deiner `~/.bashrc` oder `~/.zshrc` hinzu:

```bash
# AskProAI Power Commands
alias ask-setup='php artisan askpro:quick-setup'
alias ask-phone='php artisan phone:test-resolution'
alias ask-kpi='php artisan kpi:dashboard'
alias ask-impact='php artisan analyze:impact --git'
alias ask-mcp='php artisan mcp:discover'
alias ask-webhook='./monitor-webhooks.sh'
alias ask-fix='rm -f bootstrap/cache/config.php && php artisan config:cache && php artisan horizon:terminate && php artisan horizon'
alias ask-health='php artisan health:check --all'
alias ask-flow='php artisan dataflow:list --today'
alias ask-deploy='php artisan test:production-readiness && php artisan analyze:impact --git && composer quality'

# Database Access
alias ask-db='mysql -u askproai_user -p"lkZ57Dju9EDjrMxn" askproai_db'
```

---

💡 **Pro-Tipp**: Drucke diese Seite aus oder pinne sie an deinen Monitor! Diese 10 Befehle machen dich zum AskProAI Power-User! 🚀