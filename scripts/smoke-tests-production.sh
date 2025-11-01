#!/bin/bash
# ==============================================================================
# Production Smoke Tests with Vite Asset Validation
# ==============================================================================
# Purpose: Verify deployment health + all Vite assets are accessible
# Trigger: After deployment, before marking as success
# Failure: Triggers auto-rollback
# ==============================================================================

set -euo pipefail

DOMAIN="${1:-https://api.askproai.de}"
FAILED=0

echo "🔍 Production Smoke Tests starting..."
echo "Domain: $DOMAIN"
echo ""

# ==============================================================================
# 1. Vite Asset Validation
# ==============================================================================
echo "📦 Vite Asset Validation..."

# Check manifest.json exists
if ! curl -sf "$DOMAIN/build/manifest.json" > /dev/null; then
    echo "❌ Vite manifest.json NOT FOUND"
    FAILED=1
else
    echo "✅ Vite manifest.json found"

    # Parse manifest and check each asset
    MANIFEST=$(curl -s "$DOMAIN/build/manifest.json")

    if command -v jq &> /dev/null; then
        ASSETS=$(echo "$MANIFEST" | jq -r '.[] | .file' 2>/dev/null || echo "")

        if [ -n "$ASSETS" ]; then
            while IFS= read -r ASSET; do
                if [ -n "$ASSET" ]; then
                    if ! curl -sf "$DOMAIN/build/$ASSET" > /dev/null; then
                        echo "❌ Asset NOT FOUND: $ASSET"
                        FAILED=1
                    else
                        echo "   ✓ Asset OK: $ASSET"
                    fi
                fi
            done <<< "$ASSETS"
        else
            echo "⚠️  No assets found in manifest (jq parsing failed)"
        fi
    else
        echo "⚠️  jq not installed, skipping detailed asset validation"
    fi
fi

echo ""

# ==============================================================================
# 2. HTTP Endpoints
# ==============================================================================
echo "🌐 HTTP Endpoint Tests..."

# Homepage
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$DOMAIN/")
if [ "$STATUS" -eq 200 ]; then
    echo "✅ Homepage OK (200)"
else
    echo "❌ Homepage FAILED (Status: $STATUS)"
    FAILED=1
fi

# Health Endpoint
if curl -sf "$DOMAIN/health" > /dev/null; then
    echo "✅ Health endpoint OK"
else
    echo "❌ Health endpoint FAILED"
    FAILED=1
fi

# API Health
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$DOMAIN/api/health")
if [ "$STATUS" -eq 200 ]; then
    echo "✅ API Health OK (200)"
else
    echo "❌ API Health FAILED (Status: $STATUS)"
    FAILED=1
fi

# Admin Login
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$DOMAIN/admin/login")
if [ "$STATUS" -eq 200 ]; then
    echo "✅ Admin login OK (200)"
else
    echo "❌ Admin login FAILED (Status: $STATUS)"
    FAILED=1
fi

echo ""

# ==============================================================================
# 3. Result
# ==============================================================================
if [[ $FAILED -eq 1 ]]; then
    echo "🚨 SMOKE TESTS FAILED - Triggering Rollback!"
    exit 1
fi

echo "✅ All smoke tests passed successfully"
exit 0
