# Complete Retell.ai Setup Guide for AskProAI

## Quick Start (5 Minutes)

### 1. Import Agent Configuration
1. Log in to [Retell Dashboard](https://dashboard.retellai.com)
2. Go to **Agents** → **Import Agent**
3. Upload `askproai-retell-agent-config.json`
4. Note the generated Agent ID

### 2. Configure Webhook
1. In Retell Dashboard → **Webhooks**
2. Add webhook URL: `https://api.askproai.de/api/retell/webhook`
3. Copy the webhook secret
4. Enable events: `call_started`, `call_ended`, `call_analyzed`

### 3. Update Environment Variables
```bash
cp .env.retell.example .env.retell
# Edit .env.retell with your values
cat .env.retell >> .env
```

### 4. Test the Setup
```bash
# Test API connection
php artisan retell:test-connection

# Import recent calls
php artisan retell:sync-calls --days=7

# Make a test call to your Retell number
```

## Detailed Setup Instructions

### Step 1: Retell Account Setup

#### 1.1 Create Retell Account
- Sign up at [retellai.com](https://retellai.com)
- Choose appropriate plan for call volume
- Verify email and phone number

#### 1.2 Get API Credentials
```
Dashboard → API Keys → Create New Key
- Name: "AskProAI Production"
- Copy API Key and Secret
```

#### 1.3 Purchase Phone Number
```
Dashboard → Phone Numbers → Buy Number
- Country: Germany (+49)
- Type: Local or Toll-Free
- Features: Voice, SMS (optional)
```

### Step 2: Agent Configuration

#### 2.1 Import Base Configuration
The provided `askproai-retell-agent-config.json` includes:
- German language settings
- Appointment booking functions
- Optimized voice parameters
- Professional prompts

#### 2.2 Customize for Your Business
Edit these placeholders in the agent:
- `{{company_name}}` → Your company name
- `{{branch_name}}` → Branch/location name
- `{{branch_address}}` → Physical address
- `{{business_hours}}` → Opening hours

#### 2.3 Voice Selection
Test different German voices:
1. **Matilda** - Professional female (recommended)
2. **Daniel** - Professional male
3. **Freya** - Friendly female

### Step 3: Webhook Integration

#### 3.1 Configure Webhook in Retell
```
Dashboard → Webhooks → Add Webhook
- URL: https://api.askproai.de/api/retell/webhook
- Method: POST
- Authentication: Signature
- Events: All call events
```

#### 3.2 Test Webhook
```bash
# From AskProAI server
curl -X POST http://localhost/api/retell/webhook \
  -H "Content-Type: application/json" \
  -H "x-retell-signature: test" \
  -d '{"event_type":"test","timestamp":"2025-06-25T12:00:00Z"}'
```

#### 3.3 Verify Webhook Security
```php
// Check middleware is active
grep -r "VerifyRetellSignature" routes/api.php
```

### Step 4: Function Configuration

#### 4.1 Required Functions
Your agent needs these functions configured:
1. **check_availability** - Check calendar slots
2. **book_appointment** - Create bookings
3. **get_business_info** - Provide info
4. **transfer_call** - Human handoff

#### 4.2 Function Response Format
Ensure your API returns:
```json
{
  "success": true,
  "data": {
    "message": "Human-readable response",
    "details": { /* structured data */ }
  }
}
```

### Step 5: Testing Protocol

#### 5.1 API Connection Test
```bash
php artisan tinker
>>> $retell = app(App\Services\RetellV2Service::class);
>>> $retell->testConnection();
```

#### 5.2 Make Test Calls
1. **Basic Greeting Test**
   - Call your Retell number
   - Verify greeting message
   - Test language recognition

2. **Appointment Booking Test**
   - Request appointment
   - Provide all required info
   - Verify booking creation

3. **Edge Cases**
   - Invalid dates
   - No availability
   - Incomplete information

#### 5.3 Monitor Webhook Processing
```bash
# Watch webhook logs
tail -f storage/logs/retell-webhooks.log

# Check queue processing
php artisan horizon
```

### Step 6: Production Deployment

#### 6.1 Environment Configuration
```bash
# Production .env settings
APP_ENV=production
RETELL_DEBUG_MODE=false
RETELL_ENABLE_ANALYTICS=true
RETELL_GDPR_MODE=true
```

#### 6.2 Queue Configuration
```bash
# Supervisor configuration for queue workers
[program:retell-webhook-worker]
command=php artisan queue:work --queue=retell-webhooks --tries=3
autostart=true
autorestart=true
```

#### 6.3 Monitoring Setup
```bash
# Add to crontab
*/5 * * * * php artisan retell:sync-calls
0 2 * * * php artisan retell:cleanup-old-recordings
```

### Step 7: Troubleshooting

#### Common Issues

**Issue: No calls appearing in dashboard**
```bash
# Check webhook is receiving data
tail -f storage/logs/laravel.log | grep retell

# Manually sync calls
php artisan retell:sync-calls --force
```

**Issue: Functions not working**
```bash
# Test function directly
curl -X POST https://api.askproai.de/api/appointments/check-availability \
  -H "Content-Type: application/json" \
  -d '{"date_from":"2025-06-26","service_id":"1"}'
```

**Issue: Voice quality problems**
- Reduce voice_temperature to 0.2
- Increase voice_speed to 1.0
- Test different voice models

#### Debug Commands
```bash
# Test specific call
php artisan retell:debug-call {call_id}

# Check agent configuration
php artisan retell:show-agent {agent_id}

# Validate webhook signature
php artisan retell:validate-webhook
```

## Best Practices

### 1. Call Handling
- Keep initial greeting under 10 seconds
- Confirm important details twice
- Spell out email addresses
- Use NATO phonetic alphabet for spelling

### 2. Error Handling
- Always have fallback responses
- Offer human transfer for complex cases
- Log all errors with context
- Monitor function timeout rates

### 3. Performance Optimization
- Cache agent configurations
- Use connection pooling for APIs
- Implement circuit breakers
- Monitor response times

### 4. Security
- Rotate API keys quarterly
- Monitor for unusual call patterns
- Implement rate limiting
- Regular security audits

### 5. Customer Experience
- Test with real users
- Monitor call satisfaction
- A/B test prompts
- Regular voice tuning

## Maintenance Schedule

### Daily
- Monitor error rates
- Check webhook processing
- Review failed calls

### Weekly
- Analyze call patterns
- Update prompts based on feedback
- Test all functions

### Monthly
- Full system test
- Voice quality review
- Security audit
- Performance optimization

## Support Resources

### Retell Documentation
- [API Reference](https://docs.retellai.com)
- [Best Practices](https://docs.retellai.com/best-practices)
- [Troubleshooting](https://docs.retellai.com/troubleshooting)

### AskProAI Resources
- Internal Wiki: `/docs/retell-integration`
- Support: support@askproai.de
- Emergency: +49 30 123 456 789

### Community
- Retell Discord
- AskProAI Slack (#retell-integration)