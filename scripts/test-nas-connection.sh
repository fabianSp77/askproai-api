#!/bin/bash
# ==============================================================================
# NAS Connection Test Script
# ==============================================================================
# Quick test to check if Synology NAS is reachable
# ==============================================================================

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "======================================"
echo "🔍 Synology NAS Connection Test"
echo "======================================"
echo ""

# Configuration
NAS_HOST="fs-cloud1977.synology.me"
NAS_IP="212.86.60.237"
NAS_PORT="50222"
SSH_KEY="/root/.ssh/synology_backup_key"

# Test 1: DNS Resolution
echo "1️⃣  DNS Resolution Test"
if host "$NAS_HOST" > /dev/null 2>&1; then
    IP=$(getent hosts "$NAS_HOST" | awk '{print $1}')
    echo -e "   ${GREEN}✅ DNS OK${NC}: $NAS_HOST → $IP"
else
    echo -e "   ${RED}❌ DNS FAILED${NC}"
fi
echo ""

# Test 2: Network Ping
echo "2️⃣  Network Ping Test (3 attempts)"
if ping -c 3 -W 2 "$NAS_HOST" > /dev/null 2>&1; then
    echo -e "   ${GREEN}✅ PING OK${NC}: NAS is reachable"
else
    echo -e "   ${RED}❌ PING FAILED${NC}: 100% packet loss"
fi
echo ""

# Test 3: Port Check
echo "3️⃣  SSH Port Test (Port $NAS_PORT)"
if timeout 5 bash -c "cat < /dev/null > /dev/tcp/$NAS_IP/$NAS_PORT" 2>/dev/null; then
    echo -e "   ${GREEN}✅ PORT OK${NC}: Port $NAS_PORT is open"
else
    echo -e "   ${RED}❌ PORT CLOSED${NC}: Port $NAS_PORT timeout/blocked"
fi
echo ""

# Test 4: SSH Connection
echo "4️⃣  SSH Authentication Test"
if ssh -i "$SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -o ConnectTimeout=5 \
    -p "$NAS_PORT" \
    "AskProAI@${NAS_HOST}" \
    "echo OK" > /dev/null 2>&1; then
    echo -e "   ${GREEN}✅ SSH OK${NC}: Authentication successful"

    # Get NAS info
    NAS_INFO=$(ssh -i "$SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$NAS_PORT" \
        "AskProAI@${NAS_HOST}" \
        "hostname && uptime" 2>/dev/null)
    echo "   📊 NAS Info:"
    echo "$NAS_INFO" | sed 's/^/      /'
else
    echo -e "   ${RED}❌ SSH FAILED${NC}: Cannot connect"
fi
echo ""

# Summary
echo "======================================"
echo "📊 Summary"
echo "======================================"

if ssh -i "$SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -o ConnectTimeout=5 \
    -p "$NAS_PORT" \
    "AskProAI@${NAS_HOST}" \
    "echo OK" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ NAS IS ONLINE${NC}"
    echo "   Backup system will work in NORMAL MODE"
    echo "   Next backup will upload to NAS"
    exit 0
else
    echo -e "${RED}❌ NAS IS OFFLINE${NC}"
    echo "   Backup system will work in DEGRADED MODE"
    echo "   Backups will be LOCAL ONLY"
    exit 1
fi
