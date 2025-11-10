<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\CalcomHostMapping;
use App\Models\Company;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  COMPREHENSIVE SERVICE AUDIT - Cal.com vs AskPro Database\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

$companyId = 1;
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';

// Get company and branch info
$company = Company::find($companyId);
$branch = Branch::find($branchId);

echo "Company: {$company->name}\n";
echo "  Cal.com Team ID: " . ($company->calcom_team_id ?? 'NOT SET') . "\n";
echo "Branch: {$branch->name} (ID: {$branch->id})\n\n";

if (!$company->calcom_team_id) {
    echo "❌ ERROR: Company has no Cal.com Team ID set!\n";
    exit(1);
}

$calcomService = app(CalcomV2Service::class);

// ============================
// STEP 1: Get all Cal.com Event Types
// ============================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 1: Fetching Cal.com Event Types\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $response = $calcomService->fetchTeamEventTypes($company->calcom_team_id);

    if (!$response->successful()) {
        echo "❌ Error fetching Cal.com event types: " . $response->body() . "\n";
        exit(1);
    }

    $responseData = $response->json();
    $eventTypes = $responseData['data'] ?? $responseData;

    echo "✅ Found " . count($eventTypes) . " event types in Cal.com\n\n";

    // Create lookup array
    $calcomEventTypesById = [];
    $calcomEventTypesByName = [];

    foreach ($eventTypes as $eventType) {
        $calcomEventTypesById[$eventType['id']] = $eventType;
        $normalizedName = strtolower(trim($eventType['title']));
        $calcomEventTypesByName[$normalizedName] = $eventType;
    }

} catch (\Exception $e) {
    echo "❌ Error fetching Cal.com event types: " . $e->getMessage() . "\n";
    exit(1);
}

// ============================
// STEP 2: Get all Services from Database
// ============================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 2: Fetching Services from Database\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$services = Service::where('company_id', $companyId)
    ->where('branch_id', $branchId)
    ->get();

echo "✅ Found " . count($services) . " services in database\n\n";

// ============================
// STEP 3: Compare and Analyze
// ============================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 3: Service Analysis\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$issues = [];
$fixable = [];

foreach ($services as $service) {
    echo str_repeat("─", 79) . "\n";
    echo "Service: {$service->name}\n";
    echo str_repeat("─", 79) . "\n";

    // Check basic info
    echo "  DB ID: {$service->id}\n";
    echo "  Slug: " . ($service->slug ?: '❌ MISSING') . "\n";
    echo "  Active: " . ($service->is_active ? '✅ YES' : '❌ NO') . "\n";
    echo "  Cal.com Event Type ID: " . ($service->calcom_event_type_id ?: '❌ NOT SET') . "\n";
    echo "  Price: €" . number_format($service->price, 2) . "\n";
    echo "  Duration: {$service->duration_minutes} min\n";

    // Check if has Cal.com mapping
    if (!$service->calcom_event_type_id) {
        echo "\n  ⚠️  WARNING: No Cal.com Event Type ID assigned\n";
        $issues[] = [
            'type' => 'no_calcom_id',
            'service' => $service->name,
            'service_id' => $service->id,
            'severity' => 'high'
        ];
    } else {
        // Check if Cal.com Event Type exists
        if (!isset($calcomEventTypesById[$service->calcom_event_type_id])) {
            echo "\n  ❌ ERROR: Cal.com Event Type {$service->calcom_event_type_id} NOT FOUND in Cal.com!\n";
            $issues[] = [
                'type' => 'calcom_not_found',
                'service' => $service->name,
                'service_id' => $service->id,
                'calcom_id' => $service->calcom_event_type_id,
                'severity' => 'critical'
            ];
        } else {
            // Compare names
            $calcomEvent = $calcomEventTypesById[$service->calcom_event_type_id];
            $dbName = strtolower(trim($service->name));
            $calcomName = strtolower(trim($calcomEvent['title']));

            echo "\n  Cal.com Details:\n";
            echo "    Title: {$calcomEvent['title']}\n";
            echo "    Length: {$calcomEvent['length']} min\n";
            echo "    Slug: {$calcomEvent['slug']}\n";

            if ($dbName !== $calcomName) {
                echo "\n  ⚠️  NAME MISMATCH:\n";
                echo "    Database: '{$service->name}'\n";
                echo "    Cal.com:  '{$calcomEvent['title']}'\n";

                $issues[] = [
                    'type' => 'name_mismatch',
                    'service_id' => $service->id,
                    'db_name' => $service->name,
                    'calcom_name' => $calcomEvent['title'],
                    'severity' => 'medium'
                ];

                $fixable[] = [
                    'service_id' => $service->id,
                    'action' => 'update_name',
                    'current_name' => $service->name,
                    'correct_name' => $calcomEvent['title']
                ];
            } else {
                echo "\n  ✅ Names match perfectly\n";
            }

            // Check duration
            if ($service->duration_minutes != $calcomEvent['length']) {
                echo "\n  ⚠️  DURATION MISMATCH:\n";
                echo "    Database: {$service->duration_minutes} min\n";
                echo "    Cal.com:  {$calcomEvent['length']} min\n";

                $issues[] = [
                    'type' => 'duration_mismatch',
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'db_duration' => $service->duration_minutes,
                    'calcom_duration' => $calcomEvent['length'],
                    'severity' => 'low'
                ];

                $fixable[] = [
                    'service_id' => $service->id,
                    'action' => 'update_duration',
                    'current_duration' => $service->duration_minutes,
                    'correct_duration' => $calcomEvent['length']
                ];
            }
        }
    }

    // Check if slug is missing
    if (empty($service->slug) && !empty($service->name)) {
        $suggestedSlug = \Illuminate\Support\Str::slug($service->name);
        echo "\n  ⚠️  Slug missing. Suggested: '{$suggestedSlug}'\n";

        $fixable[] = [
            'service_id' => $service->id,
            'action' => 'add_slug',
            'suggested_slug' => $suggestedSlug
        ];
    }

    // Check active status
    if (!$service->is_active && $service->calcom_event_type_id) {
        echo "\n  ⚠️  Service has Cal.com ID but is INACTIVE\n";

        $issues[] = [
            'type' => 'inactive_with_calcom',
            'service_id' => $service->id,
            'service_name' => $service->name,
            'severity' => 'high'
        ];

        $fixable[] = [
            'service_id' => $service->id,
            'action' => 'activate',
            'service_name' => $service->name
        ];
    }

    echo "\n";
}

// ============================
// STEP 4: Check Staff/Host Mappings
// ============================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 4: Staff & Host Mappings\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$staff = Staff::where('branch_id', $branchId)->get();
echo "Staff Members: " . count($staff) . "\n\n";

foreach ($staff as $member) {
    echo "Staff: {$member->name}\n";

    // Check Cal.com mapping
    $mapping = CalcomHostMapping::where('staff_id', $member->id)->first();

    if (!$mapping) {
        echo "  ❌ No Cal.com host mapping found\n";
        $issues[] = [
            'type' => 'no_host_mapping',
            'staff_id' => $member->id,
            'staff_name' => $member->name,
            'severity' => 'high'
        ];
    } else {
        echo "  ✅ Cal.com User ID: {$mapping->calcom_user_id}\n";
        echo "  ✅ Cal.com Username: {$mapping->calcom_username}\n";
    }

    echo "\n";
}

// ============================
// STEP 5: Check for Orphaned Cal.com Event Types
// ============================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 5: Orphaned Cal.com Event Types\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$mappedCalcomIds = $services->pluck('calcom_event_type_id')->filter()->toArray();
$unmappedEventTypes = [];

foreach ($eventTypes as $eventType) {
    if (!in_array($eventType['id'], $mappedCalcomIds)) {
        $unmappedEventTypes[] = $eventType;
    }
}

if (count($unmappedEventTypes) > 0) {
    echo "⚠️  Found " . count($unmappedEventTypes) . " Cal.com Event Types without database mapping:\n\n";

    foreach ($unmappedEventTypes as $eventType) {
        echo "  - {$eventType['title']} (ID: {$eventType['id']}, Length: {$eventType['length']}min)\n";

        $issues[] = [
            'type' => 'unmapped_calcom_event',
            'calcom_id' => $eventType['id'],
            'calcom_title' => $eventType['title'],
            'severity' => 'medium'
        ];
    }
} else {
    echo "✅ All Cal.com Event Types are mapped\n";
}

echo "\n";

// ============================
// SUMMARY
// ============================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$criticalIssues = array_filter($issues, fn($i) => $i['severity'] === 'critical');
$highIssues = array_filter($issues, fn($i) => $i['severity'] === 'high');
$mediumIssues = array_filter($issues, fn($i) => $i['severity'] === 'medium');
$lowIssues = array_filter($issues, fn($i) => $i['severity'] === 'low');

echo "Total Issues Found: " . count($issues) . "\n";
echo "  🔴 Critical: " . count($criticalIssues) . "\n";
echo "  🟠 High:     " . count($highIssues) . "\n";
echo "  🟡 Medium:   " . count($mediumIssues) . "\n";
echo "  🟢 Low:      " . count($lowIssues) . "\n\n";

echo "Auto-Fixable Issues: " . count($fixable) . "\n\n";

// ============================
// EXPORT DATA
// ============================

$auditData = [
    'timestamp' => now()->toIso8601String(),
    'branch' => [
        'id' => $branch->id,
        'name' => $branch->name,
    ],
    'stats' => [
        'services_in_db' => count($services),
        'event_types_in_calcom' => count($eventTypes),
        'total_issues' => count($issues),
        'fixable_issues' => count($fixable),
    ],
    'issues' => $issues,
    'fixable' => $fixable,
    'services' => $services->map(function($s) {
        return [
            'id' => $s->id,
            'name' => $s->name,
            'slug' => $s->slug,
            'active' => $s->is_active,
            'calcom_id' => $s->calcom_event_type_id,
            'price' => $s->price,
            'duration' => $s->duration_minutes,
        ];
    }),
    'calcom_event_types' => $eventTypes,
];

$reportPath = '/var/www/api-gateway/SERVICE_AUDIT_REPORT_' . now()->format('Y-m-d_His') . '.json';
file_put_contents($reportPath, json_encode($auditData, JSON_PRETTY_PRINT));

echo "📄 Full report saved to: {$reportPath}\n\n";

// ============================
// RECOMMENDATIONS
// ============================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "RECOMMENDATIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (count($fixable) > 0) {
    echo "Run the fix script to automatically resolve " . count($fixable) . " issues:\n";
    echo "  php scripts/fix_service_issues.php\n\n";
}

if (count($criticalIssues) > 0) {
    echo "🔴 CRITICAL: Fix these issues immediately:\n";
    foreach ($criticalIssues as $issue) {
        echo "  - {$issue['type']}: " . json_encode($issue) . "\n";
    }
    echo "\n";
}

if (count($highIssues) > 0) {
    echo "🟠 HIGH PRIORITY: Address these issues soon:\n";
    foreach ($highIssues as $issue) {
        echo "  - {$issue['type']}\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "  AUDIT COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
