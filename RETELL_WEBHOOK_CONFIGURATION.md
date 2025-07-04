# Retell Webhook Configuration Guide

## Webhook URL
```
https://api.askproai.de/api/retell/webhook
```

## Required Events
- ✅ `call_started` - Für Live-Updates während des Anrufs
- ✅ `call_ended` - Für abgeschlossene Anrufe  
- ✅ `call_analyzed` - Für erweiterte Analysen (optional)

## Configuration Steps

1. **Login to Retell Dashboard**
   - URL: https://dashboard.retellai.com/
   - oder: https://app.retellai.com/

2. **Navigate to Agent Settings**
   - Agents → Select Agent: `agent_9a8202a740cd3120d96fcfda1e`
   - Name: "Online: Assistent für Fabian Spitzer Rechtliches/V33"

3. **Configure Webhook**
   - Scroll to "Webhook" section
   - Webhook URL: `https://api.askproai.de/api/retell/webhook`
   - Enable Events:
     - [x] call_started
     - [x] call_ended  
     - [x] call_analyzed
   - Save Changes

4. **Verify Phone Number Assignment**
   - Go to "Phone Numbers" section
   - Verify `+493083793369` is assigned to this agent
   - If not, assign it

## Test Webhook

After configuration, test with:
```bash
curl -X POST https://api.askproai.de/api/retell/webhook \
  -H "Content-Type: application/json" \
  -d '{"event":"test","message":"Webhook configured successfully"}'
```

## Troubleshooting

If webhooks don't arrive:
1. Check Retell Dashboard → Logs for failed webhook attempts
2. Verify the URL is exactly: `https://api.askproai.de/api/retell/webhook`
3. Check if Retell can reach your server (no IP blocking)
4. Look for webhook signature errors in Laravel logs

## Important Notes

⚠️ The webhook signature verification is currently active. Make sure Retell is sending the correct signature headers:
- `X-Retell-Signature`
- `X-Retell-Timestamp`

## Contact Retell Support

If configuration doesn't work:
- Email: support@retellai.com
- Reference: Agent ID `agent_9a8202a740cd3120d96fcfda1e`
- Issue: Webhooks not being sent to configured URL