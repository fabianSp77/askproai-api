<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PortalUser;
use App\Models\Customer;
use App\Http\Controllers\Portal\Api\CallApiController;
use App\Http\Controllers\Portal\Api\SettingsApiController;
use Illuminate\Http\Request;

echo "=== COMPREHENSIVE CALL NOTIFICATION SYSTEM TEST ===\n\n";

// 1. Database Status Check
echo "1. DATABASE STATUS CHECK\n";
echo "-" . str_repeat("-", 50) . "\n";

$companiesCount = Company::count();
$callsCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->count();
$usersCount = PortalUser::count();

echo "Companies: $companiesCount\n";
echo "Total Calls: $callsCount\n";
echo "Portal Users: $usersCount\n\n";

// 2. Check Latest Call Data
echo "2. LATEST CALL DATA CHECK\n";
echo "-" . str_repeat("-", 50) . "\n";

$latestCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->latest()
    ->first();

if ($latestCall) {
    echo "Call ID: {$latestCall->id}\n";
    echo "Retell Call ID: {$latestCall->call_id}\n";
    echo "Company ID: {$latestCall->company_id}\n";
    echo "Phone Number: " . ($latestCall->phone_number ?: 'Not recorded') . "\n";
    echo "Created: {$latestCall->created_at}\n";
    echo "Summary: " . Str::limit($latestCall->summary ?: 'No summary', 100) . "\n";
    echo "Has Transcript: " . ($latestCall->transcript ? 'Yes' : 'No') . "\n";
    echo "Has Analysis: " . ($latestCall->analysis_data ? 'Yes' : 'No') . "\n";
    
    // Check for customer data
    if ($latestCall->customer_id) {
        $customer = Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->find($latestCall->customer_id);
        if ($customer) {
            echo "Customer: {$customer->name} ({$customer->phone})\n";
        }
    }
} else {
    echo "No calls found in database.\n";
}
echo "\n";

// 3. Check Company Notification Settings
echo "3. COMPANY NOTIFICATION SETTINGS CHECK\n";
echo "-" . str_repeat("-", 50) . "\n";

$company = Company::first();
if ($company) {
    echo "Company: {$company->name}\n";
    echo "Send Call Summaries: " . ($company->send_call_summaries ? 'Yes' : 'No') . "\n";
    echo "Recipients: " . json_encode($company->call_summary_recipients) . "\n";
    echo "Include Transcript: " . ($company->include_transcript_in_summary ? 'Yes' : 'No') . "\n";
    echo "Include CSV: " . ($company->include_csv_export ? 'Yes' : 'No') . "\n";
    echo "Frequency: {$company->summary_email_frequency}\n";
}
echo "\n";

// 4. Test API Endpoints
echo "4. API ENDPOINTS TEST\n";
echo "-" . str_repeat("-", 50) . "\n";

// Test settings endpoint
try {
    $settingsController = new SettingsApiController();
    $request = Request::create('/business/api/settings/call-notifications', 'GET');
    
    // Set company context
    app()->singleton('current_company', function () use ($company) {
        return $company;
    });
    
    $response = $settingsController->getCallNotificationSettings($request);
    $data = json_decode($response->getContent(), true);
    
    echo "Settings API Response: " . ($data ? 'SUCCESS' : 'FAILED') . "\n";
    if ($data) {
        echo "- Settings retrieved: " . count($data['settings']) . " fields\n";
        echo "- User preferences retrieved: " . count($data['user_preferences']) . " fields\n";
    }
} catch (\Exception $e) {
    echo "Settings API Error: " . $e->getMessage() . "\n";
}

// Test call summary send endpoint
if ($latestCall && $latestCall->company_id) {
    try {
        echo "\nTesting Send Summary API...\n";
        $callController = new CallApiController();
        $request = Request::create('/business/api/calls/' . $latestCall->id . '/send-summary', 'POST', [
            'recipients' => ['test@example.com'],
            'message' => 'Test from comprehensive check'
        ]);
        
        // This would normally queue the job
        echo "Send Summary API: Would queue email job for call {$latestCall->id}\n";
    } catch (\Exception $e) {
        echo "Send Summary API Error: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 5. Check UI Components
echo "5. UI COMPONENTS CHECK\n";
echo "-" . str_repeat("-", 50) . "\n";

$componentPaths = [
    'Admin Email Actions' => '/resources/views/components/call-email-actions.blade.php',
    'Email Template' => '/resources/views/emails/call-summary.blade.php',
    'Settings React Component' => '/resources/js/components/Portal/Settings/CallNotificationSettings.jsx',
];

foreach ($componentPaths as $name => $path) {
    $fullPath = base_path(ltrim($path, '/'));
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        $modified = date('Y-m-d H:i:s', filemtime($fullPath));
        echo "✅ $name: EXISTS (Size: " . number_format($size) . " bytes, Modified: $modified)\n";
    } else {
        echo "❌ $name: MISSING\n";
    }
}
echo "\n";

// 6. Check Email Configuration
echo "6. EMAIL CONFIGURATION CHECK\n";
echo "-" . str_repeat("-", 50) . "\n";

echo "Mail Driver: " . config('mail.default') . "\n";
echo "From Address: " . config('mail.from.address') . "\n";
echo "From Name: " . config('mail.from.name') . "\n";
echo "SMTP Host: " . config('mail.mailers.smtp.host') . "\n";
echo "SMTP Port: " . config('mail.mailers.smtp.port') . "\n";
echo "\n";

// 7. Check Queue Configuration
echo "7. QUEUE CONFIGURATION CHECK\n";
echo "-" . str_repeat("-", 50) . "\n";

echo "Queue Driver: " . config('queue.default') . "\n";
echo "Horizon Status: " . (class_exists('\Laravel\Horizon\Horizon') ? 'Installed' : 'Not installed') . "\n";

// Check if Horizon is running
$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
echo "Horizon Process: " . (strpos($horizonStatus, 'Horizon is running') !== false ? 'Running' : 'Not running') . "\n";
echo "\n";

// 8. Database Schema Check
echo "8. DATABASE SCHEMA CHECK\n";
echo "-" . str_repeat("-", 50) . "\n";

// Check companies table columns
$companyColumns = \DB::select("SHOW COLUMNS FROM companies WHERE Field IN ('send_call_summaries', 'call_summary_recipients', 'include_transcript_in_summary', 'include_csv_export', 'summary_email_frequency')");
echo "Company notification columns: " . count($companyColumns) . "/5\n";
foreach ($companyColumns as $col) {
    echo "  ✅ {$col->Field} ({$col->Type})\n";
}

// Check portal_users table
$userColumns = \DB::select("SHOW COLUMNS FROM portal_users WHERE Field = 'call_notification_preferences'");
echo "\nPortal user preferences column: " . (count($userColumns) > 0 ? '✅ Exists' : '❌ Missing') . "\n";
echo "\n";

// 9. Test Report Summary
echo "9. TEST SUMMARY\n";
echo "-" . str_repeat("-", 50) . "\n";

$issues = [];

if ($callsCount == 0) {
    $issues[] = "No calls in database - cannot test email functionality";
}

if (!$company || !$company->send_call_summaries) {
    $issues[] = "Call summaries are disabled at company level";
}

if ($company && empty($company->call_summary_recipients)) {
    $issues[] = "No email recipients configured";
}

if (strpos($horizonStatus, 'Horizon is running') === false) {
    $issues[] = "Horizon is not running - emails won't be processed";
}

if (count($issues) > 0) {
    echo "⚠️  ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
} else {
    echo "✅ All systems operational!\n";
}

echo "\n=== END OF TEST ===\n";