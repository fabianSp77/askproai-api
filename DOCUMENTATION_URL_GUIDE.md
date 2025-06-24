# AskProAI Documentation URL Guide

## Important Note on URL Structure

The documentation URLs have **two different patterns** depending on the page location:

### Pattern 1: Pages in Navigation Menu
These pages use the standard path **without** `/docs/`:
- API Documentation: https://api.askproai.de/mkdocs/api/rest-v2/
- Live Dashboard: https://api.askproai.de/mkdocs/monitoring/live-dashboard/
- Architecture Overview: https://api.askproai.de/mkdocs/architecture/overview/
- Quickstart: https://api.askproai.de/mkdocs/quickstart/

### Pattern 2: Pages in Subdirectories  
These pages require the `/docs/` prefix:
- Migration guides: https://api.askproai.de/mkdocs/docs/migration/database-consolidation/
- Configuration: https://api.askproai.de/mkdocs/docs/configuration/environment/
- Deployment: https://api.askproai.de/mkdocs/docs/deployment/production/
- Development: https://api.askproai.de/mkdocs/docs/development/setup/

## Complete URL Reference

### 🏠 Main Documentation
- **Home**: https://api.askproai.de/mkdocs/
- **Quickstart**: https://api.askproai.de/mkdocs/quickstart/
- **Status**: https://api.askproai.de/mkdocs/status/
- **Changelog**: https://api.askproai.de/mkdocs/changelog/

### 🏗️ Architecture
- **Overview**: https://api.askproai.de/mkdocs/architecture/overview/
- **System Design**: https://api.askproai.de/mkdocs/architecture/system-design/
- **MCP Architecture**: https://api.askproai.de/mkdocs/architecture/mcp-architecture/
- **Data Flow**: https://api.askproai.de/mkdocs/architecture/data-flow/

### 🚀 Features
- **Phone System**: https://api.askproai.de/mkdocs/features/phone-system/
- **Appointment Booking**: https://api.askproai.de/mkdocs/features/appointment-booking/
- **Multi-Tenancy**: https://api.askproai.de/mkdocs/features/multi-tenancy/
- **Analytics**: https://api.askproai.de/mkdocs/features/analytics/

### 🔌 API Documentation
- **REST API v2**: https://api.askproai.de/mkdocs/api/rest-v2/
- **Webhooks**: https://api.askproai.de/mkdocs/api/webhooks/
- **MCP Endpoints**: https://api.askproai.de/mkdocs/api/mcp/
- **Authentication**: https://api.askproai.de/mkdocs/api/authentication/

### 📊 Live Monitoring
- **Live Dashboard**: https://api.askproai.de/mkdocs/monitoring/live-dashboard/
- **Live Metrics**: https://api.askproai.de/mkdocs/monitoring/live-metrics/

### 🔧 Configuration (with /docs/)
- **Environment**: https://api.askproai.de/mkdocs/docs/configuration/environment/
- **Services**: https://api.askproai.de/mkdocs/docs/configuration/services/
- **Security**: https://api.askproai.de/mkdocs/docs/configuration/security/
- **Cache**: https://api.askproai.de/mkdocs/docs/configuration/cache/

### 🚢 Deployment (with /docs/)
- **Requirements**: https://api.askproai.de/mkdocs/docs/deployment/requirements/
- **Installation**: https://api.askproai.de/mkdocs/docs/deployment/installation/
- **Production**: https://api.askproai.de/mkdocs/docs/deployment/production/
- **Scaling**: https://api.askproai.de/mkdocs/docs/deployment/scaling/

### 🔄 Migration Guides (with /docs/)
- **Database Consolidation**: https://api.askproai.de/mkdocs/docs/migration/database-consolidation/
- **Service Unification**: https://api.askproai.de/mkdocs/docs/migration/service-unification/
- **Cal.com v2**: https://api.askproai.de/mkdocs/docs/migration/calcom-v2/

### 💻 Development (with /docs/)
- **Setup Guide**: https://api.askproai.de/mkdocs/docs/development/setup/
- **Standards**: https://api.askproai.de/mkdocs/docs/development/standards/
- **Testing**: https://api.askproai.de/mkdocs/docs/development/testing/
- **Debugging**: https://api.askproai.de/mkdocs/docs/development/debugging/

### 🛠️ Operations (with /docs/)
- **Monitoring**: https://api.askproai.de/mkdocs/docs/operations/monitoring/
- **Troubleshooting**: https://api.askproai.de/mkdocs/docs/operations/troubleshooting/
- **Performance**: https://api.askproai.de/mkdocs/docs/operations/performance/
- **Security Audit**: https://api.askproai.de/mkdocs/operations/security-audit/

## Quick Test Commands

Test all main URLs:
```bash
# Main pages
curl -s -o /dev/null -w "%{http_code}\n" https://api.askproai.de/mkdocs/
curl -s -o /dev/null -w "%{http_code}\n" https://api.askproai.de/mkdocs/api/rest-v2/
curl -s -o /dev/null -w "%{http_code}\n" https://api.askproai.de/mkdocs/monitoring/live-dashboard/

# Pages with /docs/ prefix
curl -s -o /dev/null -w "%{http_code}\n" https://api.askproai.de/mkdocs/docs/migration/database-consolidation/
curl -s -o /dev/null -w "%{http_code}\n" https://api.askproai.de/mkdocs/docs/deployment/production/
```

## Note
The URL structure inconsistency is due to MkDocs' handling of navigation vs. directory structure. This will be unified in a future update.