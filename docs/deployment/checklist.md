# ✅ Deployment Checklist

**AskProAI Deployment Verification Checklist**  
**Version:** 1.2.0

---

## 🔧 Pre-Deployment

### Server Preparation
- [ ] Server meets minimum requirements (4GB RAM, 2 vCPUs, 50GB SSD)
- [ ] Ubuntu 22.04 LTS / CentOS 8+ installed
- [ ] SSH key authentication configured
- [ ] Root access available
- [ ] Domain DNS properly configured
- [ ] SSL certificate ready for installation

### Security Setup
- [ ] Firewall (UFW) installed and configured
- [ ] Fail2Ban installed and configured
- [ ] SSH password authentication disabled
- [ ] Non-root deployment user created
- [ ] Sudo privileges properly configured

---

## 📦 System Installation

### Core Components
- [ ] **PHP 8.3+** installed with all required extensions
- [ ] **MySQL 8.0+** / **MariaDB 10.4+** installed and secured
- [ ] **Redis 6.0+** installed and configured
- [ ] **Nginx** installed and configured
- [ ] **Node.js 18+** and **NPM** installed
- [ ] **Composer** installed globally
- [ ] **Supervisor** installed for process management

### PHP Configuration
- [ ] `memory_limit = 512M`
- [ ] `upload_max_filesize = 20M`
- [ ] `post_max_size = 25M`
- [ ] `cgi.fix_pathinfo = 0`
- [ ] OPCache enabled and configured
- [ ] PHP-FPM properly configured

### Database Configuration
- [ ] Database `askproai_db` created
- [ ] Database user `askproai_user` created with proper privileges
- [ ] Strong password set for database user
- [ ] MySQL performance settings optimized
- [ ] Slow query log enabled

### Redis Configuration
- [ ] Memory limit configured (512MB recommended)
- [ ] Eviction policy set to `allkeys-lru`
- [ ] Redis systemd service enabled

---

## 🚀 Application Deployment

### Code Deployment
- [ ] Repository cloned to `/var/www/askproai`
- [ ] Proper file permissions set (`755` for directories, `644` for files)
- [ ] Storage and cache directories writable (`775`)
- [ ] Ownership set to `askproai:www-data`

### Dependencies Installation
- [ ] Composer dependencies installed (`composer install --optimize-autoloader --no-dev`)
- [ ] NPM dependencies installed (`npm ci`)
- [ ] Frontend assets built (`npm run build`)
- [ ] No build errors or warnings

### Environment Configuration
- [ ] `.env` file created from `.env.example`
- [ ] `APP_KEY` generated (`php artisan key:generate`)
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Database credentials configured
- [ ] Redis connection configured
- [ ] All external API keys configured (Cal.com, RetellAI, etc.)
- [ ] Email configuration set
- [ ] `.env` file permissions set to `600`

### Database Setup
- [ ] Migrations executed (`php artisan migrate --force`)
- [ ] Seeders run (`php artisan db:seed`)
- [ ] Admin user created successfully
- [ ] Database connection verified

### Laravel Optimization
- [ ] Configuration cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)
- [ ] Views cached (`php artisan view:cache`)
- [ ] Storage link created (`php artisan storage:link`)

---

## ⚙️ Web Server Configuration

### Nginx Configuration
- [ ] Virtual host configured for domain
- [ ] Document root points to `/var/www/askproai/public`
- [ ] PHP-FPM integration configured
- [ ] HTTP to HTTPS redirect configured
- [ ] Security headers configured
- [ ] Gzip compression enabled
- [ ] Static file caching configured
- [ ] Nginx configuration tested (`nginx -t`)

### SSL Certificate
- [ ] Certbot installed
- [ ] SSL certificate generated for domain
- [ ] Auto-renewal configured
- [ ] HTTPS redirect working
- [ ] SSL Labs grade A or higher
- [ ] Certificate expiry date verified

---

## 🔄 Process Management

### Supervisor Configuration
- [ ] Horizon supervisor configuration created
- [ ] Supervisor configuration reloaded
- [ ] Horizon process started successfully
- [ ] Horizon auto-restart configured
- [ ] Log files properly configured

### Cron Jobs
- [ ] Laravel scheduler cron job added
- [ ] Backup cron jobs configured
- [ ] Log rotation configured
- [ ] Health check cron jobs configured

### Queue System
- [ ] Redis queue connection working
- [ ] Horizon dashboard accessible
- [ ] Test jobs processed successfully
- [ ] Failed job handling configured

---

## 📊 Monitoring & Logging

### Health Checks
- [ ] Health check script created
- [ ] Health check endpoint responding
- [ ] Database connectivity verified
- [ ] External API connectivity tested
- [ ] Automated health checks configured

### Logging Configuration
- [ ] Laravel log channel configured
- [ ] Log rotation configured
- [ ] Log file permissions correct
- [ ] Error monitoring configured
- [ ] Performance logging enabled

### System Monitoring
- [ ] System stats script created
- [ ] Resource usage monitoring configured
- [ ] Alert thresholds configured
- [ ] Monitoring dashboard accessible (if applicable)

---

## 💾 Backup System

### Backup Configuration
- [ ] Backup directories created
- [ ] Database backup script configured
- [ ] File backup script configured
- [ ] Configuration backup included
- [ ] S3/external storage configured (if applicable)

### Backup Testing
- [ ] Manual backup executed successfully
- [ ] Backup files generated correctly
- [ ] Database backup tested (restore test)
- [ ] Backup cleanup configured
- [ ] Backup monitoring configured

### Restore Procedures
- [ ] Restore scripts created
- [ ] Restore procedure documented
- [ ] Restore tested on staging environment
- [ ] Recovery time objectives defined

---

## 🔒 Security Verification

### Application Security
- [ ] API rate limiting configured
- [ ] CSRF protection enabled
- [ ] XSS protection configured
- [ ] SQL injection prevention verified
- [ ] File upload security configured
- [ ] Session security configured

### Server Security
- [ ] Firewall rules verified
- [ ] SSH security hardened
- [ ] Fail2Ban monitoring active
- [ ] File permissions secure
- [ ] Sensitive files protected
- [ ] Security headers verified

### API Security
- [ ] Webhook signature verification working
- [ ] API authentication working
- [ ] API rate limits enforced
- [ ] CORS configuration correct
- [ ] API documentation access controlled

---

## 🧪 Testing & Verification

### Functionality Testing
- [ ] Homepage loads correctly
- [ ] Admin panel accessible
- [ ] User authentication working
- [ ] API endpoints responding
- [ ] Webhooks processing correctly
- [ ] Database operations working

### Performance Testing
- [ ] Page load times < 2 seconds
- [ ] API response times < 200ms
- [ ] Database queries optimized
- [ ] Memory usage within limits
- [ ] CPU usage normal
- [ ] Concurrent user handling tested

### External Integrations
- [ ] Cal.com integration working
- [ ] RetellAI integration working
- [ ] Email sending working
- [ ] Payment processing working (if applicable)
- [ ] Third-party API connectivity verified

---

## 🌐 DNS & Domain Configuration

### DNS Settings
- [ ] A record pointing to server IP
- [ ] AAAA record configured (if IPv6)
- [ ] CNAME records configured (if subdomains)
- [ ] MX records configured (if email)
- [ ] TTL values appropriate

### Domain Verification
- [ ] Domain resolves correctly from multiple locations
- [ ] www redirect configured (if applicable)
- [ ] Subdomain routing working
- [ ] SSL certificate covers all domains/subdomains

---

## 📋 Documentation & Handover

### Documentation
- [ ] Environment variables documented
- [ ] Deployment procedure documented
- [ ] Troubleshooting guide provided
- [ ] API documentation updated
- [ ] User guides provided

### Access & Credentials
- [ ] Server access credentials secured
- [ ] Database credentials documented
- [ ] API keys documented and secured
- [ ] Third-party account access provided
- [ ] Emergency contact information provided

### Knowledge Transfer
- [ ] Technical team trained
- [ ] Support team briefed
- [ ] Maintenance procedures explained
- [ ] Escalation procedures defined
- [ ] Contact information updated

---

## 🔍 Post-Deployment Verification

### 24-Hour Monitoring
- [ ] **Hour 1:** Initial stability check
- [ ] **Hour 6:** Performance monitoring
- [ ] **Hour 12:** Error rate verification
- [ ] **Hour 24:** Full system health check

### Week 1 Monitoring
- [ ] **Day 1:** User feedback collection
- [ ] **Day 3:** Performance optimization
- [ ] **Day 7:** Security audit
- [ ] **Week 1:** Capacity planning review

### Metrics to Track
- [ ] Response times
- [ ] Error rates
- [ ] User engagement
- [ ] System resource usage
- [ ] External API success rates
- [ ] Backup success rates

---

## ⚠️ Rollback Plan

### Rollback Readiness
- [ ] Previous version backup available
- [ ] Database rollback plan prepared
- [ ] DNS rollback procedures ready
- [ ] Rollback triggers defined
- [ ] Rollback team assignments clear

### Rollback Testing
- [ ] Rollback procedure tested on staging
- [ ] Rollback time estimated
- [ ] Data loss assessment completed
- [ ] Communication plan for rollback ready

---

## 🎉 Go-Live Completion

### Final Verification
- [ ] All checklist items completed ✅
- [ ] Stakeholder approval received
- [ ] Go-live time confirmed
- [ ] Support team notified
- [ ] Monitoring dashboards active

### Success Criteria Met
- [ ] Application fully functional
- [ ] Performance targets met
- [ ] Security requirements satisfied
- [ ] Backup system operational
- [ ] Monitoring systems active
- [ ] Documentation complete

---

## 📞 Emergency Contacts

### Technical Support
- **Primary:** [Name] - [Phone] - [Email]
- **Secondary:** [Name] - [Phone] - [Email]
- **Escalation:** [Name] - [Phone] - [Email]

### Service Providers
- **Hosting Provider:** [Contact Info]
- **DNS Provider:** [Contact Info]
- **SSL Certificate Provider:** [Contact Info]
- **Third-party APIs:** [Contact Info]

---

**Deployment Status: [ ] COMPLETED**

**Go-Live Date:** _______________  
**Deployment Engineer:** _______________  
**Project Manager:** _______________  
**Final Approval:** _______________

---

*Deployment Checklist v1.2.0*  
*Last Updated: August 14, 2025*