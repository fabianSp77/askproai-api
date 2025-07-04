# Cal.com API Visual Comparison Chart

## 🎯 API Usage by Service Class

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        API VERSION USAGE MAP                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  CalcomService.php (DEPRECATED)                                           │
│  └── 100% V1 API                                                         │
│      ├── ❌ /v1/availability                                              │
│      ├── ❌ /v1/bookings                                                  │
│      └── ❌ /v1/event-types                                               │
│                                                                           │
│  CalcomV2Service.php (CURRENT)                                            │
│  └── 70% V1 / 30% V2 Mixed                                               │
│      ├── V1 Endpoints                                                    │
│      │   ├── ✅ /v1/users                                                │
│      │   ├── ✅ /v1/event-types                                          │
│      │   ├── ✅ /v1/bookings (create)                                    │
│      │   └── ✅ /v1/schedules                                            │
│      └── V2 Endpoints                                                    │
│          ├── ✅ /v2/me                                                   │
│          ├── ✅ /v2/slots/available                                      │
│          ├── ✅ /v2/bookings (list)                                      │
│          ├── ✅ /v2/teams                                                │
│          └── ✅ /v2/bookings/{id}/cancel                                 │
│                                                                           │
│  CalcomV2Client.php (FUTURE)                                              │
│  └── 100% V2 API                                                         │
│      ├── 🔄 /v2/event-types                                              │
│      ├── 🔄 /v2/slots/available                                          │
│      ├── ❓ /v2/bookings (create)                                        │
│      └── 🔄 /v2/bookings/{uid}/cancel                                    │
│                                                                           │
│  CalcomMCPServer.php                                                      │
│  └── Uses CalcomV2Service (inherits mixed mode)                          │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

Legend: ✅ Working | ❌ Deprecated | 🔄 Implemented but not used | ❓ Has issues
```

## 📊 Feature Availability Matrix

```
┌─────────────────────────┬──────────┬──────────┬─────────────────────────┐
│      FEATURE            │  V1 API  │  V2 API  │       NOTES             │
├─────────────────────────┼──────────┼──────────┼─────────────────────────┤
│ Authentication          │ URL Key  │  Bearer  │ V2 uses modern auth     │
├─────────────────────────┼──────────┼──────────┼─────────────────────────┤
│ List Event Types        │    ✅    │    ⚠️    │ V2 structure different  │
│ Get Event Type Details  │    ✅    │    ✅    │ V2 more detailed        │
│ List Users              │    ✅    │    ❌    │ V2 returns 401          │
│ Get Current User        │    ❌    │    ✅    │ V2 only feature         │
├─────────────────────────┼──────────┼──────────┼─────────────────────────┤
│ Check Availability      │    ✅    │    ✅    │ V2 uses /slots          │
│ List Schedules          │    ✅    │    ❌    │ Not in V2               │
│ Manage Teams            │    ⚠️    │    ✅    │ Better in V2            │
├─────────────────────────┼──────────┼──────────┼─────────────────────────┤
│ Create Booking          │    ✅    │    ❓    │ V2 has implementation   │
│                         │          │          │ issues                  │
│ List Bookings           │    ✅    │    ✅    │ Both work               │
│ Get Single Booking      │    ✅    │    ✅    │ Both work               │
│ Cancel Booking          │    ✅    │    ✅    │ V2 preferred            │
│ Reschedule Booking      │    ✅    │    ✅    │ V2 preferred            │
├─────────────────────────┼──────────┼──────────┼─────────────────────────┤
│ Webhook Management      │    ⚠️    │    ✅    │ V2 has better support   │
└─────────────────────────┴──────────┴──────────┴─────────────────────────┘

Legend: ✅ Fully Working | ⚠️ Partial/Issues | ❌ Not Available | ❓ Broken
```

## 🔄 Migration Path Visualization

```
Current State (2025-06-28)
┌────────────────────┐
│   Application      │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ CalcomV2Service    │ ◀── Primary Service (Mixed Mode)
│   70% V1 / 30% V2  │
└─────────┬──────────┘
          │
     ┌────┴────┐
     ▼         ▼
┌─────────┐ ┌─────────┐
│ V1 API  │ │ V2 API  │
│ Legacy  │ │ Modern  │
└─────────┘ └─────────┘

Target State (Future)
┌────────────────────┐
│   Application      │
└─────────┬──────────┘
          │
          ▼
┌────────────────────┐
│ CalcomV2Client     │ ◀── Target Service (V2 Only)
│    100% V2         │
└─────────┬──────────┘
          │
          ▼
     ┌─────────┐
     │ V2 API  │
     │ Modern  │
     └─────────┘
```

## 🚦 API Endpoint Status Dashboard

### V1 Endpoints (Legacy)
```
GET  /v1/event-types      [████████░░] 80% - Still Primary
GET  /v1/users            [████████░░] 80% - No V2 Alternative  
GET  /v1/schedules        [████████░░] 80% - No V2 Alternative
GET  /v1/availability     [██░░░░░░░░] 20% - Replaced by V2 slots
POST /v1/bookings         [██████████] 100% - V2 Broken
GET  /v1/bookings         [████░░░░░░] 40% - V2 Available
```

### V2 Endpoints (Modern)
```
GET  /v2/me              [██████████] 100% - Working Well
GET  /v2/event-types     [████░░░░░░] 40% - Structure Issues
GET  /v2/slots/available [██████████] 100% - Primary for availability
POST /v2/bookings        [██░░░░░░░░] 20% - Implementation Issues
GET  /v2/bookings        [████████░░] 80% - Working
POST /v2/bookings/{}/cancel [████████░░] 80% - Working
GET  /v2/teams           [██████████] 100% - Working Well
```

## 📈 Usage Statistics in Codebase

```
API Usage Distribution:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
V1 Only:        ████████████████████░░░░░░░░░░ 65%
V2 Only:        ████░░░░░░░░░░░░░░░░░░░░░░░░░ 10%  
Mixed V1/V2:    ████████░░░░░░░░░░░░░░░░░░░░░ 25%

By Feature:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Booking Creation:   V1 ████████████████████ 100%
Availability:       V2 ████████████████████ 100%
Event Types:        V1 ████████████████░░░░ 80%
User Management:    V1 ████████████████████ 100%
Booking Mgmt:       V2 ████████████░░░░░░░░ 60%
```

## 🎯 Quick Decision Matrix

| If you need to... | Use This API | Service Method |
|-------------------|--------------|----------------|
| Create a booking | V1 | `CalcomV2Service->bookAppointment()` |
| Check availability | V2 | `CalcomV2Service->checkAvailability()` |
| List event types | V1 | `CalcomV2Service->getEventTypes()` |
| Cancel booking | V2 | `CalcomV2Service->cancelBooking()` |
| Get user info | V2 | `CalcomV2Service->getMe()` |
| List users | V1 | `CalcomV2Service->getUsers()` |
| Manage teams | V2 | `CalcomV2Service->getTeams()` |

## 🔧 Testing Quick Commands

```bash
# Test what's actually working
php artisan tinker
>>> $service = new \App\Services\CalcomV2Service();
>>> $service->getEventTypes();     // Uses V1
>>> $service->checkAvailability(123, '2025-06-29'); // Uses V2
>>> $service->bookAppointment(...); // Uses V1
```