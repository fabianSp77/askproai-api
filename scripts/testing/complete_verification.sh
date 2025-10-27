#!/bin/bash

# Complete Verification Script for V54 Deployment
# Usage: ./scripts/testing/complete_verification.sh [--after-call]

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "V54 DEPLOYMENT - COMPLETE VERIFICATION"
echo "═══════════════════════════════════════════════════════════"
echo ""

AFTER_CALL=false

if [ "$1" == "--after-call" ]; then
    AFTER_CALL=true
fi

# Step 1: Check if V54 is ready
echo "STEP 1: Checking if V54 is published & phone is mapped..."
echo "-----------------------------------------------------------"
echo ""

php scripts/testing/verify_v54_ready.php

V54_READY=$?

if [ $V54_READY -ne 0 ]; then
    echo ""
    echo "🔴 STOP: V54 is not ready yet!"
    echo ""
    echo "Please complete these dashboard actions first:"
    echo "  1. Publish Version 54 in Retell Dashboard"
    echo "  2. Map +493033081738 to agent_f1ce85d06a84afb989dfbb16a9"
    echo ""
    echo "Then run this script again."
    echo ""
    exit 1
fi

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "✅ V54 IS READY!"
echo "═══════════════════════════════════════════════════════════"
echo ""

if [ "$AFTER_CALL" = false ]; then
    echo "NEXT STEP: Make a test call"
    echo ""
    echo "Call: +493033081738"
    echo "Say: 'Ich möchte einen Herrenhaarschnitt morgen um 14 Uhr'"
    echo ""
    echo "After the call, run:"
    echo "  ./scripts/testing/complete_verification.sh --after-call"
    echo ""
    exit 0
fi

# Step 2: Check latest call (only if --after-call)
echo ""
echo "STEP 2: Checking latest call for function execution..."
echo "-----------------------------------------------------------"
echo ""

php scripts/testing/check_latest_call_success.php

CALL_SUCCESS=$?

echo ""

if [ $CALL_SUCCESS -eq 0 ]; then
    echo "═══════════════════════════════════════════════════════════"
    echo "🎉 COMPLETE SUCCESS!"
    echo "═══════════════════════════════════════════════════════════"
    echo ""
    echo "All systems GO:"
    echo "  ✅ Version 54 is published"
    echo "  ✅ Phone is mapped correctly"
    echo "  ✅ check_availability WAS CALLED"
    echo "  ✅ Fix successful: 0% → 100%"
    echo ""
    echo "Mission accomplished! 🚀"
    echo ""
    exit 0
else
    echo "═══════════════════════════════════════════════════════════"
    echo "❌ VERIFICATION FAILED"
    echo "═══════════════════════════════════════════════════════════"
    echo ""
    echo "Please review the errors above and:"
    echo "  1. Check dashboard that correct version is published"
    echo "  2. Check phone mapping is correct"
    echo "  3. Make another test call if needed"
    echo ""
    exit 1
fi
