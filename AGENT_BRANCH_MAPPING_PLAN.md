# Agent & Branch Mapping Implementation Plan

## Aktuelle Situation
- Anrufe kommen über Retell.ai rein mit `to_number` (angerufene Nummer)
- System weiß nicht, welcher Filiale/Agent diese Nummer gehört
- Calls werden ohne branch_id und agent_id gespeichert

## Implementierungsschritte

### Phase 1: Datenmodell erweitern

#### 1.1 Agent Model erweitern
```php
// Migration: add_company_branch_to_agents
$table->foreignId('company_id')->after('id')->constrained();
$table->foreignId('branch_id')->after('company_id')->nullable()->constrained();
$table->string('retell_agent_id')->after('agent_id')->unique();
$table->json('assigned_phone_numbers')->nullable();
$table->index(['company_id', 'branch_id']);
```

#### 1.2 PhoneNumber Tabelle aktivieren & erweitern
```php
// Migration: enhance_phone_numbers_table
$table->string('retell_phone_id')->nullable()->unique();
$table->foreignId('agent_id')->nullable()->constrained();
$table->enum('type', ['main', 'secondary', 'toll_free', 'mobile'])->default('main');
$table->json('routing_rules')->nullable(); // Zeitbasiertes Routing etc.
$table->index(['number', 'active']);
```

#### 1.3 Branch-Agent Zuordnungstabelle
```php
// Migration: create_branch_agents_table
$table->id();
$table->foreignId('branch_id')->constrained();
$table->foreignId('agent_id')->constrained();
$table->boolean('is_primary')->default(false);
$table->json('schedule')->nullable(); // Wann ist dieser Agent aktiv
$table->timestamps();
$table->unique(['branch_id', 'agent_id']);
```

### Phase 2: Webhook-Logic implementieren

#### 2.1 Phone Number Resolver Service
```php
namespace App\Services;

class PhoneNumberResolver
{
    public function resolveFromNumber(string $phoneNumber): ?array
    {
        // 1. Direkte Suche in phone_numbers Tabelle
        $phoneRecord = PhoneNumber::where('number', $phoneNumber)
            ->where('active', true)
            ->with(['branch', 'agent'])
            ->first();
            
        if ($phoneRecord) {
            return [
                'branch_id' => $phoneRecord->branch_id,
                'agent_id' => $phoneRecord->agent_id,
                'company_id' => $phoneRecord->branch->company_id
            ];
        }
        
        // 2. Fallback: Branch Hauptnummer
        $branch = Branch::where('phone_number', $phoneNumber)->first();
        if ($branch) {
            return [
                'branch_id' => $branch->id,
                'agent_id' => $branch->retell_agent_id ? 
                    Agent::where('retell_agent_id', $branch->retell_agent_id)->first()?->id : null,
                'company_id' => $branch->company_id
            ];
        }
        
        return null;
    }
}
```

#### 2.2 ProcessRetellWebhookJob anpassen
```php
protected function saveCallRecord(): Call
{
    $resolver = new PhoneNumberResolver();
    $mapping = $resolver->resolveFromNumber($this->data['to_number'] ?? '');
    
    // Fallback auf Retell Agent ID
    if (!$mapping && isset($this->data['agent_id'])) {
        $agent = Agent::where('retell_agent_id', $this->data['agent_id'])->first();
        if ($agent) {
            $mapping = [
                'agent_id' => $agent->id,
                'branch_id' => $agent->branch_id,
                'company_id' => $agent->company_id
            ];
        }
    }
    
    return Call::updateOrCreate(
        ['call_id' => $this->data['call_id']],
        [
            'company_id' => $mapping['company_id'] ?? $this->extractCompanyId(),
            'branch_id' => $mapping['branch_id'] ?? null,
            'agent_id' => $mapping['agent_id'] ?? null,
            'from_number' => $this->data['from_number'] ?? null,
            'to_number' => $this->data['to_number'] ?? null,
            // ... rest of data
        ]
    );
}
```

### Phase 3: Admin UI für Verwaltung

#### 3.1 PhoneNumberResource erstellen
- Liste aller Telefonnummern mit Branch/Agent Zuordnung
- Import von Retell.ai Nummern
- Zuordnung zu Branches/Agents

#### 3.2 Agent Management erweitern
- Agent zu Branch Zuordnung
- Telefonnummern-Verwaltung pro Agent
- Zeitpläne für Agent-Verfügbarkeit

#### 3.3 Branch-Übersicht erweitern
- Alle Telefonnummern der Filiale anzeigen
- Agent-Zuordnungen verwalten
- Routing-Regeln konfigurieren

### Phase 4: Retell.ai Synchronisation

#### 4.1 Sync Command erstellen
```bash
php artisan retell:sync-phone-numbers
```
- Holt alle Phone Numbers von Retell API
- Matched mit lokalen Branches
- Erstellt PhoneNumber Records

#### 4.2 Agent Sync
```bash
php artisan retell:sync-agents
```
- Holt alle Agents von Retell API
- Erstellt/Updated lokale Agent Records
- Verknüpft mit Companies/Branches basierend auf Naming Convention

### Phase 5: Testing & Monitoring

#### 5.1 Test Cases
- Anruf auf Hauptnummer → Branch zugeordnet ✓
- Anruf auf Nebennummer → Richtiger Agent ✓
- Unbekannte Nummer → Fallback auf Company ✓
- Agent wechselt Branch → Calls korrekt zugeordnet ✓

#### 5.2 Monitoring Dashboard
- Nicht zugeordnete Anrufe
- Calls pro Branch/Agent
- Fehlerhafte Zuordnungen

## Beispiel-Szenario

**Unternehmen:** Zahnarztpraxis Dr. Schmidt
- **Zentrale Berlin:** +49 30 12345678
  - Agent: "Zahnarzt Berlin Empfang"
  - Weitere Nummern: +49 30 12345679 (Notfall)
  
- **Filiale Potsdam:** +49 331 98765432
  - Agent: "Zahnarzt Potsdam Empfang"
  
**Anruf-Flow:**
1. Patient ruft +49 30 12345678 an
2. Webhook empfängt Call mit `to_number` = "+493012345678"
3. PhoneNumberResolver findet: branch_id=1 (Berlin)
4. Call wird mit branch_id=1 gespeichert
5. Dashboard zeigt Anruf unter "Filiale Berlin"

## Offene Fragen

1. **Retell Agent Struktur**: Ein Agent pro Filiale oder geteilte Agents?
2. **Routing-Regeln**: Zeitbasiert? Auslastungsbasiert?
3. **Fallback-Strategie**: Was wenn Nummer nicht zugeordnet?
4. **Multi-Agent pro Branch**: Wie werden mehrere Agents verwaltet?

## Prioritäten

1. **MUSS**: Phone-to-Branch Mapping
2. **MUSS**: Agent-Company/Branch Verknüpfung  
3. **SOLLTE**: Retell Sync Automation
4. **KANN**: Erweiterte Routing-Regeln