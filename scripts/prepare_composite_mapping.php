<?php

/**
 * Phase 3: Cal.com Event Type Mapping - Preparation
 *
 * This script documents the mapping requirements for composite services.
 * Since Cal.com V2 API doesn't provide Event Type listing, the Event Type IDs
 * for segments must be obtained manually from Cal.com UI.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "Phase 3: Cal.com Event Type Mapping - Vorbereitung\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get context
$company = DB::table('companies')->where('id', 1)->first();
$branch = DB::table('branches')->where('company_id', 1)->first();
$staff = DB::table('staff')->where('company_id', 1)->first();

if (!$company || !$branch || !$staff) {
    echo "❌ Company, Branch oder Staff nicht gefunden\n";
    exit(1);
}

echo "📋 Kontext:\n";
echo "  Company: {$company->name} (ID: {$company->id})\n";
echo "  Branch: {$branch->name} (UUID: {$branch->id})\n";
echo "  Staff: {$staff->name} (UUID: {$staff->id})\n\n";

echo str_repeat("─", 63) . "\n\n";

// Get composite services
$services = DB::select(
    'SELECT id, name, calcom_event_type_id, segments
     FROM services
     WHERE id IN (440, 442, 444)
     ORDER BY id'
);

echo "🎨 Composite Services - Mapping Anforderungen:\n\n";

foreach ($services as $svc) {
    $segments = json_decode($svc->segments, true);

    echo "  Service {$svc->id}: {$svc->name}\n";
    echo "    Haupt Event Type: {$svc->calcom_event_type_id}\n";
    echo "    Segmente:\n";

    $segmentNum = 1;
    foreach ($segments as $segment) {
        echo "      • Segment {$segment['key']}: {$segment['name']}\n";
        echo "        → Braucht Cal.com Event Type ID\n";
        echo "           Format in Cal.com: \"{$svc->name} ({$segmentNum} von 4)\"\n";
        $segmentNum++;
    }
    echo "\n";
}

echo str_repeat("─", 63) . "\n\n";

echo "💡 Schritte zum Mapping erstellen:\n\n";
echo "1️⃣  Cal.com UI öffnen: https://app.cal.com/event-types\n\n";

echo "2️⃣  Event Type IDs aus Cal.com UI ablesen:\n";
echo "   → Event Type öffnen\n";
echo "   → URL enthält die ID: /event-types/[ID]\n";
echo "   → IDs für alle Segmente notieren\n\n";

echo "3️⃣  Mapping-Script erstellen (Beispiel unten)\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "📝 BEISPIEL: Mapping Script Template\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "<?php\n\n";
echo "// Service 442: Ansatz + Längenausgleich\n";
echo "\$mappings_442 = [\n";
echo "    'A' => 3757XXX,  // (1 von 4) Auftragen\n";
echo "    'B' => 3757XXX,  // (2 von 4) Auswaschen\n";
echo "    'C' => 3757XXX,  // (3 von 4) Formschnitt\n";
echo "    'D' => 3757XXX,  // (4 von 4) Föhnen\n";
echo "];\n\n";

echo "// Mappings erstellen\n";
echo "foreach (\$mappings_442 as \$segmentKey => \$eventTypeId) {\n";
echo "    DB::table('calcom_event_map')->insert([\n";
echo "        'company_id' => 1,\n";
echo "        'branch_id' => '{$branch->id}',\n";
echo "        'service_id' => 442,\n";
echo "        'segment_key' => \$segmentKey,\n";
echo "        'staff_id' => '{$staff->id}',\n";
echo "        'event_type_id' => \$eventTypeId,\n";
echo "        'event_name_pattern' => \"FRISEUR-{$branch->name}-442-{\$segmentKey}\",\n";
echo "        'sync_status' => 'pending',\n";
echo "        'created_at' => now(),\n";
echo "        'updated_at' => now(),\n";
echo "    ]);\n";
echo "}\n\n";

echo "═══════════════════════════════════════════════════════════════\n\n";

echo "⚠️  HINWEIS: Die Event Type IDs für Segmente können nur manuell\n";
echo "   aus der Cal.com Web-UI abgelesen werden, da die V2 API\n";
echo "   keine Event Type Listing-Endpunkte bereitstellt.\n\n";

echo "✅ Phase 3 Vorbereitung abgeschlossen!\n";
echo "   → Dokumentation erstellt\n";
echo "   → Template-Script bereitgestellt\n";
echo "   → Bereit für manuelle Event Type ID Erfassung\n\n";
