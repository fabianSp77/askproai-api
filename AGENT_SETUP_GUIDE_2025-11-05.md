# Agent Setup Guide - Fehlende Filialen-Agents

**Datum:** 2025-11-05
**Status:** 10 von 11 Filialen benötigen Agent-Konfiguration
**Priorität:** P0 - Kritisch für Voice AI Funktionalität

---

## Übersicht

| Status | Anzahl | Details |
|--------|--------|---------|
| ✅ Konfiguriert | 1 | Friseur 1 Zentrale |
| ❌ Fehlt | 10 | Siehe Tabelle unten |

---

## Filialen ohne Agent-Konfiguration

### Priorität 1: Produktiv-Filialen (Echte Businesses)

| # | Unternehmen | Filiale | Telefon | Branch ID |
|---|-------------|---------|---------|-----------|
| 1 | **Friseur Schmidt** | Hauptfiliale | +49488719359 | e3b0137e-060c-4d86-a206-57998dcd66a7 |
| 2 | **Dr. Müller Zahnarztpraxis** | Hauptfiliale | +49645858004 | 5e9ba1eb-ecf5-41e6-a8db-0ca47e6a419d |
| 3 | **Salon Schönheit** | Hauptfiliale | +494098765432 | 9f905e49-a068-4f24-b648-50d6ea1890fd |
| 4 | **Restaurant Bella Vista** | Hauptfiliale | +49795550663 | 7a422d3c-8cc1-47b7-9702-2bef728c2f09 |

### Priorität 2: Platform/Demo Filialen

| # | Unternehmen | Filiale | Telefon | Branch ID |
|---|-------------|---------|---------|-----------|
| 5 | **AskProAI** | AskProAI Zentrale | +493083793369 | 9f4d5e2a-46f7-41b6-b81d-1532725381d4 |
| 6 | **Premium Telecom Solutions** | Hauptfiliale | +49358840585 | 4d96cf13-aad5-4fd4-a03e-8f21b9a7a6af |
| 7 | **Demo Zahnarztpraxis** | Praxis Berlin-Mitte | *kein Telefon* | 9f49ab77-2811-46b2-a609-305975c423da |

### Priorität 3: Test/Seeder Filialen

| # | Unternehmen | Filiale | Telefon | Branch ID |
|---|-------------|---------|---------|-----------|
| 8 | Peters Linke AG | Popp Kunze Branch | +49 (04225) 191 9613 | c8a10bfe-8c96-4337-87e3-50abfa1ffc6c |
| 9 | Ulrich | Thiel Baumgartner Branch | +4887025763096 | ac639b9c-5bab-4ea0-bc91-1394b14725fe |
| 10 | Wirth Voigt AG | Frey e.V. Branch | (06862) 819 0823 | 1ee9251b-38b0-42e5-9cb4-183ddc32718f |

---

## Agent Setup - Option 1: Manuell (Retell Dashboard)

### Schritt 1: Agent in Retell Dashboard erstellen

1. **Login:** https://app.retellai.com
2. **Neuer Agent:**
   - Name: `[Unternehmen] [Filiale]` (z.B. "Friseur Schmidt Hauptfiliale")
   - Type: **Conversation Flow**
   - Model: gpt-4o-mini
   - Temperature: 0.3

3. **Conversation Flow kopieren:**
   - Referenz: `conversation_flow_a58405e3f67a` (Friseur 1)
   - Oder: Neuen Flow mit Services-Liste erstellen

4. **Global Prompt konfigurieren:**
   ```markdown
   ## Unsere Services ([Unternehmen]) - VOLLSTÄNDIGE LISTE

   **WICHTIG:** Dies sind ALLE verfügbaren Dienstleistungen.
   Sage NIEMALS 'Wir bieten [X] nicht an', ohne vorher diese
   Liste geprüft oder check_availability_v17 aufgerufen zu haben!

   ### Alle verfügbaren Services:
   [Services aus Database für company_id]

   ### Häufige Synonyme & Varianten:
   [Synonyme aus service_synonyms Tabelle]

   **Bei Unsicherheit:**
   1. Prüfe diese Liste
   2. Nutze check_availability_v17
   3. Frage den Kunden zur Klarstellung
   4. NIEMALS sofort ablehnen ohne Backend-Check!
   ```

5. **Tools konfigurieren:**
   - check_availability_v17
   - book_appointment_v17
   - reschedule_appointment_v17
   - cancel_appointment_v17

6. **Agent ID kopieren** (z.B. `agent_xyz123abc...`)

### Schritt 2: Agent ID in Database eintragen

```sql
UPDATE branches
SET retell_agent_id = 'agent_xyz123abc...',
    updated_at = NOW()
WHERE id = '[BRANCH_UUID]';
```

Oder via Laravel Tinker:
```php
$branch = \App\Models\Branch::find('[BRANCH_UUID]');
$branch->retell_agent_id = 'agent_xyz123abc...';
$branch->save();
```

### Schritt 3: Agent publishen

In Retell Dashboard:
- Agent → Publish Button
- Version wird inkrementiert

---

## Agent Setup - Option 2: Automatisches Script (TODO)

**Script zu entwickeln:** `php artisan branch:setup-agent {branch_id}`

### Features:
1. ✅ Services für company_id laden
2. ✅ Synonyme aus service_synonyms laden
3. ✅ Conversation Flow Template laden
4. ✅ Global Prompt mit Services generieren
5. ✅ Retell API: Create Agent
6. ✅ Retell API: Publish Agent
7. ✅ Database: Update branches.retell_agent_id
8. ✅ Validation: Test Agent via API

### Nutzung (geplant):
```bash
# Einzelne Filiale
php artisan branch:setup-agent e3b0137e-060c-4d86-a206-57998dcd66a7

# Alle Filialen ohne Agent
php artisan branch:setup-agents --missing

# Spezifisches Unternehmen
php artisan branch:setup-agents --company=1

# Dry-run (nur anzeigen)
php artisan branch:setup-agents --missing --dry-run
```

**Implementierungs-Aufwand:** ~2-3 Stunden
**Vorteil:** Standardisierung, weniger Fehler, schnellere Rollouts

---

## Referenz: Friseur 1 Agent (Erfolgreich konfiguriert)

### Details
```
Company: Friseur 1 (ID: 1)
Branch: Friseur 1 Zentrale
Branch ID: 34c4d48e-4753-4715-9c30-c55843a943e8
Phone: +493033081738

Agent ID: agent_45daa54928c5768b52ba3db736
Agent Name: Friseur1 Fixed V2 (parameter_mapping)
Version: 40
Type: conversation-flow
Flow ID: conversation_flow_a58405e3f67a

Services: 18 aktive Services
Synonyms: 114 Synonyme konfiguriert
Status: ✅ Voll funktionsfähig

Features:
- Voice AI Terminbuchung
- "Hair Detox" Recognition (98% confidence)
- Synonym-System aktiv
- Cal.com Integration
- Backend Function Calls
```

### Services Liste (Referenz)
```
1. Ansatz + Längenausgleich (85.00 EUR, 155 min)
2. Ansatzfärbung (58.00 EUR, 135 min)
3. Balayage/Ombré (110.00 EUR, 150 min)
4. Damenhaarschnitt (45.00 EUR, 45 min)
5. Dauerwelle (78.00 EUR, 135 min)
6. Föhnen & Styling Damen (32.00 EUR, 30 min)
7. Föhnen & Styling Herren (20.00 EUR, 20 min)
8. Gloss (38.00 EUR, 30 min)
9. Haarspende (28.00 EUR, 30 min)
10. Hairdetox (22.00 EUR, 15 min) ← ✅ Mit Synonymen
11. Herrenhaarschnitt (32.00 EUR, 55 min)
12. Intensiv Pflege Maria Nila (28.00 EUR, 15 min)
13. Kinderhaarschnitt (20.00 EUR, 30 min)
14. Komplette Umfärbung (Blondierung) (145.00 EUR, 180 min)
15. Rebuild Treatment Olaplex (42.00 EUR, 15 min)
16. Trockenschnitt (30.00 EUR, 30 min)
17. Waschen & Styling (28.00 EUR, 45 min)
18. Waschen, schneiden, föhnen (55.00 EUR, 60 min)
```

---

## Test nach Agent-Setup

### 1. Database Verification
```bash
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$branch = DB::table('branches')->where('id', '[BRANCH_UUID]')->first();
echo \"Agent ID: \" . (\$branch->retell_agent_id ?? 'NICHT GESETZT') . \"\\n\";
"
```

### 2. Retell API Verification
```bash
curl -X GET \
  https://api.retellai.com/get-agent/[AGENT_ID] \
  -H "Authorization: Bearer [API_KEY]" \
  -H "Content-Type: application/json"
```

### 3. Phone Test
1. Anruf auf Telefonnummer der Filiale
2. Agent sollte sich melden
3. Service-Namen testen (z.B. "Herrenhaarschnitt")
4. Termin-Buchungsprozess durchlaufen

---

## Checkliste pro Filiale

- [ ] Services für company_id in Database vorhanden?
- [ ] Synonyme in service_synonyms vorhanden? (Seeder ausführen)
- [ ] Retell Agent erstellt?
- [ ] Conversation Flow konfiguriert?
- [ ] Global Prompt mit allen Services?
- [ ] Tools konfiguriert (check_availability, book_appointment)?
- [ ] Agent published?
- [ ] Agent ID in branches.retell_agent_id eingetragen?
- [ ] Telefonnummer in branches.phone_number vorhanden?
- [ ] Cal.com Integration konfiguriert?
- [ ] Test-Anruf erfolgreich?

---

## Quick Commands

### Alle Filialen ohne Agent auflisten
```bash
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$branches = DB::table('branches')
    ->whereNull('retell_agent_id')
    ->orWhere('retell_agent_id', '')
    ->get(['name', 'phone_number']);

foreach (\$branches as \$b) {
    echo \$b->name . ' - ' . (\$b->phone_number ?? 'no phone') . \"\\n\";
}
"
```

### Agent ID setzen
```php
// Laravel Tinker
$branch = \App\Models\Branch::find('BRANCH_UUID');
$branch->retell_agent_id = 'agent_xyz123';
$branch->save();
```

### Services für Unternehmen auflisten
```bash
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$companyId = 18; // z.B. Friseur Schmidt
\$services = DB::table('services')
    ->where('company_id', \$companyId)
    ->where('is_active', true)
    ->get(['name', 'price', 'duration_minutes']);

foreach (\$services as \$s) {
    printf(\"- %s (%.2f EUR, %d min)\\n\", \$s->name, \$s->price, \$s->duration_minutes);
}
"
```

---

## Nächste Schritte

### Sofort (heute)
1. **Friseur Schmidt** Agent erstellen (P1 - produktiv)
2. **Dr. Müller Zahnarztpraxis** Agent erstellen (P1 - produktiv)
3. Test mit echten Telefon-Anrufen

### Diese Woche
4. **Salon Schönheit** Agent erstellen
5. **Restaurant Bella Vista** Agent erstellen
6. **AskProAI Zentrale** Agent erstellen (eigene Plattform!)

### Nächste Woche
7. Automatisches Setup-Script entwickeln
8. Restliche Filialen konfigurieren
9. Monitoring Dashboard für Agent-Status

---

**Dokumentation:**
- Erfolgreiches Beispiel: `/var/www/api-gateway/FRISEUR1_FIX_STATUS_2025-11-05.md`
- Synonym System: `/var/www/api-gateway/HAIRDETOX_FIX_FINAL_COMPLETE_2025-11-05.md`
- Admin Fix: `/var/www/api-gateway/SUPER_ADMIN_FIX_2025-11-05.md`

**Scripts:**
- Verification: `php scripts/verify_friseur1_complete.php`
- Agent Check: `php scripts/get_correct_friseur_agent.php`
