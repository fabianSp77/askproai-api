<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\MCP\NotionMCPServer;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$notion = new NotionMCPServer();

// First, let's search for existing documentation to find the parent page
echo "🔍 Searching for existing documentation pages...\n";

$searchResult = $notion->executeTool('search_pages', [
    'query' => 'AskProAI Documentation'
]);

$parentId = null;

if ($searchResult['success'] && !empty($searchResult['data']['pages'])) {
    echo "✅ Found existing documentation pages:\n";
    foreach ($searchResult['data']['pages'] as $page) {
        echo "  - {$page['title']} (ID: {$page['id']})\n";
        if (stripos($page['title'], 'documentation') !== false) {
            $parentId = $page['id'];
        }
    }
} else {
    echo "ℹ️ No existing documentation found. Will create at root level.\n";
    // You'll need to provide a parent page ID or database ID
    // For now, we'll need to get this from Notion
    echo "\n⚠️ Please provide a parent page ID from your Notion workspace.\n";
    echo "You can find this by:\n";
    echo "1. Opening your Notion workspace\n";
    echo "2. Creating or selecting a parent page for documentation\n";
    echo "3. Copying the page ID from the URL\n";
    echo "   Example: https://notion.so/workspace/Page-Name-{PAGE_ID}\n";
    echo "\nFor now, I'll create a search for 'AskProAI' to find potential parent pages:\n\n";
    
    $searchResult = $notion->executeTool('search_pages', [
        'query' => 'AskProAI'
    ]);
    
    if ($searchResult['success'] && !empty($searchResult['data']['pages'])) {
        echo "Found these pages:\n";
        foreach ($searchResult['data']['pages'] as $page) {
            echo "  - {$page['title']} (ID: {$page['id']})\n";
        }
        echo "\nPlease update the script with one of these page IDs as parentId.\n";
    }
    
    exit(1);
}

if (!$parentId) {
    echo "\n❌ No suitable parent page found. Please create a parent page in Notion first.\n";
    exit(1);
}

echo "\n📝 Creating Stripe Payment System documentation...\n";

// Create main Stripe documentation page
$mainPageResult = $notion->executeTool('create_page', [
    'parent_id' => $parentId,
    'title' => '💳 Stripe Payment System',
    'content' => "# Stripe Payment System Documentation

Welcome to the comprehensive Stripe integration documentation for AskProAI.

## Overview
This documentation covers everything you need to know about our Stripe payment integration, from development setup to production operations.

## Documentation Structure

### 🚀 Developer Setup Guide
Complete guide for setting up Stripe in your development environment.

### 📖 Operations Manual
Day-to-day operations, handling payments, and managing subscriptions.

### 🔧 Troubleshooting Guide
Common issues and their solutions, debugging tips, and error handling.

### 🔒 Security Best Practices
Security guidelines for handling payment data and PCI compliance.

### 📋 Quick Reference
Quick access to common tasks, API endpoints, and configuration options.

## Key Features
- Prepaid balance management
- Automatic top-ups
- Subscription handling
- Invoice generation
- Webhook processing
- Multi-currency support

## Important Links
- [Stripe Dashboard](https://dashboard.stripe.com)
- [API Reference](https://stripe.com/docs/api)
- [Testing Guide](https://stripe.com/docs/testing)"
]);

if (!$mainPageResult['success']) {
    echo "❌ Failed to create main page: {$mainPageResult['error']}\n";
    exit(1);
}

$mainPageId = $mainPageResult['data']['page_id'];
echo "✅ Created main page: {$mainPageResult['data']['title']}\n";
echo "   URL: {$mainPageResult['data']['url']}\n\n";

// Create sub-pages
$subPages = [
    [
        'title' => '🚀 Developer Setup Guide',
        'content' => file_get_contents(__DIR__ . '/STRIPE_SETUP_GUIDE.md')
    ],
    [
        'title' => '📖 Operations Manual',
        'content' => "# Operations Manual

## Daily Operations

### Monitoring Payments
1. Check Stripe Dashboard for new payments
2. Review failed payments in admin panel
3. Monitor webhook status

### Processing Refunds
1. Navigate to Admin > Prepaid Balances
2. Find the transaction
3. Click 'Refund' and confirm

### Managing Subscriptions
- View active subscriptions
- Handle upgrades/downgrades
- Process cancellations

## Webhook Management
- Monitor webhook health
- Handle failed webhooks
- Retry mechanism

## Reporting
- Daily revenue reports
- Failed payment analysis
- Customer payment history"
    ],
    [
        'title' => '🔧 Troubleshooting Guide',
        'content' => "# Troubleshooting Guide

## Common Issues

### Webhook Failures
**Symptom**: Payments not reflecting in system
**Solution**: 
1. Check webhook logs
2. Verify webhook signature
3. Replay failed webhooks

### Payment Failures
**Symptom**: Customer can't complete payment
**Common Causes**:
- Insufficient funds
- Card declined
- 3D Secure required

### Test Mode Issues
**Symptom**: Test payments not working
**Solution**:
1. Verify test API keys
2. Use test card numbers
3. Check test webhook endpoint

## Debugging Tools
```bash
# Check Stripe webhook logs
php artisan stripe:check-webhooks

# Test webhook endpoint
curl -X POST https://api.askproai.de/stripe/webhook \\
  -H 'Content-Type: application/json' \\
  -d @test-webhook.json

# Verify configuration
php test-stripe-config.php
```

## Error Codes
- `payment_intent_authentication_failure`: 3D Secure failed
- `card_declined`: Generic decline
- `insufficient_funds`: Not enough balance"
    ],
    [
        'title' => '🔒 Security Best Practices',
        'content' => "# Security Best Practices

## PCI Compliance
- Never log full card numbers
- Use Stripe.js for card collection
- Implement webhook signatures
- Regular security audits

## API Key Management
- Separate test/live keys
- Rotate keys regularly
- Never commit keys to git
- Use environment variables

## Data Protection
- Encrypt sensitive data
- Implement access controls
- Regular backups
- Audit logging

## Webhook Security
```php
// Always verify webhook signatures
\$endpointSecret = config('services.stripe.webhook_secret');
\$payload = @file_get_contents('php://input');
\$sigHeader = \$_SERVER['HTTP_STRIPE_SIGNATURE'];

try {
    \$event = \\Stripe\\Webhook::constructEvent(
        \$payload, \$sigHeader, \$endpointSecret
    );
} catch(\\Exception \$e) {
    // Invalid signature
    http_response_code(400);
    exit();
}
```"
    ],
    [
        'title' => '📋 Quick Reference',
        'content' => "# Quick Reference

## Test Card Numbers
- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`
- 3D Secure: `4000 0000 0000 3220`

## API Endpoints
- Create Payment Intent: `POST /api/stripe/create-payment-intent`
- Confirm Payment: `POST /api/stripe/confirm-payment`
- Webhook: `POST /stripe/webhook`

## Environment Variables
```bash
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

## Common Commands
```bash
# Check Stripe status
php artisan stripe:status

# Process pending webhooks
php artisan stripe:process-webhooks

# Generate test payment
php test-stripe-payment.php
```

## Useful Links
- [Stripe Dashboard](https://dashboard.stripe.com)
- [Test Cards](https://stripe.com/docs/testing#cards)
- [API Docs](https://stripe.com/docs/api)
- [Webhook Events](https://stripe.com/docs/webhooks/stripe-events)"
    ]
];

echo "Creating sub-pages...\n";

foreach ($subPages as $subPage) {
    $result = $notion->executeTool('create_page', [
        'parent_id' => $mainPageId,
        'title' => $subPage['title'],
        'content' => $subPage['content']
    ]);
    
    if ($result['success']) {
        echo "✅ Created: {$subPage['title']}\n";
        echo "   URL: {$result['data']['url']}\n";
    } else {
        echo "❌ Failed to create {$subPage['title']}: {$result['error']}\n";
    }
}

echo "\n🎉 Stripe documentation created successfully!\n";
echo "Main page URL: " . ($mainPageResult['data']['url'] ?? 'Check Notion') . "\n";