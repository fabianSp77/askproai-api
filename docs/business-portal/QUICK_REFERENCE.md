# Business Portal Quick Reference

## 🚀 Quick Start

### Access URLs
- **Production Portal**: https://portal.askproai.de
- **Admin Panel**: https://admin.askproai.de
- **API Base**: https://api.askproai.de/api/v2/portal

### Default Credentials (Dev/Test)
```
Portal User: demo@askproai.de / demo123
Admin User: admin@askproai.de / password
```

## 🛠️ Essential Commands

### Development
```bash
# Start development server
npm run dev
php artisan serve

# Build for production
npm run build
php artisan optimize

# Clear all caches
php artisan optimize:clear
```

### Database
```bash
# Run migrations
php artisan migrate --force

# Seed test data
php artisan db:seed --class=PortalSeeder

# Reset database
php artisan migrate:fresh --seed
```

### Queue & Jobs
```bash
# Start queue worker
php artisan horizon

# Check queue status
php artisan horizon:status

# Retry failed jobs
php artisan queue:retry all
```

### Debugging
```bash
# Check portal health
php artisan portal:health-check

# Debug user
php debug-portal-user.php user@example.com

# Test API
php test-portal-api.php

# Check logs
tail -f storage/logs/laravel.log
```

## 📁 Key File Locations

### Frontend
```
resources/js/Pages/Portal/          # React pages
resources/js/components/Portal/     # React components
resources/js/services/             # API services
resources/js/hooks/                # React hooks
resources/css/                     # Stylesheets
```

### Backend
```
app/Http/Controllers/Api/V2/Portal/  # API controllers
app/Services/Portal/                 # Business logic
app/Models/                         # Eloquent models
routes/api.php                      # API routes
config/                            # Configuration
```

### Database
```
database/migrations/               # Schema migrations
database/seeders/                 # Test data seeders
```

## 🔑 API Quick Reference

### Authentication
```javascript
// Login
POST /api/v2/portal/auth/login
{ email, password, two_factor_code? }

// Headers for all requests
Authorization: Bearer {token}
Accept: application/json
```

### Common Endpoints
```javascript
GET    /dashboard                 // Dashboard data
GET    /calls                    // List calls
GET    /calls/{id}              // Call details
GET    /appointments             // List appointments
POST   /appointments             // Create appointment
GET    /customers               // List customers
GET    /customers/{id}/journey  // Customer journey
GET    /analytics/overview      // Analytics
```

## 🐛 Common Issues & Fixes

### Login Issues
```bash
# 419 Session Expired
php artisan config:cache
php artisan session:table
php artisan migrate

# 401 Unauthorized
# Check token in localStorage
# Verify Authorization header
```

### No Data Showing
```php
// Check tenant scope
$companyId = auth()->user()->company_id;
$data = Model::where('company_id', $companyId)->get();
```

### Build Issues
```bash
# Assets not loading
npm run build
php artisan optimize:clear

# Check vite.config.js
# Verify @vite directive in Blade
```

## 📊 Database Schema (Key Tables)

### portal_users
```sql
id, email, password, company_id, two_factor_secret, role
```

### calls
```sql
id, company_id, phone_number, customer_id, duration, status, transcript
```

### appointments
```sql
id, company_id, customer_id, staff_id, scheduled_at, status, notes
```

### company_goals
```sql
id, company_id, name, type, target_value, current_value, status
```

### customer_relationships
```sql
id, company_id, customer_id, current_stage, lifetime_value, risk_score
```

## 🔧 Configuration Files

### .env (Key Variables)
```env
# Database
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user

# Portal
PORTAL_DOMAIN=portal.askproai.de
PORTAL_2FA_ENABLED=true

# External Services
RETELL_API_KEY=key_xxx
CALCOM_API_KEY=cal_xxx
STRIPE_KEY=sk_xxx

# Features
FEATURE_GOALS_ENABLED=true
FEATURE_JOURNEY_ENABLED=true
```

### Key Config Files
- `config/session.php` - Session settings
- `config/cors.php` - CORS configuration
- `config/services.php` - External services
- `config/portal.php` - Portal settings

## 🚨 Emergency Procedures

### Reset Everything
```bash
php artisan down
php artisan optimize:clear
rm -rf bootstrap/cache/*
rm -rf storage/framework/cache/*
composer dump-autoload
npm run build
php artisan up
```

### Create Emergency Access
```php
// Run: php emergency-access.php
PortalUser::create([
    'email' => 'emergency@admin.com',
    'password' => Hash::make('Emergency123!'),
    'company_id' => 1,
    'is_super_admin' => true
]);
```

### Check System Health
```bash
# Quick health check
curl https://portal.askproai.de/api/health

# Detailed check
php portal-health-check.php

# Monitor logs
tail -f storage/logs/*.log
```

## 📱 Frontend Development

### React Component Structure
```javascript
// Basic component template
import React, { useState, useEffect } from 'react';
import { useApi } from '@/hooks/useApi';

export default function ComponentName() {
    const [data, setData] = useState(null);
    const api = useApi();
    
    useEffect(() => {
        fetchData();
    }, []);
    
    const fetchData = async () => {
        const response = await api.get('/endpoint');
        setData(response.data);
    };
    
    return <div>{/* Component JSX */}</div>;
}
```

### API Service Pattern
```javascript
// services/portalApi.js
export const portalApi = {
    dashboard: () => api.get('/dashboard'),
    calls: {
        list: (params) => api.get('/calls', { params }),
        get: (id) => api.get(`/calls/${id}`),
    },
    appointments: {
        create: (data) => api.post('/appointments', data),
        update: (id, data) => api.put(`/appointments/${id}`, data),
    }
};
```

## 🔍 Useful SQL Queries

### Active Users Today
```sql
SELECT COUNT(DISTINCT portal_user_id) 
FROM portal_sessions 
WHERE DATE(created_at) = CURDATE();
```

### Call Conversion Rate
```sql
SELECT 
    COUNT(*) as total_calls,
    COUNT(CASE WHEN appointment_booked = 1 THEN 1 END) as converted,
    ROUND(COUNT(CASE WHEN appointment_booked = 1 THEN 1 END) * 100.0 / COUNT(*), 2) as conversion_rate
FROM calls 
WHERE company_id = 1 
AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Customer Journey Distribution
```sql
SELECT 
    current_stage,
    COUNT(*) as customer_count,
    ROUND(AVG(lifetime_value), 2) as avg_ltv
FROM customer_relationships
WHERE company_id = 1
GROUP BY current_stage
ORDER BY avg_ltv DESC;
```

## 🎯 Performance Tips

### Backend
1. **Always use eager loading** for relationships
2. **Add indexes** on foreign keys and frequently queried columns
3. **Cache expensive queries** with appropriate TTL
4. **Use chunking** for large data processing

### Frontend
1. **Implement lazy loading** for routes
2. **Use React.memo** for expensive components
3. **Debounce** search and filter inputs
4. **Virtual scrolling** for long lists

### Database
1. **Regular OPTIMIZE TABLE** for heavily used tables
2. **Monitor slow query log**
3. **Use query explain** to optimize
4. **Implement read replicas** for scaling

## 🔐 Security Checklist

- [ ] Enable 2FA for admin accounts
- [ ] Regular API key rotation
- [ ] Monitor audit logs
- [ ] Check for failed login attempts
- [ ] Verify webhook signatures
- [ ] Use HTTPS everywhere
- [ ] Implement rate limiting
- [ ] Regular security audits

## 📞 Support Contacts

- **Technical Issues**: tech@askproai.de
- **Security**: security@askproai.de
- **Documentation**: [GitHub Wiki](https://github.com/askproai/docs)
- **Status Page**: https://status.askproai.de

---

*Last updated: 2025-01-10 | [Full Documentation](./BUSINESS_PORTAL_COMPLETE_DOCUMENTATION.md)*