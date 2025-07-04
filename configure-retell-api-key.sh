#!/bin/bash

# Configure Retell API Key Script
# This script helps configure the Retell API key for webhook signature verification

echo "🔐 Retell API Key Configuration"
echo "==============================="
echo ""
echo "This script will help you configure the Retell API key for webhook signature verification."
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "Please create .env from .env.example first."
    exit 1
fi

# Function to add or update env variable
update_env() {
    local key=$1
    local value=$2
    
    if grep -q "^${key}=" .env; then
        # Update existing
        sed -i "s|^${key}=.*|${key}=${value}|" .env
        echo "✅ Updated ${key}"
    else
        # Add new
        echo "" >> .env
        echo "${key}=${value}" >> .env
        echo "✅ Added ${key}"
    fi
}

# Get current values if they exist
CURRENT_API_KEY=$(grep "^RETELL_TOKEN=" .env | cut -d'=' -f2 || echo "")
CURRENT_AGENT_ID=$(grep "^DEFAULT_RETELL_AGENT_ID=" .env | cut -d'=' -f2 || echo "")

echo "Current Configuration:"
echo "API Key: ${CURRENT_API_KEY:-[NOT SET]}"
echo "Agent ID: ${CURRENT_AGENT_ID:-[NOT SET]}"
echo ""

# Prompt for API key
echo "Enter your Retell API key (starts with 'key_'):"
read -r API_KEY

if [ -z "$API_KEY" ]; then
    echo "❌ Error: API key cannot be empty!"
    exit 1
fi

if [[ ! "$API_KEY" =~ ^key_ ]]; then
    echo "⚠️  Warning: API key should start with 'key_'"
    echo "Continue anyway? (y/n)"
    read -r CONTINUE
    if [ "$CONTINUE" != "y" ]; then
        exit 1
    fi
fi

# Prompt for Agent ID
echo ""
echo "Enter your default Retell Agent ID (optional):"
read -r AGENT_ID

echo ""
echo "📝 Updating .env file..."

# Update all Retell-related env vars
update_env "RETELL_TOKEN" "$API_KEY"
update_env "DEFAULT_RETELL_API_KEY" "\${RETELL_TOKEN}"
update_env "RETELL_WEBHOOK_SECRET" "\${RETELL_TOKEN}"
update_env "RETELL_BASE_URL" "https://api.retellai.com"
update_env "RETELL_BASE" "https://api.retellai.com"

if [ -n "$AGENT_ID" ]; then
    update_env "DEFAULT_RETELL_AGENT_ID" "$AGENT_ID"
fi

echo ""
echo "🗄️  Updating database..."

# Update company record
mysql -u root -p'V9LGz2tdR5gpDQz' -e "
UPDATE askproai_db.companies 
SET retell_api_key = '$API_KEY'
WHERE id = 1;
" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✅ Database updated"
else
    echo "❌ Failed to update database. You may need to update manually."
fi

echo ""
echo "🧹 Clearing caches..."

php artisan config:clear
php artisan cache:clear

echo ""
echo "✅ Configuration complete!"
echo ""
echo "📋 Next Steps:"
echo "1. Test webhook signature verification:"
echo "   curl -X POST http://localhost/api/retell/webhook \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -H 'X-Retell-Signature: test' \\"
echo "     -d '{\"event\":\"test\"}'"
echo ""
echo "2. Register webhook URL in Retell dashboard:"
echo "   https://api.askproai.de/api/retell/webhook"
echo ""
echo "3. Monitor logs for webhook activity:"
echo "   tail -f storage/logs/laravel.log | grep -i retell"
echo ""

# Create test script
cat > test-retell-webhook.php << 'EOF'
<?php
// Test Retell Webhook Signature

require_once 'vendor/autoload.php';

$apiKey = env('RETELL_TOKEN');
if (!$apiKey) {
    die("Error: RETELL_TOKEN not configured in .env\n");
}

// Test payload
$payload = json_encode([
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_' . uniqid(),
        'from_number' => '+1234567890',
        'to_number' => '+0987654321',
        'duration' => 120
    ]
]);

// Generate signature (method 1: body only)
$signature = hash_hmac('sha256', $payload, $apiKey);

echo "Test Webhook Request:\n";
echo "====================\n";
echo "URL: https://api.askproai.de/api/retell/webhook\n";
echo "Header: X-Retell-Signature: $signature\n";
echo "Body: $payload\n";
echo "\n";

// Generate signature (method 2: with timestamp)
$timestamp = time() * 1000; // milliseconds
$payloadWithTimestamp = "$timestamp.$payload";
$signatureWithTimestamp = hash_hmac('sha256', $payloadWithTimestamp, $apiKey);

echo "With Timestamp:\n";
echo "Header: X-Retell-Signature: v=$timestamp,d=$signatureWithTimestamp\n";
echo "\n";

echo "Test with curl:\n";
echo "curl -X POST https://api.askproai.de/api/retell/webhook \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'X-Retell-Signature: $signature' \\\n";
echo "  -d '$payload'\n";
EOF

echo "✅ Created test-retell-webhook.php for testing signatures"