<?php

/**
 * Manual Role Visibility Check
 *
 * Quick verification script to test role-based visibility gates
 * Run: php tests/manual_role_visibility_check.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  ROLE-BASED VISIBILITY TEST SETUP\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

// Check if roles exist
echo "✓ Checking existing roles...\n";
$roles = Role::all();
if ($roles->isEmpty()) {
    echo "  ⚠️  No roles found! Run: php artisan db:seed --class=RoleSeeder\n";
    exit(1);
}

echo "  Found roles:\n";
foreach ($roles as $role) {
    echo "    - {$role->name}\n";
}
echo "\n";

// Check/Create test users
$testUsers = [
    [
        'name' => 'Test Endkunde',
        'email' => 'endkunde@test.local',
        'role' => 'viewer',
        'description' => 'End customer - should NOT see technical details'
    ],
    [
        'name' => 'Test Praxis-Mitarbeiter',
        'email' => 'mitarbeiter@test.local',
        'role' => 'operator',
        'description' => 'Practice staff - should see basic technical details'
    ],
    [
        'name' => 'Test Administrator',
        'email' => 'admin@test.local',
        'role' => 'admin',
        'description' => 'Administrator - should see ALL details'
    ]
];

echo "✓ Setting up test users...\n\n";

foreach ($testUsers as $userData) {
    $user = User::where('email', $userData['email'])->first();

    if (!$user) {
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make('Test1234!'),
            'company_id' => 1,
        ]);

        echo "  ✓ Created user: {$userData['email']}\n";
    } else {
        echo "  ℹ User exists: {$userData['email']}\n";
    }

    // Ensure role is assigned
    if (!$user->hasRole($userData['role'])) {
        $user->assignRole($userData['role']);
        echo "    → Assigned role: {$userData['role']}\n";
    } else {
        echo "    → Role already assigned: {$userData['role']}\n";
    }

    echo "    → {$userData['description']}\n";
    echo "    → Password: Test1234!\n\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "  MANUAL TESTING INSTRUCTIONS\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

echo "1. LOGIN AS ENDKUNDE (viewer)\n";
echo "   Email: endkunde@test.local\n";
echo "   Password: Test1234!\n";
echo "   URL: /admin/appointments/675\n";
echo "   Expected:\n";
echo "     ✓ Can see: Aktueller Status, Historische Daten, Verknüpfter Anruf\n";
echo "     ✗ CANNOT see: 🔧 Technische Details, 🕐 Zeitstempel\n";
echo "\n";

echo "2. LOGIN AS PRAXIS-MITARBEITER (operator)\n";
echo "   Email: mitarbeiter@test.local\n";
echo "   Password: Test1234!\n";
echo "   URL: /admin/appointments/675\n";
echo "   Expected:\n";
echo "     ✓ Can see: All sections + 🔧 Technische Details\n";
echo "     ✗ CANNOT see: 🕐 Zeitstempel\n";
echo "\n";

echo "3. LOGIN AS ADMINISTRATOR (admin)\n";
echo "   Email: admin@test.local\n";
echo "   Password: Test1234!\n";
echo "   URL: /admin/appointments/675\n";
echo "   Expected:\n";
echo "     ✓ Can see: ALL sections including 🕐 Zeitstempel\n";
echo "\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "  VERIFICATION CHECKLIST\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

echo "ViewAppointment Page (/admin/appointments/675):\n";
echo "  [ ] Endkunde: Technical Details section HIDDEN\n";
echo "  [ ] Endkunde: Zeitstempel section HIDDEN\n";
echo "  [ ] Mitarbeiter: Technical Details section VISIBLE\n";
echo "  [ ] Mitarbeiter: Zeitstempel section HIDDEN\n";
echo "  [ ] Admin: Technical Details section VISIBLE\n";
echo "  [ ] Admin: Zeitstempel section VISIBLE\n";
echo "\n";

echo "Appointment Infolist (/admin/appointments → View):\n";
echo "  [ ] Endkunde: Buchungsdetails section HIDDEN\n";
echo "  [ ] Mitarbeiter: Buchungsdetails section VISIBLE\n";
echo "  [ ] Admin: Buchungsdetails section VISIBLE\n";
echo "\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "  ROLE VISIBILITY GATES\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

echo "Testing role checks for created users:\n\n";

foreach ($testUsers as $userData) {
    $user = User::where('email', $userData['email'])->first();

    echo "User: {$user->name} ({$userData['role']})\n";

    // Test Technical Details visibility
    $canViewTechnical = $user->hasAnyRole(['operator', 'manager', 'admin', 'super-admin']);
    echo "  🔧 Technical Details: " . ($canViewTechnical ? "✅ VISIBLE" : "❌ HIDDEN") . "\n";

    // Test Timestamps visibility
    $canViewTimestamps = $user->hasAnyRole(['admin', 'super-admin']);
    echo "  🕐 Zeitstempel: " . ($canViewTimestamps ? "✅ VISIBLE" : "❌ HIDDEN") . "\n";

    echo "\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "  IMPLEMENTATION FILES MODIFIED\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

$files = [
    'app/Filament/Resources/AppointmentResource/Pages/ViewAppointment.php' => [
        'Line 283: Technical Details section visibility gate',
        'Line 345: Zeitstempel section visibility gate'
    ],
    'app/Filament/Resources/AppointmentResource.php' => [
        'Line 786: Buchungsdetails infolist visibility gate'
    ]
];

foreach ($files as $file => $changes) {
    echo "✓ {$file}\n";
    foreach ($changes as $change) {
        echo "  → {$change}\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "  NEXT STEPS\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

echo "1. Clear Filament caches:\n";
echo "   php artisan filament:cache-components\n";
echo "   php artisan view:clear\n";
echo "\n";

echo "2. Test each user account manually:\n";
echo "   - Use Chrome/Firefox incognito for clean sessions\n";
echo "   - Verify section visibility matches expectations\n";
echo "   - Check both ViewAppointment and Infolist views\n";
echo "\n";

echo "3. Document test results:\n";
echo "   - Update ROLE_VISIBILITY_MATRIX.md with test outcomes\n";
echo "   - Note any discrepancies or issues\n";
echo "\n";

echo "4. If all tests pass:\n";
echo "   - Commit changes with descriptive message\n";
echo "   - Update FILAMENT_UI_COMPLIANCE_IMPLEMENTATION_SUMMARY.md\n";
echo "   - Mark Phase 3 as COMPLETE\n";
echo "\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "  ROLLBACK PROCEDURE (if issues found)\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

echo "git checkout HEAD app/Filament/Resources/AppointmentResource/Pages/ViewAppointment.php\n";
echo "git checkout HEAD app/Filament/Resources/AppointmentResource.php\n";
echo "php artisan filament:cache-components\n";
echo "php artisan view:clear\n";
echo "\n";

echo "✅ Test setup complete!\n";
echo "\n";
