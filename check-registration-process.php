<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "\n🔍 REGISTRIERUNGS-PROZESS ANALYSE\n";
echo "==================================\n\n";

// 1. Check the registration data
echo "1️⃣ REGISTRIERUNGS-DATEN PRÜFEN:\n";
echo "--------------------------------\n";

// Get raw data from DB
$rawUser = DB::table('portal_users')
    ->where('email', 'fabianspitzer@icloud.com')
    ->first();

echo "Raw DB Data:\n";
echo "   ID: {$rawUser->id}\n";
echo "   Email: {$rawUser->email}\n";
echo "   Name: {$rawUser->name}\n";
echo "   Password (first 20 chars): " . substr($rawUser->password, 0, 20) . "...\n";
echo "   Password Length: " . strlen($rawUser->password) . "\n";
echo "   Is Active: {$rawUser->is_active}\n";
echo "   Created At: {$rawUser->created_at}\n";

// 2. Check password encoding
echo "\n2️⃣ PASSWORT-ENCODING PRÜFEN:\n";
echo "------------------------------\n";

// Check if password might be double-hashed or plain text
if (strlen($rawUser->password) != 60) {
    echo "⚠️ WARNUNG: Passwort-Hash hat nicht die erwartete Länge (60 für bcrypt)\n";
}

// Check if it starts with $2y$ (bcrypt marker)
if (!str_starts_with($rawUser->password, '$2y$')) {
    echo "❌ KRITISCH: Passwort ist kein gültiger bcrypt-Hash!\n";
    echo "   Passwort beginnt mit: " . substr($rawUser->password, 0, 4) . "\n";
} else {
    echo "✅ Passwort ist ein bcrypt-Hash\n";
}

// 3. Test password with various methods
echo "\n3️⃣ PASSWORT-TESTS:\n";
echo "-------------------\n";

$testPassword = 'test123'; // Assuming this was used during registration

// Test 1: Direct Hash check
$directCheck = Hash::check($testPassword, $rawUser->password);
echo "Test 1 - Direct Hash::check: " . ($directCheck ? 'ERFOLG' : 'FEHLGESCHLAGEN') . "\n";

// Test 2: Create new hash and compare
$newHash = Hash::make($testPassword);
echo "Test 2 - Neuer Hash erstellt: " . substr($newHash, 0, 20) . "...\n";
echo "         Check gegen neuen Hash: " . (Hash::check($testPassword, $newHash) ? 'ERFOLG' : 'FEHLGESCHLAGEN') . "\n";

// Test 3: Check if password might be plain text
if ($rawUser->password === $testPassword) {
    echo "❌ KRITISCH: Passwort ist im Klartext gespeichert!\n";
}

// 4. Check RegisterController
echo "\n4️⃣ REGISTER CONTROLLER PRÜFEN:\n";
echo "--------------------------------\n";

$registerControllerPath = app_path('Http/Controllers/Portal/Auth/RegisterController.php');
if (file_exists($registerControllerPath)) {
    $content = file_get_contents($registerControllerPath);
    
    // Check for password hashing in controller
    if (strpos($content, 'Hash::make') !== false) {
        echo "✅ RegisterController verwendet Hash::make\n";
    } else if (strpos($content, 'bcrypt') !== false) {
        echo "✅ RegisterController verwendet bcrypt\n";
    } else {
        echo "⚠️ WARNUNG: Keine offensichtliche Passwort-Hashierung im RegisterController gefunden\n";
    }
    
    // Check the specific line
    preg_match('/password.*=>.*Hash::make\(\$request->password\)/', $content, $matches);
    if ($matches) {
        echo "✅ Passwort wird korrekt gehasht: " . $matches[0] . "\n";
    }
}

// 5. Manual password reset
echo "\n5️⃣ MANUELLER PASSWORT-RESET:\n";
echo "------------------------------\n";

// Update password directly
$newPasswordHash = Hash::make('demo123');
DB::table('portal_users')
    ->where('id', $rawUser->id)
    ->update(['password' => $newPasswordHash]);

echo "✅ Passwort direkt in DB aktualisiert\n";
echo "   Neues Passwort: demo123\n";
echo "   Neuer Hash: " . substr($newPasswordHash, 0, 20) . "...\n";

// Test login with new password
$user = PortalUser::find($rawUser->id);
$loginTest = Hash::check('demo123', $user->password);
echo "   Login-Test mit neuem Passwort: " . ($loginTest ? 'ERFOLG' : 'FEHLGESCHLAGEN') . "\n";

// 6. Check auth config
echo "\n6️⃣ AUTH KONFIGURATION:\n";
echo "-----------------------\n";

$portalProvider = config('auth.providers.portal_users');
echo "Portal Provider:\n";
echo "   Driver: " . ($portalProvider['driver'] ?? 'N/A') . "\n";
echo "   Model: " . ($portalProvider['model'] ?? 'N/A') . "\n";

// Check if model exists and is correct
if (isset($portalProvider['model']) && class_exists($portalProvider['model'])) {
    echo "✅ Model-Klasse existiert\n";
    
    // Check if it extends Authenticatable
    $reflection = new ReflectionClass($portalProvider['model']);
    if ($reflection->isSubclassOf(\Illuminate\Contracts\Auth\Authenticatable::class)) {
        echo "✅ Model implementiert Authenticatable\n";
    } else {
        echo "❌ Model implementiert NICHT Authenticatable!\n";
    }
}

echo "\n📝 ZUSAMMENFASSUNG:\n";
echo "===================\n";
echo "Email: fabianspitzer@icloud.com\n";
echo "Neues Passwort: demo123\n";
echo "Status: Aktiv\n";
echo "Login URL: https://api.askproai.de/business/login\n";
echo "\n⚠️ WICHTIG: Browser-Cache und Cookies löschen vor dem nächsten Login-Versuch!\n";