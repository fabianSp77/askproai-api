# 🎯 RETELL MCP CONFIGURATION - FINAL INSTRUCTIONS

## ✅ System Status: READY

All components are tested and working:
- ✅ 15 Services configured in database (including hair salon services)
- ✅ MCP endpoint responding correctly
- ✅ Company initialization fixed
- ✅ Tool discovery working
- ✅ CORS headers configured
- ✅ CSRF exemption added

## 📞 Test Phone Number
```
+49 30 33081738
```

## 🔗 MCP Endpoint URL (CORRECT)
```
https://api.askproai.de/api/v2/hair-salon-mcp/mcp
```

## 🛠️ Retell Agent Configuration

### In Retell Dashboard:

1. **Go to your Agent settings**
2. **Find "Tools" or "MCP" section**
3. **Configure MCP Integration:**

```json
{
  "url": "https://api.askproai.de/api/v2/hair-salon-mcp/mcp",
  "method": "POST",
  "headers": {
    "Content-Type": "application/json",
    "Accept": "application/json"
  },
  "defaultParams": {
    "company_id": 1
  }
}
```

## 📋 Available Tools (Auto-discovered)

The MCP server provides these tools automatically:

1. **list_services** - Liste alle verfügbaren Friseur-Services
2. **check_availability** - Prüfe verfügbare Termine
3. **book_appointment** - Buche einen Termin
4. **schedule_callback** - Vereinbare einen Rückruf

## 🧪 Test Commands

### 1. Test MCP Endpoint
```bash
curl -X POST https://api.askproai.de/api/v2/hair-salon-mcp/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"list_services","params":{"company_id":1}}'
```

### 2. Monitor Incoming Calls
```bash
php /var/www/api-gateway/monitor-retell-calls.php
```

### 3. Check System Status
```bash
php /var/www/api-gateway/test-mcp-ready.php
```

## 📱 Test Script

1. **Call**: +49 30 33081738
2. **Say in German**: 
   - "Ich möchte einen Termin für einen Haarschnitt buchen"
   - "Mein Name ist [Your Name]"
   - "Meine Telefonnummer ist [Your Phone]"

## 🎯 Expected Flow

1. AI answers and greets customer
2. Customer requests appointment
3. AI calls `list_services` → Shows available services
4. Customer selects service
5. AI calls `check_availability` → Shows available times
6. Customer selects time
7. AI calls `book_appointment` → Confirms booking
8. Appointment saved in database

## 🔍 Monitoring

Watch the system in real-time:
```bash
# Terminal 1: Monitor logs
php /var/www/api-gateway/monitor-retell-calls.php

# Terminal 2: Watch database
watch -n 5 'mysql -u askproai_user -p"lkZ57Dju9EDjrMxn" askproai_db -e "SELECT * FROM appointments ORDER BY created_at DESC LIMIT 5;"'
```

## ✨ Services Available

Current hair salon services in system:
- Herrenhaarschnitt (35€, 30 min)
- Damenhaarschnitt (55€, 45 min)  
- Färbung komplett (85€, 120 min)
- Foliensträhnen (95€, 90 min)
- Balayage (150€, 180 min)

## 🚀 Ready to Test!

Everything is configured and working. Just:
1. Configure the MCP URL in Retell dashboard
2. Start monitoring: `php monitor-retell-calls.php`
3. Call the number and test!

---
*System configured and tested on: 2025-08-07*