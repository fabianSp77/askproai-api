#!/bin/bash
###############################################################################
# AGENT RESTORATION VERIFICATION SCRIPT
# Purpose: Verify Retell Agent V117 has been restored to working state
# Created: 2025-10-19 22:35 UTC
# Usage: bash VERIFY_AGENT_RESTORATION_2025_10_19.sh
###############################################################################

set -e

echo "🔍 AGENT RESTORATION VERIFICATION"
echo "================================================"
echo ""

cd /var/www/api-gateway

# Check database state
echo "📊 DATABASE STATUS:"
php artisan tinker --execute="
\$agent = \DB::table('retell_agents')
  ->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')
  ->first();
echo 'Version: ' . \$agent->version . PHP_EOL;
echo 'Sync Status: ' . \$agent->sync_status . PHP_EOL;
echo 'Last Synced: ' . \$agent->last_synced_at . PHP_EOL;
echo 'Published: ' . (\$agent->is_published ? 'YES' : 'NO') . PHP_EOL;
" 2>/dev/null

echo ""

# Check last 3 calls
echo "📞 LAST 3 CALLS:"
php artisan tinker --execute="
\$calls = \DB::table('calls')
  ->orderBy('created_at', 'desc')
  ->limit(3)
  ->get(['id', 'call_id', 'created_at', 'call_time', 'call_successful', 'appointment_made', 'agent_version', 'llm_token_usage', 'disconnection_reason']);

foreach(\$calls as \$i => \$call) {
  echo PHP_EOL . '─────────────────────────────────────────' . PHP_EOL;
  echo 'Call ' . (\$i + 1) . ' (ID: ' . \$call->id . ')' . PHP_EOL;
  echo 'Call ID: ' . \$call->call_id . PHP_EOL;
  echo 'Time: ' . \$call->created_at . PHP_EOL;
  echo 'Duration: ' . \$call->call_time . 's' . PHP_EOL;
  echo 'Agent Version: ' . (\$call->agent_version ?? 'NULL') . PHP_EOL;

  \$tokenUsage = json_decode(\$call->llm_token_usage, true);
  \$llmRequests = \$tokenUsage['num_requests'] ?? 0;
  echo 'LLM Requests: ' . \$llmRequests;

  if (\$llmRequests == 1) {
    echo ' ❌ FROZEN (only greeting)' . PHP_EOL;
  } elseif (\$llmRequests >= 3) {
    echo ' ✅ HEALTHY (multi-turn)' . PHP_EOL;
  } else {
    echo ' ⚠️  SUSPICIOUS (low requests)' . PHP_EOL;
  }

  echo 'Success: ' . (\$call->call_successful ? '✅ YES' : '❌ NO') . PHP_EOL;
  echo 'Appointment Made: ' . (\$call->appointment_made ? '✅ YES' : '❌ NO') . PHP_EOL;
  echo 'Disconnection: ' . (\$call->disconnection_reason ?? 'N/A') . PHP_EOL;
}
" 2>/dev/null

echo ""
echo "================================================"
echo ""

# Analysis
echo "🎯 HEALTH ASSESSMENT:"
php artisan tinker --execute="
\$recentCalls = \DB::table('calls')
  ->where('created_at', '>=', now()->subHour())
  ->get(['call_successful', 'llm_token_usage', 'disconnection_reason']);

\$totalCalls = \$recentCalls->count();
if (\$totalCalls == 0) {
  echo 'No calls in last hour - cannot assess health' . PHP_EOL;
  exit;
}

\$successfulCalls = \$recentCalls->where('call_successful', true)->count();
\$frozenCalls = \$recentCalls->filter(function(\$call) {
  \$tokenUsage = json_decode(\$call->llm_token_usage, true);
  return isset(\$tokenUsage['num_requests']) && \$tokenUsage['num_requests'] == 1;
})->count();

\$userHangups = \$recentCalls->where('disconnection_reason', 'user_hangup')->count();

echo 'Total Calls (last hour): ' . \$totalCalls . PHP_EOL;
echo 'Successful: ' . \$successfulCalls . ' (' . round((\$successfulCalls / \$totalCalls) * 100) . '%)' . PHP_EOL;
echo 'Frozen (1 LLM request): ' . \$frozenCalls . ' (' . round((\$frozenCalls / \$totalCalls) * 100) . '%)' . PHP_EOL;
echo 'User Hangups: ' . \$userHangups . ' (' . round((\$userHangups / \$totalCalls) * 100) . '%)' . PHP_EOL;
echo PHP_EOL;

// Health verdict
if (\$frozenCalls > 0) {
  echo '❌ AGENT STILL BROKEN - Frozen calls detected' . PHP_EOL;
  echo '   Action: Re-run emergency restoration script' . PHP_EOL;
} elseif (\$successfulCalls / \$totalCalls >= 0.5) {
  echo '✅ AGENT HEALTHY - Success rate >= 50%' . PHP_EOL;
} elseif (\$userHangups / \$totalCalls >= 0.5) {
  echo '⚠️  AGENT SUSPICIOUS - High user hangup rate' . PHP_EOL;
  echo '   Action: Investigate call quality issues' . PHP_EOL;
} else {
  echo '⚠️  AGENT NEEDS MONITORING - Collecting more data' . PHP_EOL;
}
" 2>/dev/null

echo ""
echo "================================================"
echo ""
echo "NEXT STEPS:"
echo "1. If agent still broken: Re-run EMERGENCY_RESTORATION_SCRIPT_2025_10_19.sh"
echo "2. If agent healthy: Make 3 more test calls to confirm stability"
echo "3. Continue monitoring: Run this script every 15 minutes"
echo ""
echo "MAKE TEST CALL: +493083793369"
echo "Expected: Agent responds after greeting (not silence)"
echo ""
