# Quick Start Guide

!!! success "Only 2 Configurations Needed!"
    The system is 85% production ready. You only need to configure 2 values to start booking appointments!

## 🚀 5-Minute Setup

### Prerequisites

- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+
- Composer 2.0+
- Node.js 18+

### Step 1: Clone & Install

```bash
# Clone the repository
git clone https://github.com/askproai/api-gateway.git
cd api-gateway

# Install PHP dependencies
composer install --optimize-autoloader

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env
```

### Step 2: Configure Environment

Edit `.env` with your credentials:

```bash
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cal.com (REQUIRED)
DEFAULT_CALCOM_API_KEY="cal_live_xxxxxxxxxxxxxx"
DEFAULT_CALCOM_TEAM_SLUG="your-team"

# Retell.ai (REQUIRED)
DEFAULT_RETELL_API_KEY="key_xxxxxxxxxxxxxx"
RETELL_WEBHOOK_SECRET="key_xxxxxxxxxxxxxx"
```

### Step 3: Database Setup

```bash
# Generate application key
php artisan key:generate

# Run migrations with seeders
php artisan migrate --seed

# Create first company (optional)
php artisan tinker
>>> Company::create([
...     'name' => 'Your Company',
...     'slug' => 'your-company',
...     'is_active' => true
... ]);
```

### Step 4: Configure the 2 Critical Values

!!! warning "Critical Configuration"
    Without these 2 values, the system cannot book appointments!

#### 1. Set Cal.com Event Type ID

```sql
-- Find your Cal.com event type ID from Cal.com dashboard
-- Then update your branch:
UPDATE branches 
SET calcom_event_type_id = 2563193  -- Your actual event type ID
WHERE id = 1;
```

#### 2. Set Retell Agent ID

```sql
-- Get your Retell agent ID from Retell.ai dashboard
-- Then update your phone number:
UPDATE phone_numbers 
SET retell_agent_id = 'agent_9a8202a740cd3120d96fcfda1e'  -- Your agent ID
WHERE phone_number = '+493083793369';  -- Your phone number
```

### Step 5: Start Services

```bash
# Start Laravel development server
php artisan serve

# In another terminal: Start Horizon (queue worker)
php artisan horizon

# In another terminal: Start Vite (frontend assets)
npm run dev

# Optional: Start MCP servers
php artisan mcp:start
```

### Step 6: Configure Webhooks

In your Retell.ai dashboard, set the webhook URL:

```
https://your-domain.com/api/mcp/retell/webhook
```

Enable these events:
- ✅ call_started
- ✅ call_ended
- ✅ call_analyzed

### Step 7: Test Your Setup

1. **Test Phone Resolution**:
   ```bash
   php test-phone-resolution.php +493083793369
   ```

2. **Test Webhook Processing**:
   ```bash
   php test-mcp-webhook.php
   ```

3. **Make a Test Call**:
   - Call your configured phone number
   - Say you want to book an appointment
   - Provide date and time
   - Confirm the booking

## 🎉 That's It!

Your AskProAI system is now ready to:
- Answer calls 24/7
- Book appointments automatically
- Send confirmations
- Track all interactions

## 🔧 Common Issues

### "Time slot no longer available"
```sql
-- Clear appointment locks
DELETE FROM appointment_locks WHERE expires_at < NOW();
```

### "Cal.com sync failed"
```bash
# Reset circuit breaker
php artisan circuit-breaker:reset calcom

# Manually sync
php artisan cal:sync-event-types
```

### "Webhook not processing"
```bash
# Check webhook logs
tail -f storage/logs/laravel.log | grep webhook

# Test webhook signature
php test-retell-signature.php
```

## 📊 Verify Everything Works

Visit your admin dashboard:
```
http://localhost:8000/admin
```

Default credentials (if using seeders):
- Email: admin@askproai.de
- Password: password

Check these sections:
1. **Phone Numbers** - Should show your configured number
2. **Branches** - Should have Cal.com event type set
3. **Recent Calls** - Should show test calls
4. **Appointments** - Should show booked appointments

## 🚀 Next Steps

1. **Production Deployment**: See [Production Setup](deployment/production.md)
2. **Add More Features**: Enable WhatsApp, SMS, Customer Portal
3. **Configure Analytics**: Set up monitoring and alerts
4. **Security Hardening**: Remove debug routes, enable 2FA

---

!!! tip "Need Help?"
    - Check [Troubleshooting Guide](operations/troubleshooting.md)
    - Review [API Documentation](api/rest-v2.md)
    - Join our [Discord Community](https://discord.gg/askproai)