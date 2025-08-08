# Hair Salon MCP Integration - Complete Documentation

## 🦰 Overview

A complete Hair Salon booking system with MCP (Model Context Protocol) integration for Retell.ai voice assistant. This system handles appointment booking, staff scheduling, Google Calendar integration, and billing for a professional hair salon.

## 🚀 Quick Start

### 1. Setup
```bash
# Run the setup script
php setup-hair-salon-mcp.php

# Or manual setup:
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\HairSalonSeeder
```

### 2. Test the System
```bash
# Run comprehensive tests
php test-hair-salon-mcp-comprehensive.php

# Test health endpoint
curl http://localhost/api/hair-salon-mcp/health
```

## 📋 System Components

### Core Services
- **HairSalonMCPServer** - Main MCP server handling all booking logic
- **GoogleCalendarService** - Google Calendar API integration
- **HairSalonBillingService** - Usage tracking and billing calculations
- **HairSalonMCPController** - HTTP API endpoints for Retell.ai

### Database Models
- **Company** - Hair salon company (under reseller structure)
- **Staff** - 3 staff members (Paula, Claudia, Katrin) with Google Calendar IDs
- **Service** - Complete service catalog with consultation requirements
- **Customer** - Client information and history
- **Appointment** - Bookings with complex time block support

## 🏗️ Architecture

```
Retell.ai Agent
    ↓ (HTTP API calls)
HairSalonMCPController
    ↓
HairSalonMCPServer
    ↓
GoogleCalendarService ← → Google Calendar API
    ↓
Database (Company/Staff/Services/Appointments)
    ↓
HairSalonBillingService → Billing Reports
```

## 🎯 Features

### Service Types
1. **Standard Services** - Direct booking available
   - Herrenhaarschnitt (€25, 30min)
   - Kinderhaarschnitt (€20.50, 30min)
   - Waschen, schneiden, föhnen (€45, 60min)
   - Beratung (€30, 30min)

2. **Consultation Required** - Callback scheduling
   - Klassisches Strähnen-Paket (€89, 120min)
   - Globale Blondierung (€120, 180min)
   - Stähnentechnik Balayage (€95, 150min)
   - Faceframe (€65, 90min)

3. **Multi-Block Services** - Complex scheduling with breaks
   - Ansatzfärbung + Waschen, schneiden, föhnen (€75, 120min)
     - 30min work (application)
     - 30min break (processing time)  
     - 60min work (wash, cut, style)

### Staff Management
- **Paula**: 8356d9e1f6480e139b45d109b4ccfd9d293bfe3b0a72d6f626dbfd6c03142a6a@group.calendar.google.com
- **Claudia**: e8b310b5dbdb5e001f813080a21030d7e16447c155420d21f9bb91340af2724b@group.calendar.google.com
- **Katrin**: 46ff314dc0442572c6167f980f41731efe6e95845ba58866ab37b6e8c132bd30@group.calendar.google.com

### Business Hours
- Monday-Wednesday: 09:00-18:00
- Thursday-Friday: 09:00-20:00
- Saturday: 09:00-16:00
- Sunday: Closed
- Lunch Break: 12:30-13:30

## 🌐 API Endpoints

### Core Endpoints

#### Health Check
```
GET /api/hair-salon-mcp/health
```
Response:
```json
{
  "status": "healthy",
  "timestamp": "2025-08-07T10:00:00Z",
  "services": {
    "mcp_server": "operational",
    "google_calendar": "operational", 
    "billing_service": "operational"
  },
  "version": "1.0.0"
}
```

#### Initialize MCP
```
POST /api/hair-salon-mcp/initialize
Content-Type: application/json

{
  "company_id": 1,
  "retell_agent_id": "hair_salon_agent_001"
}
```

#### Get Services
```
POST /api/hair-salon-mcp/services
Content-Type: application/json

{
  "company_id": 1
}
```

Response:
```json
{
  "success": true,
  "services": [
    {
      "id": 1,
      "name": "Herrenhaarschnitt",
      "price": 25.00,
      "duration_minutes": 30,
      "requires_consultation": false,
      "has_breaks": false,
      "available_with": ["Paula", "Claudia", "Katrin"]
    }
  ],
  "total": 12,
  "consultation_note": "Services marked with requires_consultation need a callback"
}
```

#### Check Availability  
```
POST /api/hair-salon-mcp/availability
Content-Type: application/json

{
  "company_id": 1,
  "service_id": 1,
  "staff_id": 2,
  "date": "2025-08-08",
  "days": 7
}
```

Response:
```json
{
  "success": true,
  "service": "Herrenhaarschnitt",
  "duration_minutes": 30,
  "available_slots": [
    {
      "staff_id": 2,
      "staff_name": "Claudia",
      "date": "2025-08-08", 
      "time": "09:00",
      "datetime": "2025-08-08 09:00"
    }
  ],
  "total_slots": 15,
  "date_range": "2025-08-08 to 2025-08-14"
}
```

#### Book Appointment
```
POST /api/hair-salon-mcp/book
Content-Type: application/json

{
  "company_id": 1,
  "customer_name": "Max Mustermann",
  "customer_phone": "+49 40 12345678",
  "service_id": 1,
  "staff_id": 2,
  "datetime": "2025-08-08 09:00",
  "notes": "Neukunde, erste Beratung gewünscht",
  "call_id": 123
}
```

Response:
```json
{
  "success": true,
  "appointment_id": 456,
  "message": "Appointment booked successfully",
  "customer_name": "Max Mustermann",
  "service_name": "Herrenhaarschnitt", 
  "staff_name": "Claudia",
  "datetime": "08.08.2025 09:00",
  "duration": "30 minutes",
  "price": "25.00€"
}
```

#### Schedule Callback (for consultation services)
```
POST /api/hair-salon-mcp/callback
Content-Type: application/json

{
  "company_id": 1,
  "customer_phone": "+49 40 12345678",
  "service_id": 5,
  "preferred_time": "2025-08-08 14:00",
  "notes": "Interessiert an Balayage-Behandlung"
}
```

Response:
```json
{
  "success": true,
  "callback_scheduled": true,
  "callback_id": 789,
  "message": "A callback has been scheduled. Our specialist will contact you for consultation.",
  "expected_callback": "08.08.2025 14:00"
}
```

#### Customer Lookup
```
POST /api/hair-salon-mcp/customer
Content-Type: application/json

{
  "company_id": 1,
  "phone": "+49 40 12345678"
}
```

Response:
```json
{
  "success": true,
  "customer_found": true,
  "customer": {
    "id": 123,
    "name": "Max Mustermann",
    "phone": "+49 40 12345678", 
    "email": "max@example.com",
    "appointment_count": 3,
    "last_appointment": {
      "date": "15.07.2025",
      "service": "Herrenhaarschnitt"
    }
  }
}
```

### Billing Endpoints (Authenticated)

#### Usage Statistics
```
POST /api/hair-salon-mcp/usage-stats
Authorization: Bearer <token>
Content-Type: application/json

{
  "company_id": 1
}
```

#### Monthly Report  
```
POST /api/hair-salon-mcp/monthly-report
Authorization: Bearer <token>
Content-Type: application/json

{
  "company_id": 1,
  "month": "2025-08"
}
```

## 💰 Billing Structure

### Pricing
- **Call Cost**: €0.30 per minute (rounded up)
- **Setup Fee**: €199.00 (one-time)
- **Monthly Fee**: €49.00

### Reseller Margins
- **Call Margin**: €0.05 per minute
- **Setup Share**: €50.00
- **Monthly Share**: €10.00

### Usage Tracking
All calls are automatically tracked with:
- Duration and cost calculation
- Booking success/failure
- Service type and staff utilization
- Monthly reporting and invoicing

## 🔧 Configuration

### Environment Variables
```env
# Google Calendar Integration
GOOGLE_CALENDAR_API_KEY=your_api_key_here
GOOGLE_SERVICE_ACCOUNT_TOKEN=your_service_token_here

# Hair Salon Billing
HAIR_SALON_COST_PER_MINUTE=0.30
HAIR_SALON_SETUP_FEE=199.00
HAIR_SALON_MONTHLY_FEE=49.00

# Reseller Configuration
HAIR_SALON_RESELLER_CALL_MARGIN=0.05
HAIR_SALON_RESELLER_SETUP_SHARE=50.00
HAIR_SALON_RESELLER_MONTHLY_SHARE=10.00

# System Configuration
HAIR_SALON_MCP_ENABLED=true
HAIR_SALON_GOOGLE_CALENDAR_ENABLED=true
HAIR_SALON_RETELL_ENABLED=true
```

### Configuration File
See `config/hairsalon.php` for complete configuration options including:
- Business hours and scheduling
- Service categories and consultation requirements
- Google Calendar settings
- Error handling and fallbacks
- Development and testing options

## 🧪 Testing

### Comprehensive Test Suite
```bash
php test-hair-salon-mcp-comprehensive.php
```

Test Coverage:
- ✅ Database setup and migrations
- ✅ Service component functionality  
- ✅ MCP server operations
- ✅ HTTP endpoint responses
- ✅ Billing calculations
- ✅ Integration workflows
- ✅ Performance benchmarks
- ✅ Error handling

### Manual Testing
```bash
# Health check
curl -X GET http://localhost/api/hair-salon-mcp/health

# Initialize MCP
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"company_id": 1, "retell_agent_id": "test"}' \
  http://localhost/api/hair-salon-mcp/initialize

# Get services
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"company_id": 1}' \
  http://localhost/api/hair-salon-mcp/services
```

## 🔐 Security

### Authentication
- MCP endpoints use company_id validation
- Billing endpoints require authentication token
- Rate limiting on all endpoints (100 req/min)

### Data Protection
- Customer data retention: 24 months
- GDPR compliance built-in
- Data anonymization after 36 months
- Secure webhook signature verification

## 🚨 Error Handling

### Common Error Responses
```json
{
  "success": false,
  "error": "Service not found",
  "method": "checkAvailability",
  "timestamp": "2025-08-07T10:00:00Z"
}
```

### Fallback Mechanisms
- Default business hours when calendar unavailable
- Manual booking fallback for system failures
- Error logging and monitoring
- Automatic retry with exponential backoff

## 📊 Monitoring & Logging

### Health Monitoring
- System health endpoint
- Service status tracking
- Performance metrics
- Error rate monitoring

### Logging
- All API requests logged
- Billing events tracked
- Error events with stack traces
- Performance metrics collection

### Metrics Tracked
- Call-to-booking conversion rate
- Average booking value
- Staff utilization rates
- Service popularity
- Revenue per call

## 🔄 Integration with Retell.ai

### Agent Configuration
1. Configure Retell.ai agent with MCP endpoints
2. Set up webhook for call completion
3. Enable function calling for booking operations
4. Configure voice prompts for German language

### Webhook Setup
```
POST https://your-domain.com/api/hair-salon-webhooks/retell
```

### Function Definitions for Retell.ai
The system provides these functions for voice integration:
- `get_services()` - List available services
- `get_staff()` - List available staff
- `check_availability(service_id, date, staff_id?)` - Check time slots
- `book_appointment(customer_info, service_id, staff_id, datetime)` - Book appointment
- `schedule_callback(customer_info, service_id, notes)` - Schedule consultation
- `get_customer(phone)` - Lookup existing customer

## 📈 Scalability

### Performance Optimizations
- Response caching (5-15 minutes)
- Database indexing on key fields
- Query optimization
- Connection pooling

### Scaling Considerations
- Horizontal scaling support
- Load balancing ready
- Database read replicas
- CDN for static assets

## 🛠️ Development

### File Structure
```
app/
├── Services/
│   ├── MCP/HairSalonMCPServer.php
│   ├── GoogleCalendarService.php
│   └── HairSalonBillingService.php
├── Http/Controllers/
│   └── HairSalonMCPController.php
└── Models/ (existing models extended)

database/
├── migrations/
│   └── 2025_08_07_add_hair_salon_fields_to_services_table.php
└── seeders/
    └── HairSalonSeeder.php

config/
└── hairsalon.php

routes/
└── api.php (extended with MCP routes)
```

### Adding New Services
1. Add service to seeder or admin panel
2. Configure consultation/multi-block requirements
3. Update staff assignments
4. Test booking flow

### Extending Functionality
- Add new service types in config
- Extend billing calculations
- Add new staff specialties
- Integrate additional calendar providers

## 📞 Support

### Troubleshooting
- Check health endpoint for service status
- Review logs in `storage/logs/`
- Run test suite for system verification
- Verify Google Calendar API access

### Common Issues
1. **Calendar API Errors**: Check API key and permissions
2. **Booking Failures**: Verify service and staff availability
3. **Billing Miscalculations**: Check duration rounding logic
4. **Webhook Issues**: Verify signature validation

### Contact
- System logs: `storage/logs/laravel.log`
- Error reports: Automatically generated and stored
- Performance reports: Available via test suite

---

## 🎉 Conclusion

The Hair Salon MCP system provides a complete, production-ready solution for voice-enabled appointment booking with:

- ✅ Complete service catalog with consultation handling
- ✅ Google Calendar integration for real-time availability
- ✅ Multi-block appointment support for complex treatments
- ✅ Comprehensive billing and usage tracking
- ✅ Robust error handling and fallbacks
- ✅ Production-ready scalability and monitoring
- ✅ Full test coverage and documentation

Ready for immediate deployment and Retell.ai integration!