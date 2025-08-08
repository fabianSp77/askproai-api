# Hair Salon MCP Settings - Comprehensive Documentation

> **Agent ID**: `agent_d7da9e5c49c4ccfff2526df5c1`  
> **API Endpoint**: `https://api.askproai.de/api/v2/hair-salon-mcp`  
> **Version**: 2.0.0 Production  
> **Created**: August 2025

---

## 📋 Table of Contents

1. [🎯 Quick Overview](#quick-overview)
2. [🤖 Retell.ai Agent Configuration](#retellai-agent-configuration)
3. [🔧 MCP Node Settings](#mcp-node-settings)
4. [⚙️ Custom Function Definitions](#custom-function-definitions)
5. [🔐 API Authentication Setup](#api-authentication-setup)
6. [👥 Staff Configuration](#staff-configuration)
7. [💼 Service Catalog](#service-catalog)
8. [💰 Billing Model](#billing-model)
9. [🧪 Testing Scenarios](#testing-scenarios)
10. [🚀 Production Deployment Checklist](#production-deployment-checklist)

---

## 🎯 Quick Overview

The Hair Salon MCP (Model Context Protocol) integration enables AI-powered phone appointment booking for hair salons. This system processes customer calls through Retell.ai and automatically books appointments via Google Calendar integration.

### Key Features
- ⚡ **Ultra-fast response times** (<500ms)
- 🗣️ **German language support** with professional salon terminology
- 📅 **Real-time calendar integration** with 3 staff members
- 🤖 **Smart consultation detection** for complex services
- 💰 **Transparent billing** (€0.30/min + monthly fees)
- 🔄 **Automatic callback scheduling** for consultations

---

## 🤖 Retell.ai Agent Configuration

### Agent Details
```json
{
  "agent_id": "agent_d7da9e5c49c4ccfff2526df5c1",
  "agent_name": "Hair Salon Booking Assistant",
  "language": "de-DE",
  "voice": "german_female_professional",
  "max_call_duration": 600
}
```

### System Prompt Configuration

```text
Du bist die KI-Assistentin des Hair & Style Salons. Du hilfst Kunden beim Buchen von Terminen und beantwortest Fragen zu unseren Dienstleistungen.

WICHTIGE REGELN:
1. Sprich nur DEUTSCH und verwende professionelle, freundliche Sprache
2. Sammle IMMER diese Daten für Buchungen:
   - Vollständiger Name
   - Telefonnummer
   - Gewünschte Dienstleistung
   - Bevorzugter Termin (Datum + Uhrzeit)
   - Besondere Wünsche

SERVICES MIT BERATUNG ERFORDERLICH:
- Klassisches Strähnen-Paket
- Globale Blondierung  
- Stähnentechnik Balayage
- Faceframe

Für diese Services: Erkläre dass eine Beratung nötig ist und biete Rückruf an.

WORKFLOW:
1. Begrüße freundlich
2. Frage nach gewünschter Dienstleistung
3. Prüfe Verfügbarkeit mit checkAvailability
4. Bei Verfügbarkeit: Buche mit bookAppointment
5. Bei Beratungsbedarf: Nutze scheduleCallback
6. Bestätige Termin und beende mit endCallSession
```

### Voice & Conversation Settings
```json
{
  "voice_settings": {
    "language": "de-DE",
    "voice_id": "german_female_professional",
    "speed": 1.0,
    "pitch": 0.0
  },
  "conversation_settings": {
    "greeting_message": "Guten Tag! Hier ist Hair & Style Salon. Wie kann ich Ihnen helfen?",
    "goodbye_message": "Vielen Dank für Ihren Anruf! Wir freuen uns auf Ihren Besuch.",
    "silence_timeout": 30,
    "max_duration": 600
  }
}
```

---

## 🔧 MCP Node Settings

### Primary MCP Server Configuration

```json
{
  "server_name": "Hair Salon MCP v2",
  "server_url": "https://api.askproai.de/api/v2/hair-salon-mcp",
  "timeout_ms": 5000,
  "retry_attempts": 3,
  "version": "2.0.0"
}
```

### Endpoint URLs

| Function | HTTP Method | Endpoint URL |
|----------|------------|--------------|
| **Initialize** | POST | `/initialize` |
| **List Services** | POST | `/services` |
| **Get Staff** | POST | `/staff` |
| **Check Availability** | POST | `/availability/check` |
| **Book Appointment** | POST | `/appointments/book` |
| **Schedule Callback** | POST | `/callbacks/schedule` |
| **Customer Lookup** | POST | `/customers/lookup` |
| **Health Check** | GET | `/health` |

### Request Headers Configuration

```json
{
  "Content-Type": "application/json",
  "Authorization": "Bearer {{MCP_API_TOKEN}}",
  "X-Agent-ID": "agent_d7da9e5c49c4ccfff2526df5c1",
  "X-Call-ID": "{{call_id}}",
  "X-Request-ID": "{{uuid}}"
}
```

---

## ⚙️ Custom Function Definitions

### 1. list_services

**Purpose**: Retrieve all available salon services with pricing and duration

```json
{
  "name": "list_services",
  "description": "Get list of all salon services with prices and durations",
  "parameters": {
    "type": "object",
    "properties": {
      "category": {
        "type": "string",
        "enum": ["herren", "damen", "kinder", "faerbung", "styling", "beratung"],
        "description": "Optional: Filter by service category"
      }
    }
  },
  "return_type": "object"
}
```

**Response Example**:
```json
{
  "success": true,
  "services": [
    {
      "id": "herrenhaarschnitt",
      "name": "Herrenhaarschnitt",
      "price": 25.00,
      "duration": 30,
      "category": "herren",
      "consultation_required": false
    }
  ]
}
```

### 2. check_availability

**Purpose**: Check available appointment slots for specific service and date

```json
{
  "name": "check_availability",
  "description": "Check available appointment slots",
  "parameters": {
    "type": "object",
    "properties": {
      "service_name": {
        "type": "string",
        "description": "Name of the service"
      },
      "date": {
        "type": "string",
        "format": "date",
        "description": "Preferred date (YYYY-MM-DD)"
      },
      "staff_preference": {
        "type": "string",
        "enum": ["Paula", "Claudia", "Katrin", "any"],
        "description": "Preferred staff member or 'any'"
      }
    },
    "required": ["service_name", "date"]
  }
}
```

**Response Example**:
```json
{
  "success": true,
  "available_slots": [
    {
      "time": "09:00",
      "staff": "Paula",
      "duration": 60
    },
    {
      "time": "14:30", 
      "staff": "Claudia",
      "duration": 60
    }
  ],
  "date": "2025-08-15"
}
```

### 3. book_appointment

**Purpose**: Book a confirmed appointment slot

```json
{
  "name": "book_appointment",
  "description": "Book an appointment for a customer",
  "parameters": {
    "type": "object",
    "properties": {
      "customer_name": {
        "type": "string",
        "description": "Full customer name"
      },
      "customer_phone": {
        "type": "string",
        "description": "Customer phone number"
      },
      "service_name": {
        "type": "string",
        "description": "Service to book"
      },
      "appointment_date": {
        "type": "string",
        "format": "date",
        "description": "Appointment date (YYYY-MM-DD)"
      },
      "appointment_time": {
        "type": "string",
        "format": "time",
        "description": "Appointment time (HH:MM)"
      },
      "staff_name": {
        "type": "string",
        "enum": ["Paula", "Claudia", "Katrin"]
      },
      "notes": {
        "type": "string",
        "description": "Additional notes or special requests"
      }
    },
    "required": ["customer_name", "customer_phone", "service_name", "appointment_date", "appointment_time"]
  }
}
```

**Response Example**:
```json
{
  "success": true,
  "appointment_id": "apt_abc123",
  "confirmation": "Termin gebucht für 15.08.2025 um 14:30 bei Paula",
  "total_cost": 45.00,
  "estimated_duration": 60
}
```

### 4. schedule_callback

**Purpose**: Schedule callback for consultation-required services

```json
{
  "name": "schedule_callback",
  "description": "Schedule a consultation callback",
  "parameters": {
    "type": "object",
    "properties": {
      "customer_name": {
        "type": "string",
        "description": "Customer name"
      },
      "customer_phone": {
        "type": "string",
        "description": "Customer phone number"
      },
      "service_interest": {
        "type": "string",
        "description": "Service they're interested in"
      },
      "preferred_callback_time": {
        "type": "string",
        "description": "When they prefer to be called back"
      },
      "consultation_notes": {
        "type": "string",
        "description": "What they want to discuss"
      }
    },
    "required": ["customer_name", "customer_phone", "service_interest"]
  }
}
```

**Response Example**:
```json
{
  "success": true,
  "callback_id": "cb_xyz789",
  "message": "Rückruf für Beratung geplant. Wir melden uns innerhalb von 2 Stunden.",
  "callback_scheduled_for": "2025-08-07 16:30:00"
}
```

---

## 🔐 API Authentication Setup

### Authentication Methods

#### 1. Bearer Token Authentication (Recommended)
```bash
Authorization: Bearer mcp_a7b9c3d5e8f2g4h6i9j1k3l5m7n9o1p3q5r7s9t1u3v5w7x9y1z3
```

#### 2. API Key Authentication
```bash
X-API-Key: mcp_hair_salon_2024_production_key_xyz
X-Company-ID: salon-beauty-berlin-001  
```

### Security Configuration
```json
{
  "authentication": {
    "type": "bearer_token",
    "token_rotation": "monthly",
    "ip_whitelist": ["retell.ai", "api.retellai.com"],
    "rate_limiting": {
      "requests_per_minute": 100,
      "burst_limit": 20
    }
  },
  "encryption": {
    "in_transit": "TLS 1.3",
    "at_rest": "AES-256"
  }
}
```

### Environment Variables
```bash
# Required for production
HAIR_SALON_MCP_ENABLED=true
HAIR_SALON_RETELL_AGENT_ID=agent_d7da9e5c49c4ccfff2526df5c1
HAIR_SALON_MCP_AUTH_TOKEN=your_secure_token_here

# Google Calendar Integration
GOOGLE_CALENDAR_API_KEY=your_google_api_key
GOOGLE_SERVICE_ACCOUNT_TOKEN=your_service_account_json

# Notification Settings
HAIR_SALON_SMS_ENABLED=false
HAIR_SALON_EMAIL_ENABLED=true
```

---

## 👥 Staff Configuration

### Staff Members & Google Calendar Integration

| Name | Role | Google Calendar ID | Specialties |
|------|------|-------------------|-------------|
| **Paula** | Senior Stylist | `8356d9e1f6480e139b45d109b4ccfd9d293bfe3b0a72d6f626dbfd6c03142a6a@group.calendar.google.com` | Färbungen, Balayage |
| **Claudia** | Hair Stylist | `e8b310b5dbdb5e001f813080a21030d7e16447c155420d21f9bb91340af2724b@group.calendar.google.com` | Herrenschnitte, Styling |
| **Katrin** | Junior Stylist | `46ff314dc0442572c6167f980f41731efe6e95845ba58866ab37b6e8c132bd30@group.calendar.google.com` | Kinderschnitte, Grundbehandlungen |

### Working Hours Configuration

```json
{
  "business_hours": {
    "monday": {"start": "09:00", "end": "18:00"},
    "tuesday": {"start": "09:00", "end": "18:00"},
    "wednesday": {"start": "09:00", "end": "18:00"}, 
    "thursday": {"start": "09:00", "end": "20:00"},
    "friday": {"start": "09:00", "end": "20:00"},
    "saturday": {"start": "09:00", "end": "16:00"},
    "sunday": "closed"
  },
  "lunch_break": {
    "start": "12:30",
    "end": "13:30"
  },
  "appointment_slots": {
    "interval_minutes": 30,
    "buffer_minutes": 15,
    "advance_booking_days": 30,
    "minimum_advance_hours": 2
  }
}
```

### Staff Availability Settings

```json
{
  "availability_settings": {
    "sync_frequency": "real-time",
    "cache_duration": 15,
    "conflict_resolution": "prefer_existing_appointments",
    "overbooking_protection": true,
    "automatic_blocking": {
      "blocked_services": ["Globale Blondierung"],
      "max_bookings_per_day": 8,
      "consecutive_appointment_limit": 3
    }
  }
}
```

---

## 💼 Service Catalog

### Standard Services (Direct Booking)

| Service | Duration | Price | Category | Consultation |
|---------|----------|-------|----------|--------------|
| **Herrenhaarschnitt** | 30 min | €25.00 | Herren | ❌ No |
| **Kinderhaarschnitt** | 30 min | €20.50 | Kinder | ❌ No |
| **Waschen, schneiden, föhnen** | 60 min | €45.00 | Damen | ❌ No |
| **Beratung** | 30 min | €30.00 | Beratung | ❌ No |
| **Bartpflege** | 20 min | €15.00 | Herren | ❌ No |

### Consultation Required Services

| Service | Duration | Price | Category | Consultation |
|---------|----------|-------|----------|--------------|
| **Klassisches Strähnen-Paket** | 120 min | €89.00 | Färbung | ✅ Required |
| **Globale Blondierung** | 180 min | €120.00 | Färbung | ✅ Required |
| **Stähnentechnik Balayage** | 150 min | €95.00 | Färbung | ✅ Required |
| **Faceframe** | 90 min | €65.00 | Färbung | ✅ Required |

### Service Configuration JSON

```json
{
  "services": [
    {
      "id": "herrenhaarschnitt",
      "name": "Herrenhaarschnitt",
      "description": "Klassischer Herrenhaarschnitt mit Beratung",
      "duration_minutes": 30,
      "price_euro": 25.00,
      "category": "herren",
      "consultation_required": false,
      "staff_capable": ["Paula", "Claudia", "Katrin"],
      "booking_rules": {
        "advance_booking_hours": 2,
        "cancellation_hours": 24
      }
    },
    {
      "id": "globale-blondierung",
      "name": "Globale Blondierung", 
      "description": "Vollblondierung - Beratung und Haaranalyse erforderlich",
      "duration_minutes": 180,
      "price_euro": 120.00,
      "category": "faerbung",
      "consultation_required": true,
      "staff_capable": ["Paula"],
      "booking_rules": {
        "consultation_callback_required": true,
        "advance_booking_hours": 48,
        "hair_analysis_required": true
      }
    }
  ]
}
```

---

## 💰 Billing Model

### Cost Structure

#### Base Pricing
- **Per-minute rate**: €0.30/minute
- **Setup fee**: €199.00 (one-time)
- **Monthly subscription**: €49.00/month
- **Currency**: EUR

#### Volume Discounts

| Tier | Monthly Minutes | Per-Minute Rate | Discount |
|------|----------------|-----------------|-----------|
| **Starter** | 0-100 min | €0.30 | - |
| **Professional** | 101-500 min | €0.27 | 10% |
| **Business** | 501-1000 min | €0.255 | 15% |
| **Enterprise** | 1000+ min | €0.24 | 20% |

#### Reseller Margins

| Component | Standard | Premium | Enterprise |
|-----------|----------|---------|------------|
| **Call margin/min** | €0.05 | €0.08 | €0.10 |
| **Setup fee share** | €50.00 | €75.00 | €100.00 |
| **Monthly share** | €10.00 | €15.00 | €20.00 |

### Billing Configuration

```json
{
  "billing_model": {
    "cost_per_minute": 0.30,
    "setup_fee": 199.00,
    "monthly_fee": 49.00,
    "currency": "EUR",
    "billing_cycle": "monthly",
    "payment_terms_days": 14,
    "late_fee_percentage": 2.5,
    "volume_discounts": {
      "professional": {"min_minutes": 101, "discount": 0.10},
      "business": {"min_minutes": 501, "discount": 0.15},
      "enterprise": {"min_minutes": 1001, "discount": 0.20}
    },
    "included_features": [
      "google_calendar_integration",
      "automatic_appointment_booking", 
      "sms_confirmations",
      "basic_reporting",
      "german_language_support"
    ]
  }
}
```

### Usage Tracking

```json
{
  "metrics": {
    "calls_per_month": 150,
    "total_minutes": 450,
    "successful_bookings": 120,
    "consultation_callbacks": 25,
    "conversion_rate": 0.80,
    "average_call_duration": 3.2,
    "peak_hours": ["10:00-12:00", "14:00-16:00"]
  }
}
```

---

## 🧪 Testing Scenarios

### 1. Standard Appointment Booking

**Test Scenario**: Customer calls for a standard haircut
```
Customer: "Hallo, ich hätte gerne einen Termin für einen Herrenhaarschnitt."
Expected Flow:
1. AI asks for name and phone
2. AI checks availability using check_availability 
3. AI presents available slots
4. Customer chooses slot
5. AI books using book_appointment
6. AI confirms details and ends call
```

**Voice Interaction Example**:
```
AI: "Guten Tag! Hier ist Hair & Style Salon. Wie kann ich Ihnen helfen?"
Customer: "Ich möchte einen Herrenhaarschnitt buchen."
AI: "Gerne! Darf ich Ihren Namen haben?"
Customer: "Max Müller"
AI: "Und Ihre Telefonnummer, Herr Müller?"
Customer: "0172 123 4567"
AI: "Für welchen Tag hätten Sie gerne einen Termin?"
Customer: "Morgen, wenn möglich."
AI: "Einen Moment bitte... Morgen, den 8. August hätte ich um 10:00 Uhr bei Paula oder um 14:30 bei Claudia frei. Was passt Ihnen besser?"
Customer: "14:30 bei Claudia wäre perfekt."
AI: "Ausgezeichnet! Ich buche Ihnen den Herrenhaarschnitt für morgen, 8. August um 14:30 Uhr bei Claudia. Der Preis beträgt 25 Euro und dauert 30 Minuten. Sie erhalten eine SMS-Bestätigung. Vielen Dank und bis morgen!"
```

### 2. Consultation Required Service

**Test Scenario**: Customer interested in hair coloring
```
Customer: "Ich interessiere mich für Balayage."
Expected Flow:
1. AI identifies consultation requirement
2. AI explains consultation process
3. AI offers callback scheduling
4. AI uses schedule_callback function
5. AI confirms callback timing
```

**Voice Interaction Example**:
```
AI: "Balayage ist eine wunderschöne Technik! Da es sich um eine komplexe Färbung handelt, benötigen wir vorher eine Beratung, um das beste Ergebnis für Ihr Haar zu erzielen. Ich kann gerne einen Rückruf für eine kostenlose telefonische Beratung organisieren. Wann wäre das für Sie passend?"
Customer: "Am besten heute nachmittag."
AI: "Perfekt! Ich trage Sie für einen Beratungs-Rückruf heute nachmittag ein. Unsere Expertin Paula wird Sie innerhalb der nächsten 2 Stunden anrufen. Sie erhalten auch eine SMS-Bestätigung. Gibt es noch spezielle Fragen zur Balayage-Technik?"
```

### 3. Error Handling

**Test Scenario**: No availability for requested time
```
Customer: "Ich brauche heute noch einen Termin."
Expected Flow:
1. AI checks availability 
2. No slots available today
3. AI offers next available slots
4. AI provides alternatives
```

### 4. Complex Booking Scenarios

**Test Scenario**: Customer with specific staff preference
```
Customer: "Ich war letztes Mal bei Paula und möchte wieder zu ihr."
Expected Flow:
1. AI notes staff preference
2. AI checks Paula's availability specifically
3. AI presents Paula's available slots
4. AI books with Paula or offers alternatives
```

### Test Suite Configuration

```json
{
  "test_scenarios": [
    {
      "name": "standard_booking",
      "service": "Herrenhaarschnitt",
      "expected_duration": "2-3 minutes",
      "success_criteria": ["appointment_booked", "sms_sent", "calendar_updated"]
    },
    {
      "name": "consultation_required",
      "service": "Globale Blondierung", 
      "expected_duration": "1-2 minutes",
      "success_criteria": ["callback_scheduled", "consultation_noted"]
    },
    {
      "name": "no_availability",
      "scenario": "all_slots_booked",
      "expected_behavior": "offer_next_available_day"
    }
  ]
}
```

---

## 🚀 Production Deployment Checklist

### Pre-Deployment Requirements

#### ✅ Technical Setup
- [ ] **Google Calendar API** configured and tested
- [ ] **Staff calendar IDs** verified and accessible
- [ ] **MCP authentication** tokens generated and secured
- [ ] **Database migrations** executed successfully
- [ ] **SSL certificates** installed and valid
- [ ] **Rate limiting** configured (100 req/min)
- [ ] **Error monitoring** system activated
- [ ] **Backup systems** configured and tested

#### ✅ Retell.ai Configuration  
- [ ] **Agent ID** `agent_d7da9e5c49c4ccfff2526df5c1` configured
- [ ] **System prompt** updated with German instructions
- [ ] **MCP server** connection established
- [ ] **Custom functions** activated (all 4)
- [ ] **Voice settings** optimized for German
- [ ] **Timeout settings** configured (10 minutes)
- [ ] **Webhook endpoints** verified working

#### ✅ Staff & Business Setup
- [ ] **Staff calendars** synchronized and accessible
- [ ] **Business hours** configured correctly
- [ ] **Service catalog** uploaded and priced
- [ ] **Consultation services** marked appropriately
- [ ] **Staff specialties** assigned correctly
- [ ] **Holiday schedules** accounted for
- [ ] **Emergency contacts** configured

#### ✅ Testing & Quality Assurance
- [ ] **End-to-end tests** passing (all scenarios)
- [ ] **Load testing** completed (100+ concurrent calls)
- [ ] **German language** accuracy verified
- [ ] **Calendar integration** stress tested
- [ ] **Billing calculations** verified accurate
- [ ] **SMS notifications** working correctly
- [ ] **Error handling** robust and user-friendly

#### ✅ Security & Compliance
- [ ] **API authentication** secure and rotating
- [ ] **Data encryption** in place (TLS 1.3)
- [ ] **GDPR compliance** verified
- [ ] **Customer data** properly protected
- [ ] **Access logs** monitored and secured
- [ ] **IP whitelisting** configured
- [ ] **Rate limiting** prevents abuse

#### ✅ Monitoring & Operations
- [ ] **Health checks** automated and alerting
- [ ] **Performance monitoring** dashboards active
- [ ] **Error tracking** and notifications set up
- [ ] **Billing monitoring** alerts configured
- [ ] **Usage analytics** collection enabled
- [ ] **Staff notification** systems working
- [ ] **Customer communication** templates ready

### Go-Live Process

#### Phase 1: Soft Launch (Week 1)
```bash
# Enable for limited hours
HAIR_SALON_MCP_ENABLED=true
HAIR_SALON_ROLLOUT_PERCENTAGE=25
```
- Test with limited call volume
- Monitor for 48 hours continuously
- Fix any issues immediately
- Staff training and feedback

#### Phase 2: Full Production (Week 2)
```bash
# Full production rollout
HAIR_SALON_ROLLOUT_PERCENTAGE=100
```
- Monitor performance metrics
- Customer feedback collection
- Staff satisfaction assessment
- Performance optimization

### Emergency Rollback Plan

```bash
# Immediate rollback if critical issues
HAIR_SALON_MCP_ENABLED=false
# Activate backup booking system
BACKUP_BOOKING_SYSTEM=true
```

### Success Metrics

#### Technical KPIs
- **Response time**: <500ms average
- **Uptime**: 99.9%+
- **Error rate**: <1%
- **Booking success rate**: >95%

#### Business KPIs  
- **Call-to-booking conversion**: >80%
- **Customer satisfaction**: >4.5/5
- **Staff productivity**: Improved
- **Revenue per call**: Tracked

### Support & Maintenance

#### 24/7 Support Contacts
- **Technical Support**: tech@askproai.de
- **Emergency Hotline**: +49 xxx xxx xxx
- **Slack Channel**: #hair-salon-mcp-support

#### Regular Maintenance
- **Weekly**: Performance review and optimization
- **Monthly**: Security audit and token rotation
- **Quarterly**: Staff calendar sync verification
- **Annually**: Complete system health check

---

## 📞 Contact & Support

### Technical Team
- **Lead Developer**: Available via Slack #mcp-support
- **DevOps Team**: 24/7 monitoring and support
- **QA Team**: Continuous testing and validation

### Business Team
- **Account Manager**: Customer success and feedback
- **Billing Support**: Usage questions and optimization
- **Training Team**: Staff onboarding and education

---

*This documentation is maintained and updated regularly. Last update: August 2025*

---

**🎯 Ready for Production**: This comprehensive setup provides everything needed for a successful Hair Salon MCP deployment with professional German AI phone assistant capabilities.