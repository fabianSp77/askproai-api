<?php

/**
 * Diagnose Webhook Structure - What does Retell actually send?
 *
 * Based on analysis of call_86ba8c303e902256e5d31f065d0
 */

echo "🔍 WEBHOOK STRUCTURE DIAGNOSIS\n";
echo str_repeat('=', 80) . "\n\n";

echo "PROBLEM IDENTIFIED:\n";
echo str_repeat('-', 80) . "\n\n";

echo "❌ Current Implementation (getCanonicalCallId):\n";
echo "   \$callIdFromWebhook = \$request->input('call.call_id');\n";
echo "   \$callIdFromArgs = \$request->input('args.call_id');\n\n";

echo "✅ What Retell Actually Sends:\n";
echo "   Function call webhook structure:\n";
echo "   {\n";
echo "     \"call_id\": \"call_xxx\",  // ✅ ROOT LEVEL!\n";
echo "     \"args\": {\n";
echo "       \"name\": \"Hans\",\n";
echo "       \"call_id\": \"\"  // ❌ Empty from agent\n";
echo "     }\n";
echo "   }\n\n";

echo "🔧 REQUIRED FIX:\n";
echo str_repeat('-', 80) . "\n\n";

echo "Change in RetellFunctionCallHandler.php getCanonicalCallId():\n\n";

echo "OLD (Line 83):\n";
echo "   \$callIdFromWebhook = \$request->input('call.call_id');  ❌\n\n";

echo "NEW (Line 83):\n";
echo "   \$callIdFromWebhook = \$request->input('call_id');  ✅\n\n";

echo "EVIDENCE:\n";
echo str_repeat('-', 80) . "\n\n";

echo "From call_86ba8c303e902256e5d31f065d0 end-call webhook:\n";
echo "   \"call_id\": \"call_86ba8c303e902256e5d31f065d0\",  ✅ Root level\n";
echo "   \"tool_call_invocation\": {\n";
echo "     \"arguments\": \"{\\\"call_id\\\":\\\"\\\"}\"  ❌ Agent sent empty\n";
echo "   }\n\n";

echo "WHY THIS HAPPENED:\n";
echo str_repeat('-', 80) . "\n\n";

echo "1. ❌ We assumed webhook sends nested object: { \"call\": { \"call_id\": \"...\" } }\n";
echo "2. ❌ We tried {{call.call_id}} and {{call_id}} in agent - both empty\n";
echo "3. ✅ TRUTH: Webhook sends flat structure: { \"call_id\": \"...\" }\n";
echo "4. ✅ Controller should read from root: \$request->input('call_id')\n\n";

echo "IMPACT:\n";
echo str_repeat('-', 80) . "\n\n";

echo "✅ After Fix:\n";
echo "   - getCanonicalCallId() will extract call_id from webhook root\n";
echo "   - Backend will inject it into args (lines 4773, 4819)\n";
echo "   - All function calls will have valid call_id\n";
echo "   - Availability checks will succeed\n";
echo "   - Bookings will work\n\n";

echo str_repeat('=', 80) . "\n";
echo "NEXT STEP: Apply fix to RetellFunctionCallHandler.php\n";
echo str_repeat('=', 80) . "\n";
