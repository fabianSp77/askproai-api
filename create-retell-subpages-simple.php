<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $notionMCP = new \App\Services\MCP\NotionMCPServer();
    
    // Parent page ID for the main Retell.ai Integration page we just created
    $parentId = '22caba11-76e2-8114-a6b8-f13896c8fd38';
    
    echo "📝 Creating Retell.ai sub-pages...\n\n";
    
    // Create Operation Manual page
    $operationsContent = "# Operations Manual\n\n## Daily Tasks\n\n### Morning Checks (9:00 AM)\n1. Check Horizon status: `php artisan horizon:status`\n2. Review overnight calls in admin dashboard\n3. Check for failed webhooks\n4. Monitor error rates\n\n### Throughout the Day\n- Monitor live calls widget\n- Check webhook processing queue\n- Review appointment creation success rate\n\n### End of Day (6:00 PM)\n1. Generate daily report\n2. Check for stuck calls\n3. Review error logs\n4. Backup call recordings\n\n## Weekly Tasks\n\n### Monday\n- Analyze call patterns from previous week\n- Update agent prompts based on feedback\n- Test all custom functions\n\n### Wednesday\n- Review webhook performance metrics\n- Check API rate limits\n- Update documentation\n\n### Friday\n- Full system test\n- Review and clear old logs\n- Plan improvements for next week\n\n## Monthly Tasks\n\n### First Monday\n- Full security audit\n- API key rotation (if needed)\n- Performance optimization review\n- Update voice settings\n\n### Mid-Month\n- Review call transcripts for quality\n- Update agent training data\n- Check compliance requirements\n\n## Monitoring Commands\n\n### Real-time Monitoring\n```bash\n# Watch incoming webhooks\ntail -f storage/logs/laravel.log | grep -i retell\n\n# Monitor queue processing\nphp artisan horizon\n\n# Check database\nwatch -n 5 'mysql -u askproai_user -p askproai_db -e \"SELECT COUNT(*) FROM calls WHERE created_at > NOW() - INTERVAL 1 HOUR\"'\n```\n\n### Health Checks\n```bash\n# System health\nphp artisan health:check\n\n# Queue health\nphp artisan queue:monitor\n\n# API connectivity\ncurl -I https://api.retellai.com/health\n```\n\n## Backup Procedures\n\n### Daily Backups\n1. Call recordings: Synced to S3 automatically\n2. Database: Nightly mysqldump at 3 AM\n3. Logs: Rotated and compressed daily\n\n### Recovery Procedures\n1. Restore from latest backup\n2. Replay webhooks from webhook_events table\n3. Re-sync with Retell.ai API\n\n## Emergency Contacts\n\n- **Retell.ai Support**: support@retellai.com\n- **AskProAI DevOps**: devops@askproai.de\n- **On-Call Engineer**: +49 123 456 789\n";
    
    $result1 = $notionMCP->executeTool('create_page', [
        'parent_id' => $parentId,
        'title' => '📖 Operations Manual',
        'content' => $operationsContent
    ]);
    
    if ($result1['success']) {
        echo "✅ Created Operations Manual\n";
        echo "   URL: " . $result1['data']['url'] . "\n\n";
    }
    
    // Create Critical Fixes Timeline
    $fixesContent = "# Critical Fixes Timeline\n\n## 2025-07-02: Major Webhook Structure Change\n\n### Problem\nRetell.ai changed their webhook payload structure without notice:\n- **Before**: Flat structure with direct field access\n- **After**: Nested structure with all data under 'call' object\n\n### Impact\n- All webhooks failing with 500 errors\n- No new calls being recorded\n- Appointments not being created\n\n### Solution Implemented\n\n#### 1. Updated Webhook Controller\nFile: `app/Http/Controllers/Api/RetellWebhookWorkingController.php`\n\nAdded structure flattening logic to handle both old and new formats.\n\n#### 2. Fixed Timestamp Parsing\nFile: `app/Helpers/RetellDataExtractor.php`\n\nNow handles both numeric timestamps and ISO 8601 strings.\n\n#### 3. Bypassed Tenant Scope\nFile: `app/Scopes/TenantScope.php`\n\nWebhook routes now bypass tenant filtering.\n\n### Verification Steps\n1. Test webhook with new structure\n2. Verify calls appear in dashboard\n3. Check appointment creation\n4. Monitor error logs\n\n## 2025-06-29: Phone Number Resolution\n\n### Problem\nCalls not being assigned to correct company/branch.\n\n### Solution\n- Enhanced PhoneNumberResolver service\n- Added branch_id to phone_numbers table\n- Improved number formatting and matching\n\n## 2025-06-25: Queue Processing Issues\n\n### Problem\nHorizon not processing webhook jobs.\n\n### Solution\n- Added supervisor configuration\n- Implemented automatic restart on failure\n- Added monitoring alerts\n\n## Critical Files - DO NOT MODIFY\n\nThese files contain critical fixes that must be preserved:\n\n1. **RetellWebhookWorkingController.php**\n   - Contains structure flattening logic\n   - Handles both old and new webhook formats\n\n2. **RetellDataExtractor.php**\n   - Flexible timestamp parsing\n   - Timezone conversion logic\n\n3. **TenantScope.php**\n   - Webhook bypass for API routes\n   - Critical for multi-tenant isolation\n\n## Rollback Procedures\n\nIf issues occur after deployment:\n\n1. **Immediate Rollback**\n   ```bash\n   git checkout stable-2025-07-02\n   php artisan config:clear\n   php artisan cache:clear\n   supervisorctl restart horizon\n   ```\n\n2. **Verify Services**\n   ```bash\n   php artisan horizon:status\n   curl -X POST https://api.askproai.de/api/retell/webhook-simple -d '{\"test\":true}'\n   ```\n\n3. **Monitor Logs**\n   ```bash\n   tail -f storage/logs/laravel.log\n   ```\n";
    
    $result2 = $notionMCP->executeTool('create_page', [
        'parent_id' => $parentId,
        'title' => '🚨 Critical Fixes Timeline',
        'content' => $fixesContent
    ]);
    
    if ($result2['success']) {
        echo "✅ Created Critical Fixes Timeline\n";
        echo "   URL: " . $result2['data']['url'] . "\n\n";
    }
    
    // Create Quick Reference Guide
    $quickRefContent = "# Quick Reference Guide\n\n## Essential Commands\n\n### Testing & Debugging\n```bash\n# Test webhook\nphp test-retell-real-data.php\n\n# Check status\nphp artisan horizon:status\n\n# View logs\ntail -f storage/logs/laravel.log | grep -i retell\n\n# Database check\nmysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db\n```\n\n### Common Fixes\n```bash\n# Clear all caches\nphp artisan optimize:clear\n\n# Restart services\nsupervisorctl restart horizon\nsystemctl restart php8.3-fpm\n\n# Manual import\nphp manual-retell-import.php\n```\n\n## Configuration\n\n### Environment Variables\n```\nRETELL_TOKEN=key_6ff998f44bb8a9bae37bb7e2c8e\nRETELL_WEBHOOK_SECRET=key_6ff998f44bb8a9bae37bb7e2c8e\nRETELL_BASE=https://api.retellai.com\nDEFAULT_RETELL_AGENT_ID=agent_9a8202a740cd3120d96fcfda1e\n```\n\n### Webhook URL\n```\nhttps://api.askproai.de/api/retell/webhook-simple\n```\n\n### Cron Jobs\n```bash\n# Import calls every 15 minutes\n*/15 * * * * /usr/bin/php /var/www/api-gateway/manual-retell-import.php\n\n# Clean stale calls every 5 minutes  \n*/5 * * * * /usr/bin/php /var/www/api-gateway/cleanup-stale-calls.php\n```\n\n## SQL Queries\n\n### Recent Calls\n```sql\nSELECT * FROM calls \nORDER BY created_at DESC \nLIMIT 10;\n```\n\n### Failed Webhooks\n```sql\nSELECT * FROM webhook_events \nWHERE service = 'retell' \nAND status = 'failed'\nORDER BY created_at DESC;\n```\n\n### Phone Mapping\n```sql\nSELECT * FROM phone_numbers \nWHERE number LIKE '%NUMBER%';\n```\n\n## Error Codes\n\n| Code | Meaning | Fix |\n|------|---------|-----|\n| 401 | Invalid API key | Check RETELL_TOKEN |\n| 403 | Signature failed | Verify webhook secret |\n| 404 | Not found | Check endpoint URL |\n| 429 | Rate limited | Implement backoff |\n| 500 | Server error | Check logs |\n\n## Support Contacts\n\n- **Retell.ai**: support@retellai.com\n- **AskProAI Dev**: dev@askproai.de  \n- **Emergency**: +49 30 123 456 789\n";
    
    $result3 = $notionMCP->executeTool('create_page', [
        'parent_id' => $parentId,
        'title' => '⚡ Quick Reference',
        'content' => $quickRefContent
    ]);
    
    if ($result3['success']) {
        echo "✅ Created Quick Reference\n";
        echo "   URL: " . $result3['data']['url'] . "\n\n";
    }
    
    echo "\n🎉 Documentation creation complete!\n";
    echo "\n📚 Main page: https://www.notion.so/Retell-ai-Integration-22caba1176e28114a6b8f13896c8fd38\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}