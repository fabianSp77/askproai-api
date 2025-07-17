<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PortalUser;

echo "\n🔍 DATENBANK-VERBINDUNGS-CHECK\n";
echo "=================================\n\n";

// 1. Direct database query
echo "1️⃣ DIREKTE DATENBANK-ABFRAGE:\n";
echo "-------------------------------\n";
try {
    $users = DB::table('portal_users')
        ->select('id', 'email', 'name', 'company_id', 'is_active')
        ->get();
    
    echo "✅ Datenbankverbindung funktioniert\n";
    echo "Gefundene Portal-User: " . $users->count() . "\n\n";
    
    foreach ($users as $user) {
        echo "ID: {$user->id} | Email: {$user->email} | Name: {$user->name} | Aktiv: {$user->is_active}\n";
    }
    
    // Specific user
    echo "\n🔎 Suche nach fabianspitzer@icloud.com:\n";
    $specificUser = DB::table('portal_users')
        ->where('email', 'fabianspitzer@icloud.com')
        ->first();
    
    if ($specificUser) {
        echo "✅ Benutzer in DB gefunden!\n";
        echo "   ID: {$specificUser->id}\n";
        echo "   Company ID: {$specificUser->company_id}\n";
    } else {
        echo "❌ Benutzer NICHT in DB gefunden!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Datenbankfehler: " . $e->getMessage() . "\n";
}

// 2. Check with Model (without scope)
echo "\n2️⃣ MODEL OHNE SCOPE:\n";
echo "---------------------\n";
try {
    $userWithoutScope = PortalUser::withoutGlobalScopes()->where('email', 'fabianspitzer@icloud.com')->first();
    
    if ($userWithoutScope) {
        echo "✅ Benutzer mit Model (ohne Scopes) gefunden!\n";
        echo "   ID: {$userWithoutScope->id}\n";
        echo "   Company ID: {$userWithoutScope->company_id}\n";
    } else {
        echo "❌ Benutzer auch ohne Scopes nicht gefunden!\n";
    }
} catch (\Exception $e) {
    echo "❌ Model-Fehler: " . $e->getMessage() . "\n";
}

// 3. Check with Model (with scope)
echo "\n3️⃣ MODEL MIT SCOPE:\n";
echo "--------------------\n";
try {
    // Set company context
    app()->instance('company_id', 16); // Demo GmbH ID
    
    $userWithScope = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();
    
    if ($userWithScope) {
        echo "✅ Benutzer mit Model (mit Scopes) gefunden!\n";
    } else {
        echo "❌ Benutzer mit Scopes nicht gefunden!\n";
        echo "   → TenantScope könnte das Problem sein!\n";
    }
} catch (\Exception $e) {
    echo "❌ Scope-Fehler: " . $e->getMessage() . "\n";
}

// 4. Check TenantScope
echo "\n4️⃣ TENANT SCOPE CHECK:\n";
echo "-----------------------\n";
$hasScope = false;
$scopes = (new PortalUser)->getGlobalScopes();
foreach ($scopes as $identifier => $scope) {
    echo "   Scope: {$identifier} - " . get_class($scope) . "\n";
    if (strpos(get_class($scope), 'TenantScope') !== false) {
        $hasScope = true;
    }
}

if ($hasScope) {
    echo "⚠️ TenantScope ist aktiv - könnte Benutzer filtern!\n";
} else {
    echo "✅ Kein TenantScope aktiv\n";
}

// 5. Raw SQL query
echo "\n5️⃣ RAW SQL QUERY:\n";
echo "------------------\n";
$result = DB::select("SELECT id, email, name, company_id FROM portal_users WHERE email = ?", ['fabianspitzer@icloud.com']);
if (!empty($result)) {
    echo "✅ Raw SQL findet Benutzer:\n";
    foreach ($result as $row) {
        echo "   ID: {$row->id}, Email: {$row->email}, Company: {$row->company_id}\n";
    }
} else {
    echo "❌ Auch Raw SQL findet keinen Benutzer!\n";
}

// 6. Check table structure
echo "\n6️⃣ TABELLEN-STRUKTUR:\n";
echo "----------------------\n";
$columns = DB::select("SHOW COLUMNS FROM portal_users");
echo "Spalten in portal_users:\n";
foreach ($columns as $column) {
    echo "   - {$column->Field} ({$column->Type})\n";
}