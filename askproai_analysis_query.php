#!/usr/bin/env php
<?php

/**
 * AskProAI Company Setup Complete Analysis
 *
 * This script queries the database to extract all configuration details
 * for the AskProAI company (ID: 15)
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Staff;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   ASKPROAI COMPANY SETUP - COMPLETE ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // 1. COMPANY INFORMATION
    echo "1️⃣  COMPANY INFORMATION\n";
    echo "───────────────────────────────────────────────────────────────\n";

    $company = Company::find(15);

    if (!$company) {
        echo "❌ ERROR: Company ID 15 (AskProAI) not found in database!\n\n";
        exit(1);
    }

    echo "Company ID: {$company->id}\n";
    echo "Company Name: {$company->name}\n";
    echo "Email: {$company->email}\n";
    echo "Phone: {$company->phone}\n";
    echo "Active: " . ($company->is_active ? 'YES' : 'NO') . "\n";
    echo "Cal.com Team ID: {$company->calcom_team_id}\n";
    echo "Cal.com Team Slug: {$company->calcom_team_slug}\n";

    if ($company->settings) {
        echo "\nSettings (JSON):\n";
        echo json_encode(json_decode($company->settings), JSON_PRETTY_PRINT) . "\n";
    }

    echo "\n";

    // 2. PHONE NUMBERS
    echo "2️⃣  PHONE NUMBERS\n";
    echo "───────────────────────────────────────────────────────────────\n";

    $phoneNumbers = PhoneNumber::where('company_id', 15)->get();

    if ($phoneNumbers->isEmpty()) {
        echo "⚠️  No phone numbers found for AskProAI\n";
    } else {
        foreach ($phoneNumbers as $phone) {
            echo "\nPhone Number: {$phone->number}\n";
            echo "  ID: {$phone->id}\n";
            echo "  Description: " . ($phone->description ?? 'N/A') . "\n";
            echo "  Type: {$phone->type}\n";
            echo "  Active: " . ($phone->is_active ? 'YES' : 'NO') . "\n";
            echo "  Primary: " . ($phone->is_primary ? 'YES' : 'NO') . "\n";
            echo "  Branch ID: " . ($phone->branch_id ?? 'NULL') . "\n";
            echo "  Retell Phone ID: " . ($phone->retell_phone_id ?? 'N/A') . "\n";
            echo "  Retell Agent ID: " . ($phone->retell_agent_id ?? 'N/A') . "\n";
            echo "  Agent ID: " . ($phone->agent_id ?? 'N/A') . "\n";
        }
    }

    echo "\n\n";

    // 3. CHECK FOR SPECIFIC AGENT
    echo "3️⃣  RETELL AGENT CHECK\n";
    echo "───────────────────────────────────────────────────────────────\n";

    $targetAgent = 'agent_616d645570ae613e421edb98e7';
    echo "Searching for agent: {$targetAgent}\n\n";

    $phoneWithAgent = PhoneNumber::where('retell_agent_id', $targetAgent)
        ->orWhere('agent_id', $targetAgent)
        ->first();

    if ($phoneWithAgent) {
        echo "✅ FOUND! Agent {$targetAgent} assigned to:\n";
        echo "   Phone Number: {$phoneWithAgent->number}\n";
        echo "   Company ID: {$phoneWithAgent->company_id}\n";
    } else {
        echo "❌ Agent {$targetAgent} NOT found in phone_numbers table\n";
    }

    // Check all retell agents in phone_numbers
    echo "\nAll Retell Agents in phone_numbers:\n";
    $allAgents = DB::table('phone_numbers')
        ->select('retell_agent_id', 'agent_id', 'number', 'company_id')
        ->whereNotNull('retell_agent_id')
        ->orWhereNotNull('agent_id')
        ->get();

    foreach ($allAgents as $agent) {
        echo "  - Agent: " . ($agent->retell_agent_id ?? $agent->agent_id) . "\n";
        echo "    Phone: {$agent->number}\n";
        echo "    Company: {$agent->company_id}\n\n";
    }

    echo "\n";

    // 4. SERVICES
    echo "4️⃣  SERVICES CONFIGURATION\n";
    echo "───────────────────────────────────────────────────────────────\n";

    $services = Service::where('company_id', 15)
        ->orderBy('priority', 'asc')
        ->get();

    if ($services->isEmpty()) {
        echo "⚠️  No services found for AskProAI\n";
    } else {
        echo "Total Services: " . $services->count() . "\n\n";

        foreach ($services as $service) {
            echo "Service ID: {$service->id}\n";
            echo "  Name: {$service->name}\n";
            echo "  Duration: " . ($service->duration_minutes ?? $service->duration ?? 'N/A') . " minutes\n";
            echo "  Price: €" . number_format($service->price, 2) . "\n";
            echo "  Active: " . ($service->is_active ? 'YES ✅' : 'NO ❌') . "\n";
            echo "  Default: " . ($service->is_default ?? false ? 'YES ✅' : 'NO') . "\n";
            echo "  Priority: " . ($service->priority ?? 'N/A') . "\n";
            echo "  Cal.com Event Type ID: " . ($service->calcom_event_type_id ?? 'NOT SET') . "\n";
            echo "  Branch ID: " . ($service->branch_id ?? 'NULL (company-wide)') . "\n";

            if ($service->is_default ?? false) {
                echo "  >>> DEFAULT SERVICE <<<\n";
            }

            echo "\n";
        }

        // Highlight 15 and 30 minute services
        echo "─── SPECIFIC CONSULTATION SERVICES ───\n\n";

        $service15 = $services->first(function($s) {
            return str_contains(strtolower($s->name), '15') &&
                   str_contains(strtolower($s->name), 'minuten');
        });

        if ($service15) {
            echo "📌 15-MINUTE SERVICE:\n";
            echo "   ID: {$service15->id}\n";
            echo "   Name: {$service15->name}\n";
            echo "   Event Type ID: {$service15->calcom_event_type_id}\n";
            echo "   Default: " . ($service15->is_default ?? false ? 'YES' : 'NO') . "\n";
            echo "   Active: " . ($service15->is_active ? 'YES' : 'NO') . "\n\n";
        } else {
            echo "⚠️  No 15-minute service found\n\n";
        }

        $service30 = $services->first(function($s) {
            return str_contains(strtolower($s->name), '30') &&
                   str_contains(strtolower($s->name), 'minuten');
        });

        if (!$service30) {
            // Try alternative search
            $service30 = $services->first(function($s) {
                return str_contains(strtolower($s->name), 'beratung') &&
                       ($s->is_default ?? false);
            });
        }

        if ($service30) {
            echo "📌 30-MINUTE SERVICE (or DEFAULT):\n";
            echo "   ID: {$service30->id}\n";
            echo "   Name: {$service30->name}\n";
            echo "   Event Type ID: {$service30->calcom_event_type_id}\n";
            echo "   Default: " . ($service30->is_default ?? false ? 'YES' : 'NO') . "\n";
            echo "   Active: " . ($service30->is_active ? 'YES' : 'NO') . "\n\n";
        } else {
            echo "⚠️  No 30-minute service found\n\n";
        }
    }

    echo "\n";

    // 5. BRANCHES
    echo "5️⃣  BRANCHES\n";
    echo "───────────────────────────────────────────────────────────────\n";

    $branches = Branch::where('company_id', 15)->get();

    if ($branches->isEmpty()) {
        echo "⚠️  No branches found for AskProAI\n";
    } else {
        echo "Total Branches: " . $branches->count() . "\n\n";

        foreach ($branches as $branch) {
            echo "Branch ID: {$branch->id}\n";
            echo "  Name: {$branch->name}\n";
            echo "  Address: " . ($branch->address ?? 'N/A') . "\n";
            echo "  Phone: " . ($branch->phone_number ?? 'N/A') . "\n";
            echo "  Email: " . ($branch->notification_email ?? 'N/A') . "\n";
            echo "  Active: " . ($branch->is_active ? 'YES' : 'NO') . "\n";
            echo "\n";
        }
    }

    echo "\n";

    // 6. STAFF
    echo "6️⃣  STAFF MEMBERS\n";
    echo "───────────────────────────────────────────────────────────────\n";

    $staff = Staff::whereHas('branch', function($query) {
        $query->where('company_id', 15);
    })->get();

    if ($staff->isEmpty()) {
        echo "⚠️  No staff members found for AskProAI\n";
    } else {
        echo "Total Staff: " . $staff->count() . "\n\n";

        foreach ($staff as $member) {
            echo "Staff ID: {$member->id}\n";
            echo "  Name: {$member->name}\n";
            echo "  Email: {$member->email}\n";
            echo "  Phone: " . ($member->phone ?? 'N/A') . "\n";
            echo "  Branch ID: {$member->branch_id}\n";
            echo "  Active: " . ($member->is_active ? 'YES' : 'NO') . "\n";
            echo "  Cal.com User ID: " . ($member->calcom_user_id ?? 'NOT LINKED') . "\n";
            echo "\n";
        }
    }

    echo "\n";

    // 7. RETELL AGENTS TABLE (if exists)
    echo "7️⃣  RETELL AGENTS TABLE\n";
    echo "───────────────────────────────────────────────────────────────\n";

    if (DB::getSchemaBuilder()->hasTable('retell_agents')) {
        $retellAgents = DB::table('retell_agents')
            ->where('company_id', 15)
            ->get();

        if ($retellAgents->isEmpty()) {
            echo "⚠️  No retell agents found in retell_agents table for AskProAI\n";
        } else {
            foreach ($retellAgents as $agent) {
                echo "Agent ID: {$agent->agent_id}\n";
                echo "  Name: " . ($agent->name ?? 'N/A') . "\n";
                echo "  Company ID: {$agent->company_id}\n";
                echo "  Created: {$agent->created_at}\n";
                echo "\n";
            }
        }
    } else {
        echo "ℹ️  retell_agents table does not exist\n";
    }

    echo "\n";

    // 8. RETELL AGENT PROMPTS TABLE (if exists)
    echo "8️⃣  RETELL AGENT PROMPTS TABLE\n";
    echo "───────────────────────────────────────────────────────────────\n";

    if (DB::getSchemaBuilder()->hasTable('retell_agent_prompts')) {
        $retellPrompts = DB::table('retell_agent_prompts')
            ->join('retell_agents', 'retell_agent_prompts.agent_id', '=', 'retell_agents.agent_id')
            ->where('retell_agents.company_id', 15)
            ->select('retell_agent_prompts.*')
            ->get();

        if ($retellPrompts->isEmpty()) {
            echo "⚠️  No retell agent prompts found for AskProAI\n";
        } else {
            foreach ($retellPrompts as $prompt) {
                echo "Prompt ID: {$prompt->id}\n";
                echo "  Agent ID: {$prompt->agent_id}\n";
                echo "  Version: " . ($prompt->version ?? 'N/A') . "\n";
                echo "  Created: {$prompt->created_at}\n";
                echo "\n";
            }
        }
    } else {
        echo "ℹ️  retell_agent_prompts table does not exist\n";
    }

    echo "\n";

    // 9. SUMMARY & CONFIGURATION ISSUES
    echo "9️⃣  CONFIGURATION SUMMARY & ISSUES\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $issues = [];
    $warnings = [];

    // Check for missing data
    if ($phoneNumbers->isEmpty()) {
        $issues[] = "❌ No phone numbers configured";
    }

    if ($services->isEmpty()) {
        $issues[] = "❌ No services configured";
    } else {
        $defaultServices = $services->where('is_default', true);
        if ($defaultServices->isEmpty()) {
            $warnings[] = "⚠️  No default service set";
        } elseif ($defaultServices->count() > 1) {
            $warnings[] = "⚠️  Multiple default services set (" . $defaultServices->count() . ")";
        }

        $servicesWithoutEventType = $services->whereNull('calcom_event_type_id')->where('is_active', true);
        if ($servicesWithoutEventType->count() > 0) {
            $warnings[] = "⚠️  " . $servicesWithoutEventType->count() . " active services without Cal.com Event Type ID";
        }
    }

    if ($branches->isEmpty()) {
        $warnings[] = "⚠️  No branches configured";
    }

    if (!$company->calcom_team_id) {
        $issues[] = "❌ Cal.com Team ID not set";
    }

    // Display issues
    if (!empty($issues)) {
        echo "CRITICAL ISSUES:\n";
        foreach ($issues as $issue) {
            echo "  {$issue}\n";
        }
        echo "\n";
    }

    if (!empty($warnings)) {
        echo "WARNINGS:\n";
        foreach ($warnings as $warning) {
            echo "  {$warning}\n";
        }
        echo "\n";
    }

    if (empty($issues) && empty($warnings)) {
        echo "✅ No configuration issues detected!\n\n";
    }

    // Configuration summary
    echo "CONFIGURATION SUMMARY:\n";
    echo "  Company: {$company->name} (ID: {$company->id})\n";
    echo "  Phone Numbers: " . $phoneNumbers->count() . "\n";
    echo "  Services: " . $services->count() . " (" . $services->where('is_active', true)->count() . " active)\n";
    echo "  Branches: " . $branches->count() . "\n";
    echo "  Staff: " . $staff->count() . "\n";
    echo "  Cal.com Team: " . ($company->calcom_team_id ?? 'NOT SET') . "\n";

    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ANALYSIS COMPLETE\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}
