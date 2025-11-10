<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;
use App\Models\Company;
use Illuminate\Support\Str;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  SERVICE FIX SCRIPT - Activate & Clean Services\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$company = Company::find(1);
echo "Company: {$company->name}\n";
echo "Cal.com Team ID: " . ($company->calcom_team_id ?? 'NOT SET') . "\n";
echo "Cal.com API Key: " . (empty($company->calcom_api_key) ? '❌ NOT SET' : '✅ SET') . "\n\n";

// Critical issue
if (empty($company->calcom_api_key)) {
    echo "🔴 CRITICAL: Cal.com API Key is not set!\n";
    echo "    This prevents synchronization with Cal.com.\n";
    echo "    Please set it in the admin panel or database.\n\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ANALYZING SERVICES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$services = Service::where('company_id', 1)
    ->where('branch_id', '34c4d48e-4753-4715-9c30-c55843a943e8')
    ->whereNotNull('calcom_event_type_id')
    ->get();

echo "Found " . $services->count() . " services with Cal.com Event Type IDs\n\n";

$fixes = [];
$inactive = 0;
$noSlug = 0;

foreach ($services as $service) {
    $issues = [];

    if (!$service->is_active) {
        $issues[] = 'INACTIVE';
        $inactive++;
    }

    if (empty($service->slug)) {
        $issues[] = 'NO SLUG';
        $noSlug++;
    }

    if (!empty($issues)) {
        $fixes[] = [
            'service' => $service,
            'issues' => $issues
        ];
    }
}

echo "Issues found:\n";
echo "  ❌ Inactive services: $inactive\n";
echo "  ❌ Missing slugs: $noSlug\n\n";

if (empty($fixes)) {
    echo "✅ All services are properly configured!\n";
    exit(0);
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "APPLYING FIXES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$fixed = 0;

foreach ($fixes as $fix) {
    $service = $fix['service'];
    $issues = $fix['issues'];

    echo "Service: {$service->name} (ID: {$service->id})\n";
    echo "  Issues: " . implode(', ', $issues) . "\n";

    $changes = [];

    // Fix: Activate service
    if (!$service->is_active) {
        $service->is_active = true;
        $changes[] = 'Activated';
    }

    // Fix: Add slug
    if (empty($service->slug)) {
        $service->slug = Str::slug($service->name);
        $changes[] = "Added slug: '{$service->slug}'";
    }

    if (!empty($changes)) {
        try {
            $service->save();
            echo "  ✅ Fixed: " . implode(', ', $changes) . "\n";
            $fixed++;
        } catch (\Exception $e) {
            echo "  ❌ Error: " . $e->getMessage() . "\n";
        }
    }

    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "✅ Fixed $fixed services\n\n";

// Verify
echo "Verifying fixes...\n\n";

$stillInactive = Service::where('company_id', 1)
    ->where('branch_id', '34c4d48e-4753-4715-9c30-c55843a943e8')
    ->whereNotNull('calcom_event_type_id')
    ->where('is_active', false)
    ->count();

$stillNoSlug = Service::where('company_id', 1)
    ->where('branch_id', '34c4d48e-4753-4715-9c30-c55843a943e8')
    ->whereNotNull('calcom_event_type_id')
    ->where(function($q) {
        $q->whereNull('slug')->orWhere('slug', '');
    })
    ->count();

if ($stillInactive == 0 && $stillNoSlug == 0) {
    echo "✅ All services are now properly configured!\n\n";
} else {
    if ($stillInactive > 0) {
        echo "⚠️  Still $stillInactive inactive services\n";
    }
    if ($stillNoSlug > 0) {
        echo "⚠️  Still $stillNoSlug services without slug\n";
    }
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "RECOMMENDATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "1. 🔴 CRITICAL: Set Cal.com API Key in Company settings\n";
echo "   Without API key, you cannot:\n";
echo "   - Sync event types from Cal.com\n";
echo "   - Verify service names match Cal.com\n";
echo "   - Check availability\n";
echo "   - Create bookings\n\n";

echo "2. ✅ Verify Services in Admin Panel\n";
echo "   Go to: /admin/services\n";
echo "   Check that all services are visible and active\n\n";

echo "3. 📞 Test Booking Flow\n";
echo "   Call Retell number and try booking each service\n";
echo "   Verify:\n";
echo "   - Service is recognized by name\n";
echo "   - Availability check works\n";
echo "   - Booking succeeds\n\n";

echo "4. 🔄 Sync with Cal.com (once API key is set)\n";
echo "   Run: php artisan calcom:sync\n";
echo "   This will verify names match between systems\n\n";

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  FIX COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
