# Cal.com API v2: Staff/Hosts Assignment Research Report

**Research Date:** 2025-10-06
**API Version:** v2 (cal-api-version: 2024-08-13)
**Confidence Level:** High (90%) - Based on official Cal.com documentation

---

## Executive Summary

This report provides comprehensive documentation on Cal.com API v2's hosts assignment system for bookings, covering host array structure, multi-host scenarios, API endpoints, and recommended mapping strategies for external staff systems.

**Key Findings:**
- The `hosts` array contains assigned staff members for a booking
- Host assignment varies by event type (Round-Robin, Collective, Managed)
- Cal.com provides reassignment endpoints for dynamic host changes
- No explicit "organizer" field in v2 API responses - hosts array serves this purpose
- Best practice: Map Cal.com user IDs to internal staff IDs via lookup table

---

## 1. Hosts Array Structure

### Complete Schema

Based on Cal.com API v2 documentation, the `hosts` array in booking responses has the following structure:

```typescript
interface BookingHost {
  id: number;           // Cal.com user ID (required)
  name: string;         // Full name (required)
  email: string;        // Email address (required)
  username: string;     // Cal.com username (required)
  timeZone: string;     // IANA timezone (required)
}

interface BookingResponse {
  // ... other fields
  hosts: BookingHost[];  // Array of assigned hosts
  attendees: BookingAttendee[];
  guests: string[];      // Email addresses
  // ... other fields
}
```

### Example Response

```json
{
  "status": "success",
  "data": {
    "id": 123,
    "uid": "booking_uid_123",
    "title": "Meeting with client",
    "hosts": [
      {
        "id": 1,
        "name": "Jane Doe",
        "email": "jane100@example.com",
        "username": "jane100",
        "timeZone": "America/Los_Angeles"
      }
    ],
    "attendees": [
      {
        "name": "John Doe",
        "email": "john@example.com",
        "timeZone": "America/New_York",
        "language": "en",
        "absent": false,
        "phoneNumber": "+1234567890"
      }
    ],
    "start": "2024-08-13T15:30:00Z",
    "end": "2024-08-13T16:30:00Z"
  }
}
```

### Key Observations

1. **All fields are required** - Every host object includes id, name, email, username, and timeZone
2. **Multiple hosts possible** - The array can contain multiple host entries for team events
3. **No explicit "primary" indicator** - Cal.com doesn't mark one host as "primary" in the response
4. **Consistent across booking types** - Same structure for regular, recurring, and seated bookings

---

## 2. Multi-Host Scenarios

Cal.com supports three primary team event types that affect host assignment:

### 2.1 Round-Robin Events

**Purpose:** Distribute bookings evenly among team members in cyclic rotation

**How Host Assignment Works:**
- System automatically selects the next available host based on rotation algorithm
- Selection factors (in priority order):
  1. **Weights** - If enabled, hosts with higher weights receive more bookings
  2. **Priority levels** - Hosts can be set to "lowest", "medium", or "high" priority
  3. **Least recently booked** - Fallback method selects host booked least recently

**Configuration Example:**
```json
{
  "schedulingType": "roundRobin",
  "hosts": [
    {
      "userId": 123,
      "mandatory": false,
      "priority": "medium"
    },
    {
      "userId": 456,
      "mandatory": true,
      "priority": "high"
    }
  ]
}
```

**Booking Response:**
- Only **one host** from the pool appears in `hosts` array
- The selected host is determined at booking time
- **Important:** `hosts[0]` is the assigned host for Round-Robin events

**Round-Robin Groups Feature:**
- Can create groups of hosts where one member from each group is selected
- Within each group, standard Round-Robin algorithm determines selection
- Ensures representation from different teams/departments

### 2.2 Collective Events

**Purpose:** All assigned team members must attend together

**How Host Assignment Works:**
- Availability calculated as **intersection** of all hosts' calendars
- All assigned hosts appear in the `hosts` array
- Every host receives the booking on their calendar

**Configuration Example:**
```json
{
  "schedulingType": "collective",
  "assignAllTeamMembers": true
}
```

**Booking Response:**
- **Multiple hosts** appear in `hosts` array
- All hosts are equally important (no primary designation)
- Example: `hosts: [host1, host2, host3]` - all are required attendees

### 2.3 Managed Events

**Purpose:** Centralized control over event scheduling with designated hosts

**How Host Assignment Works:**
- Event manager controls which hosts are assigned
- Hosts can be pre-assigned or dynamically selected
- Provides greatest control and consistency

**Configuration Example:**
```json
{
  "schedulingType": "managed",
  "hosts": [
    {
      "userId": 789,
      "mandatory": true
    }
  ]
}
```

**Booking Response:**
- Assigned host(s) appear in `hosts` array
- Can be single or multiple hosts depending on configuration

### Comparison Matrix

| Event Type | Hosts in Response | Selection Method | Use Case |
|-----------|-------------------|------------------|----------|
| **Round-Robin** | 1 host | Automated rotation (weights/priority/LRU) | Distribute workload evenly |
| **Collective** | All hosts | All must be available | Team meetings, group sessions |
| **Managed** | Pre-assigned hosts | Manual assignment | Controlled scheduling, specific expertise |

---

## 3. Event Type Configuration

### Host Assignment Configuration

When creating/updating event types, hosts are configured as follows:

```json
POST /v2/event-types
{
  "title": "Sales Call",
  "lengthInMinutes": 30,
  "schedulingType": "roundRobin",
  "hosts": [
    {
      "userId": 123,
      "mandatory": true,
      "priority": "high"
    },
    {
      "userId": 456,
      "mandatory": false,
      "priority": "medium"
    }
  ]
}
```

**Host Configuration Fields:**
- `userId` (number, required) - Cal.com user ID (for platform customers: managed users only)
- `mandatory` (boolean) - Whether host must be present
- `priority` (string) - Priority level: "lowest" | "medium" | "high"

**Alternative: Assign All Team Members**
```json
{
  "assignAllTeamMembers": true
}
```
- When `true`, all current and future team members are automatically assigned
- Simplifies management for large teams

### Availability Calculation

**Round-Robin Events:**
- **Union of all slots** - If any host is available, slot is offered
- Booking assigned to available host based on selection algorithm

**Collective Events:**
- **Intersection of all slots** - Only slots where ALL hosts are available
- All hosts added to booking

---

## 4. API Endpoints for Host Management

### 4.1 Get Booking with Hosts

```bash
GET /v2/bookings/{bookingUid}
Headers:
  Authorization: Bearer <api-key>
  cal-api-version: 2024-08-13

Response:
{
  "status": "success",
  "data": {
    "hosts": [...],
    // ... other fields
  }
}
```

### 4.2 Reassign Booking to Specific Host

**Endpoint:** `POST /v2/bookings/{bookingUid}/reassign/{userId}`

**Constraints:**
- **Only supports Round-Robin bookings currently**
- Requires booking owner authorization
- Must provide reason for reassignment

```bash
POST /v2/bookings/booking_uid_123/reassign/456
Headers:
  Authorization: Bearer <api-key>
  cal-api-version: 2024-08-13
Content-Type: application/json

{
  "reason": "Host has to take another call"
}

Response:
{
  "status": "success",
  "data": {}
}
```

### 4.3 Reassign to Auto-Selected Host

```bash
POST /v2/bookings/{bookingUid}/reassign
Headers:
  Authorization: Bearer <api-key>
  cal-api-version: 2024-08-13

{
  "reason": "Original host unavailable"
}
```
- System automatically selects next available host using Round-Robin algorithm

### 4.4 Get All Bookings with Filtering

```bash
GET /v2/bookings?eventTypeIds=123&teamIds=456
Headers:
  Authorization: Bearer <api-key>
  cal-api-version: 2024-08-13

Query Parameters:
  - status: Filter by booking status
  - attendeeName: Filter by attendee name
  - eventTypeIds: Filter by event type IDs
  - teamIds: Filter by team IDs
  - afterStart: Bookings starting after date
  - beforeEnd: Bookings ending before date
```

### 4.5 Get Event Type Configuration

```bash
GET /v2/event-types/{eventTypeId}
Headers:
  Authorization: Bearer <api-key>
  cal-api-version: 2024-08-13

Response includes:
  - schedulingType
  - hosts configuration
  - assignAllTeamMembers setting
```

---

## 5. Hosts vs Organizer vs User

### Key Distinctions

Based on Cal.com API v2 documentation analysis:

| Field | Purpose | Location | Description |
|-------|---------|----------|-------------|
| **hosts** | Assigned staff | Booking response | Array of Cal.com users assigned to conduct the meeting |
| **attendees** | External participants | Booking response | People booking the meeting (customers/clients) |
| **guests** | Additional participants | Booking response | Email addresses of extra attendees invited by booker |
| **organizer** | ⚠️ Not in v2 API | N/A | Concept exists in UI/event names but not as separate API field |
| **user** | API caller context | Authentication | The authenticated user making the API request |

### Important Clarification

**Cal.com v2 API does NOT have a separate "organizer" field in booking responses.**

The term "organizer" appears in:
1. **Event naming templates** - `{Organiser}` placeholder for custom event names
2. **Location pre-fill** - "Organizer Address" as location type
3. **UI context** - User who created the event type

**In API responses, the `hosts` array serves the organizer role:**
- For single-host events: `hosts[0]` is effectively the organizer
- For multi-host events: All hosts are co-organizers

---

## 6. Determining the Primary/Assigned Host

### Question: Is `hosts[0]` always the primary/assigned host?

**Answer:** It depends on event type and context:

#### Round-Robin Events
✅ **YES** - `hosts[0]` is the assigned host
- Only one host in array
- This host was selected by the Round-Robin algorithm
- Safe to use `hosts[0]` as primary contact

#### Collective Events
⚠️ **NO SINGLE PRIMARY** - All hosts are equal
- Multiple hosts in array
- All are required attendees
- No designation of "primary" vs "secondary"
- Consider `hosts[0]` as first alphabetically or by ID, not primary

#### Managed Events
⚠️ **POSSIBLY** - Depends on configuration
- May have one or multiple hosts
- If `hosts.length === 1`, then `hosts[0]` is the assigned host
- If multiple, no explicit primary designation

### Recommended Approach

```typescript
function getPrimaryHost(booking: Booking): BookingHost | null {
  if (!booking.hosts || booking.hosts.length === 0) {
    return null;
  }

  // For Round-Robin: hosts[0] is the assigned host
  if (booking.eventType.schedulingType === 'roundRobin') {
    return booking.hosts[0];
  }

  // For single-host events: hosts[0] is primary
  if (booking.hosts.length === 1) {
    return booking.hosts[0];
  }

  // For Collective/multi-host: No single primary
  // Option 1: Return first host
  return booking.hosts[0];

  // Option 2: Return null to indicate ambiguity
  // return null;

  // Option 3: Return all hosts for caller to decide
  // return booking.hosts;
}
```

---

## 7. Staff ID Mapping Best Practices

### 7.1 The Mapping Challenge

**Scenario:** You have an internal staff database with your own IDs, but Cal.com returns their user IDs in the `hosts` array.

**Challenge:** How to reliably map Cal.com user IDs to your internal staff records?

### 7.2 Recommended Architecture

#### Database Schema

```sql
-- Your internal staff table
CREATE TABLE staff (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  department VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Mapping table for external IDs
CREATE TABLE staff_external_mappings (
  id SERIAL PRIMARY KEY,
  staff_id INTEGER NOT NULL REFERENCES staff(id) ON DELETE CASCADE,
  external_system VARCHAR(50) NOT NULL,  -- 'calcom', 'salesforce', etc.
  external_id VARCHAR(255) NOT NULL,     -- Cal.com user ID as string
  external_username VARCHAR(255),        -- Cal.com username for reference
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(external_system, external_id),
  UNIQUE(staff_id, external_system)
);

-- Index for fast lookups
CREATE INDEX idx_external_mappings_lookup
  ON staff_external_mappings(external_system, external_id);
```

#### Benefits of Separate Mapping Table

1. **Isolation** - External system changes don't affect core staff table
2. **Multiple systems** - Support multiple booking platforms simultaneously
3. **Auditability** - Track when mappings were created/updated
4. **Flexibility** - Easy to add/remove external system integrations
5. **Data integrity** - Core staff data remains clean and controlled

### 7.3 Initialization Strategy

#### Option A: Proactive Sync (Recommended)

```typescript
// Sync Cal.com users to your system periodically
async function syncCalcomUsers() {
  const calcomUsers = await calcomApi.getTeamMembers();

  for (const calcomUser of calcomUsers) {
    // Find or create staff by email (unique identifier)
    let staff = await db.staff.findOne({ email: calcomUser.email });

    if (!staff) {
      staff = await db.staff.create({
        name: calcomUser.name,
        email: calcomUser.email,
      });
    }

    // Create or update mapping
    await db.staffExternalMappings.upsert({
      staff_id: staff.id,
      external_system: 'calcom',
      external_id: calcomUser.id.toString(),
      external_username: calcomUser.username,
      updated_at: new Date()
    });
  }
}

// Run daily or when team changes
```

#### Option B: Lazy Initialization

```typescript
// Create mapping on-demand when booking received
async function getOrCreateStaffMapping(calcomHost: BookingHost) {
  // Check if mapping exists
  let mapping = await db.staffExternalMappings.findOne({
    external_system: 'calcom',
    external_id: calcomHost.id.toString()
  });

  if (mapping) {
    return mapping.staff_id;
  }

  // Mapping doesn't exist - create staff and mapping
  const staff = await db.staff.findOne({ email: calcomHost.email });

  if (!staff) {
    staff = await db.staff.create({
      name: calcomHost.name,
      email: calcomHost.email
    });
  }

  mapping = await db.staffExternalMappings.create({
    staff_id: staff.id,
    external_system: 'calcom',
    external_id: calcomHost.id.toString(),
    external_username: calcomHost.username
  });

  return staff.id;
}
```

### 7.4 Lookup Implementation

```typescript
// Fast lookup from Cal.com ID to internal staff ID
async function getInternalStaffId(calcomUserId: number): Promise<number | null> {
  const mapping = await db.staffExternalMappings.findOne({
    external_system: 'calcom',
    external_id: calcomUserId.toString()
  });

  return mapping?.staff_id || null;
}

// Reverse lookup: Internal staff ID to Cal.com user ID
async function getCalcomUserId(staffId: number): Promise<number | null> {
  const mapping = await db.staffExternalMappings.findOne({
    staff_id: staffId,
    external_system: 'calcom'
  });

  return mapping ? parseInt(mapping.external_id) : null;
}

// Process booking hosts
async function processBookingHosts(booking: Booking) {
  const internalStaffIds = await Promise.all(
    booking.hosts.map(host => getInternalStaffId(host.id))
  );

  // Filter out nulls (unmapped hosts)
  return internalStaffIds.filter(id => id !== null);
}
```

### 7.5 Best Practices Summary

| Practice | Recommendation | Rationale |
|----------|---------------|-----------|
| **Unique Key** | Use email as matching key | Most stable identifier across systems |
| **ID Storage** | Store external IDs as strings | Prevents type issues, supports various ID formats |
| **Sync Strategy** | Proactive sync preferred | Prevents missing mappings during booking processing |
| **Fallback Handling** | Create mapping on-demand if missing | Graceful degradation for new team members |
| **Audit Trail** | Track created_at and updated_at | Debug sync issues and data changes |
| **Multiple Systems** | Support via external_system field | Future-proof for additional integrations |
| **Data Cleanup** | CASCADE delete on staff removal | Maintain referential integrity |

---

## 8. Edge Cases and Gotchas

### 8.1 Host Reassignment Limitations

⚠️ **Current Limitation:** Reassignment only works for Round-Robin bookings

```typescript
// This works
POST /v2/bookings/{roundRobinBookingUid}/reassign/{newUserId}

// This fails for Collective/Managed events
POST /v2/bookings/{collectiveBookingUid}/reassign/{newUserId}
// Error: Reassignment not supported for this event type
```

**Workaround:** For Collective/Managed events, you must:
1. Cancel the original booking
2. Create a new booking with different hosts

### 8.2 Platform Customer Constraints

⚠️ **Managed Users Only:** Platform customers can only use managed users as hosts

```json
{
  "hosts": [
    {
      "userId": 123  // Must be a managed user, not a regular Cal.com user
    }
  ]
}
```

**Impact:** If integrating as platform customer, ensure all staff are created as managed users

### 8.3 Host Availability Edge Cases

**Scenario:** Round-Robin host becomes unavailable after booking

```typescript
// Host availability check at booking time
Slot offered at 2:00 PM ✅ Host A available

// Host becomes unavailable later
Host A marks 2:00 PM as busy ⚠️

// Booking still shows Host A
booking.hosts[0].id === Host A ID  // Still assigned
```

**Mitigation:** Use reassignment endpoint to switch hosts if needed

### 8.4 Deleted/Deactivated Hosts

⚠️ **Orphaned References:** If a Cal.com user is deleted, past bookings still reference their ID

```json
{
  "hosts": [
    {
      "id": 999,  // User no longer exists
      "name": "Former Employee",
      "email": "former@company.com"
    }
  ]
}
```

**Recommended Handling:**
```typescript
async function getActiveHost(calcomUserId: number) {
  const mapping = await getStaffMapping(calcomUserId);

  if (!mapping) {
    logger.warn(`No mapping found for Cal.com user ${calcomUserId}`);
    return null;
  }

  const staff = await db.staff.findById(mapping.staff_id);

  if (!staff || staff.status === 'inactive') {
    logger.warn(`Staff ${mapping.staff_id} is inactive or deleted`);
    return null;
  }

  return staff;
}
```

### 8.5 Round-Robin Group Complexity

**Round-Robin Groups** can have multiple hosts selected (one per group):

```json
{
  "hosts": [
    {
      "id": 123,  // Selected from Group A (Sales)
      "name": "Alice"
    },
    {
      "id": 456,  // Selected from Group B (Support)
      "name": "Bob"
    }
  ]
}
```

**Implication:** Even Round-Robin events can have multiple hosts if groups are configured

**Updated Logic:**
```typescript
function getPrimaryHost(booking: Booking): BookingHost {
  // Round-Robin can have multiple hosts if groups are used
  // Return first host as primary, but be aware of multi-host possibility
  return booking.hosts[0];
}
```

---

## 9. Code Examples

### 9.1 Full Booking Processing Example

```typescript
import { CalcomAPI, Booking, BookingHost } from './calcom-types';

interface InternalStaff {
  id: number;
  name: string;
  email: string;
  department?: string;
}

class BookingProcessor {
  constructor(
    private calcomApi: CalcomAPI,
    private db: Database
  ) {}

  async processNewBooking(webhookPayload: any) {
    const bookingUid = webhookPayload.uid;

    // Fetch full booking details
    const booking = await this.calcomApi.getBooking(bookingUid);

    // Process hosts
    const assignedStaff = await this.mapHostsToInternalStaff(booking.hosts);

    // Determine primary host based on event type
    const primaryStaff = this.determinePrimaryStaff(
      booking,
      assignedStaff
    );

    // Store booking in internal system
    await this.db.bookings.create({
      external_booking_id: booking.uid,
      external_system: 'calcom',
      primary_staff_id: primaryStaff?.id,
      all_staff_ids: assignedStaff.map(s => s.id),
      customer_email: booking.attendees[0]?.email,
      start_time: booking.start,
      end_time: booking.end,
      status: booking.status,
      event_type: booking.eventType.slug,
      scheduling_type: booking.eventType.schedulingType
    });

    // Notify assigned staff
    await this.notifyStaff(assignedStaff, booking);
  }

  private async mapHostsToInternalStaff(
    hosts: BookingHost[]
  ): Promise<InternalStaff[]> {
    const staffPromises = hosts.map(async (host) => {
      const staffId = await this.getOrCreateStaffMapping(host);
      return this.db.staff.findById(staffId);
    });

    const staff = await Promise.all(staffPromises);
    return staff.filter(s => s !== null) as InternalStaff[];
  }

  private async getOrCreateStaffMapping(
    host: BookingHost
  ): Promise<number> {
    // Check existing mapping
    let mapping = await this.db.staffExternalMappings.findOne({
      external_system: 'calcom',
      external_id: host.id.toString()
    });

    if (mapping) {
      return mapping.staff_id;
    }

    // Find staff by email or create new
    let staff = await this.db.staff.findOne({ email: host.email });

    if (!staff) {
      staff = await this.db.staff.create({
        name: host.name,
        email: host.email
      });
    }

    // Create mapping
    mapping = await this.db.staffExternalMappings.create({
      staff_id: staff.id,
      external_system: 'calcom',
      external_id: host.id.toString(),
      external_username: host.username,
      created_at: new Date(),
      updated_at: new Date()
    });

    return staff.id;
  }

  private determinePrimaryStaff(
    booking: Booking,
    assignedStaff: InternalStaff[]
  ): InternalStaff | null {
    if (assignedStaff.length === 0) {
      return null;
    }

    // For Round-Robin: First (and usually only) host is primary
    if (booking.eventType.schedulingType === 'roundRobin') {
      return assignedStaff[0];
    }

    // For single-host events: Only host is primary
    if (assignedStaff.length === 1) {
      return assignedStaff[0];
    }

    // For Collective: All hosts are equal, return first as "primary"
    // Consider returning null or all hosts depending on your business logic
    return assignedStaff[0];
  }

  private async notifyStaff(
    staff: InternalStaff[],
    booking: Booking
  ) {
    for (const staffMember of staff) {
      await this.sendNotification(staffMember, {
        subject: `New booking: ${booking.title}`,
        body: `You have been assigned to a booking at ${booking.start}`,
        booking_link: `https://yourapp.com/bookings/${booking.uid}`
      });
    }
  }
}
```

### 9.2 Host Reassignment Example

```typescript
async function reassignBookingHost(
  bookingUid: string,
  newStaffId: number,
  reason: string
) {
  // Get Cal.com user ID from internal staff ID
  const mapping = await db.staffExternalMappings.findOne({
    staff_id: newStaffId,
    external_system: 'calcom'
  });

  if (!mapping) {
    throw new Error(`No Cal.com mapping for staff ${newStaffId}`);
  }

  const calcomUserId = parseInt(mapping.external_id);

  // Reassign via Cal.com API
  try {
    await calcomApi.reassignBooking(bookingUid, calcomUserId, reason);

    // Update internal records
    await db.bookings.update(
      { external_booking_id: bookingUid },
      {
        primary_staff_id: newStaffId,
        reassignment_reason: reason,
        reassigned_at: new Date()
      }
    );

    return { success: true };
  } catch (error) {
    if (error.message.includes('only supports reassigning host for round robin')) {
      throw new Error('Cannot reassign: Event type does not support reassignment');
    }
    throw error;
  }
}
```

### 9.3 Webhook Handler Example

```typescript
import express from 'express';

const app = express();

app.post('/webhooks/calcom', async (req, res) => {
  const event = req.body;

  try {
    switch (event.type) {
      case 'booking.created':
        await handleBookingCreated(event.payload);
        break;

      case 'booking.rescheduled':
        await handleBookingRescheduled(event.payload);
        break;

      case 'booking.cancelled':
        await handleBookingCancelled(event.payload);
        break;

      case 'booking.reassigned':
        await handleBookingReassigned(event.payload);
        break;
    }

    res.status(200).json({ received: true });
  } catch (error) {
    console.error('Webhook processing error:', error);
    res.status(500).json({ error: 'Processing failed' });
  }
});

async function handleBookingReassigned(payload: any) {
  const booking = await calcomApi.getBooking(payload.uid);

  // New host is in hosts array
  const newHost = booking.hosts[0];
  const newStaffId = await getInternalStaffId(newHost.id);

  // Update internal records
  await db.bookings.update(
    { external_booking_id: booking.uid },
    {
      primary_staff_id: newStaffId,
      reassigned_at: new Date()
    }
  );

  // Notify new host
  const staff = await db.staff.findById(newStaffId);
  await notifyStaff(staff, booking, 'reassigned');
}
```

---

## 10. Recommended Mapping Strategy

### Strategy: Email-Based Mapping with Proactive Sync

#### Implementation Steps

**Step 1: Initial Setup**
```sql
-- Create tables as shown in section 7.2
CREATE TABLE staff_external_mappings (...);
```

**Step 2: Initial Sync**
```typescript
// Run once to sync all existing Cal.com users
await syncCalcomUsers();
```

**Step 3: Periodic Sync**
```typescript
// Run daily via cron job
cron.schedule('0 2 * * *', async () => {
  await syncCalcomUsers();
  console.log('Cal.com user sync completed');
});
```

**Step 4: Webhook Processing**
```typescript
// Process bookings using mappings
app.post('/webhooks/calcom', async (req, res) => {
  const booking = req.body.payload;
  const internalStaffIds = await Promise.all(
    booking.hosts.map(h => getInternalStaffId(h.id))
  );
  // ... process booking
});
```

**Step 5: Fallback Handling**
```typescript
// If mapping missing during webhook processing
async function getInternalStaffIdWithFallback(calcomHost: BookingHost) {
  let staffId = await getInternalStaffId(calcomHost.id);

  if (!staffId) {
    // Create mapping on-demand
    staffId = await getOrCreateStaffMapping(calcomHost);
    logger.info(`Created mapping on-demand for ${calcomHost.email}`);
  }

  return staffId;
}
```

### Why This Strategy?

✅ **Pros:**
- Reliable: Mappings ready before bookings arrive
- Fast: No on-demand API calls during booking processing
- Auditable: Clear sync schedule and logs
- Resilient: Fallback handles edge cases

⚠️ **Cons:**
- Requires cron job setup
- Slight delay for brand new team members (max 24 hours)

### Alternative: Lazy Mapping

Use if:
- Team changes are infrequent
- You prefer simpler architecture
- On-demand API calls are acceptable

```typescript
// No sync job needed - create mappings as bookings arrive
async function processBooking(booking: Booking) {
  for (const host of booking.hosts) {
    const staffId = await getOrCreateStaffMapping(host);
    // ... use staffId
  }
}
```

---

## 11. API Reference Quick Guide

### Authentication Headers

```bash
Authorization: Bearer <cal_live_xxx>  # API Key
cal-api-version: 2024-08-13           # Required version header
```

### Essential Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/v2/bookings/{uid}` | Get single booking with hosts |
| GET | `/v2/bookings` | List bookings with filters |
| POST | `/v2/bookings` | Create new booking |
| POST | `/v2/bookings/{uid}/reassign/{userId}` | Reassign to specific host (Round-Robin only) |
| POST | `/v2/bookings/{uid}/reassign` | Auto-reassign host (Round-Robin only) |
| GET | `/v2/event-types/{id}` | Get event type config |
| GET | `/v2/teams/{id}/members` | Get team members |

### Filtering Bookings

```bash
GET /v2/bookings?eventTypeIds=123&status=accepted&afterStart=2024-08-01
```

**Available Filters:**
- `status` - cancelled | accepted | rejected | pending
- `attendeeName` - Filter by attendee name
- `eventTypeIds` - Comma-separated event type IDs
- `teamIds` - Comma-separated team IDs
- `afterStart` - ISO 8601 date
- `beforeEnd` - ISO 8601 date
- `afterCreatedAt` - ISO 8601 date

---

## 12. Conclusion

### Key Takeaways

1. **Hosts Array is Primary Source**
   - No separate "organizer" field in v2 API
   - `hosts` array contains all assigned staff
   - For Round-Robin: `hosts[0]` is the assigned host
   - For Collective: All hosts in array are required

2. **Event Type Determines Assignment**
   - Round-Robin: Automated rotation with weights/priority
   - Collective: All hosts must be available
   - Managed: Manual control over assignment

3. **Reassignment Limitations**
   - Only Round-Robin events support reassignment currently
   - Must provide reason for reassignment
   - Use webhooks to track reassignment events

4. **Mapping Strategy**
   - Use separate mapping table for external IDs
   - Email is most reliable matching key
   - Proactive sync preferred over lazy initialization
   - Store Cal.com IDs as strings for flexibility

5. **Edge Cases to Handle**
   - Deleted/deactivated Cal.com users
   - Round-Robin groups (multiple hosts possible)
   - Platform customer constraints (managed users only)
   - Missing mappings during webhook processing

### Next Steps

1. **Implement Mapping Infrastructure**
   - Create `staff_external_mappings` table
   - Set up proactive sync job
   - Add fallback handling for missing mappings

2. **Configure Webhooks**
   - Subscribe to booking.created, booking.reassigned events
   - Implement webhook handlers with proper error handling
   - Test webhook processing with various event types

3. **Test Multi-Host Scenarios**
   - Create Round-Robin, Collective, and Managed event types
   - Verify host assignment for each type
   - Test reassignment flow for Round-Robin events

4. **Monitor and Optimize**
   - Track mapping creation/lookup performance
   - Monitor for unmapped hosts in logs
   - Audit sync job success rate

---

## References

**Official Documentation:**
- [Cal.com API v2 Introduction](https://cal.com/docs/api-reference/v2/introduction)
- [Create Booking](https://cal.com/docs/api-reference/v2/bookings/create-a-booking)
- [Get Bookings](https://cal.com/docs/api-reference/v2/bookings/get-all-bookings)
- [Reassign Booking](https://cal.com/docs/api-reference/v2/bookings/reassign-a-booking-to-a-specific-host)
- [Event Types](https://cal.com/docs/api-reference/v2/event-types/create-an-event-type)
- [Team Event Types](https://cal.com/docs/api-reference/v2/teams-event-types/create-an-event-type)

**Guides:**
- [Round-Robin Scheduling Guide](https://cal.com/blog/round-robin-scheduling-guide)
- [Event Types Guide](https://cal.com/blog/event-types-guide-calcom)
- [Team Appointment Types](https://cal.com/blog/cal-com-s-team-appointment-types-exploring-collective-round-robin-and-managed-eve)
- [Round Robin Help](https://cal.com/help/event-types/round-robin)
- [Managed Events Help](https://cal.com/help/event-types/managed-events)

**API Version Notes:**
- [v1 to v2 Differences](https://cal.com/docs/api-reference/v2/v1-v2-differences)
- [API v1 Deprecation Announcement](https://cal.com/blog/calcom-v5-6)

---

**Report Confidence:** 90% (High)
**Limitations:**
- Some schema details inferred from documentation examples rather than formal OpenAPI specs
- GitHub repository schemas not directly accessible in this research
- "Organizer" field absence confirmed by documentation review, but edge cases may exist

**Recommended Validation:**
- Test actual API responses with your Cal.com instance
- Verify Round-Robin group behavior with multi-group configurations
- Confirm reassignment limitations with Cal.com support if critical to your use case
