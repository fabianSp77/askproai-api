<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Scopes\TenantScope;

echo "=== RETELL AGENT KONFIGURATION ===\n\n";

// Prüfe Companies und ihre Retell Konfiguration
$companies = Company::withoutGlobalScope(TenantScope::class)
    ->whereIn('id', [1, 15])
    ->get();
    
foreach ($companies as $company) {
    echo "🏢 {$company->name} (ID: {$company->id})\n";
    echo "   Retell API Key: " . ($company->retell_api_key ? substr($company->retell_api_key, 0, 20) . '...' : 'NICHT GESETZT') . "\n";
    echo "   Needs Appointment Booking: " . ($company->needsAppointmentBooking() ? 'JA' : 'NEIN') . "\n";
    
    $branches = Branch::withoutGlobalScope(TenantScope::class)
        ->where('company_id', $company->id)
        ->get();
        
    foreach ($branches as $branch) {
        echo "\n   📍 Branch: {$branch->name}\n";
        echo "      Retell Agent ID: " . ($branch->retell_agent_id ?: 'NICHT GESETZT') . "\n";
        
        $phones = PhoneNumber::withoutGlobalScope(TenantScope::class)
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->get();
            
        foreach ($phones as $phone) {
            echo "      📞 {$phone->number}\n";
            echo "         Agent: " . ($phone->retell_agent_id ?: $branch->retell_agent_id ?: 'KEIN AGENT') . "\n";
        }
    }
    echo "\n" . str_repeat('-', 50) . "\n";
}

// AskProAI braucht einen echten Agent
echo "\n=== ASKPROAI AGENT KONFIGURATION ===\n";
echo "HINWEIS: AskProAI benötigt einen eigenen Retell Agent!\n";
echo "Der Agent muss im Retell.ai Dashboard erstellt werden.\n";
echo "\nBis dahin kann der Default Agent verwendet werden:\n";

// Hole den Default Agent von Krückeberg als Backup
$krueckebergBranch = Branch::withoutGlobalScope(TenantScope::class)
    ->where('company_id', 1)
    ->first();
    
$defaultAgentId = $krueckebergBranch ? $krueckebergBranch->retell_agent_id : null;

if ($defaultAgentId) {
    echo "Default Agent verfügbar: {$defaultAgentId}\n\n";
    
    // Update AskProAI Branch mit diesem Agent
    $askproaiBranch = Branch::withoutGlobalScope(TenantScope::class)
        ->where('company_id', 15)
        ->first();
        
    if ($askproaiBranch && !$askproaiBranch->retell_agent_id) {
        $askproaiBranch->retell_agent_id = $defaultAgentId;
        $askproaiBranch->save();
        echo "✅ Agent ID für AskProAI Branch gesetzt: {$defaultAgentId}\n";
    }
    
    // Update Phone Number
    $askproaiPhone = PhoneNumber::withoutGlobalScope(TenantScope::class)
        ->where('number', '+493083793369')
        ->first();
        
    if ($askproaiPhone) {
        $askproaiPhone->retell_agent_id = $defaultAgentId;
        $askproaiPhone->save();
        echo "✅ Agent ID für AskProAI Telefonnummer gesetzt: {$defaultAgentId}\n";
    }
}

echo "\n=== FINALE KONFIGURATION ===\n";
echo "📞 Krückeberg Servicegruppe:\n";
echo "   Nummer: +493033081738\n";
echo "   Agent: agent_b36ecd3927a81834b6d56ab07b\n";
echo "   Zweck: Reine Datensammlung (keine Termine)\n";

echo "\n📞 AskProAI:\n";
echo "   Nummer: +493083793369\n";
echo "   Agent: " . ($defaultAgentId ?: 'MUSS NOCH KONFIGURIERT WERDEN') . "\n";
echo "   Zweck: Kann Termine buchen (wenn aktiviert)\n";