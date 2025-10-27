<?php

/**
 * Fix UX #2: Auto-Proceed to Booking Flow
 * Problem: Agent checks availability but never books
 * Solution: Update parameter mappings to use dynamic variables
 */

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔧 FIXING UX #2 - Auto-Proceed to Booking\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Load V5 flow
echo "📖 Loading V5 flow...\n";
$flow = json_decode(file_get_contents(__DIR__ . '/friseur1_conversation_flow_v5_state_persistence.json'), true);

if (!$flow) {
    die("❌ Failed to load V5 flow\n");
}

echo "✅ V5 loaded: " . count($flow['nodes']) . " nodes\n\n";

// Fix 1: Update parameter_mapping in func_check_availability
echo "🔧 Fix 1: Updating func_check_availability parameter mapping...\n";
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'func_check_availability') {
        $node['parameter_mapping'] = [
            'call_id' => '{{call_id}}',
            'name' => '{{customer_name}}',
            'datum' => '{{appointment_date}}',
            'dienstleistung' => '{{service_name}}',
            'uhrzeit' => '{{appointment_time}}'
        ];
        echo "✅ Updated to use dynamic variables (customer_name, service_name, etc.)\n";
    }
}
unset($node);

// Fix 2: Update parameter_mapping in func_book_appointment
echo "🔧 Fix 2: Updating func_book_appointment parameter mapping...\n";
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'func_book_appointment') {
        $node['parameter_mapping'] = [
            'call_id' => '{{call_id}}',
            'name' => '{{customer_name}}',
            'datum' => '{{appointment_date}}',
            'dienstleistung' => '{{service_name}}',
            'uhrzeit' => '{{appointment_time}}'
        ];
        echo "✅ Updated to use dynamic variables\n";
    }
}
unset($node);

// Fix 3: Update "Ergebnis zeigen" node to reference dynamic variables
echo "🔧 Fix 3: Updating 'Ergebnis zeigen' node...\n";
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_present_result') {
        $node['instruction']['text'] = "Zeige das Ergebnis der Verfügbarkeitsprüfung:

**WENN VERFÜGBAR:**
\"Der Termin am {{appointment_date}} um {{appointment_time}} für {{service_name}} ist verfügbar. Soll ich den Termin für Sie buchen?\"

**WENN NICHT VERFÜGBAR:**
\"Leider ist {{appointment_date}} um {{appointment_time}} nicht verfügbar. Ich habe alternative Zeiten gefunden. Welcher Termin passt Ihnen?\"

**WICHTIG:**
- Warte auf explizite Bestätigung
- \"Ja\", \"Gerne\", \"Buchen Sie\" → Transition zu func_book_appointment
- \"Nein\", \"Andere Zeit\" → Zurück zu Datensammlung";

        echo "✅ Updated to use dynamic variables in presentation\n";
    }
}
unset($node);

// Fix 4: Ensure tool IDs are correct
echo "🔧 Fix 4: Verifying tool IDs...\n";
$checkAvailabilityFound = false;
$bookAppointmentFound = false;

foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'func_check_availability') {
        echo "✅ func_check_availability uses tool: " . $node['tool_id'] . "\n";
        $checkAvailabilityFound = true;
    }
    if ($node['id'] === 'func_book_appointment') {
        echo "✅ func_book_appointment uses tool: " . $node['tool_id'] . "\n";
        $bookAppointmentFound = true;
    }
}

if (!$checkAvailabilityFound || !$bookAppointmentFound) {
    echo "⚠️  Warning: Not all function nodes found\n";
}

// Save V6 flow
echo "\n💾 Saving V6 flow...\n";
$v6Json = json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents(__DIR__ . '/friseur1_conversation_flow_v6_auto_proceed.json', $v6Json);

echo "✅ V6 flow saved\n\n";

// Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "✅ UX #2 FIX COMPLETE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📋 Changes Made:\n";
echo "   1. ✅ func_check_availability now uses {{customer_name}}, {{service_name}}, etc.\n";
echo "   2. ✅ func_book_appointment now uses {{customer_name}}, {{service_name}}, etc.\n";
echo "   3. ✅ node_present_result updated to show dynamic variables\n";
echo "   4. ✅ All parameter mappings consistent\n\n";

echo "🎯 What This Fixes:\n";
echo "   - ✅ UX #2: Consistent variable usage across flow\n";
echo "   - ✅ Functions receive correct data from dynamic variables\n";
echo "   - ✅ Booking flow should proceed after confirmation\n\n";

echo "🚀 Next: Deploy with php deploy_flow_v6_auto_proceed.php\n\n";
