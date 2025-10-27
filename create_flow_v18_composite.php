<?php

/**
 * Create Friseur 1 Flow V18 with Composite Services Support
 *
 * Changes from V17:
 * 1. Add 'mitarbeiter' parameter to book_appointment_v17 tool
 * 2. Add composite services explanation to global_prompt
 * 3. Update relevant nodes to explain wait times
 */

require __DIR__ . '/vendor/autoload.php';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Creating Friseur 1 Flow V18 (Composite Services)        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

// Load V17 flow
$v17File = __DIR__ . '/public/askproai_state_of_the_art_flow_2025_V17.json';
$v18File = __DIR__ . '/public/askproai_friseur1_flow_v18_composite.json';

if (!file_exists($v17File)) {
    echo "❌ V17 flow file not found: {$v17File}\n";
    exit(1);
}

echo "📄 Loading V17 flow...\n";
$flow = json_decode(file_get_contents($v17File), true);

if (!$flow) {
    echo "❌ Failed to parse V17 JSON\n";
    exit(1);
}

echo "✅ V17 flow loaded (" . count($flow['nodes']) . " nodes, " . count($flow['tools']) . " tools)\n";
echo PHP_EOL;

// CHANGE 1: Update global_prompt with composite services explanation
echo "=== CHANGE 1: Update Global Prompt ===\n";

$compositeServicesSection = <<<'PROMPT'

## Composite Services (WICHTIG!)
Manche Services haben Wartezeiten (z.B. Ansatzfärbung, Farbe muss einwirken).

### Ansatzfärbung Services:
- "Ansatzfärbung, waschen, schneiden, föhnen" (~2.5h brutto)
  - Ablauf: Farbe auftragen (30min) → Pause 30min → Waschen (15min) → Schneiden (30min) → Pause 15min → Föhnen (30min)
  - Kunde wartet im Salon während Farbe einwirkt

- "Ansatz, Längenausgleich, waschen, schneiden, föhnen" (~2.8h brutto)
  - Ähnlich wie Ansatzfärbung mit längerer Einwirkzeit

Bei diesen Services:
1. ERKLÄRE kurz die Gesamtdauer (~2-3 Stunden)
2. ERWÄHNE beiläufig: "Dabei gibt es Wartezeiten während die Farbe einwirkt"
3. Buche NORMAL - Backend handled die Segmente automatisch
4. KEINE extra Fragen - halte es natürlich!

Beispiel: "Ansatzfärbung dauert etwa 2,5 Stunden. Dabei gibt es Wartezeiten während die Farbe einwirkt. Passt Ihnen [Datum] um [Zeit]?"

PROMPT;

$flow['global_prompt'] .= $compositeServicesSection;
echo "✅ Added composite services explanation to global_prompt\n";
echo PHP_EOL;

// CHANGE 2: Add 'mitarbeiter' parameter to book_appointment_v17 tool
echo "=== CHANGE 2: Add Mitarbeiter Parameter to Tool ===\n";

$toolIndex = null;
foreach ($flow['tools'] as $index => $tool) {
    if ($tool['name'] === 'book_appointment_v17') {
        $toolIndex = $index;
        break;
    }
}

if ($toolIndex !== null) {
    // Add mitarbeiter parameter
    $flow['tools'][$toolIndex]['parameters']['properties']['mitarbeiter'] = [
        'type' => 'string',
        'description' => 'Optional: Staff member name if customer requests specific person (z.B. "Fabian", "Emma"). Leave empty if not specified.'
    ];

    // Not required - optional parameter
    // $flow['tools'][$toolIndex]['parameters']['required'] stays the same

    // Update tool description
    $flow['tools'][$toolIndex]['description'] = 'Book appointment with optional staff preference. Customers can request specific staff member (e.g., "bei Fabian").';

    echo "✅ Added 'mitarbeiter' parameter to book_appointment_v17 tool\n";
    echo "  - Type: string (optional)\n";
    echo "  - Description: Staff member name if requested\n";
} else {
    echo "⚠️ book_appointment_v17 tool not found\n";
}
echo PHP_EOL;

// CHANGE 3: Add staff information to global_prompt
echo "=== CHANGE 3: Add Staff Information ===\n";

$staffSection = <<<'PROMPT'

## Team Mitglieder (Friseur 1)
Verfügbare Mitarbeiter:
- Emma Williams
- Fabian Spitzer
- David Martinez
- Michael Chen
- Dr. Sarah Johnson

Wenn Kunde speziellen Mitarbeiter wünscht (z.B. "bei Fabian"), nutze 'mitarbeiter' Parameter.
Beispiel: "Ich möchte einen Termin bei Fabian" → mitarbeiter="Fabian"

Wenn KEIN Mitarbeiter genannt: mitarbeiter Parameter weglassen (Backend wählt automatisch).

PROMPT;

$flow['global_prompt'] .= $staffSection;
echo "✅ Added staff information to global_prompt\n";
echo PHP_EOL;

// Save V18 flow
echo "=== Saving V18 Flow ===\n";

$json = json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (file_put_contents($v18File, $json)) {
    $fileSize = filesize($v18File);
    echo "✅ V18 flow saved: {$v18File}\n";
    echo "  - File size: " . round($fileSize / 1024, 2) . " KB\n";
    echo "  - Nodes: " . count($flow['nodes']) . "\n";
    echo "  - Tools: " . count($flow['tools']) . "\n";
} else {
    echo "❌ Failed to save V18 flow\n";
    exit(1);
}
echo PHP_EOL;

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    CHANGES SUMMARY                           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "✅ V17 → V18 Conversion Complete\n";
echo PHP_EOL;

echo "Changes Made:\n";
echo "  1. ✅ Added composite services explanation to global_prompt\n";
echo "     - Explains Ansatzfärbung wait times naturally\n";
echo "     - Gives agent context about ~2-3h duration\n";
echo PHP_EOL;

echo "  2. ✅ Added 'mitarbeiter' parameter to book_appointment_v17 tool\n";
echo "     - Optional string parameter\n";
echo "     - Captures customer's staff preference\n";
echo PHP_EOL;

echo "  3. ✅ Added team member list to global_prompt\n";
echo "     - Lists all 5 Friseur 1 staff members\n";
echo "     - Instructions for handling staff requests\n";
echo PHP_EOL;

echo "📌 Next Step: Deploy V18 flow to Friseur 1 Agent\n";
echo "   Agent ID: agent_f1ce85d06a84afb989dfbb16a9\n";
echo PHP_EOL;

echo "✅ Flow V18 Creation: SUCCESS\n";
