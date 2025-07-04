# Archivierte Blocker und historische Informationen (Juni 2025)

> ⚠️ **Hinweis**: Diese Informationen stammen aus Juni 2025 und sind möglicherweise nicht mehr aktuell. Sie wurden zu Dokumentationszwecken archiviert.

## 🔑 Kritische Verständnispunkte (Juni 2025)

### Telefonnummer → Filiale → Cal.com Zuordnung
**Das Kernproblem**: Wie weiß das System, bei welcher Firma/Filiale der Kunde anruft?

**Die Lösung**: 
```yaml
Telefonnummer (+49 30 837 93 369)
    ↓ PhoneNumberResolver
Branch (AskProAI Berlin)
    ↓ branch.calcom_event_type_id
Cal.com Event Type (2026361)
    ↓ CalcomV2Service
Termin gebucht!
```

**Kritische Konfiguration**:
1. Telefonnummer MUSS einer Filiale zugeordnet sein
2. Filiale MUSS aktiv sein (is_active = true)
3. Filiale MUSS Cal.com Event Type haben
4. Retell Agent ID MUSS korrekt sein

### Vereinfachung des Systems (Stand Juni 2025)
- **Problem**: 119 Tabellen, 7 Cal.com Services, 5 Retell Services
- **Lösung**: Konsolidierung auf 20 Kern-Tabellen, 3 Services
- **Ziel**: 3-Minuten Setup statt 2 Stunden

### Wichtige Dokumente (Stand Juni 2025)
- **System Status**: Production Ready 85%, 94 Tabellen, 6 MCP Server
- `DOCUMENTATION_UPDATE_ANALYSIS_2025_06_25.md` - Vollständige Codebase-Analyse
- `DOCUMENTATION_SECURITY_AUDIT_2025-06-25.md` - Security Audit mit Fixes
- `ASKPROAI_ULTIMATE_IMPLEMENTATION_PLAN.md` - Master Implementation Plan
- `TELEFON_ZU_TERMIN_FLOW.md` - Kompletter Datenfluss erklärt
- **Neue Features**: Knowledge Base System, Mobile API, Security Layer, GDPR Tools

## 🚨 Kritische Blocker (Stand 2025-06-17)

### Ultra-Analyse Ergebnis: **NO-GO für Production** ❌

Nach umfassender Analyse mit 5 parallelen Sub-Agents wurden folgende kritische Blocker identifiziert:

### 1. **Test-Suite komplett defekt (94% Failure Rate)**
- **Problem**: SQLite-inkompatible Migration `fix_company_json_fields_defaults`
- **Impact**: Keine Qualitätssicherung möglich
- **Lösung**: Database-agnostische Migration mit CompatibleMigration Base Class
- **Effort**: 3 Stunden

### 2. **Onboarding blockiert**
- **Problem**: RetellAgentProvisioner erwartet Service der nicht existiert
- **Impact**: Neue Kunden können nicht angelegt werden
- **Lösung**: Pre-Provisioning Validation statt Auto-Creation
- **Effort**: 2 Stunden

### 3. **Race Condition in Webhooks**
- **Problem**: Cache-basierte Deduplication hat Race Condition
- **Impact**: Duplicate Bookings möglich
- **Lösung**: Redis SETNX mit Lua Scripts für Atomarität
- **Effort**: 1 Stunde

### 4. **Database Connection Pool fehlt**
- **Problem**: Bei >100 Requests werden Connections erschöpft
- **Impact**: System Crash unter Last
- **Lösung**: PDO Persistent Connections + Pool Manager
- **Effort**: 1 Stunde

### 5. **Security: Phone Validation fehlt**
- **Problem**: Keine Validierung, SQL Injection möglich
- **Impact**: Security Breach Risiko
- **Lösung**: libphonenumber Integration
- **Effort**: 1 Stunde

### Zusätzliche kritische Findings:
- **52 SQL Injection Risiken** (whereRaw Verwendungen)
- **Multi-Tenancy Silent Failures** (Daten können verloren gehen)
- **Webhook Timeout Risiko** (Synchrone Verarbeitung)
- **Keine Production Monitoring** (Blind im Ernstfall)

### Zeitplan bis Production Ready
**Kritischer Review ergab**: Ursprüngliche Schätzungen 2-3x zu optimistisch!
- **Realistische Zeit**: 18 Arbeitstage (statt 8)
- **Mit Risiko-Buffer**: 20-25 Tage

### Spezifikation Review Ergebnis: **4.7/10** ❌
- **Technische Korrektheit**: 5/10 (Mehrere Fehler gefunden)
- **Implementierbarkeit**: 4/10 (Zu komplex)
- **Security**: 4/10 (Neue Risiken eingeführt)
- **Performance**: 5/10 (30% Degradation erwartet)

### Empfohlene Vorgehensweise:
1. **STOP** - Keine neuen Features bis Blocker gefixt
2. **Spezifikation überarbeiten** - Fokus auf Einfachheit
3. **Security Review** - Externe Prüfung empfohlen
4. **Realistische Planung** - x2.5 Faktor auf alle Schätzungen

### Neue Best Practices für Spezifikationen:
1. **Immer mehrere Sub-Agents** für umfassende Analyse
2. **Kritischer Review** als Pflichtschritt
3. **Zeitschätzungen x2.5** für realistische Planung
4. **Security-First** Ansatz bei allen Änderungen
5. **Einfachheit vor Cleverness** - KISS Prinzip