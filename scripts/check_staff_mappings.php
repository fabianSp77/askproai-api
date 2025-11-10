<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\CalcomHostMapping;
use App\Models\Branch;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  STAFF & CAL.COM HOST MAPPINGS CHECK\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';
$branch = Branch::find($branchId);

echo "Branch: {$branch->name}\n\n";

$staff = Staff::where('branch_id', $branchId)->get();

echo "Found " . $staff->count() . " staff members\n\n";

if ($staff->isEmpty()) {
    echo "⚠️  No staff members found for this branch!\n";
    echo "    Without staff, appointments cannot be assigned to hosts.\n\n";
    exit(1);
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STAFF MEMBERS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$withMapping = 0;
$withoutMapping = 0;

foreach ($staff as $member) {
    echo "─────────────────────────────────────────────────────────\n";
    echo "Staff: {$member->name}\n";
    echo "  ID: {$member->id}\n";
    echo "  Email: {$member->email}\n";
    echo "  Active: " . ($member->is_active ? 'YES' : 'NO') . "\n";

    // Check Cal.com mapping
    $mapping = CalcomHostMapping::where('staff_id', $member->id)->first();

    if ($mapping) {
        echo "  ✅ Cal.com Mapping:\n";
        echo "     User ID: {$mapping->calcom_user_id}\n";
        echo "     Username: {$mapping->calcom_username}\n";
        echo "     Email: {$mapping->calcom_email}\n";
        $withMapping++;
    } else {
        echo "  ❌ No Cal.com mapping found\n";
        echo "     This staff member cannot be assigned to appointments!\n";
        $withoutMapping++;
    }

    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Total Staff: " . $staff->count() . "\n";
echo "  ✅ With Cal.com mapping: $withMapping\n";
echo "  ❌ Without mapping: $withoutMapping\n\n";

if ($withoutMapping > 0) {
    echo "⚠️  WARNING: $withoutMapping staff member(s) have no Cal.com mapping!\n";
    echo "   Appointments cannot be assigned to these staff members.\n";
    echo "   Run: php artisan calcom:sync-team-members\n\n";
}

if ($withMapping == 0) {
    echo "🔴 CRITICAL: No staff members have Cal.com mappings!\n";
    echo "   Without mappings:\n";
    echo "   - Cannot check availability\n";
    echo "   - Cannot create bookings\n";
    echo "   - System will fail to assign hosts to appointments\n\n";
    echo "   Fix: Run php artisan calcom:sync-team-members\n\n";
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  CHECK COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
