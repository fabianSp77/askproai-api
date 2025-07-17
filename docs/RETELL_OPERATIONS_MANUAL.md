# Retell.ai Operations Manual

> **Daily operations guide for managing AI phone agents**  
> Version 1.0 - July 2025

## Quick Reference

- **Admin Dashboard**: https://api.askproai.de/admin
- **Retell Dashboard**: https://app.retellai.com
- **Horizon Queue Monitor**: https://api.askproai.de/horizon
- **Emergency Contact**: #askproai-alerts on Slack

---

## Table of Contents

1. [Daily Operations](#daily-operations)
2. [Agent Management](#agent-management)
3. [Monitoring & Metrics](#monitoring--metrics)
4. [Common Tasks](#common-tasks)
5. [Troubleshooting](#troubleshooting)
6. [Cost Management](#cost-management)
7. [Customer Support](#customer-support)
8. [Emergency Procedures](#emergency-procedures)

---

## Daily Operations

### Morning Checklist (9:00 AM)

```bash
# 1. Check system health
php artisan health:check

# 2. Verify Horizon is running
php artisan horizon:status

# 3. Check overnight calls
mysql -u askproai_user -p askproai_db -e "
SELECT 
    COUNT(*) as total_calls,
    COUNT(CASE WHEN call_status = 'failed' THEN 1 END) as failed,
    AVG(duration_sec) as avg_duration,
    SUM(cost) as total_cost
FROM calls 
WHERE created_at >= CURDATE() - INTERVAL 1 DAY"

# 4. Review failed jobs
php artisan queue:failed

# 5. Check webhook status
tail -n 100 storage/logs/laravel.log | grep "Webhook.*error"
```

### Evening Checklist (6:00 PM)

```bash
# 1. Daily cost report
php artisan retell:daily-report

# 2. Check unusual activity
mysql -u askproai_user -p askproai_db -e "
SELECT * FROM calls 
WHERE cost > 5 
AND DATE(created_at) = CURDATE()
ORDER BY cost DESC"

# 3. Backup critical data
php artisan backup:run --only-db

# 4. Clear old logs
find storage/logs -name "*.log" -mtime +30 -delete
```

---

## Agent Management

### Viewing Agent Configuration

1. **Via Admin Panel**
   - Navigate to `/admin`
   - Click "Retell Agents" in sidebar
   - Select company
   - View agent details

2. **Via Command Line**
   ```bash
   php artisan tinker
   >>> $retell = new \App\Services\RetellV2Service();
   >>> $agent = $retell->getAgent('agent_id_here');
   >>> print_r($agent);
   ```

### Updating Agent Prompts

#### Safe Method (Recommended)
1. Login to admin panel
2. Navigate to Retell Control Center
3. Select agent
4. Click "Edit Prompt"
5. Make changes
6. Click "Save Draft"
7. Test with test call
8. Click "Publish" when ready

#### Direct Method (Emergency Only)
```php
// update-agent-prompt.php
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$agentId = 'agent_xxx';
$newPrompt = <<<PROMPT
Du bist der KI-Assistent von [Company Name].

DEINE HAUPTAUFGABEN:
1. Freundliche Begrüßung
2. Terminvereinbarungen
3. Informationen geben

WICHTIGE REGELN:
- Sei immer höflich
- Frage nach allen notwendigen Informationen
- Bestätige alle Details
PROMPT;

$retell = new \App\Services\RetellV2Service();
$result = $retell->updateAgent($agentId, ['agent_prompt' => $newPrompt]);

echo $result ? "Updated successfully\n" : "Update failed\n";
```

### Voice Configuration

#### Available Voices
- **Standard German**: `elevenlabs_voice_abc123`
- **Professional Female**: `elevenlabs_voice_def456`
- **Professional Male**: `elevenlabs_voice_ghi789`
- **Custom Cloned**: Contact support for setup

#### Changing Voice Settings
```php
$retell->updateAgent($agentId, [
    'voice_id' => 'new_voice_id',
    'voice_speed' => 0.95,  // 0.5-2.0
    'voice_temperature' => 0.8  // 0-1
]);
```

### Managing Phone Numbers

#### Assigning Agent to Phone Number
```bash
php artisan tinker
>>> $retell = new \App\Services\RetellV2Service();
>>> $retell->updatePhoneNumber('+491234567890', [
...     'agent_id' => 'agent_xxx',
...     'inbound_agent_id' => 'agent_xxx'
... ]);
```

#### Listing All Phone Numbers
```bash
php artisan tinker
>>> $numbers = $retell->listPhoneNumbers();
>>> foreach($numbers['phone_numbers'] as $num) {
...     echo $num['phone_number'] . ' -> ' . $num['agent_id'] . "\n";
... }
```

---

## Monitoring & Metrics

### Real-Time Dashboard

Access at: `/admin/retell-dashboard`

Key Metrics:
- **Active Calls**: Currently in progress
- **Today's Calls**: Total count
- **Success Rate**: Completed vs failed
- **Average Duration**: In minutes
- **Total Cost**: Today's spending

### SQL Queries for Reporting

#### Hourly Call Distribution
```sql
SELECT 
    HOUR(created_at) as hour,
    COUNT(*) as calls,
    AVG(duration_sec) as avg_duration
FROM calls
WHERE DATE(created_at) = CURDATE()
GROUP BY HOUR(created_at)
ORDER BY hour;
```

#### Company Usage Report
```sql
SELECT 
    c.name as company,
    COUNT(calls.id) as total_calls,
    SUM(calls.cost) as total_cost,
    AVG(calls.duration_sec) as avg_duration,
    COUNT(CASE WHEN calls.appointment_made = 1 THEN 1 END) as appointments_made
FROM companies c
LEFT JOIN calls ON c.id = calls.company_id
WHERE calls.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY c.id
ORDER BY total_cost DESC;
```

#### Failed Calls Analysis
```sql
SELECT 
    DATE(created_at) as date,
    disconnection_reason,
    COUNT(*) as count
FROM calls
WHERE call_status = 'failed'
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at), disconnection_reason
ORDER BY date DESC, count DESC;
```

### Alert Thresholds

Set up alerts for:
- Failed call rate > 10%
- Average cost per call > €3
- Call duration > 15 minutes
- No calls received > 1 hour (during business hours)

---

## Common Tasks

### Testing an Agent

1. **Test Call via Admin Panel**
   ```
   1. Go to Retell Control Center
   2. Select agent
   3. Click "Test Call"
   4. Enter test phone number
   5. Click "Initiate Call"
   ```

2. **Manual Test Call**
   ```bash
   php artisan tinker
   >>> $retell = new \App\Services\RetellV2Service();
   >>> $call = $retell->createPhoneCall([
   ...     'from_number' => '+4930123456',  // Retell number
   ...     'to_number' => '+491234567890',  // Your test number
   ...     'agent_id' => 'agent_xxx'
   ... ]);
   ```

### Handling Customer Complaints

1. **Find the Call**
   ```sql
   SELECT * FROM calls 
   WHERE from_number LIKE '%CUSTOMER_PHONE%'
   ORDER BY created_at DESC
   LIMIT 5;
   ```

2. **Review Transcript**
   - Copy call_id
   - Go to admin panel
   - Search for call
   - Review transcript and recording

3. **Common Issues & Solutions**
   - **"AI was rude"**: Review prompt, adjust tone
   - **"Couldn't book appointment"**: Check Cal.com integration
   - **"Wrong information"**: Update agent knowledge
   - **"Call dropped"**: Check disconnection_reason

### Updating Business Information

When business details change (hours, services, etc.):

1. **Update Database**
   ```sql
   UPDATE companies 
   SET settings = JSON_SET(settings, '$.business_hours', 'Mo-Fr 9-18 Uhr')
   WHERE id = COMPANY_ID;
   ```

2. **Update Agent Prompt**
   - Include new information in prompt
   - Test thoroughly
   - Deploy during low-traffic hours

3. **Notify Team**
   - Send update to support team
   - Update documentation
   - Monitor for issues

---

## Troubleshooting

### Call Not Appearing

**Quick Diagnosis**
```bash
# 1. Check if webhook was received
tail -f /var/log/nginx/access.log | grep retell

# 2. Check Laravel logs
tail -f storage/logs/laravel.log | grep -i retell

# 3. Check queue status
php artisan queue:work --queue=webhooks --tries=1
```

### High Error Rate

**Investigation Steps**
1. Check error patterns
   ```sql
   SELECT 
       disconnection_reason,
       COUNT(*) as count,
       AVG(duration_sec) as avg_duration
   FROM calls
   WHERE created_at >= NOW() - INTERVAL 1 HOUR
   GROUP BY disconnection_reason
   ORDER BY count DESC;
   ```

2. Review recent changes
   - Agent prompt updates?
   - System deployments?
   - External service issues?

3. Test affected scenarios

### Performance Issues

**Slow Response Times**
1. Check Retell latency metrics
2. Review custom function performance
3. Check database query times
4. Verify Redis is running

**High Costs**
1. Review long calls
2. Check for infinite loops in conversation
3. Verify hang-up detection working
4. Consider adding time limits

---

## Cost Management

### Understanding Costs

**Cost Components**:
- **Transcript**: ~€0.001 per second
- **Synthesis**: ~€0.002 per second  
- **Analysis**: ~€0.10 per call
- **Phone**: ~€0.01 per minute

**Average Call Costs**:
- Short inquiry (2 min): €0.50-0.80
- Appointment booking (5 min): €1.00-1.50
- Complex support (10 min): €2.00-3.00

### Cost Optimization

1. **Prompt Optimization**
   - Keep prompts concise
   - Avoid repetitive responses
   - Use efficient language

2. **Call Duration Management**
   ```php
   // Set maximum call duration
   $retell->updateAgent($agentId, [
       'max_call_duration_ms' => 600000  // 10 minutes
   ]);
   ```

3. **Implement Pre-Screening**
   - Use IVR for basic filtering
   - Route only relevant calls to AI
   - Handle FAQs with recordings

### Budget Monitoring

```sql
-- Daily budget check
SELECT 
    DATE(created_at) as date,
    COUNT(*) as calls,
    SUM(cost) as total_cost,
    SUM(cost) / COUNT(*) as avg_cost_per_call
FROM calls
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Company budget status
SELECT 
    c.name,
    c.monthly_budget,
    SUM(calls.cost) as month_to_date_cost,
    (SUM(calls.cost) / c.monthly_budget * 100) as budget_used_percent
FROM companies c
LEFT JOIN calls ON c.id = calls.company_id
WHERE calls.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
GROUP BY c.id
HAVING budget_used_percent > 80;
```

---

## Customer Support

### Handling Support Requests

1. **"AI doesn't understand our services"**
   - Review service descriptions in prompt
   - Add specific examples
   - Test with actual service names

2. **"Appointments are double-booked"**
   - Check Cal.com sync status
   - Verify webhook processing
   - Review appointment creation logs

3. **"Customers complain about voice"**
   - Test different voice options
   - Adjust speed and tone
   - Consider custom voice cloning

### Providing Call Recordings

```bash
# 1. Find call record
mysql -u askproai_user -p askproai_db -e "
SELECT call_id, recording_url, public_log_url 
FROM calls 
WHERE from_number LIKE '%PHONE%' 
ORDER BY created_at DESC 
LIMIT 1"

# 2. Generate temporary access link
php artisan call:generate-access-link CALL_ID

# 3. Send to customer (expires in 24 hours)
```

### Training New Staff

1. **Provide Documentation**
   - This operations manual
   - Retell.ai basics guide
   - Company-specific procedures

2. **Hands-On Training**
   - Shadow experienced operator
   - Practice common scenarios
   - Review real call examples

3. **Access Setup**
   - Admin panel account
   - Retell dashboard (read-only)
   - Monitoring tools access

---

## Emergency Procedures

### Complete System Failure

```bash
# 1. Immediate response
php artisan down --message="Wartungsarbeiten" --retry=60

# 2. Diagnose
systemctl status nginx
systemctl status php8.1-fpm
systemctl status mysql
systemctl status redis

# 3. Restart services
systemctl restart nginx
systemctl restart php8.1-fpm
supervisorctl restart all

# 4. Verify and restore
php artisan up
php artisan horizon
```

### Runaway Costs

```bash
# 1. Immediately disable agents
php artisan retell:disable-all-agents

# 2. Investigate
mysql -u askproai_user -p askproai_db -e "
SELECT * FROM calls 
WHERE cost > 10 
AND created_at >= NOW() - INTERVAL 1 HOUR"

# 3. Fix issue and re-enable
php artisan retell:enable-agents --company=1
```

### Data Breach Response

1. **Immediate Actions**
   - Disable affected agents
   - Revoke API keys
   - Document timeline

2. **Investigation**
   - Review access logs
   - Check for data exports
   - Identify scope

3. **Remediation**
   - Reset all credentials
   - Notify affected parties
   - Implement additional security

### Backup Procedures

```bash
# Daily automated backup
0 2 * * * /usr/bin/php /var/www/api-gateway/artisan backup:run --only-db

# Manual emergency backup
php artisan backup:run

# Restore from backup
php artisan backup:restore --backup=2025-07-10-020000.sql
```

---

## Appendix

### Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# Reprocess failed webhooks
php artisan webhooks:retry --service=retell

# Generate call report
php artisan report:calls --from=2025-07-01 --to=2025-07-10

# Export call data
php artisan export:calls --format=csv --output=/tmp/calls.csv

# Test phone number mapping
php test-phone-resolution.php +491234567890

# Monitor real-time
watch -n 5 'php artisan retell:status'
```

### Key Files & Locations

- **Logs**: `/var/www/api-gateway/storage/logs/`
- **Configs**: `/var/www/api-gateway/config/`
- **Webhooks**: `/var/www/api-gateway/app/Http/Controllers/Api/`
- **Services**: `/var/www/api-gateway/app/Services/`
- **Jobs**: `/var/www/api-gateway/app/Jobs/`
- **Backups**: `/var/www/api-gateway/storage/backups/`

### External Resources

- **Retell Status Page**: https://status.retellai.com
- **Cal.com Status**: https://status.cal.com
- **Twilio Status**: https://status.twilio.com
- **ElevenLabs Status**: https://status.elevenlabs.io

---

**Document Version**: 1.0  
**Last Updated**: July 10, 2025  
**Next Review**: August 2025

For updates or corrections, please submit to the operations team.