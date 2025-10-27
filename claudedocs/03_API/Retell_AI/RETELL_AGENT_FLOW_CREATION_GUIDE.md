# Retell AI - Agent & Conversation Flow Erstellungs-Guide

**Erstellt**: 2025-10-23
**Basiert auf**: Friseur 1 Deployment Erfahrung
**Status**: Production Best Practices

---

## Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Conversation Flow Erstellung](#conversation-flow-erstellung)
3. [Branding & Anpassung](#branding--anpassung)
4. [Deployment Prozess](#deployment-prozess)
5. [Phone Number Konfiguration](#phone-number-konfiguration)
6. [Troubleshooting](#troubleshooting)
7. [Lessons Learned](#lessons-learned)

---

## Übersicht

### Was ist ein Retell Conversation Flow Agent?

Ein Retell Conversation Flow Agent ist ein **strukturierter Voice AI Agent**, der durch:
- **Global Prompt**: Definiert Rolle, Verhalten, Services und Workflows
- **Nodes**: Definieren Gesprächslogik und Verzweigungen
- **Tools**: API-Endpoints die der Agent aufrufen kann
- **Model Configuration**: LLM-Auswahl und Parameter

gesteuert wird.

### Wann neue Agents erstellen?

**Erstelle einen neuen Agent wenn**:
- Neue Firma/Branch mit eigenem Branding
- Komplett andere Services oder Workflows
- Unterschiedliche Team-Konfiguration

**Dupliziere bestehenden Agent wenn**:
- Ähnliche Services und Workflows
- Nur Branding-Unterschiede
- Test-Umgebungen

---

## Conversation Flow Erstellung

### Schritt 1: Basis-Template auswählen

Wir haben einen **Production-Ready Template**:
```
/var/www/api-gateway/public/askproai_state_of_the_art_flow_2025_V17.json
```

**Dieser Template enthält**:
- ✅ Optimierte Node-Struktur (34 Nodes)
- ✅ Alle benötigten Tools (7 Tools)
- ✅ Function Nodes für 100% reliable Tool-Calls
- ✅ 2-Stufen Booking (Race Condition Protection)
- ✅ Composite Services Support (Backend)

### Schritt 2: Flow anpassen

Erstelle ein PHP Script für vollständige Anpassung:

```php
<?php
/**
 * Create [FIRMENNAME] Flow
 */

require __DIR__ . '/vendor/autoload.php';

// Load template
$templateFile = __DIR__ . '/public/askproai_state_of_the_art_flow_2025_V17.json';
$outputFile = __DIR__ . '/public/[firmenname]_flow_complete.json';

$flow = json_decode(file_get_contents($templateFile), true);

// === 1. GLOBAL PROMPT ANPASSEN ===
$flow['global_prompt'] = <<<'PROMPT'
# [FIRMENNAME] - Voice AI Terminassistent 2025

## Deine Rolle
Du bist der intelligente Terminassistent von **[FIRMENNAME]**.
Sprich natürlich, freundlich und effizient auf Deutsch.

## Unser [Geschäft/Praxis/Salon]: [FIRMENNAME]
[Beschreibung des Geschäfts]
Unsere Services: [Service 1], [Service 2], [Service 3]

## WICHTIG: Anrufer-Telefonnummer
Die Telefonnummer des Anrufers ist AUTOMATISCH verfügbar.
Nutze sie für check_customer() um den Kunden zu erkennen.

## Unsere Services ([FIRMENNAME])

### Standard-Services:
- **[Service 1]** (~[Dauer])
- **[Service 2]** (~[Dauer])
- **[Service 3]** (~[Dauer])

### [Optional: Composite Services wenn relevant]
**[Composite Service Name]** (~[Gesamtdauer]):
- [Beschreibung des Ablaufs mit Wartezeiten]

## Unser Team ([FIRMENNAME])

Verfügbare Mitarbeiter:
- **[Name 1]**
- **[Name 2]**
- **[Name 3]**

### Mitarbeiter-Wünsche
Wenn ein Kunde einen bestimmten Mitarbeiter wünscht:
- "Ich möchte gerne zu [Name]" → nutze `mitarbeiter` Parameter

## [Rest des Prompts - siehe Template]
PROMPT;

// === 2. TOOL DESCRIPTIONS ANPASSEN ===
foreach ($flow['tools'] as &$tool) {
    // dienstleistung Parameter auf firmenspezifische Services anpassen
    if (isset($tool['parameters']['properties']['dienstleistung']['description'])) {
        $tool['parameters']['properties']['dienstleistung']['description'] =
            'Service type (z.B. [Service 1], [Service 2], [Service 3])';
    }

    // mitarbeiter Parameter hinzufügen wenn Staff Preference gewünscht
    if ($tool['name'] === 'book_appointment_v17') {
        $tool['parameters']['properties']['mitarbeiter'] = [
            'type' => 'string',
            'description' => 'Optional: Gewünschter Mitarbeiter (z.B. "[Name 1]", "[Name 2]"). Nur angeben wenn Kunde explizit einen Mitarbeiter wünscht.'
        ];

        $tool['description'] = 'Book appointment with optional staff preference for [FIRMENNAME]';
    }
}
unset($tool);

// === 3. SPEICHERN ===
file_put_contents($outputFile, json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Flow created: $outputFile\n";
```

### Schritt 3: Flow validieren

```bash
# JSON Syntax prüfen
php -r "json_decode(file_get_contents('public/[firmenname]_flow_complete.json'));"

# Struktur prüfen
cat public/[firmenname]_flow_complete.json | jq '{
  nodes: (.nodes | length),
  tools: (.tools | length),
  start_node: .start_node_id,
  model: .model_choice
}'

# Branding prüfen
cat public/[firmenname]_flow_complete.json | jq -r '.global_prompt' | head -20
```

---

## Branding & Anpassung

### Kritische Anpassungen (IMMER erforderlich)

#### 1. Global Prompt

**Muss geändert werden**:
- ✅ Agent Identity: "Terminassistent von **[FIRMENNAME]**"
- ✅ Geschäfts-Beschreibung: Salon/Praxis/Studio spezifisch
- ✅ Services: Firmenspezifische Dienstleistungen
- ✅ Team Members: Echte Mitarbeiter-Namen
- ✅ Composite Services: Falls zutreffend, mit Ablauf-Beschreibung

**Beispiele**:
```
❌ "Du bist der Terminassistent von AskPro AI"
✅ "Du bist der Terminassistent von Friseur 1"

❌ "Unsere Services: Beratung, Konsultation"
✅ "Unsere Services: Herrenhaarschnitt, Damenhaarschnitt, Ansatzfärbung"

❌ Keine Team-Information
✅ "Unser Team: Emma Williams, Fabian Spitzer, David Martinez"
```

#### 2. Tool Parameter Descriptions

**Muss geändert werden**:
- ✅ `dienstleistung` Parameter: Firmenspezifische Beispiele
- ✅ `mitarbeiter` Parameter: Mit echten Namen (falls Staff Preference)

**Beispiele**:
```php
// ❌ FALSCH (Generisch)
"description": "Service type (z.B. Beratung)"

// ✅ RICHTIG (Firmenspezifisch)
"description": "Service type (z.B. Herrenhaarschnitt, Damenhaarschnitt, Ansatzfärbung)"
```

**Script zum Fixen**:
```php
foreach ($flow['tools'] as &$tool) {
    if (isset($tool['parameters']['properties']['dienstleistung']['description'])) {
        $tool['parameters']['properties']['dienstleistung']['description'] = str_replace(
            'z.B. Beratung',
            'z.B. [Service 1], [Service 2], [Service 3]',
            $tool['parameters']['properties']['dienstleistung']['description']
        );
    }
}
```

#### 3. Model Configuration (Optional)

Standard-Konfiguration (empfohlen):
```json
{
  "model_choice": {
    "type": "cascading",
    "model": "gpt-4o-mini"
  },
  "model_temperature": 0.3
}
```

**Nicht ändern** außer du hast spezifische Anforderungen!

### Optionale Anpassungen

#### Mitarbeiter-Präferenz (Staff Preference)

Nur hinzufügen wenn:
- ✅ Firma hat mehrere Mitarbeiter
- ✅ Kunden können/sollten Mitarbeiter wählen
- ✅ Backend unterstützt Mitarbeiter-Zuordnung

**Implementation**:
```php
if ($tool['name'] === 'book_appointment_v17') {
    $tool['parameters']['properties']['mitarbeiter'] = [
        'type' => 'string',
        'description' => 'Optional: Gewünschter Mitarbeiter (z.B. "Emma", "Fabian"). Nur wenn Kunde explizit wünscht.'
    ];
}
```

**Global Prompt Addition**:
```
### Mitarbeiter-Wünsche
Wenn ein Kunde einen bestimmten Mitarbeiter wünscht:
- "Ich möchte gerne zu [Name]" → nutze `mitarbeiter` Parameter: "[Name]"
- "Bei [Name] bitte" → `mitarbeiter: "[Name]"`

Wenn KEIN Mitarbeiter genannt wird: Parameter weglassen (wir wählen automatisch).
```

#### Composite Services

Nur hinzufügen wenn:
- ✅ Services haben eingebaute Wartezeiten
- ✅ Backend unterstützt Segment-Buchungen
- ✅ Erklärung der Wartezeiten ist kundenwert

**Global Prompt Section**:
```
### Composite Services - [Name] mit Wartezeiten (WICHTIG!)

Manche Services haben **Wartezeiten** während [Grund].
Der Kunde wartet [wo], aber unser Team kann zwischendurch andere Kunden bedienen.

**[Service Name]** (~[Gesamtdauer]):
- [Schritt 1] ([Dauer]) → **Pause [Dauer]** → [Schritt 2] ([Dauer]) → ...

**Wie du damit umgehst**:
1. ERKLÄRE die Gesamtdauer natürlich: "[Service] dauert etwa [Dauer]"
2. ERWÄHNE beiläufig: "Dabei gibt es Wartezeiten während [Grund]"
3. Buche NORMAL - unser Backend organisiert die Segmente automatisch
```

---

## Deployment Prozess

### Deployment-Strategie

Es gibt **2 Wege** einen Flow zu deployen:

#### Option A: Bestehenden Flow updaten (EMPFOHLEN)

**Wann verwenden**:
- ✅ Agent existiert bereits
- ✅ Agent hat bereits einen Conversation Flow
- ✅ Du willst nur den Flow-Content ändern

**Vorteile**:
- ✅ Keine neue Flow-ID
- ✅ Keine Agent-Konfiguration nötig
- ✅ Funktioniert auch bei Agent Version > 0

**Script**:
```php
<?php
/**
 * Update Existing Conversation Flow
 */

$retellApiKey = config('services.retellai.api_key');
$existingFlowId = 'conversation_flow_[ID]'; // Von Agent Export
$flowFile = __DIR__ . '/public/[firmenname]_flow_complete.json';

$flow = json_decode(file_get_contents($flowFile), true);

// Update Flow
$updatePayload = [
    'global_prompt' => $flow['global_prompt'],
    'nodes' => $flow['nodes'],
    'start_node_id' => $flow['start_node_id'],
    'start_speaker' => $flow['start_speaker'],
    'tools' => $flow['tools'],
    'model_choice' => $flow['model_choice'],
    'model_temperature' => $flow['model_temperature']
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-conversation-flow/{$existingFlowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($updatePayload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Flow updated: {$existingFlowId}\n";
} else {
    echo "❌ Failed: HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}

// Publish Agent (WICHTIG!)
$agentId = 'agent_[ID]';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/publish-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Agent published!\n";
} else {
    echo "❌ Publish failed: HTTP {$httpCode}\n";
}
```

#### Option B: Neuen Flow erstellen + zuweisen

**Wann verwenden**:
- ✅ Komplett neuer Agent
- ✅ Agent hat noch keinen Flow (Version 0)
- ✅ Du willst einen separaten Flow haben

**Nachteile**:
- ❌ Funktioniert NICHT bei Agent Version > 0
- ❌ Erfordert manuelle Zuweisung im Dashboard

**Script**:
```php
<?php
// Step 1: Create Flow
$createPayload = [
    'global_prompt' => $flow['global_prompt'],
    'nodes' => $flow['nodes'],
    // ...
];

curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/create-conversation-flow",
    CURLOPT_POST => true,
    // ...
]);

$response = curl_exec($ch);
$flowData = json_decode($response, true);
$conversationFlowId = $flowData['conversation_flow_id'];

// Step 2: Update Agent (NUR bei Version 0!)
$updatePayload = [
    'response_engine' => [
        'type' => 'conversation-flow',
        'version' => 2,
        'conversation_flow_id' => $conversationFlowId
    ]
];

curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-agent/{$agentId}",
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    // ...
]);

// Step 3: Publish Agent
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/publish-agent/{$agentId}",
    CURLOPT_POST => true,
    // ...
]);
```

### Deployment Checklist

**Vor Deployment**:
- [ ] Flow JSON validiert (JSON syntax)
- [ ] Branding vollständig angepasst (Global Prompt)
- [ ] Tool Descriptions firmenspezifisch
- [ ] Mitarbeiter-Namen korrekt
- [ ] Services korrekt
- [ ] Composite Services erklärt (falls relevant)

**Nach Deployment**:
- [ ] HTTP 200 Response erhalten
- [ ] Agent published (neues `inbound_agent_version`)
- [ ] Flow-ID notiert
- [ ] Phone Number updated (siehe nächster Abschnitt)
- [ ] Dashboard überprüft

**Verification**:
```bash
# Agent Version prüfen
curl -H "Authorization: Bearer $API_KEY" \
  https://api.retellai.com/get-agent/agent_[ID] | jq '.response_engine.version'

# Flow Content prüfen
curl -H "Authorization: Bearer $API_KEY" \
  https://api.retellai.com/get-conversation-flow/conversation_flow_[ID] | \
  jq -r '.global_prompt' | head -20
```

---

## Phone Number Konfiguration

### Kritisches Problem: Agent Version Mismatch

**Das Problem**:
Wenn du einen Agent **publishst**, erstellt Retell eine **neue Agent Version**.
Deine Phone Number zeigt aber noch auf die **alte Version**!

**Symptom**:
```
User ruft an → Hört alte Version (altes Branding, alte Services)
```

**Root Cause**:
```json
{
  "phone_number": "+493033081738",
  "inbound_agent_id": "agent_f1ce85d06a84afb989dfbb16a9",  // ✅ Richtig
  "inbound_agent_version": 5  // ❌ ALT! Sollte 6 sein
}
```

### Lösung: Phone Number Update

**Nach JEDEM Agent Publish**:

```php
<?php
// 1. Aktuelle Agent Version checken
$agentData = json_decode(
    file_get_contents("https://api.retellai.com/get-agent/{$agentId}", false, stream_context_create([
        'http' => ['header' => "Authorization: Bearer {$apiKey}"]
    ])),
    true
);

$currentVersion = $agentData['response_engine']['version'];
echo "Current Agent Version: {$currentVersion}\n";

// 2. Phone Number updaten
$phoneNumber = '+493033081738'; // E.164 Format

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-phone-number/{$phoneNumber}",
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'inbound_agent_version' => $currentVersion
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $phoneData = json_decode($response, true);
    echo "✅ Phone updated to version {$phoneData['inbound_agent_version']}\n";
} else {
    echo "❌ Failed: HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
}
```

### Phone Number Update Checklist

**Nach jedem Deployment**:
1. [ ] Agent published
2. [ ] Neue Agent Version ermittelt
3. [ ] Phone Number auf neue Version updated
4. [ ] Update erfolgreich (HTTP 200)
5. [ ] Test-Anruf gemacht

**Verification**:
```bash
# Phone Number Konfiguration prüfen
curl -H "Authorization: Bearer $API_KEY" \
  https://api.retellai.com/list-phone-numbers | \
  jq '.[] | select(.phone_number == "+493033081738") | {
    phone: .phone_number,
    agent: .inbound_agent_id,
    version: .inbound_agent_version
  }'
```

---

## Troubleshooting

### Problem 1: Änderungen nicht sichtbar im Dashboard

**Symptom**:
- Deployment Script meldet Success
- Dashboard zeigt alte Version

**Mögliche Ursachen**:

1. **Agent nicht published**
   ```bash
   # Lösung: Agent publishen
   curl -X POST -H "Authorization: Bearer $API_KEY" \
     https://api.retellai.com/publish-agent/agent_[ID]
   ```

2. **Browser Cache**
   ```bash
   # Lösung: Hard Refresh (Ctrl+Shift+R) oder Incognito
   ```

3. **Falsche Flow-ID**
   ```bash
   # Check welche Flow-ID der Agent nutzt
   curl -H "Authorization: Bearer $API_KEY" \
     https://api.retellai.com/get-agent/agent_[ID] | \
     jq '.response_engine.conversation_flow_id'
   ```

### Problem 2: Test-Anruf zeigt altes Branding

**Symptom**:
- Dashboard zeigt neue Version
- Test-Anruf zeigt alte Version

**Root Cause**: Phone Number zeigt auf alte Agent Version

**Lösung**:
```bash
# 1. Check aktuelle Phone Config
curl -H "Authorization: Bearer $API_KEY" \
  https://api.retellai.com/list-phone-numbers | \
  jq '.[] | select(.phone_number == "+49...")'

# 2. Check Agent Version
curl -H "Authorization: Bearer $API_KEY" \
  https://api.retellai.com/get-agent/agent_[ID] | \
  jq '.response_engine.version'

# 3. Update Phone auf neueste Version
curl -X PATCH \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"inbound_agent_version": [NEUE_VERSION]}' \
  "https://api.retellai.com/update-phone-number/+49..."
```

### Problem 3: "Cannot update response engine of agent version > 0"

**Symptom**:
```json
{
  "status": "error",
  "message": "Cannot update response engine of agent version > 0"
}
```

**Root Cause**: Du versuchst einen bestehenden Agent auf einen neuen Flow umzustellen

**Lösung**: Update den bestehenden Flow statt neuen zu erstellen
```php
// ❌ FALSCH
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/create-conversation-flow");

// ✅ RICHTIG
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/update-conversation-flow/{$existingFlowId}");
```

### Problem 4: Tool Descriptions noch generisch

**Symptom**:
- Global Prompt ist firmenspezifisch
- Tool Parameter Descriptions zeigen "z.B. Beratung"

**Root Cause**: Tool Descriptions wurden vergessen anzupassen

**Lösung**:
```php
foreach ($flow['tools'] as &$tool) {
    if (isset($tool['parameters']['properties']['dienstleistung']['description'])) {
        $old = $tool['parameters']['properties']['dienstleistung']['description'];

        if (strpos($old, 'Beratung') !== false) {
            $tool['parameters']['properties']['dienstleistung']['description'] = str_replace(
                'z.B. Beratung',
                'z.B. [Service 1], [Service 2], [Service 3]',
                $old
            );
        }
    }
}

// Re-deploy
```

### Problem 5: Mitarbeiter-Parameter funktioniert nicht

**Symptom**:
- User sagt "Termin bei Fabian"
- Backend erhält kein `mitarbeiter` Parameter

**Debugging**:

1. **Check Flow Tool Definition**:
   ```bash
   curl -H "Authorization: Bearer $API_KEY" \
     https://api.retellai.com/get-conversation-flow/conversation_flow_[ID] | \
     jq '.tools[] | select(.name == "book_appointment_v17") | .parameters.properties.mitarbeiter'
   ```

2. **Check Global Prompt**:
   ```bash
   # Muss Mitarbeiter-Wünsche erklären
   curl -H "Authorization: Bearer $API_KEY" \
     https://api.retellai.com/get-conversation-flow/conversation_flow_[ID] | \
     jq -r '.global_prompt' | grep -A 5 "Mitarbeiter-Wünsche"
   ```

3. **Check Backend Handler**:
   ```php
   // RetellFunctionCallHandler.php
   $mitarbeiter = $parameters['mitarbeiter'] ?? null;

   if ($mitarbeiter) {
       Log::info('Staff preference requested', ['staff' => $mitarbeiter]);
   }
   ```

---

## Lessons Learned

### 1. Agent Publishing ist KRITISCH

**Problem**: Flow deployed, aber Änderungen nicht live
**Lesson**: IMMER Agent publishen nach Flow-Update
**Lösung**: Publish-Step automatisch in Deployment-Script

```php
// IMMER nach Flow-Update
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/publish-agent/{$agentId}");
```

### 2. Phone Number Version Mismatch

**Problem**: Test-Anruf zeigt altes Branding trotz Dashboard-Update
**Lesson**: Phone Number zeigt auf spezifische Agent Version
**Lösung**: IMMER Phone Number updaten nach Agent Publish

```php
// Nach jedem Publish
$newVersion = getLatestAgentVersion($agentId);
updatePhoneNumber($phoneNumber, $newVersion);
```

### 3. Tool Descriptions sind wichtig

**Problem**: Agent versteht Services nicht richtig
**Lesson**: LLM nutzt Tool Descriptions für Service-Verständnis
**Lösung**: Firmenspezifische Beispiele in allen Tool Descriptions

```php
// ❌ Generisch
"description": "Service type (z.B. Beratung)"

// ✅ Firmenspezifisch
"description": "Service type (z.B. Herrenhaarschnitt, Damenhaarschnitt)"
```

### 4. Bestehende Flows updaten > Neue erstellen

**Problem**: "Cannot update response engine" Fehler
**Lesson**: Agents ab Version 1 können nicht auf neue Flows umgestellt werden
**Lösung**: IMMER bestehenden Flow updaten

```php
// ✅ EMPFOHLEN
PATCH /update-conversation-flow/{existingFlowId}

// ❌ VERMEIDEN (außer bei neuen Agents)
POST /create-conversation-flow
```

### 5. Global Prompt ist das Herzstück

**Problem**: Agent verhält sich nicht wie erwartet
**Lesson**: Global Prompt steuert ALLE Aspekte des Agent-Verhaltens
**Lösung**: Ausführlicher, strukturierter, firmenspezifischer Prompt

**Best Practices**:
- ✅ Klare Rolle-Definition
- ✅ Firmenspezifische Services mit Beispielen
- ✅ Workflow-Anweisungen (Datensammlung → Check → Buchen)
- ✅ Fehlerbehandlung (Empathie, nie Schuld geben)
- ✅ Team-Information
- ✅ Composite Services Erklärung (wenn relevant)

### 6. Verification ist essentiell

**Problem**: Deployment erfolgreich, aber falsche Konfiguration
**Lesson**: Immer verifizieren nach Deployment
**Lösung**: Automated Verification Script

```bash
#!/bin/bash
# verify_deployment.sh

AGENT_ID=$1
PHONE=$2

echo "=== Agent Version ==="
curl -s -H "Authorization: Bearer $API_KEY" \
  https://api.retellai.com/get-agent/$AGENT_ID | \
  jq '.response_engine.version'

echo "=== Phone Version ==="
curl -s -H "Authorization: Bearer $API_KEY" \
  https://api.retellai.com/list-phone-numbers | \
  jq ".[] | select(.phone_number == \"$PHONE\") | .inbound_agent_version"

echo "=== Global Prompt Preview ==="
curl -s -H "Authorization: Bearer $API_KEY" \
  https://api.retellai.com/get-agent/$AGENT_ID | \
  jq -r '.conversation_flow.global_prompt // empty' | head -10
```

### 7. Composite Services brauchen klare Erklärung

**Problem**: Agent erklärt Composite Services schlecht
**Lesson**: LLM braucht klare Anweisungen WIE Wartezeiten zu erklären
**Lösung**: Schritt-für-Schritt Anleitung im Global Prompt

```
**Wie du damit umgehst**:
1. ERKLÄRE die Gesamtdauer natürlich
2. ERWÄHNE beiläufig die Wartezeiten
3. Buche NORMAL - Backend macht den Rest
4. KEINE extra Fragen

Beispiel:
"Ansatzfärbung dauert etwa 2,5 Stunden. Dabei gibt es Wartezeiten
während die Farbe einwirkt. Passt Ihnen morgen um 14 Uhr?"
```

### 8. Dokumentation während Entwicklung

**Problem**: Vergessen wie etwas funktioniert nach 2 Wochen
**Lesson**: Dokumentiere WÄHREND der Entwicklung, nicht danach
**Lösung**: Deployment-Script mit Kommentaren + Separate Doku

```php
<?php
/**
 * Deploy [FIRMENNAME] Flow
 *
 * WICHTIG:
 * 1. Flow wird GEUPDATET (nicht neu erstellt)
 * 2. Agent muss PUBLISHED werden
 * 3. Phone Number muss GEUPDATET werden
 *
 * Nach Deployment:
 * - Check Agent Version
 * - Update Phone Number
 * - Test-Anruf
 */
```

---

## Checkliste: Neuen Agent erstellen

### Pre-Development
- [ ] Agent-Name festlegen
- [ ] Firmen-Branding sammeln (Name, Services, Team)
- [ ] Composite Services identifiziert (falls relevant)
- [ ] Staff Preference benötigt? (Ja/Nein)
- [ ] Backend unterstützt alle Features

### Flow Erstellung
- [ ] Template kopiert (V17)
- [ ] Global Prompt vollständig angepasst:
  - [ ] Agent Identity (Firmenname)
  - [ ] Services (firmenspezifisch)
  - [ ] Team Members (echte Namen)
  - [ ] Composite Services (falls relevant)
  - [ ] Mitarbeiter-Wünsche (falls Staff Preference)
- [ ] Tool Descriptions angepasst:
  - [ ] `dienstleistung` Parameter (firmenspezifisch)
  - [ ] `mitarbeiter` Parameter (falls relevant)
- [ ] JSON validiert (Syntax)
- [ ] Branding verifiziert (keine "AskPro AI" Reste)

### Deployment
- [ ] Deployment-Script erstellt
- [ ] Existierende Flow-ID identifiziert (falls Update)
- [ ] Flow deployed (HTTP 200)
- [ ] Agent published (HTTP 200)
- [ ] Neue Agent Version notiert

### Phone Configuration
- [ ] Phone Number identifiziert
- [ ] Phone auf neue Agent Version updated
- [ ] Update verifiziert (HTTP 200)

### Testing
- [ ] Dashboard-Check (Branding sichtbar)
- [ ] Test-Anruf (richtiges Branding hörbar)
- [ ] Service-Verständnis (Agent kennt Services)
- [ ] Composite Services (falls relevant, richtig erklärt)
- [ ] Staff Preference (falls relevant, funktioniert)

### Documentation
- [ ] Deployment dokumentiert
- [ ] Flow-ID gespeichert
- [ ] Agent-ID gespeichert
- [ ] Phone-Number notiert
- [ ] Besonderheiten dokumentiert

---

## Deployment Scripts Template

### Complete Deployment Script

```php
<?php

/**
 * Deploy [FIRMENNAME] Flow - Complete Process
 *
 * Steps:
 * 1. Update existing conversation flow
 * 2. Publish agent (creates new version)
 * 3. Update phone number to new version
 * 4. Verify deployment
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retellApiKey = config('services.retellai.api_key');

if (!$retellApiKey) {
    echo "❌ RETELLAI_API_KEY not found\n";
    exit(1);
}

// === CONFIGURATION ===
$agentId = 'agent_[ID]';
$existingFlowId = 'conversation_flow_[ID]';
$phoneNumber = '+49[NUMMER]';
$flowFile = __DIR__ . '/public/[firmenname]_flow_complete.json';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Deploy [FIRMENNAME] Flow                                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

// === STEP 1: Load Flow ===
echo "=== Step 1: Load Flow ===\n";
$flow = json_decode(file_get_contents($flowFile), true);

if (!$flow) {
    echo "❌ Failed to parse JSON\n";
    exit(1);
}

echo "✅ Flow loaded:\n";
echo "  - Nodes: " . count($flow['nodes']) . "\n";
echo "  - Tools: " . count($flow['tools']) . "\n";
echo PHP_EOL;

// === STEP 2: Update Conversation Flow ===
echo "=== Step 2: Update Conversation Flow ===\n";

$updatePayload = [
    'global_prompt' => $flow['global_prompt'],
    'nodes' => $flow['nodes'],
    'start_node_id' => $flow['start_node_id'],
    'start_speaker' => $flow['start_speaker'],
    'tools' => $flow['tools'],
    'model_choice' => $flow['model_choice'] ?? ['type' => 'cascading', 'model' => 'gpt-4o-mini'],
    'model_temperature' => $flow['model_temperature'] ?? 0.3
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-conversation-flow/{$existingFlowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($updatePayload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Flow updated: {$existingFlowId}\n";
} else {
    echo "❌ Failed to update flow\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

// === STEP 3: Publish Agent ===
echo "=== Step 3: Publish Agent ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/publish-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Agent published!\n";
} else {
    echo "❌ Failed to publish agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

// === STEP 4: Get New Agent Version ===
echo "=== Step 4: Get New Agent Version ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $agentData = json_decode($response, true);
    $newVersion = $agentData['response_engine']['version'];
    echo "✅ New Agent Version: {$newVersion}\n";
} else {
    echo "❌ Failed to get agent version\n";
    exit(1);
}
echo PHP_EOL;

// === STEP 5: Update Phone Number ===
echo "=== Step 5: Update Phone Number to Version {$newVersion} ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-phone-number/{$phoneNumber}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'inbound_agent_version' => $newVersion
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $phoneData = json_decode($response, true);
    echo "✅ Phone number updated!\n";
    echo "  - Phone: {$phoneData['phone_number']}\n";
    echo "  - Agent Version: {$phoneData['inbound_agent_version']}\n";
} else {
    echo "❌ Failed to update phone number\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

// === STEP 6: Verification ===
echo "=== Step 6: Verification ===\n";

echo "✅ Deployment Complete!\n";
echo PHP_EOL;

echo "Configuration:\n";
echo "  - Agent ID: {$agentId}\n";
echo "  - Flow ID: {$existingFlowId}\n";
echo "  - Agent Version: {$newVersion}\n";
echo "  - Phone Number: {$phoneNumber}\n";
echo PHP_EOL;

echo "Dashboard: https://dashboard.retellai.com/agents/{$agentId}\n";
echo PHP_EOL;

echo "🎯 Next: Make test call to {$phoneNumber}\n";
echo PHP_EOL;

echo "✅ DEPLOYMENT: SUCCESS\n";
```

---

## Best Practices Summary

### DO ✅

1. **IMMER bestehende Flows updaten** (nicht neu erstellen)
2. **IMMER Agent publishen** nach Flow-Update
3. **IMMER Phone Number updaten** auf neue Agent Version
4. **Firmenspezifische Tool Descriptions** verwenden
5. **Ausführlichen Global Prompt** schreiben
6. **Deployment verifizieren** (Dashboard + Test-Anruf)
7. **Dokumentieren während Entwicklung**
8. **JSON validieren** vor Deployment

### DON'T ❌

1. **NICHT neue Flows erstellen** für bestehende Agents (Version > 0)
2. **NICHT Agent Publish vergessen**
3. **NICHT Phone Number Update vergessen**
4. **NICHT generische Services** ("Beratung") in Tool Descriptions
5. **NICHT vergessen Nodes zu testen** (Test-Anruf!)
6. **NICHT Dashboard als alleinige Verification** nutzen
7. **NICHT ohne Backup deployen**
8. **NICHT komplexe Änderungen ohne Test**

---

## Referenzen

### Retell AI API Endpoints

```
GET    /get-agent/{agent_id}
PATCH  /update-agent/{agent_id}
POST   /publish-agent/{agent_id}

GET    /get-conversation-flow/{flow_id}
POST   /create-conversation-flow
PATCH  /update-conversation-flow/{flow_id}

GET    /list-phone-numbers
PATCH  /update-phone-number/{phone_number}
```

### Projekt-Dateien

```
Template:
  /var/www/api-gateway/public/askproai_state_of_the_art_flow_2025_V17.json

Beispiel-Deployment:
  /var/www/api-gateway/deploy_friseur1_update_existing.php
  /var/www/api-gateway/create_friseur1_flow_from_scratch.php
  /var/www/api-gateway/fix_friseur1_tool_descriptions.php

Dokumentation:
  /var/www/api-gateway/FRISEUR1_DEPLOYMENT_SUCCESS.md
  /var/www/api-gateway/claudedocs/03_API/Retell_AI/
```

### Related Documentation

- `claudedocs/03_API/Retell_AI/AGENT_IDS_REFERENZ.md` - Agent IDs
- `claudedocs/03_API/Retell_AI/DEPLOYMENT_PROZESS_RETELL_FLOW.md` - Deployment Details
- `claudedocs/03_API/Retell_AI/FLOW_V16_ARCHITECTURE.md` - Flow Architecture

---

**Version**: 1.0
**Autor**: Claude Code
**Letzte Aktualisierung**: 2025-10-23
**Status**: Production Ready ✅
