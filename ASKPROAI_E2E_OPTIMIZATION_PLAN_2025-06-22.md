# AskProAI E2E Prozess-Optimierungsplan
**Datum**: 2025-06-22
**Ziel**: Reibungsloser End-to-End Prozess mit optimierten Prompts und direkter Portal-Verwaltung

## 🎯 Hauptziele

1. **Direkte Prompt-Verwaltung im Portal**
2. **Optimierung des Retell.ai Prompts**
3. **Verbesserung der Datenübergabe**
4. **MCP-basierte Konfiguration**
5. **Echtzeit-Verfügbarkeitsprüfung**

## 📋 Aktuelle Situation

### Stärken:
- ✅ Funktionierender E2E-Prozess
- ✅ Intelligente Service-Zuordnung
- ✅ Multi-Branch Support
- ✅ MCP-Architektur vorhanden

### Schwächen:
- ❌ Prompts nur in Retell.ai editierbar
- ❌ Keine Versionskontrolle für Prompts
- ❌ Keine Echtzeit-Verfügbarkeitsprüfung
- ❌ Begrenzte Analytics

## 🚀 Optimierungsmaßnahmen

### 1. Direkte Prompt-Verwaltung implementieren

**Was**: Prompts direkt im Company Integration Portal bearbeiten
**Wie**: 
```php
// Neue Methode in CompanyIntegrationPortal.php
public function editAgentPrompt($agentId)
{
    $this->currentAgent = RetellMCPServer::getAgent(['agent_id' => $agentId]);
    $this->showPromptEditor = true;
}

public function saveAgentPrompt()
{
    RetellMCPServer::updateAgentPrompt([
        'agent_id' => $this->currentAgent['agent_id'],
        'general_prompt' => $this->editedPrompt,
        'begin_message' => $this->editedBeginMessage
    ]);
}
```

### 2. Optimierter Standard-Prompt

```markdown
# Optimierter Retell.ai System Prompt

Du bist der KI-Assistent von {{company_name}}, einem {{business_type}} in {{city}}.

## Deine Hauptaufgabe:
Terminvereinbarungen für unsere Kunden durchführen - freundlich, effizient und kompetent.

## Verfügbare Dienstleistungen:
{{#services}}
- {{name}} ({{duration}} Min., {{price}}€)
{{/services}}

## Gesprächsablauf:

### 1. Begrüßung
- Verwende: "{{greeting_message}}"
- Frage nach dem Anliegen des Kunden

### 2. Dienstleistung ermitteln
- Frage gezielt nach der gewünschten Dienstleistung
- Bei Unklarheiten: Kurz die Optionen erklären
- Nutze intelligente Zuordnung (z.B. "Haare schneiden" → "Herrenhaarschnitt")

### 3. Terminwunsch erfragen
- Hole aktuelle Zeit mit `current_time_berlin()`
- Frage nach Datum und bevorzugter Uhrzeit
- Biete Alternativen an, wenn nötig

### 4. Kundendaten sammeln
WICHTIG: Nutze `collect_appointment_data()` mit folgenden Feldern:
- datum: Konvertiere zu Format "TT.MM.JJJJ"
- uhrzeit: Format "HH:MM" 
- name: Vor- und Nachname
- telefonnummer: Bestätige die Anrufernummer
- dienstleistung: Exakter Service-Name aus unserer Liste
- email: Optional, aber empfohlen für Bestätigung
- mitarbeiter_wunsch: Falls Präferenz vorhanden
- kundenpraeferenzen: Besondere Wünsche

### 5. Zusammenfassung & Bestätigung
- Wiederhole alle Details
- Bestätige den Termin
- Informiere über Bestätigungs-Email/SMS

## Wichtige Regeln:
1. Sei immer höflich und professionell
2. Sprich Deutsch, außer der Kunde wechselt die Sprache
3. Bei Unsicherheiten: Nachfragen statt raten
4. Öffnungszeiten beachten: {{opening_hours}}
5. Keine Preisverhandlungen - verweise auf feste Preise

## Spezielle Szenarien:

### Kunde ist unsicher über Service:
"Gerne erkläre ich Ihnen unsere Leistungen..."

### Termin nicht verfügbar:
"Dieser Termin ist leider nicht verfügbar. Wie wäre es mit..."

### Technische Probleme:
"Entschuldigung, ich prüfe das kurz für Sie..."

## Beende das Gespräch mit:
"Vielen Dank für Ihre Buchung bei {{company_name}}. Sie erhalten in Kürze eine Bestätigung. Einen schönen Tag noch!"
```

### 3. Erweiterte Datenübergabe

```javascript
// Erweiterte collect_appointment_data Parameter
{
  "datum": "25.06.2025",
  "uhrzeit": "14:30",
  "name": "Max Mustermann",
  "telefonnummer": "+491234567890",
  "dienstleistung": "Herrenhaarschnitt",
  "email": "max@example.com",
  "mitarbeiter_wunsch": "Kevin",
  "kundenpraeferenzen": "Kurzer Schnitt, Fade an den Seiten",
  "erstbesuch": true,
  "marketing_einwilligung": true,
  "erinnerung_gewuenscht": "SMS",
  "notizen": "Kunde hat wenig Zeit, schneller Service gewünscht"
}
```

### 4. Portal-Features für direkte Verwaltung

#### A. Prompt-Editor UI
```blade
{{-- Neuer Prompt Editor im Portal --}}
<div class="prompt-editor-modal">
    <h3>Agent Prompt bearbeiten</h3>
    
    <div class="mb-4">
        <label>Begrüßungsnachricht</label>
        <textarea wire:model="editedBeginMessage" rows="3"></textarea>
    </div>
    
    <div class="mb-4">
        <label>System Prompt</label>
        <textarea wire:model="editedPrompt" rows="20"></textarea>
        
        {{-- Template Variables Helper --}}
        <div class="template-vars">
            <h4>Verfügbare Variablen:</h4>
            <code>{{company_name}}, {{business_type}}, {{services}}, {{opening_hours}}</code>
        </div>
    </div>
    
    <div class="mb-4">
        <label>Voice Settings</label>
        <select wire:model="voiceId">
            <option value="eleven_multilingual_v2">Multilingual v2</option>
            <option value="eleven_turbo_v2">Turbo v2 (schneller)</option>
        </select>
    </div>
    
    <button wire:click="saveAgentPrompt">Änderungen speichern</button>
    <button wire:click="testAgentPrompt">Test-Anruf starten</button>
</div>
```

#### B. A/B Testing für Prompts
```php
// Neue Tabelle: agent_prompt_versions
Schema::create('agent_prompt_versions', function (Blueprint $table) {
    $table->id();
    $table->string('agent_id');
    $table->integer('version');
    $table->text('prompt');
    $table->text('begin_message');
    $table->json('metrics'); // conversion_rate, avg_duration, etc.
    $table->boolean('is_active')->default(false);
    $table->timestamps();
});
```

### 5. Echtzeit-Features während des Anrufs

#### A. Live-Verfügbarkeitsprüfung
```php
// Neue Custom Function für Retell
public function check_availability($params)
{
    $date = $params['date'];
    $time = $params['time'];
    $service = $params['service'];
    $branch = $params['branch_id'];
    
    $available = CalcomService::checkAvailability([
        'date' => $date,
        'time' => $time,
        'duration' => $this->getServiceDuration($service),
        'branch_id' => $branch
    ]);
    
    return [
        'available' => $available,
        'alternatives' => $available ? [] : $this->getSuggestedTimes($date, $service, $branch)
    ];
}
```

#### B. Echtzeit-Buchung
```php
// Direkte Buchung während des Anrufs
public function book_appointment_realtime($params)
{
    $booking = AppointmentBookingService::createRealtimeBooking($params);
    
    return [
        'success' => $booking->success,
        'confirmation_number' => $booking->confirmation_number,
        'message' => $booking->message
    ];
}
```

### 6. Analytics & Monitoring

```php
// Neues Analytics Dashboard
class AgentPerformanceAnalytics
{
    public function getMetrics($agentId, $dateRange)
    {
        return [
            'total_calls' => $this->getTotalCalls($agentId, $dateRange),
            'conversion_rate' => $this->getConversionRate($agentId, $dateRange),
            'avg_call_duration' => $this->getAvgDuration($agentId, $dateRange),
            'customer_satisfaction' => $this->getSatisfactionScore($agentId, $dateRange),
            'failed_bookings' => $this->getFailedBookings($agentId, $dateRange),
            'drop_off_points' => $this->getDropOffAnalysis($agentId, $dateRange),
        ];
    }
}
```

## 📊 Implementierungs-Roadmap

### Phase 1: Basis-Optimierungen (1-2 Tage)
- [ ] Prompt-Editor UI implementieren
- [ ] UpdateAgentPrompt Integration
- [ ] Standard-Prompt Template erstellen

### Phase 2: Erweiterte Features (3-5 Tage)
- [ ] A/B Testing für Prompts
- [ ] Echtzeit-Verfügbarkeitsprüfung
- [ ] Analytics Dashboard

### Phase 3: Advanced Features (1 Woche)
- [ ] ML-basierte Prompt-Optimierung
- [ ] Multi-Language Support
- [ ] Voice Analytics

## 🎯 Erwartete Verbesserungen

1. **Conversion Rate**: +30% durch optimierte Prompts
2. **Anrufdauer**: -25% durch effizientere Gesprächsführung
3. **Kundenzufriedenheit**: +40% durch natürlichere Interaktion
4. **Fehlerrate**: -50% durch bessere Datenvalidierung
5. **Setup-Zeit**: -80% durch Portal-Verwaltung

## 🔧 Technische Anforderungen

- Laravel 10.x
- Livewire 3.x
- Retell.ai API v2
- Cal.com API v2
- Redis für Caching
- PostgreSQL/MySQL

## 📝 Nächste Schritte

1. **Sofort**: Prompt-Editor UI implementieren
2. **Diese Woche**: Standard-Prompt testen und optimieren
3. **Nächste Woche**: Echtzeit-Features entwickeln
4. **Langfristig**: ML-basierte Optimierung