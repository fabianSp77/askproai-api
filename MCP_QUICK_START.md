# MCP Quick Start Guide

Welcome to AskProAI MCP! This guide will get you up and running quickly.

## 🚀 Quick Setup (5 minutes)

### 1. Clone and Install
```bash
# Clone repository
git clone https://github.com/askproai/api-gateway.git
cd api-gateway

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.mcp.example .env
```

### 2. Configure Environment
```bash
# Generate application key
php artisan key:generate

# Set database credentials in .env
DB_DATABASE=askproai_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Set API keys
DEFAULT_CALCOM_API_KEY=your_calcom_key
DEFAULT_RETELL_API_KEY=your_retell_key
```

### 3. Setup Database
```bash
# Run migrations
php artisan migrate --seed

# Create admin user
php artisan make:admin admin@askproai.de password123
```

### 4. Start Services
```bash
# Start application
php artisan serve

# Start queue worker (new terminal)
php artisan horizon

# Start monitoring (optional)
docker-compose -f docker-compose.observability.yml up -d
```

### 5. Access Application
- **Admin Panel**: http://localhost:8000/admin
- **API Health**: http://localhost:8000/api/health
- **Horizon**: http://localhost:8000/horizon
- **Grafana**: http://localhost:3000 (admin/admin)

## 📊 Key API Endpoints

### Health & Monitoring
```bash
# System health
GET /api/health

# Prometheus metrics
GET /api/metrics

# Dashboard stats
GET /api/dashboard/stats
Authorization: Bearer {token}
```

### Appointments
```bash
# List appointments
GET /api/appointments
Authorization: Bearer {token}

# Create appointment
POST /api/appointments
{
    "customer_id": 1,
    "service_id": 1,
    "staff_id": 1,
    "start_time": "2025-06-22 10:00:00",
    "duration": 60
}
```

### Webhooks
```bash
# Retell.ai webhook
POST /api/retell/webhook
X-Retell-Signature: {signature}

# Cal.com webhook
POST /api/webhooks/calcom
X-Cal-Signature: {signature}
```

### MCP Features
```bash
# Circuit breaker status
GET /api/mcp/circuit-breakers

# Cache statistics
GET /api/mcp/cache-stats

# Performance metrics
GET /api/mcp/performance
```

## 🔧 Common Tasks

### Clear Caches
```bash
php artisan cache:clear        # Application cache
php artisan config:clear       # Configuration cache
php artisan route:clear        # Route cache
php artisan optimize:clear     # All caches
```

### Monitor Queues
```bash
php artisan horizon:snapshot   # Take snapshot
php artisan queue:monitor      # Monitor performance
php artisan queue:failed       # List failed jobs
```

### Run Tests
```bash
php artisan test              # All tests
php artisan test --parallel   # Faster execution
php artisan test --filter=MCP # MCP-specific tests
```

### Check Health
```bash
php artisan health:check      # Run health checks
php artisan mcp:verify        # Verify MCP setup
```

## 🔍 Troubleshooting

### Issue: "Connection refused"
```bash
# Check services
sudo systemctl status mysql
sudo systemctl status redis
php artisan horizon:status
```

### Issue: "Cache not working"
```bash
# Check Redis connection
redis-cli ping

# Clear and rebuild
php artisan cache:clear
php artisan cache:warm
```

### Issue: "Webhooks failing"
```bash
# Check signatures
php artisan webhook:verify

# Check logs
tail -f storage/logs/webhook.log
```

### Issue: "Slow performance"
```bash
# Enable query log
php artisan query:monitor

# Check slow queries
php artisan performance:analyze
```

## 📁 Project Structure

```
api-gateway/
├── app/
│   ├── Http/Controllers/Api/   # API controllers
│   ├── Services/               # Business logic
│   │   ├── MCP/               # MCP services
│   │   ├── Calcom/            # Calendar integration
│   │   └── Retell/            # Phone AI integration
│   ├── Models/                # Eloquent models
│   └── Jobs/                  # Queue jobs
├── config/
│   ├── mcp.php               # MCP configuration
│   ├── monitoring.php         # Monitoring settings
│   └── circuit-breaker.php    # Circuit breaker config
├── database/
│   ├── migrations/            # Database migrations
│   └── seeders/              # Sample data
├── routes/
│   ├── api.php               # API routes
│   └── web.php               # Web routes
└── tests/
    ├── Feature/              # Feature tests
    └── Unit/                 # Unit tests
```

## 🚨 Emergency Procedures

### System Down
```bash
# 1. Check status
php artisan health:check

# 2. Restart services
php artisan down
php artisan horizon:terminate
php artisan up
php artisan horizon

# 3. Check logs
tail -f storage/logs/laravel.log
```

### Database Issues
```bash
# Check connection
php artisan db:monitor

# Repair tables
php artisan db:repair

# Emergency backup
php artisan askproai:backup --type=critical
```

### High Load
```bash
# Enable emergency mode
php artisan mcp:emergency-mode on

# Scale workers
php artisan horizon:scale webhooks=10

# Clear non-essential caches
php artisan cache:clear --non-essential
```

## 📚 Additional Resources

- **Full Documentation**: `/docs/`
- **API Reference**: https://api.askproai.de/docs
- **Video Tutorials**: https://askproai.de/tutorials
- **Support**: support@askproai.de

## 🎯 Next Steps

1. **Configure Monitoring**: Set up alerts in Grafana
2. **Test Webhooks**: Use provided test scripts
3. **Import Data**: Run importers for existing data
4. **Customize**: Adapt to your business needs

## 💡 Pro Tips

1. **Use Cache Warming**: Run `php artisan cache:warm` after deployments
2. **Monitor Horizon**: Keep an eye on queue latency
3. **Enable Debug Mode**: Set `MCP_DEBUG=true` for detailed logs
4. **Regular Backups**: Schedule automated backups
5. **Update Dependencies**: Run `composer update` monthly

---

Need help? Check our [Troubleshooting Guide](docs/TROUBLESHOOTING_GUIDE.md) or contact support@askproai.de