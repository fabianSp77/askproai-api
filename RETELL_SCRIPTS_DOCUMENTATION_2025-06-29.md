# Retell.ai Scripts - Vollständige Dokumentation
**Erstellt**: 2025-06-29  
**Status**: Alle Scripts dokumentiert

---

## 🛠️ Übersicht aller erstellten Scripts

### 1. **Health Check & Auto-Repair**
```bash
php retell-health-check.php
```
**Funktion**: 
- Prüft alle Retell-Komponenten
- Repariert automatisch häufige Probleme
- Setzt fehlende API Keys
- Erstellt fehlende Agents in DB
- **WICHTIGSTES SCRIPT - ZUERST AUSFÜHREN!**

### 2. **Call Import Scripts**

#### Manueller Import (empfohlen)
```bash
php import-retell-calls-manual.php
```
- Umgeht Job Queue für sofortigen Import
- Mappt Felder korrekt (retell_call_id, duration_sec)
- Zeigt Import-Fortschritt

#### Alternative Import Scripts
```bash
php fetch-retell-calls.php        # Original Import via Jobs
php fetch-retell-calls-now.php    # Sofort-Import Variante
```

### 3. **Agent Management**

#### Agent aktivieren
```bash
php activate-retell-agent.php
```
- Aktiviert Agent in lokaler DB
- Setzt is_active = true

#### Agent Funktionen reparieren
```bash
php fix-agent-functions.php
```
- Synchronisiert Custom Functions
- Behebt Funktions-Zuordnungen

#### Agent UI prüfen
```bash
php check-retell-agent-ui.php
php test-retell-ui-display.php
```
- Testet UI-Komponenten
- Prüft Anzeige-Logik

### 4. **Metriken & Statistiken**

#### Metriken reparieren (WICHTIG!)
```bash
php fix-metrics-display-final.php
```
- Setzt Test-Dauern für Calls
- Behebt 0-Anzeige Problem
- Korrigiert Feldnamen

#### Debug Scripts
```bash
php debug-metrics-display.php      # Detaillierte Metrik-Analyse
php check-retell-metrics.php       # Einfacher Metrik-Check
php debug-agent-metrics.php        # Agent-spezifische Metriken
php test-metrics-directly.php      # Direkte DB-Queries
```

### 5. **Call-Daten Reparatur**

#### Dauer-Felder korrigieren
```bash
php fix-call-durations-v2.php              # Hauptscript
php update-call-durations-from-retell.php  # Von API aktualisieren
php check-call-fields.php                  # Feld-Analyse
```

#### Call-Anzeige reparieren
```bash
php fix-call-display-timezone.php   # Timezone-Probleme
php fix-call-agent-assignment.php   # Agent-Zuordnungen
```

### 6. **UI Component Scripts**

#### Activate Button hinzufügen
```bash
php add-activate-button-to-cards.php
```
- Fügt Activate Button zu Agent Cards hinzu
- Modifiziert Blade Templates

### 7. **Analyse & Debug Tools**

#### Phone Number Resolution
```bash
php check-phone-numbers.php
```
- Zeigt Phone Number → Branch → Company Mapping
- Debuggt Multi-Tenancy Zuordnung

#### Agent Statistiken
```bash
php debug-retell-statistics.php
```
- Detaillierte Statistik-Analyse
- Performance-Metriken

---

## 📋 Script-Ausführungsreihenfolge

Bei Problemen diese Reihenfolge verwenden:

1. **Health Check**
   ```bash
   php retell-health-check.php
   ```

2. **Calls importieren**
   ```bash
   php import-retell-calls-manual.php
   ```

3. **Metriken reparieren**
   ```bash
   php fix-metrics-display-final.php
   ```

4. **UI prüfen**
   ```bash
   php test-retell-ui-display.php
   ```

---

## 🔧 Quick Setup Script

Für komplette Einrichtung:
```bash
#!/bin/bash
# retell-quick-setup.sh

echo "🚀 Starting Retell Quick Setup..."

# 1. Health Check
php retell-health-check.php

# 2. Import Calls
php import-retell-calls-manual.php

# 3. Fix Metrics
php fix-metrics-display-final.php

# 4. Clear Cache
php artisan optimize:clear

echo "✅ Setup complete! Please refresh your browser."
```

---

## ⚠️ Wichtige Hinweise

### Feldnamen-Mapping
- `duration_sec` (richtig) vs `duration_seconds` (falsch)
- `retell_call_id` muss gesetzt sein
- `company_id` ist Pflichtfeld

### TenantScope
- In Admin-Bereichen: `withoutGlobalScope(\App\Scopes\TenantScope::class)`
- Verhindert "No company context" Fehler

### API Version
- Immer v2 Endpoints verwenden: `/v2/list-calls`
- In `RetellV2Service.php` bereits korrigiert

---

## 🐛 Bekannte Probleme & Lösungen

1. **"Metriken zeigen 0"**
   - Lösung: `php fix-metrics-display-final.php`

2. **"Keine Calls werden importiert"**
   - Lösung: `php import-retell-calls-manual.php`

3. **"Agent ist nicht aktiv"**
   - Lösung: `php activate-retell-agent.php`

4. **"TenantScope Exception"**
   - Lösung: Company Context setzen oder withoutGlobalScope verwenden