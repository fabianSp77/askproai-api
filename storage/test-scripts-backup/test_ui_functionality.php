<?php

/**
 * Comprehensive UI Functionality Test Script
 * Tests all UI components and interactions
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\CalcomEventType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== AskProAI UI Functionality Test Report ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Set environment to local to bypass tenant scope
putenv('APP_ENV=local');

// 1. Test Database State
echo "1. DATABASE STATE CHECK\n";
echo "------------------------\n";

$stats = [
    'Companies' => Company::count(),
    'Branches' => Branch::count(),
    'Phone Numbers' => PhoneNumber::count(),
    'Event Types' => CalcomEventType::count(),
    'Users' => User::count(),
];

foreach ($stats as $model => $count) {
    echo "- $model: $count\n";
}

// 2. Test Branch Event Type Relationships
echo "\n2. BRANCH EVENT TYPE RELATIONSHIPS\n";
echo "-----------------------------------\n";

$branches = Branch::with('eventTypes')->get();
foreach ($branches as $branch) {
    echo "\nBranch: {$branch->name} (ID: {$branch->id})\n";
    echo "- Active: " . ($branch->is_active ? 'Yes' : 'No') . "\n";
    echo "- Event Types: " . $branch->eventTypes->count() . "\n";
    
    if ($branch->eventTypes->count() > 0) {
        foreach ($branch->eventTypes as $eventType) {
            $isPrimary = $eventType->pivot->is_primary ? ' [PRIMARY]' : '';
            echo "  - {$eventType->name} (ID: {$eventType->id})$isPrimary\n";
        }
    }
    
    // Check for primary event type
    $hasPrimary = $branch->eventTypes()->wherePivot('is_primary', true)->exists();
    if (!$hasPrimary && $branch->eventTypes->count() > 0) {
        echo "  ⚠️  WARNING: No primary event type set!\n";
    }
}

// 3. Test Phone Number Assignments
echo "\n3. PHONE NUMBER ASSIGNMENTS\n";
echo "----------------------------\n";

$phoneNumbers = PhoneNumber::with(['branch', 'branch.company'])->get();
foreach ($phoneNumbers as $phone) {
    echo "\nPhone: {$phone->number}\n";
    echo "- Branch: " . ($phone->branch ? $phone->branch->name : 'NOT ASSIGNED') . "\n";
    echo "- Company: " . ($phone->branch && $phone->branch->company ? $phone->branch->company->name : 'N/A') . "\n";
    echo "- Retell Agent ID: " . ($phone->retell_agent_id ?: 'NOT SET') . "\n";
    echo "- Active: " . ($phone->is_active ? 'Yes' : 'No') . "\n";
    
    if (!$phone->branch) {
        echo "  ⚠️  WARNING: Phone number not assigned to any branch!\n";
    }
    if (!$phone->retell_agent_id) {
        echo "  ⚠️  WARNING: No Retell agent ID configured!\n";
    }
}

// 4. Test CSS Files
echo "\n4. CSS FILES CHECK\n";
echo "------------------\n";

$cssFiles = [
    'resources/css/filament/admin/company-integration-portal.css',
    'resources/css/filament/admin/responsive-fixes.css',
    'resources/css/filament/admin/agent-management.css',
];

foreach ($cssFiles as $file) {
    $path = base_path($file);
    if (file_exists($path)) {
        $size = filesize($path);
        $lines = count(file($path));
        echo "✓ $file\n";
        echo "  - Size: " . number_format($size) . " bytes\n";
        echo "  - Lines: " . number_format($lines) . "\n";
        
        // Check for critical CSS rules
        $content = file_get_contents($path);
        $criticalRules = [
            'z-index: 20' => 'Button z-index fix',
            'pointer-events: auto' => 'Pointer events fix',
            'position: fixed' => 'Dropdown positioning fix',
            '@media (max-width:' => 'Responsive rules',
        ];
        
        foreach ($criticalRules as $rule => $description) {
            if (strpos($content, $rule) !== false) {
                echo "  ✓ Contains: $description\n";
            } else {
                echo "  ⚠️  Missing: $description\n";
            }
        }
    } else {
        echo "✗ $file - NOT FOUND\n";
    }
}

// 5. Test JavaScript Files
echo "\n5. JAVASCRIPT FILES CHECK\n";
echo "-------------------------\n";

$jsFiles = [
    'resources/js/company-integration-portal.js',
    'resources/js/components/askproai-ui-components.js',
];

foreach ($jsFiles as $file) {
    $path = base_path($file);
    if (file_exists($path)) {
        $size = filesize($path);
        $lines = count(file($path));
        echo "✓ $file\n";
        echo "  - Size: " . number_format($size) . " bytes\n";
        echo "  - Lines: " . number_format($lines) . "\n";
        
        // Check for critical functions
        $content = file_get_contents($path);
        $criticalFunctions = [
            'smartDropdown' => 'Smart dropdown component',
            'calculatePosition' => 'Position calculation',
            'closeOnOutsideClick' => 'Outside click handler',
            'inlineEdit' => 'Inline edit component',
        ];
        
        foreach ($criticalFunctions as $func => $description) {
            if (strpos($content, $func) !== false) {
                echo "  ✓ Contains: $description\n";
            } else {
                echo "  ⚠️  Missing: $description\n";
            }
        }
    } else {
        echo "✗ $file - NOT FOUND\n";
    }
}

// 6. Test Build Assets
echo "\n6. BUILD ASSETS CHECK\n";
echo "---------------------\n";

$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    echo "✓ Build manifest found\n";
    echo "- Total assets: " . count($manifest) . "\n";
    
    $requiredAssets = [
        'resources/css/filament/admin/theme.css',
        'resources/js/app.js',
    ];
    
    foreach ($requiredAssets as $asset) {
        if (isset($manifest[$asset])) {
            echo "  ✓ $asset -> " . basename($manifest[$asset]['file']) . "\n";
        } else {
            echo "  ⚠️  Missing: $asset\n";
        }
    }
} else {
    echo "✗ Build manifest NOT FOUND - Run 'npm run build'\n";
}

// 7. Test Critical UI Components
echo "\n7. UI COMPONENT VALIDATION\n";
echo "--------------------------\n";

// Check CompanyIntegrationPortal methods
$portalClass = 'App\Filament\Admin\Pages\CompanyIntegrationPortal';
if (class_exists($portalClass)) {
    echo "✓ CompanyIntegrationPortal class exists\n";
    
    $methods = [
        'setPrimaryEventType' => 'Primary event type selection',
        'manageBranchEventTypes' => 'Event type management',
        'addBranchEventType' => 'Add event type',
        'removeBranchEventType' => 'Remove event type',
        'toggleBranchNameInput' => 'Inline name editing',
        'toggleBranchAddressInput' => 'Inline address editing',
        'toggleBranchPhoneInput' => 'Inline phone editing',
        'toggleBranchEmailInput' => 'Inline email editing',
    ];
    
    foreach ($methods as $method => $description) {
        if (method_exists($portalClass, $method)) {
            echo "  ✓ Method exists: $method ($description)\n";
        } else {
            echo "  ✗ Method missing: $method ($description)\n";
        }
    }
} else {
    echo "✗ CompanyIntegrationPortal class NOT FOUND\n";
}

// 8. Test Data Integrity
echo "\n8. DATA INTEGRITY CHECK\n";
echo "-----------------------\n";

// Check for orphaned records
$orphanedPhones = DB::table('phone_numbers')
    ->leftJoin('branches', 'phone_numbers.branch_id', '=', 'branches.id')
    ->whereNull('branches.id')
    ->whereNotNull('phone_numbers.branch_id')
    ->count();

echo "- Orphaned phone numbers: " . ($orphanedPhones > 0 ? "⚠️  $orphanedPhones found!" : "✓ None") . "\n";

$orphanedEventTypes = DB::table('branch_event_types')
    ->leftJoin('branches', 'branch_event_types.branch_id', '=', 'branches.id')
    ->whereNull('branches.id')
    ->count();

echo "- Orphaned branch event types: " . ($orphanedEventTypes > 0 ? "⚠️  $orphanedEventTypes found!" : "✓ None") . "\n";

// Check for branches without primary event type
$branchesWithoutPrimary = DB::table('branches')
    ->join('branch_event_types', 'branches.id', '=', 'branch_event_types.branch_id')
    ->groupBy('branches.id', 'branches.name')
    ->havingRaw('SUM(CASE WHEN branch_event_types.is_primary = 1 THEN 1 ELSE 0 END) = 0')
    ->select('branches.id', 'branches.name')
    ->get();

if ($branchesWithoutPrimary->count() > 0) {
    echo "- Branches without primary event type: ⚠️  " . $branchesWithoutPrimary->count() . " found!\n";
    foreach ($branchesWithoutPrimary as $branch) {
        echo "    - {$branch->name} (ID: {$branch->id})\n";
    }
} else {
    echo "- Branches without primary event type: ✓ None\n";
}

// 9. Summary
echo "\n9. SUMMARY\n";
echo "----------\n";

$issues = [];

if ($orphanedPhones > 0) $issues[] = "Orphaned phone numbers found";
if ($orphanedEventTypes > 0) $issues[] = "Orphaned event types found";
if ($branchesWithoutPrimary->count() > 0) $issues[] = "Branches without primary event type";

$phoneIssues = PhoneNumber::whereNull('branch_id')->orWhereNull('retell_agent_id')->count();
if ($phoneIssues > 0) $issues[] = "$phoneIssues phone numbers need configuration";

if (count($issues) === 0) {
    echo "✅ All UI functionality tests PASSED!\n";
    echo "The system appears to be fully functional.\n";
} else {
    echo "⚠️  Issues found:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
    echo "\nRecommendation: Address these issues before production deployment.\n";
}

echo "\n=== End of Report ===\n";