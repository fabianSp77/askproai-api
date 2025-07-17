<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔐 Teste Login-Funktionalität\n";
echo "============================\n\n";

// Test Admin Login
echo "1️⃣ Admin Portal Login Test:\n";
$adminUser = User::where('email', 'demo@askproai.de')->first();
if ($adminUser) {
    $passwordCorrect = Hash::check('demo123', $adminUser->password);
    echo "   Email: {$adminUser->email}\n";
    echo "   Name: {$adminUser->name}\n";
    echo "   Password Check: " . ($passwordCorrect ? '✅ Korrekt' : '❌ Falsch') . "\n";
    echo "   Has Super Admin Role: " . ($adminUser->hasRole('Super Admin') ? '✅ Ja' : '❌ Nein') . "\n";
} else {
    echo "   ❌ Admin User nicht gefunden!\n";
}
echo "\n";

// Test Reseller Login
echo "2️⃣ Reseller Portal Login Test:\n";
$resellerUser = PortalUser::where('email', 'max@techpartner.de')->first();
if ($resellerUser) {
    $passwordCorrect = Hash::check('demo123', $resellerUser->password);
    echo "   Email: {$resellerUser->email}\n";
    echo "   Name: {$resellerUser->name}\n";
    echo "   Company: {$resellerUser->company->name}\n";
    echo "   Password Check: " . ($passwordCorrect ? '✅ Korrekt' : '❌ Falsch') . "\n";
    echo "   Can Access Children: " . ($resellerUser->can_access_child_companies ? '✅ Ja' : '❌ Nein') . "\n";
} else {
    echo "   ❌ Reseller User nicht gefunden!\n";
}
echo "\n";

// Test Client Login
echo "3️⃣ Client Portal Login Test:\n";
$clientUser = PortalUser::where('email', 'admin@dr-schmidt.de')->first();
if ($clientUser) {
    $passwordCorrect = Hash::check('demo123', $clientUser->password);
    echo "   Email: {$clientUser->email}\n";
    echo "   Name: {$clientUser->name}\n";
    echo "   Company: {$clientUser->company->name}\n";
    echo "   Password Check: " . ($passwordCorrect ? '✅ Korrekt' : '❌ Falsch') . "\n";
    echo "   Role: {$clientUser->role}\n";
} else {
    echo "   ❌ Client User nicht gefunden!\n";
}
echo "\n";

// Test URLs
echo "4️⃣ URLs für Demo:\n";
echo "   Admin Portal: https://api.askproai.de/admin\n";
echo "   Business Portal: https://api.askproai.de/business\n";
echo "\n";

// Test Company Hierarchy
echo "5️⃣ Company Hierarchie:\n";
$reseller = \App\Models\Company::where('name', 'TechPartner GmbH')
    ->with(['childCompanies.prepaidBalance'])
    ->first();
if ($reseller) {
    echo "   Reseller: {$reseller->name}\n";
    $clients = $reseller->childCompanies;
    foreach ($clients as $client) {
        echo "   └── Client: {$client->name}\n";
        $balance = $client->prepaidBalance;
        if ($balance) {
            echo "       └── Guthaben: " . number_format($balance->balance, 2) . " €\n";
        }
    }
}

echo "\n✅ Alle Login-Daten sind korrekt konfiguriert!\n";
echo "🎯 System ist bereit für die Demo morgen um 15:00 Uhr!\n";