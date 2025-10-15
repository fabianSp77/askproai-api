# Composite Service Configuration: Hairdresser Example
## 2+ Hours Service with Multiple Breaks

### Overview
This document explains how to configure and book a complex hairdresser service that spans over 2 hours with two 20-minute breaks, performed by a single staff member.

### Service Structure

#### Total Duration: 2 hours 40 minutes (160 minutes)
- **Active Work Time**: 120 minutes
- **Break Time**: 40 minutes (2 x 20 minutes)

#### Service Segments Breakdown

```
Timeline:
09:00 ────┬──── 09:30  [Segment A: Waschen & Vorbereitung - 30 min]
          │
09:30 ····│···· 09:50  [PAUSE 1: Einwirkzeit Produkt - 20 min]
          │
09:50 ────┼──── 10:50  [Segment B: Schneiden/Styling - 60 min]
          │
10:50 ····│···· 11:10  [PAUSE 2: Farbe/Treatment - 20 min]
          │
11:10 ────┴──── 11:40  [Segment C: Finishing - 30 min]

Legend: ──── Work Time  ···· Break Time
```

### Configuration in Admin Panel

#### Step 1: Create the Service
Navigate to **Services** → **Create Service** in the Filament admin panel.

#### Step 2: Basic Service Information
```json
{
  "name": "Premium Hairdressing Complete Package",
  "category": "Hairdressing",
  "description": "Complete hair treatment including washing, cutting, coloring and styling",
  "duration_minutes": 160,
  "price": 180.00,
  "is_active": true
}
```

#### Step 3: Enable Composite Service
Toggle **"Komposite Dienstleistung aktivieren"** to `ON`

#### Step 4: Configure Segments
Add the following segments:

**Segment A - Preparation:**
```json
{
  "key": "A",
  "name": "Waschen & Vorbereitung",
  "duration": 30,
  "gap_after": 20,
  "preferSameStaff": true
}
```

**Segment B - Main Service:**
```json
{
  "key": "B",
  "name": "Schneiden/Styling",
  "duration": 60,
  "gap_after": 20,
  "preferSameStaff": true
}
```

**Segment C - Finishing:**
```json
{
  "key": "C",
  "name": "Finishing & Final Styling",
  "duration": 30,
  "gap_after": 0,
  "preferSameStaff": true
}
```

#### Step 5: Pause Policy
Set **"Pause Bookable Policy"** to `never` (Pauses cannot be booked by other customers)

### Staff Assignment

#### Configure Staff Capabilities
When assigning staff to this service:

1. Select staff member (e.g., "Maria Schmidt")
2. Set **"Allowed Segments"**: Select all [A, B, C]
3. Set **"Can Book"**: Yes
4. Set **"Skill Level"**: Expert
5. Set **"Weight"**: 100 (for primary stylist)

```json
{
  "staff_id": 1,
  "allowed_segments": ["A", "B", "C"],
  "can_book": true,
  "skill_level": "expert",
  "weight": 100,
  "is_primary": true
}
```

### Booking Process

#### API Request Example
```bash
POST /api/v2/bookings/composite
Content-Type: application/json

{
  "service_id": 123,
  "company_id": 1,
  "branch_id": 1,
  "customer": {
    "name": "Anna Müller",
    "email": "anna.mueller@example.com",
    "phone": "+49 123 456789"
  },
  "preferred_date": "2025-09-27",
  "preferred_time": "09:00",
  "staff_preference": "same_for_all",
  "notes": "Allergic to certain hair products - please use hypoallergenic"
}
```

#### System Processing Steps

1. **Find Available Slots**
   - System searches for a 30-minute slot for Segment A
   - Ensures 20-minute gap is available after
   - Checks 60-minute availability for Segment B
   - Ensures another 20-minute gap
   - Confirms 30-minute slot for Segment C

2. **Atomic Booking**
   - Acquires locks for all time slots
   - Books segments in reverse order (C→B→A) for safer rollback
   - Creates single appointment record with all segments

3. **Database Storage**
```json
{
  "id": 456,
  "service_id": 123,
  "customer_id": 789,
  "staff_id": 1,
  "is_composite": true,
  "composite_group_uid": "uuid-xxx",
  "starts_at": "2025-09-27 09:00:00",
  "ends_at": "2025-09-27 11:40:00",
  "segments": [
    {
      "index": 0,
      "key": "A",
      "staff_id": 1,
      "starts_at": "2025-09-27 09:00:00",
      "ends_at": "2025-09-27 09:30:00",
      "status": "booked"
    },
    {
      "index": 1,
      "key": "B",
      "staff_id": 1,
      "starts_at": "2025-09-27 09:50:00",
      "ends_at": "2025-09-27 10:50:00",
      "status": "booked"
    },
    {
      "index": 2,
      "key": "C",
      "staff_id": 1,
      "starts_at": "2025-09-27 11:10:00",
      "ends_at": "2025-09-27 11:40:00",
      "status": "booked"
    }
  ],
  "metadata": {
    "composite": true,
    "segment_count": 3,
    "total_pause_duration": 40
  }
}
```

### Calendar Display

#### Visual Representation
The appointment appears in the calendar as:

```
Staff: Maria Schmidt
─────────────────────────────────────────────
09:00 │ ████ Waschen & Vorbereitung
09:30 │ ░░░░ [Einwirkzeit 20 min]
09:50 │ ████████ Schneiden/Styling
10:50 │ ░░░░ [Farbe einwirken 20 min]
11:10 │ ████ Finishing
11:40 │ END
─────────────────────────────────────────────
Total: 2h 40min | Work: 2h | Breaks: 40min
Customer: Anna Müller
Status: Confirmed ✓
```

#### Color Coding
- **Work Segments**: Displayed in staff's assigned color (e.g., #4F46E5)
- **Break Segments**: Displayed in lighter shade (e.g., #E0E7FF)
- **Border**: Continuous border around entire appointment

### Benefits of This Configuration

1. **Resource Optimization**
   - Staff can handle quick tasks during breaks
   - Breaks used for product processing (no active work needed)
   - Prevents double-booking during critical work phases

2. **Customer Experience**
   - Clear communication of total time commitment
   - Breaks allow for comfort (coffee, phone calls)
   - Professional service with structured workflow

3. **Staff Management**
   - Clear workflow segments
   - Built-in breaks prevent fatigue
   - Efficient time utilization

4. **Business Benefits**
   - Premium service pricing justified by duration
   - Optimized staff utilization
   - Reduced no-shows (clear time communication)

### Variations and Customizations

#### Express Service (Without Breaks)
```json
{
  "segments": [
    {"key": "A", "name": "Complete Service", "duration": 90, "gap_after": 0}
  ]
}
```

#### Premium Service (With Additional Treatments)
```json
{
  "segments": [
    {"key": "A", "name": "Consultation & Wash", "duration": 20, "gap_after": 15},
    {"key": "B", "name": "Cut & Initial Style", "duration": 45, "gap_after": 30},
    {"key": "C", "name": "Color Application", "duration": 30, "gap_after": 45},
    {"key": "D", "name": "Final Style & Treatment", "duration": 40, "gap_after": 0}
  ]
}
```

### Troubleshooting

#### Issue: Cannot find available slots
**Solution**: Check that:
- Staff is assigned to all segments
- Staff calendar has sufficient continuous time
- Pause durations are not too restrictive

#### Issue: Booking fails for segment B or C
**Solution**:
- Ensure Cal.com event mappings exist for all segments
- Verify staff has permission for all segments
- Check that preferSameStaff setting matches availability

### API Endpoints

#### Check Availability
```bash
GET /api/v2/services/123/composite-availability?date=2025-09-27&staff_id=1
```

#### Create Booking
```bash
POST /api/v2/bookings/composite
```

#### Cancel Composite Booking
```bash
DELETE /api/v2/appointments/456/composite
```

#### Reschedule Composite Booking
```bash
PUT /api/v2/appointments/456/composite-reschedule
```

### Best Practices

1. **Always set `preferSameStaff: true`** for hairdressing services
2. **Configure realistic pause durations** based on actual processing times
3. **Set appropriate cancellation policies** due to long duration
4. **Use weight/priority** to assign senior stylists to complex services
5. **Monitor completion rates** to optimize segment durations

### Reporting and Analytics

Track these metrics for composite services:
- Average booking lead time
- Completion rate per segment
- Staff utilization during pauses
- Customer satisfaction scores
- Revenue per time unit comparison

### Integration with External Systems

The composite booking system integrates with:
- **Cal.com**: Each segment creates separate Cal.com booking
- **Google Calendar**: Shows as single block with segment details
- **SMS/Email**: Sends consolidated confirmation with timeline
- **POS Systems**: Bills as single transaction with itemized segments