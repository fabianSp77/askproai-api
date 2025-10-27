#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Branch;

$correctAgentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "FIX: Friseur 1 Agent Mapping in Datenbank\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Korrekter Agent: $correctAgentId\n";
echo "(Conversation Flow Agent Friseur 1)\n\n";

// Find Friseur 1 company
$company = Company::where('name', 'Friseur 1')->first();

if (!$company) {
    echo "❌ ERROR: Company 'Friseur 1' not found!\n\n";
    exit(1);
}

echo "🏢 Company gefunden: {$company->name} (ID: {$company->id})\n";
echo "   Aktueller Agent: " . ($company->retell_agent_id ?? 'NOT SET') . "\n";

if ($company->retell_agent_id === $correctAgentId) {
    echo "   ✅ Company Agent ID ist bereits korrekt!\n\n";
} else {
    echo "   ⚠️  FALSCH! Muss geändert werden.\n";
    echo "   Ändere zu: $correctAgentId\n";

    $company->retell_agent_id = $correctAgentId;
    $company->save();

    echo "   ✅ Company Agent ID updated!\n\n";
}

// Find branches
$branches = $company->branches;

echo "📍 Branches ({$branches->count()}):\n\n";

foreach ($branches as $branch) {
    echo "  → Branch: {$branch->name} (ID: {$branch->id})\n";
    echo "    Aktueller Agent: " . ($branch->retell_agent_id ?? 'NOT SET') . "\n";

    if ($branch->retell_agent_id === $correctAgentId) {
        echo "    ✅ Branch Agent ID ist bereits korrekt!\n\n";
    } else {
        echo "    ⚠️  FALSCH! Muss geändert werden.\n";
        echo "    Ändere zu: $correctAgentId\n";

        $branch->retell_agent_id = $correctAgentId;
        $branch->save();

        echo "    ✅ Branch Agent ID updated!\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo "✅ DATENBANK FIX COMPLETE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Verifizierung:\n\n";

$company->refresh();
echo "Company 'Friseur 1' Agent: " . $company->retell_agent_id . "\n";

foreach ($company->branches as $branch) {
    echo "Branch '{$branch->name}' Agent: " . $branch->retell_agent_id . "\n";
}

echo "\n✅ All Agent IDs set to: $correctAgentId\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "NÄCHSTER SCHRITT: Retell Dashboard\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Du musst JETZT noch im Retell Dashboard mappen:\n\n";
echo "1. URL: https://dashboard.retellai.com/phone-numbers\n";
echo "2. Telefonnummer: +493033081674 (Musterfriseur)\n";
echo "3. Agent setzen: $correctAgentId\n";
echo "4. Speichern\n\n";

echo "Dann kannst du Test Call machen!\n\n";
