<?php

/**
 * Script to find and display cal.com Team ID
 * This script helps you identify the correct Team ID to use in your company settings
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "════════════════════════════════════════════════════════\n";
echo "           📋 Cal.com Team ID Finder\n";
echo "════════════════════════════════════════════════════════\n\n";

// Get API Key from config or environment
$apiKey = config('services.calcom.api_key') ?? env('CALCOM_API_KEY');

if (!$apiKey) {
    echo "❌ ERROR: No cal.com API key found!\n";
    echo "   Please set CALCOM_API_KEY in your .env file\n\n";
    exit(1);
}

echo "🔑 Using API Key: " . substr($apiKey, 0, 15) . "...\n\n";

// Step 1: Try to fetch teams using V1 API
echo "📡 Fetching teams from cal.com API...\n";
echo "────────────────────────────────────────\n";

$v1Url = 'https://api.cal.com/v1/teams?apiKey=' . $apiKey;

try {
    $response = Http::timeout(10)->get($v1Url);

    if ($response->successful()) {
        $data = $response->json();
        $teams = $data['teams'] ?? [];

        if (empty($teams)) {
            echo "⚠️  No teams found for this API key.\n\n";
            echo "This could mean:\n";
            echo "  1. The API key doesn't have access to any teams\n";
            echo "  2. You need to create a team first at cal.com\n";
            echo "  3. The API key might be personal, not team-based\n\n";
        } else {
            echo "✅ Found " . count($teams) . " team(s):\n\n";

            foreach ($teams as $team) {
                echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
                echo "🏢 Team Name: " . ($team['name'] ?? 'Unknown') . "\n";
                echo "🆔 Team ID:   " . ($team['id'] ?? 'Unknown') . " ← USE THIS IN ADMIN PANEL\n";
                echo "🔗 Slug:      " . ($team['slug'] ?? 'N/A') . "\n";

                if (isset($team['members'])) {
                    echo "👥 Members:   " . count($team['members']) . "\n";
                }

                echo "\n";
            }
        }
    } else {
        echo "❌ API Request failed with status: " . $response->status() . "\n";

        $error = $response->json();
        if (isset($error['message'])) {
            echo "   Error: " . $error['message'] . "\n";
        }

        echo "\n";
        echo "Possible issues:\n";
        echo "  • Invalid API key\n";
        echo "  • API key doesn't have team access\n";
        echo "  • Network/firewall issues\n\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Step 2: Instructions for setting Team ID
echo "════════════════════════════════════════════════════════\n";
echo "📝 HOW TO SET THE TEAM ID:\n";
echo "════════════════════════════════════════════════════════\n\n";

echo "1. **In Admin Panel (Web Interface):**\n";
echo "   • Go to: Unternehmen (Companies)\n";
echo "   • Click on: AskProAI (or Edit button)\n";
echo "   • Navigate to: Tab \"Integration\"\n";
echo "   • Find section: \"Cal.com Integration\"\n";
echo "   • Enter the Team ID in: \"Cal.com Team ID\" field\n";
echo "   • Save the company\n\n";

echo "2. **Via Database (Direct SQL):**\n";
echo "   ```sql\n";
echo "   UPDATE companies \n";
echo "   SET calcom_team_id = YOUR_TEAM_ID \n";
echo "   WHERE id = 15;  -- For AskProAI\n";
echo "   ```\n\n";

echo "3. **After Setting Team ID:**\n";
echo "   • Go back to Company in Admin Panel\n";
echo "   • Click Actions → \"Team Event Types synchronisieren\"\n";
echo "   • This will import all event types from the team\n\n";

echo "════════════════════════════════════════════════════════\n";
echo "📌 FINDING TEAM ID IN CAL.COM:\n";
echo "════════════════════════════════════════════════════════\n\n";

echo "If no teams were found above, you can find it manually:\n\n";
echo "1. Login to cal.com\n";
echo "2. Go to: Settings → Teams\n";
echo "3. Click on your team\n";
echo "4. Look at the URL:\n";
echo "   https://app.cal.com/settings/teams/XXXXX/profile\n";
echo "                                      ^^^^^\n";
echo "                                   This is your Team ID\n\n";

echo "════════════════════════════════════════════════════════\n\n";