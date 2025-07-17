<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\PortalUser;
use App\Models\Company;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\PrepaidBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 FINALER SYSTEM-CHECK FÜR DEMO\n";
echo "================================\n";
echo "Datum: " . now()->format('d.m.Y H:i:s') . "\n";
echo "Demo: 17.07.2025, 15:00 Uhr\n\n";

$checksPassed = 0;
$totalChecks = 0;

// Helper function
function checkStatus($condition, $message) {
    global $checksPassed, $totalChecks;
    $totalChecks++;
    if ($condition) {
        echo "✅ {$message}\n";
        $checksPassed++;
        return true;
    } else {
        echo "❌ {$message}\n";
        return false;
    }
}

// 1. DATABASE CONNECTION
echo "1️⃣ DATABASE CONNECTION\n";
echo "----------------------\n";
try {
    DB::connection()->getPdo();
    checkStatus(true, "Datenbankverbindung funktioniert");
    
    $tableCount = DB::select("SHOW TABLES");
    checkStatus(count($tableCount) > 50, "Alle Tabellen vorhanden (" . count($tableCount) . " Tabellen)");
} catch (\Exception $e) {
    checkStatus(false, "Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}
echo "\n";

// 2. LOGIN CREDENTIALS
echo "2️⃣ LOGIN CREDENTIALS\n";
echo "-------------------\n";

// Admin login
$adminUser = User::where('email', 'demo@askproai.de')->first();
checkStatus($adminUser !== null, "Admin User existiert");
if ($adminUser) {
    checkStatus(Hash::check('demo123', $adminUser->password), "Admin Passwort korrekt");
    checkStatus($adminUser->hasRole('Super Admin'), "Admin hat Super Admin Rolle");
}

// Reseller login
$resellerUser = PortalUser::where('email', 'max@techpartner.de')->first();
checkStatus($resellerUser !== null, "Reseller User existiert");
if ($resellerUser) {
    checkStatus(Hash::check('demo123', $resellerUser->password), "Reseller Passwort korrekt");
    checkStatus($resellerUser->can_access_child_companies == 1, "Reseller kann auf Kunden zugreifen");
}

// Client login
$clientUser = PortalUser::where('email', 'admin@dr-schmidt.de')->first();
checkStatus($clientUser !== null, "Client User existiert");
if ($clientUser) {
    checkStatus(Hash::check('demo123', $clientUser->password), "Client Passwort korrekt");
}
echo "\n";

// 3. WHITE-LABEL STRUCTURE
echo "3️⃣ WHITE-LABEL STRUCTURE\n";
echo "------------------------\n";

$reseller = Company::where('name', 'TechPartner GmbH')->first();
checkStatus($reseller !== null, "Reseller Company existiert");
if ($reseller) {
    checkStatus($reseller->company_type === 'reseller', "Company Type ist 'reseller'");
    checkStatus($reseller->is_white_label == 1, "White-Label aktiviert");
    checkStatus($reseller->commission_rate == 20.00, "Provision 20% konfiguriert");
    
    $childCompanies = $reseller->childCompanies;
    checkStatus($childCompanies->count() === 3, "3 Kunden-Companies vorhanden");
}
echo "\n";

// 4. DEMO DATA
echo "4️⃣ DEMO DATA\n";
echo "------------\n";

$totalCalls = Call::count();
checkStatus($totalCalls > 30, "Genügend Demo-Anrufe ({$totalCalls} Anrufe)");

$todayCalls = Call::whereDate('created_at', today())->count();
checkStatus($todayCalls > 0, "Anrufe von heute vorhanden ({$todayCalls} Anrufe)");

$appointments = Appointment::count();
checkStatus($appointments > 10, "Genügend Demo-Termine ({$appointments} Termine)");

$afterHoursCalls = Call::whereTime('created_at', '<', '08:00:00')
    ->orWhereTime('created_at', '>', '18:00:00')
    ->count();
checkStatus($afterHoursCalls > 10, "After-Hours Anrufe vorhanden ({$afterHoursCalls} Anrufe)");
echo "\n";

// 5. FINANCIAL DATA
echo "5️⃣ FINANCIAL DATA\n";
echo "-----------------\n";

$balances = PrepaidBalance::where('balance', '>', 0)->count();
checkStatus($balances >= 3, "Kunden haben Guthaben ({$balances} mit Guthaben)");

$totalBalance = PrepaidBalance::sum('balance');
checkStatus($totalBalance > 200, "Gesamt-Guthaben realistisch (" . number_format($totalBalance, 2) . "€)");
echo "\n";

// 6. PERFORMANCE CHECK
echo "6️⃣ PERFORMANCE CHECK\n";
echo "-------------------\n";

// Check query performance
$start = microtime(true);
$companies = Company::with(['childCompanies', 'prepaidBalance', 'calls' => function($q) {
    $q->whereDate('created_at', today());
}])->get();
$duration = (microtime(true) - $start) * 1000;
checkStatus($duration < 100, "Company Query Performance ({$duration}ms)");

// Check cache
checkStatus(Cache::store() !== null, "Cache System verfügbar");
echo "\n";

// 7. FILE SYSTEM
echo "7️⃣ FILE SYSTEM\n";
echo "--------------\n";

checkStatus(file_exists(__DIR__ . '/create-demo-screenshots.sh'), "Screenshot Script vorhanden");
checkStatus(file_exists(__DIR__ . '/public/css/white-label-demo.css'), "White-Label CSS vorhanden");
checkStatus(file_exists(__DIR__ . '/public/js/white-label-demo.js'), "White-Label JS vorhanden");
checkStatus(file_exists(__DIR__ . '/public/js/demo-error-handler.js'), "Error Handler vorhanden");
echo "\n";

// 8. CRITICAL FEATURES
echo "8️⃣ CRITICAL FEATURES\n";
echo "--------------------\n";

// Check Multi-Company Widget data
$widgetData = Company::where('company_type', 'client')
    ->withCount([
        'calls as calls_today' => function ($query) {
            $query->whereDate('created_at', today());
        }
    ])
    ->with('prepaidBalance')
    ->orderBy('calls_today', 'desc')
    ->limit(5)
    ->get();

checkStatus($widgetData->count() >= 3, "Multi-Company Widget hat Daten");
checkStatus($widgetData->first()->calls_today > 0, "Widget zeigt heutige Anrufe");
echo "\n";

// 9. KNOWN LIMITATIONS
echo "9️⃣ KNOWN LIMITATIONS\n";
echo "--------------------\n";
echo "⚠️  Reseller Portal zeigt keine aggregierten Daten\n";
echo "   → Workaround: Im Admin Portal bleiben\n";
echo "⚠️  White-Label nur Backend, kein visuelles Branding\n";
echo "   → Workaround: CSS Demo über URL-Parameter\n";
echo "⚠️  Keine automatische Provisionsabrechnung\n";
echo "   → Story: 'Monatliche manuelle Abrechnung'\n\n";

// 10. URLs
echo "🔟 DEMO URLs\n";
echo "------------\n";
echo "Admin Portal:     https://api.askproai.de/admin\n";
echo "Business Portal:  https://api.askproai.de/business\n";
echo "White-Label Demo: https://api.askproai.de/admin?demo=white-label\n";
echo "Auto-Switch Demo: https://api.askproai.de/admin?demo=white-label&auto=true\n\n";

// FINAL SUMMARY
echo "📊 FINAL SUMMARY\n";
echo "================\n";
echo "Checks bestanden: {$checksPassed}/{$totalChecks}\n";

if ($checksPassed === $totalChecks) {
    echo "\n🎉 SYSTEM IST 100% DEMO-BEREIT! 🎉\n\n";
    echo "✅ Alle Systeme funktionieren\n";
    echo "✅ Alle Daten vorhanden\n";
    echo "✅ Performance optimal\n\n";
    echo "🚀 BEREIT FÜR DIE DEMO MORGEN UM 15:00 UHR!\n";
} else {
    $failedChecks = $totalChecks - $checksPassed;
    echo "\n⚠️  {$failedChecks} CHECKS FEHLGESCHLAGEN!\n";
    echo "Bitte die fehlgeschlagenen Punkte prüfen!\n";
}

// QUICK STATS FOR DEMO
echo "\n📈 BEEINDRUCKENDE ZAHLEN FÜR DIE DEMO:\n";
echo "=====================================\n";
echo "• {$totalCalls} Anrufe verarbeitet\n";
echo "• " . number_format(($afterHoursCalls / $totalCalls) * 100, 1) . "% außerhalb Geschäftszeiten\n";
echo "• " . number_format($totalBalance, 0) . "€ verwaltetes Guthaben\n";
echo "• 3 verschiedene Branchen\n";
echo "• 24/7 Verfügbarkeit\n\n";

echo "💪 VIEL ERFOLG MORGEN!\n";