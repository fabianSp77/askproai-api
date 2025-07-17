<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\PortalUser;
use App\Models\Company;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Prüfe Business Portal aus Reseller-Sicht\n";
echo "==========================================\n\n";

// Get reseller user
$resellerUser = PortalUser::where('email', 'max@techpartner.de')->first();
if (!$resellerUser) {
    echo "❌ Reseller user not found!\n";
    exit(1);
}

$resellerCompany = $resellerUser->company;
echo "✅ Reseller: {$resellerCompany->name}\n";
echo "   - Type: {$resellerCompany->company_type}\n";
echo "   - Can access children: " . ($resellerUser->can_access_child_companies ? 'Yes' : 'No') . "\n\n";

// Check what data the reseller would see
echo "📊 Daten-Sichtbarkeit für Reseller:\n";
echo "=====================================\n\n";

// 1. Calls - Would reseller see client calls?
echo "1️⃣ Anrufe (Calls):\n";

// Reseller's own calls
$resellerCalls = Call::where('company_id', $resellerCompany->id)->count();
echo "   - Eigene Anrufe: {$resellerCalls}\n";

// Client calls
$clientCompanyIds = Company::where('parent_company_id', $resellerCompany->id)->pluck('id');
$clientCalls = Call::whereIn('company_id', $clientCompanyIds)->count();
echo "   - Kunden-Anrufe: {$clientCalls}\n";

// What would current query show?
$visibleCalls = Call::where('company_id', $resellerCompany->id)->count();
echo "   ⚠️  Aktuell sichtbar (ohne Fix): {$visibleCalls}\n\n";

// 2. Customers
echo "2️⃣ Kunden (Customers):\n";
$resellerCustomers = Customer::where('company_id', $resellerCompany->id)->count();
$clientCustomers = Customer::whereIn('company_id', $clientCompanyIds)->count();
echo "   - Eigene Kunden: {$resellerCustomers}\n";
echo "   - Kunden der Clients: {$clientCustomers}\n\n";

// 3. Revenue Overview
echo "3️⃣ Umsatz-Übersicht:\n";
$totalClientBalance = DB::table('prepaid_balances')
    ->whereIn('company_id', $clientCompanyIds)
    ->sum('balance');
echo "   - Gesamt-Guthaben der Kunden: " . number_format($totalClientBalance, 2) . " €\n";
echo "   - Potenzielle Provision (20%): " . number_format($totalClientBalance * 0.2, 2) . " €\n\n";

// 4. Check TenantScope impact
echo "4️⃣ TenantScope Problem:\n";
echo "   ⚠️  TenantScope filtert automatisch nach company_id\n";
echo "   ⚠️  Reseller sieht NUR eigene Daten, NICHT die der Kunden!\n\n";

// 5. Required fixes
echo "5️⃣ Notwendige Anpassungen:\n";
echo "   1. TenantScope modifizieren für Reseller\n";
echo "   2. Oder: Separate Reseller-Dashboard erstellen\n";
echo "   3. Oder: Aggregierte Views mit Raw Queries\n\n";

// 6. Quick workaround check
echo "6️⃣ Möglicher Quick-Fix für Demo:\n";
$canUseWithoutScope = method_exists(\App\Models\Call::class, 'withoutGlobalScope');
if ($canUseWithoutScope) {
    echo "   ✅ withoutGlobalScope verfügbar\n";
    
    // Test query without scope
    $allCompanyIds = collect([$resellerCompany->id])->merge($clientCompanyIds);
    $totalCallsWithoutScope = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->whereIn('company_id', $allCompanyIds)
        ->count();
    
    echo "   ✅ Calls mit withoutGlobalScope: {$totalCallsWithoutScope}\n";
} else {
    echo "   ❌ withoutGlobalScope nicht verfügbar\n";
}

echo "\n";
echo "⚠️  KRITISCH: Business Portal zeigt aktuell KEINE aggregierten Daten!\n";
echo "⚠️  Reseller sieht nur eigene Company-Daten!\n\n";

// 7. Demo workaround suggestion
echo "💡 Workaround für Demo morgen:\n";
echo "================================\n";
echo "1. Als Admin einloggen und Multi-Company Overview zeigen\n";
echo "2. Erklären: 'Dies ist die zentrale Verwaltungsansicht'\n";
echo "3. Dann ins Business Portal einzelner Kunden wechseln\n";
echo "4. Sagen: 'Die aggregierte Reseller-Ansicht kommt in Phase 2'\n";
echo "5. Fokus auf Multi-Company Management, nicht auf Reseller-Portal\n\n";

echo "🎯 Alternative: Quick-Dashboard für Reseller bauen (2 Stunden)\n";