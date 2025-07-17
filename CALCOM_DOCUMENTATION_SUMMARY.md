# Cal.com Documentation Summary for Notion Import

## 📅 Cal.com Integration - Main Page

**Status**: ✅ Fully Migrated to V2 API

### Overview
The Cal.com integration in AskProAI enables automated appointment booking through AI-powered phone calls. The system is fully integrated with Cal.com V2 API and provides real-time availability checking, booking creation, and webhook synchronization.

### Key Features
- ✅ **API Version**: V2 (fully migrated from V1)
- ✅ **Production Ready**: Yes
- ✅ **Multi-tenant Support**: Yes
- ✅ **Webhook Integration**: Active
- ✅ **Circuit Breaker**: Enabled
- ✅ **Response Caching**: Enabled

### Critical Configuration
```bash
# Environment Variables
DEFAULT_CALCOM_API_KEY=cal_live_xxxxxxxxxxxxxx
DEFAULT_CALCOM_TEAM_SLUG=your-team-slug
CALCOM_WEBHOOK_SECRET=your-webhook-secret

# Webhook URL
https://api.askproai.de/api/webhooks/calcom

# API Endpoint
https://api.cal.com/v2
```

---

## Sub-Pages Structure

### 1. 📋 Integration Guide
**Path**: `/var/www/api-gateway/CALCOM_INTEGRATION_GUIDE.md`

**Contents**:
- Complete setup instructions
- Architecture overview
- Service layer components
- Quick start guide
- Configuration details
- Security considerations
- Best practices

**Key Information**:
- How to set up Cal.com integration from scratch
- Environment variable configuration
- Initial setup commands
- Basic usage examples

### 2. 🔧 V2 API Reference
**Path**: `/var/www/api-gateway/CALCOM_V2_API_REFERENCE.md`

**Contents**:
- All API endpoints
- Request/response formats
- Authentication methods
- Service classes (CalcomV2Client, CalcomV2Service)
- Data Transfer Objects (DTOs)
- Error handling
- Circuit breaker configuration
- Caching strategies

**Key Endpoints**:
- Authentication & User
- Event Types
- Schedules
- Availability
- Bookings
- Webhooks

### 3. 🔄 V1 to V2 Migration
**Path**: `/var/www/api-gateway/CALCOM_V1_TO_V2_MIGRATION_GUIDE.md`

**Contents**:
- Migration status (COMPLETED)
- Key changes between V1 and V2
- Step-by-step migration process
- Code examples (before/after)
- Database migration scripts
- Testing procedures

**Important Changes**:
- Authentication: Query param → Bearer token
- Response format: Direct → Wrapped in status/data
- Date handling: Various → Strict ISO 8601

### 4. 🔔 Webhook Configuration
**Path**: `/var/www/api-gateway/CALCOM_WEBHOOK_GUIDE.md`

**Contents**:
- Supported webhook events
- Setup instructions
- Security implementation (HMAC-SHA256)
- Payload structures for each event type
- Controller and job implementations
- Error handling and retries

**Webhook Events**:
- BOOKING_CREATED
- BOOKING_RESCHEDULED
- BOOKING_CANCELLED
- BOOKING_CONFIRMED
- BOOKING_REJECTED

### 5. 📅 Event Type Management
**Path**: `/var/www/api-gateway/CALCOM_EVENT_TYPES_GUIDE.md`

**Contents**:
- Event type architecture
- Database schema
- Synchronization process
- Multi-location support
- Custom fields configuration
- Staff assignment

**Key Commands**:
```bash
php artisan calcom:sync-event-types --all
php artisan calcom:sync-event-types --company=1
php artisan calcom:sync-event-types --force
```

### 6. 🛠️ Troubleshooting Guide
**Path**: `/var/www/api-gateway/CALCOM_TROUBLESHOOTING_GUIDE.md`

**Contents**:
- Quick diagnostics
- Common issues and solutions
- Authentication problems
- Booking failures
- Event type sync issues
- Webhook problems
- Performance issues
- Recovery procedures

**Common Issues**:
- Invalid API Key (401)
- No Available Slots
- Booking Creation Failed
- Webhooks Not Received
- Circuit Breaker Open

### 7. 📖 Operations Manual
**Path**: `/var/www/api-gateway/CALCOM_OPERATIONS_MANUAL.md`

**Contents**:
- Daily operations checklist
- Automated tasks (cron jobs)
- Monitoring procedures
- Performance optimization
- Cache management
- Database optimization
- Maintenance routines
- Backup procedures

**Daily Tasks**:
```bash
php artisan calcom:health-check
php artisan calcom:sync-status --since=yesterday
php artisan queue:failed | grep ProcessCalcomWebhook
```

### 8. 🚀 Quick Reference
**Path**: `/var/www/api-gateway/CALCOM_QUICK_REFERENCE.md`

**Contents**:
- Essential commands
- Common API calls
- Database queries
- Error codes quick fix
- Configuration files
- Log locations
- Monitoring URLs

**Most Used Commands**:
```bash
# Check health
php artisan calcom:health-check

# Sync event types
php artisan calcom:sync-event-types --all

# Debug availability
php artisan calcom:debug-availability 2026361

# Reset circuit breaker
php artisan calcom:circuit-reset
```

---

## Notion Import Instructions

1. **Create Main Page**: Create a new page "📅 Cal.com Integration" under Technical Docs → Integrations

2. **Add Sub-pages**: Create 8 sub-pages under the main page with the titles listed above

3. **Copy Content**: Copy the content from each markdown file into the corresponding Notion page

4. **Format**: Use Notion's built-in formatting to enhance readability:
   - Use toggle lists for long code examples
   - Create tables for structured data
   - Add callout boxes for important notes
   - Use code blocks with appropriate syntax highlighting

5. **Link Pages**: Create internal links between related pages for easy navigation

6. **Add Tags**: Tag pages with relevant labels like "API", "Integration", "V2", "Production"

---

## File Locations

All documentation files are located at:
```
/var/www/api-gateway/CALCOM_*.md
```

You can access them via SSH or through the codebase to copy content into Notion.