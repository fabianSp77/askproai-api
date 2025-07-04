# Retell.ai Import Instructions

## Files Created

1. **askproai-retell-agent-config.json** - Complete agent configuration
2. **retell-webhook-config.json** - Webhook configuration reference
3. **retell-voice-optimization-guide.md** - Voice tuning guide
4. **.env.retell.example** - Environment variables template
5. **RETELL_SETUP_GUIDE.md** - Complete setup documentation
6. **retell-testing-checklist.md** - Testing checklist
7. **diagnose-retell-setup.php** - Diagnostic script

## Quick Import Steps

### 1. Import Agent Configuration

1. Log in to [Retell Dashboard](https://dashboard.retellai.com)
2. Navigate to **Agents** section
3. Click **"Import Agent"** or **"Create New Agent"**
4. If importing:
   - Click **"Import from JSON"**
   - Upload `askproai-retell-agent-config.json`
   - Click **"Import"**
5. If creating manually:
   - Copy settings from the JSON file
   - Pay special attention to:
     - Language: `de-DE`
     - Voice: `elevenlabs` with ID `XrExE9yKIg1WjnnlVkGX`
     - Functions: All 8 custom functions
     - Webhook URL: `https://api.askproai.de/api/retell/webhook`

### 2. Note Important Values

After import, note these values:
- **Agent ID**: `agent_xxxxxxxxxxxx`
- **Webhook Secret**: From webhook configuration page
- **Phone Numbers**: Any numbers you assign

### 3. Configure AskProAI

```bash
# Copy environment template
cp .env.retell.example .env.retell

# Edit with your values
nano .env.retell

# Add these critical values:
RETELL_API_KEY=your_api_key_here
RETELL_WEBHOOK_SECRET=your_webhook_secret_here
DEFAULT_RETELL_AGENT_ID=agent_xxxxxxxxxxxx

# Append to main .env
cat .env.retell >> .env
```

### 4. Test Setup

```bash
# Run diagnostic
php diagnose-retell-setup.php

# Test the integration
php artisan retell:test-connection
```

### 5. Make Test Call

1. Call your Retell phone number
2. Test basic greeting
3. Try booking an appointment
4. Verify webhook received in logs

## Important Notes

- The agent configuration is optimized for German language appointment booking
- All prompts and functions are in German
- Voice settings are tuned for clarity in German
- Webhook must be HTTPS with valid SSL certificate
- Always test in staging before production deployment

## Support

If you encounter issues:
1. Run the diagnostic script
2. Check the setup guide
3. Review the testing checklist
4. Contact support with diagnostic output

## File Locations

All configuration files are in:
```
/var/www/api-gateway/
├── askproai-retell-agent-config.json  (Import this!)
├── retell-webhook-config.json
├── retell-voice-optimization-guide.md
├── .env.retell.example
├── RETELL_SETUP_GUIDE.md
├── retell-testing-checklist.md
├── diagnose-retell-setup.php
└── RETELL_IMPORT_INSTRUCTIONS.md (This file)
```