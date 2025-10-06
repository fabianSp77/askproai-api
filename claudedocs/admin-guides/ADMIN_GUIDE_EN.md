# AskProAI Admin Guide (English)

**Last Updated:** October 2, 2025
**Version:** 1.0
**Audience:** Business Owners, Managers, Administrators

---

## Table of Contents

1. [Introduction](#introduction)
2. [Callback Management](#callback-management)
3. [Policy Configuration](#policy-configuration)
4. [Troubleshooting](#troubleshooting)
5. [Best Practices](#best-practices)

---

## Introduction

Welcome to the AskProAI Admin Guide. This document helps you understand and effectively use the core administrative features of the appointment management system.

### What is AskProAI?

AskProAI is an intelligent appointment management system that:
- Enables automatic appointment booking through Cal.com
- Processes AI-powered voice calls with Retell AI
- Intelligently manages and assigns callback requests
- Supports flexible cancellation policies with fees

---

## Callback Management

### 📋 What is Callback Management?

When a customer calls but no immediate appointment is available, a **callback request** is created. The system manages these requests automatically and ensures no customer is forgotten.

### How Does Auto-Assignment Work?

The system intelligently assigns callback requests to your staff members:

#### Assignment Strategy

1. **Preferred Staff**
   - If the customer requests a specific staff member, they are checked first
   - Staff member must be active to be assigned

2. **Service Expert**
   - Staff members qualified for the requested service
   - System selects the expert with the lowest current workload

3. **Least Loaded**
   - If no expert is available, the staff member with the fewest open callbacks is chosen
   - This distributes workload evenly across the team

#### Priorities

The system recognizes three priority levels:

| Priority | Expiration Time | Auto-Assignment | Use Case |
|----------|----------------|-----------------|----------|
| **Normal** | 24 hours | Yes (if configured) | Standard requests |
| **High** | 4 hours | Always | Important customers |
| **Urgent** | 2 hours | Always | Emergencies, VIP customers |

### Status Overview

Each callback request goes through different statuses:

```
Pending → Assigned → Contacted → Completed
     ↓
  Expired (if not processed in time)
     ↓
  Escalated (to another staff member)
```

#### Status Meanings

- **Pending**: Newly created, waiting for processing
- **Assigned**: Assigned to a staff member who should contact
- **Contacted**: Staff member has already called the customer
- **Completed**: Successfully processed, appointment booked or issue resolved
- **Expired**: Time limit exceeded without processing
- **Cancelled**: Customer withdrew the request

### Escalation Rules

If a callback is not processed in time, the system automatically escalates:

#### When Does Escalation Occur?

- Callback is **overdue** (expiration time exceeded)
- No staff available at original assignment time
- Originally assigned staff member doesn't respond

#### What Happens During Escalation?

1. System finds another available staff member in the branch
2. Callback is assigned to the new staff member
3. Escalation event is logged with:
   - Reason for escalation
   - Original staff member
   - New staff member
   - Timestamp

### Managing Callback Requests in Admin Panel

#### Opening the View

1. Log into Filament Admin Panel
2. Navigate to **Appointments → Callbacks**
3. You'll see a table of all callback requests

#### Using Filters

Filter callbacks by:
- **Status**: Show only pending, assigned, or completed requests
- **Priority**: Focus on urgent or high-priority callbacks
- **Branch**: If you have multiple locations
- **Time Period**: Today, this week, this month

#### Manually Assigning a Callback

1. Click on the callback request
2. Select **Assign to Staff**
3. Choose the desired staff member from the list
4. Click **Save**

The system notifies the staff member of the new assignment.

#### Manually Changing Status

1. Open the callback request
2. Click **Change Status**
3. Select the new status:
   - **Contacted**: When staff member reached the customer
   - **Completed**: When issue is resolved
   - **Cancelled**: When no longer relevant
4. Add notes (recommended)
5. Click **Save**

### Common Scenarios

#### ✅ Scenario 1: Customer Calls Outside Business Hours

**What Happens:**
- Retell AI agent answers the call
- Detects no available appointments
- Creates callback request with "Normal" priority
- System assigns automatically when business hours begin

**What You Should Do:**
- Check pending callbacks in the morning
- Contact customers within expiration time
- Mark as "Completed" after booking appointment

#### ✅ Scenario 2: VIP Customer Needs Urgent Appointment

**What Happens:**
- Callback request is created with "Urgent" priority
- System assigns immediately (even outside normal hours)
- Expiration time: 2 hours

**What You Should Do:**
- Respond immediately to urgent callbacks
- Try to reorganize appointments if possible
- Document outcome in notes

#### ✅ Scenario 3: Staff Member is Sick, Can't Handle Callback

**What You Can Do:**
1. Open overdue callbacks
2. Click "Reassign"
3. Select available colleague
4. System updates assignment automatically

**Alternative:**
- Wait for system to automatically escalate (after expiration time)
- System finds another staff member automatically

### Best Practices for Callback Management

1. **Respond Quickly**
   - Contact customers within priority time limits
   - Urgent requests take precedence

2. **Document Everything**
   - Add notes to each callback
   - Record conversation outcomes, customer preferences

3. **Daily Review**
   - Check pending callbacks in the morning
   - Identify overdue requests

4. **Use Priorities Wisely**
   - Set "Urgent" only for genuine emergencies
   - "High" for important customers or time-critical requests
   - "Normal" for standard requests

5. **Train Your Team**
   - Ensure all staff members know the system
   - Explain the importance of quick response

---

## Policy Configuration

### 📋 Understanding Cancellation Policies

Cancellation policies define:
- **How long in advance** customers can cancel
- **How often** customers can cancel per month
- **What fees** apply for short-notice cancellation

### Policy Hierarchy

AskProAI uses a hierarchical system for policies. This means: More specific settings override general settings.

#### Hierarchy Order (from specific to general)

```
1. Staff (highest priority)
   ↓
2. Service
   ↓
3. Branch
   ↓
4. Company (lowest priority)
```

#### Example: How Hierarchy Works

**Your Configuration:**
- **Company**: Cancellation 24 hours in advance, fee €10
- **Berlin Branch**: Cancellation 48 hours in advance, fee €15
- **Service "Hair Coloring"**: Cancellation 72 hours in advance, fee €25

**Result:**
- Appointment for "Haircut" in Berlin: 48h advance, €15 (Branch policy)
- Appointment for "Hair Coloring" in Berlin: 72h advance, €25 (Service policy overrides)
- Appointment for "Haircut" in Hamburg (no branch policy): 24h advance, €10 (Company policy)

### Policy Types

#### 1. Cancellation Policy

Governs how customers can cancel appointments.

**Key Parameters:**

| Parameter | Description | Example |
|-----------|-------------|---------|
| `hours_before` | Minimum hours before appointment for cancellation | `24` (24 hours) |
| `max_cancellations_per_month` | Maximum cancellations per customer per month | `3` |
| `fee_tiers` | Tiered fees based on advance notice | See below |

**Fee Tiers:**

```json
"fee_tiers": [
  {
    "min_hours": 48,
    "fee": 0.0
  },
  {
    "min_hours": 24,
    "fee": 10.0
  },
  {
    "min_hours": 0,
    "fee": 15.0
  }
]
```

**Meaning:**
- **≥ 48 hours in advance**: No fee (€0)
- **24-48 hours in advance**: €10 fee
- **< 24 hours in advance**: €15 fee

#### 2. Reschedule Policy

Governs how customers can reschedule appointments.

**Key Parameters:**

| Parameter | Description | Example |
|-----------|-------------|---------|
| `hours_before` | Minimum hours before appointment for reschedule | `12` |
| `max_reschedules_per_appointment` | How many times a single appointment can be moved | `2` |
| `fee_tiers` | Tiered fees | Like cancellation |

### Configuring Policies

#### Creating Company Policy (applies to all)

1. Navigate to **Settings → Policies**
2. Select **New Policy**
3. Choose **Type**: Company
4. Select your company from the list
5. Choose **Policy Type**: Cancellation or Reschedule

**Example Configuration for Medical Practice:**

```
Policy Type: Cancellation
Hours Before: 24
Max Cancellations per Month: 2

Fee Tiers:
- ≥ 48h in advance: €0
- 24-48h in advance: €20
- < 24h in advance: €30
```

**Save** the policy.

#### Creating Branch Policy (overrides company policy)

1. Navigate to **Branches → Your Branch → Policies**
2. Click **New Policy**
3. Configure specific settings for this branch

**Example: Downtown Branch with Higher Demand**

```
Policy Type: Cancellation
Hours Before: 48 (stricter than company policy)
Max Cancellations per Month: 1 (stricter)

Fees:
- ≥ 72h in advance: €0
- 48-72h in advance: €15
- < 48h in advance: €40
```

#### Creating Service Policy (overrides branch and company policies)

1. Navigate to **Services → Your Service → Policies**
2. Click **New Policy**
3. Configure service-specific settings

**Example: Hair Salon - Coloring Treatment (expensive, time-intensive)**

```
Policy Type: Cancellation
Hours Before: 72 (3 days)
Max Cancellations per Month: 1

Fees:
- ≥ 72h in advance: €0
- 48-72h in advance: €50
- < 48h in advance: 100% of service price
```

For percentage fees:
```
Fee Percentage: 100
(System calculates automatically based on service price)
```

### Fee Configuration in Detail

#### Fixed Fees

Simplest method: One fixed fee for all cancellations/reschedules.

```json
{
  "hours_before": 24,
  "fee": 15.0
}
```

**Use Case:** Small businesses, simple services

#### Tiered Fees (recommended)

Flexible: Different fees depending on advance notice.

```json
{
  "hours_before": 24,
  "fee_tiers": [
    { "min_hours": 72, "fee": 0.0 },
    { "min_hours": 48, "fee": 10.0 },
    { "min_hours": 24, "fee": 20.0 },
    { "min_hours": 0, "fee": 30.0 }
  ]
}
```

**Use Case:** Medium to large businesses, fair gradation

#### Percentage Fees

Fee based on service price.

```json
{
  "hours_before": 48,
  "fee_percentage": 50
}
```

**Example:**
- Service costs €80
- Customer cancels < 48h in advance
- Fee: €40 (50% of €80)

**Use Case:** High-value services (cosmetics, consulting)

### Examples for Different Industries

#### 🏥 Medical Practice

```
Cancellation Policy:
- Hours Before: 24
- Max Cancellations/Month: 2
- Fees:
  - ≥ 24h: €0
  - < 24h: €25 (no-show fee)

Reschedule Policy:
- Hours Before: 12
- Max Reschedules per Appointment: 2
- Fee: €0 (no fee for rescheduling)
```

**Rationale:**
- 24h advance notice allows rebooking
- Fee only for short-notice cancellation
- Rescheduling is free (flexible for patients)

#### 💇 Hair Salon

```
Cancellation Policy (Standard):
- Hours Before: 24
- Max Cancellations/Month: 3
- Fees:
  - ≥ 48h: €0
  - 24-48h: €10
  - < 24h: €15

Service "Coloring Treatment":
- Hours Before: 72
- Max Cancellations/Month: 1
- Fees:
  - ≥ 72h: €0
  - 48-72h: €30
  - < 48h: €50
```

**Rationale:**
- Standard services: Moderate policy
- Special services: Stricter (materials are prepared)
- Tiered fees encourage early cancellation

#### 💼 Business Consulting

```
Cancellation Policy:
- Hours Before: 48
- Max Cancellations/Month: 1
- Fees: 50% of consulting fee

Reschedule Policy:
- Hours Before: 24
- Max Reschedules per Appointment: 1
- Fee: €0
```

**Rationale:**
- Long advance notice (consultant time is valuable)
- Percentage fee is fair with varying prices
- One free reschedule (flexibility)

#### 🍽️ Restaurant (Table Reservation)

```
Cancellation Policy:
- Hours Before: 4
- Max Cancellations/Month: unlimited
- Fees: €0

Large Groups (>6 people):
- Hours Before: 24
- Fee: €20 per person
```

**Rationale:**
- Short advance notice (tables can be reassigned quickly)
- Stricter for large groups (higher effort)

### Testing Policies

⚠️ **Important:** Test new policies before activating them!

#### Walk Through Test Scenario

1. Create test appointment in your system
2. Try cancellation at different times:
   - 5 days in advance
   - 2 days in advance
   - 12 hours in advance
3. Check calculated fees
4. Adjust policy if necessary

#### Train Staff

Before activating new policies:
1. Inform your team about changes
2. Explain rationale for policies
3. Practice handling customer inquiries
4. Prepare FAQ for common questions

---

## Troubleshooting

### ⚠️ Common Issues and Solutions

#### Issue 1: Callbacks Are Not Auto-Assigned

**Symptoms:**
- Callback requests remain in "Pending" status
- No automatic assignment to staff

**Possible Causes and Solutions:**

✅ **Solution 1: Enable Auto-Assignment**
1. Go to **Settings → Callbacks**
2. Check: **Auto-Assignment Enabled** = Yes
3. Save the setting

✅ **Solution 2: Check Staff Availability**
1. Navigate to **Staff**
2. Check for each staff member:
   - **Status**: Must be "Active"
   - **Working Hours**: Must be configured
3. Update inactive staff members

✅ **Solution 3: Check Branch Assignment**
1. Open **Staff**
2. Ensure: Staff members are assigned to the correct **Branch**
3. Callbacks are only assigned to staff from the same branch

**How to Check if It's Working:**
- Create test callback request
- Check after 1-2 minutes: Status should be "Assigned"
- Check assigned staff member

#### Issue 2: Policy Not Applied Correctly

**Symptoms:**
- Wrong fees calculated
- Cancellation allowed/denied despite policy
- Unexpected behavior with rescheduling

**Possible Causes and Solutions:**

✅ **Solution 1: Check Hierarchy**

Remember: Specific policies override general ones!

1. Check all policy levels:
   - Staff policy (highest priority)
   - Service policy
   - Branch policy
   - Company policy (lowest priority)

2. Identify which policy applies
3. Adjust the **correct level**

**Example:**
```
Problem: Service "Massage" should have 48h advance notice, but 24h is accepted

Check:
- Service "Massage" → Policies: hours_before = ?
- Branch → Policies: hours_before = 24 (This overrides!)

Solution: Create service policy with "is_override" = true
```

✅ **Solution 2: Clear Cache**

Policies are cached for performance. After changes:

```bash
# In terminal on your server:
cd /var/www/api-gateway
php artisan cache:clear
php artisan config:clear
```

Or in Admin Panel:
1. **Settings → System → Cache**
2. Click **Clear Cache**

✅ **Solution 3: Validate Policy Configuration**

Check JSON syntax:

```json
# ✅ CORRECT
{
  "hours_before": 24,
  "max_cancellations_per_month": 3,
  "fee_tiers": [
    { "min_hours": 48, "fee": 0.0 },
    { "min_hours": 24, "fee": 10.0 }
  ]
}

# ❌ WRONG (comma error)
{
  "hours_before": 24,
  "max_cancellations_per_month": 3,
  "fee_tiers": [
    { "min_hours": 48, "fee": 0.0 }
    { "min_hours": 24, "fee": 10.0 }  # Missing comma!
  ]
}
```

Use a JSON validator: https://jsonlint.com

#### Issue 3: Fees Are Not Calculated

**Symptoms:**
- Cancellation/reschedule shows €0 fee despite policy specifying fee
- Customer sees no fee information

**Possible Causes and Solutions:**

✅ **Solution 1: Check Advance Notice**

```
Policy: hours_before = 24, fee = €10
Appointment: Tomorrow 10:00 AM
Now: Today 9:00 AM
Advance Notice: 25 hours

→ Cancellation allowed, NO fee (>24h)
```

System is correct! Fee only applies for shorter-notice cancellation.

✅ **Solution 2: Check fee_tiers Order**

Fee tiers must be sorted from **high to low**:

```json
# ✅ CORRECT
"fee_tiers": [
  { "min_hours": 72, "fee": 0.0 },
  { "min_hours": 48, "fee": 10.0 },
  { "min_hours": 24, "fee": 20.0 },
  { "min_hours": 0, "fee": 30.0 }
]

# ❌ WRONG (incorrect order)
"fee_tiers": [
  { "min_hours": 0, "fee": 30.0 },
  { "min_hours": 24, "fee": 20.0 }
]
```

✅ **Solution 3: Check Appointment Price (for percentage fee)**

```
Policy: fee_percentage = 50
Appointment Price: NULL or €0

→ Fee = €0 (50% of €0)
```

Ensure services have correct prices:
1. **Services → Your Service**
2. Check **Price** field
3. Update if empty or 0

#### Issue 4: Cal.com Integration Not Working

**Symptoms:**
- Appointments not created in Cal.com
- Availabilities not loading
- Error during appointment booking

**Possible Causes and Solutions:**

✅ **Solution 1: Check API Connection**

1. Go to **Settings → Integrations → Cal.com**
2. Check:
   - **API Key**: Must be valid
   - **Event Type ID**: Must exist
   - **Status**: Must show "Connected"
3. Test connection with **Test Button**

✅ **Solution 2: Event Type Exists in Cal.com**

1. Log into Cal.com (https://cal.com)
2. Go to **Event Types**
3. Check: Event Type ID from AskProAI exists
4. Ensure: Event Type is **active**

✅ **Solution 3: API Rate Limits**

Cal.com has limits for API requests:
- **Basic**: 100 requests/hour
- **Pro**: 1000 requests/hour

If limit reached:
- Wait 1 hour
- Upgrade Cal.com plan
- Implement request batching

**Check in Logs:**
```bash
# On your server:
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "Cal.com"
```

Look for "Rate limit exceeded" or "429" errors.

### 📊 Checking Logs

When issues occur, logs are your best source of information.

#### In Admin Panel

1. Navigate to **System → Logs**
2. Filter by:
   - **Time Period**: Last hours/days
   - **Level**: Error, Warning
   - **Category**: Callbacks, Policies, Appointments
3. Look for relevant entries

#### On Server (for IT Staff)

```bash
# Main log
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Only errors
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep ERROR

# Callback-related logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "Callback"

# Policy-related logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "Policy"
```

#### Understanding Log Entries

**Successful Actions:**
```
✅ Created callback request | callback_id: 123 | customer_name: John Doe
📋 Callback assigned to staff | staff_name: Jane Smith
```

**Warnings:**
```
⚠️ No staff available for auto-assignment | branch_id: 5
⚠️ Callback escalated | reason: overdue
```

**Errors:**
```
❌ Failed to assign callback | error: Staff not found
❌ Policy calculation error | error: Invalid configuration
```

### 🆘 When to Contact Support

Contact support when:

1. **System-Wide Errors**
   - All users affected
   - Admin panel unreachable
   - No appointments bookable

2. **Data Issues**
   - Appointments disappearing
   - Customer data incorrect
   - Financial transactions wrong

3. **After Troubleshooting**
   - You've tried all solutions
   - Problem persists after 24 hours
   - Logs show unknown errors

4. **Critical Business Impact**
   - Customer complaints mounting
   - Revenue loss imminent
   - Legal concerns

**What You Should Provide:**

1. **Problem Description**
   - What happens?
   - What should happen?
   - Since when does the problem occur?

2. **Steps to Reproduce**
   - How can support replicate the issue?
   - What actions lead to the error?

3. **Screenshots**
   - Error messages
   - Relevant admin panel views
   - Log entries

4. **System Information**
   - Your company name/ID
   - Affected branch(es)
   - Browser/device (if relevant)

**Support Contact:**

- **Email**: support@askproai.com
- **Phone**: +49 (0) XXX XXXXXXX
- **Support Portal**: https://support.askproai.com
- **Emergency Hotline** (24/7): +49 (0) XXX XXXXXXX

---

## Best Practices

### 📝 General Recommendations

#### 1. Regular Review

**Daily:**
- Check pending callbacks
- Process overdue requests
- Review appointment cancellations

**Weekly:**
- Analyze callback statistics
- Review staff workload
- Monitor policy compliance

**Monthly:**
- Evaluate policy effectiveness
- Analyze fee revenue
- Train team as needed

#### 2. Policy Management

**Start Conservative:**
- Begin with moderate policies
- Collect feedback from customers and team
- Adjust incrementally

**Transparency:**
- Communicate policies clearly
- Display them on website and in confirmation emails
- Explain rationale when asked

**Flexibility:**
- Allow exceptions in justified cases
- Document exceptions
- Use manual override when necessary

#### 3. Team Training

**Onboarding for New Staff:**
1. Introduction to admin panel
2. Explanation of callback workflow
3. Training on policy communication
4. Practical exercises with test data

**Continuous Training:**
- Monthly team meetings
- Share best practices
- Discuss difficult cases
- Update on system changes

#### 4. Customer Communication

**With Callbacks:**
- Respond within priority time limits
- Be friendly and solution-oriented
- Offer multiple appointment options
- Confirm booked appointment in writing

**With Policies:**
- Explain policies during booking
- Send reminder emails with policies
- Be empathetic with exception requests
- Document agreements

#### 5. Performance Optimization

**Monitor Metrics:**
- **Callback Processing Time**: Average time until contact
- **Success Rate**: Percentage of completed callbacks
- **Escalation Rate**: How often are callbacks escalated?
- **Cancellation Rate**: Percentage of appointments with cancellation

**Set Goals:**
```
Example Goals:
- Average callback processing: < 2 hours
- Success rate: > 90%
- Escalation rate: < 5%
- Cancellation rate: < 10%
```

**Continuous Improvement:**
1. Identify bottlenecks
2. Test improvements
3. Measure impact
4. Scale successful approaches

### 🎯 Industry-Specific Tips

#### Healthcare (Doctor, Dentist, Physiotherapy)

- **Strict Policies**: No-shows cost a lot
- **Reminder System**: Automatic reminders 24h + 2h in advance
- **Emergency Slots**: Reserve slots for emergencies
- **Documentation**: Document reasons for cancellations (insurance)

#### Beauty & Wellness (Hair Salon, Cosmetics, Spa)

- **Service-Specific Policies**: Coloring stricter than cutting
- **Material Costs**: Consider in fees
- **Regular Customers**: Leniency for long-term customers
- **Seasonal Adjustments**: Stricter before holidays

#### Consulting & Coaching

- **Long Advance Times**: 48-72h standard
- **Preparation Time**: Consider in policies
- **Flexibility**: Allow free rescheduling
- **Package Bookings**: Special rules for series appointments

#### Restaurant

- **Short Advance Times**: 4-6h sufficient
- **Large Groups**: Stricter rules for 6+ people
- **No Fees**: Usually uncommon (except no-show)
- **Waiting List**: Use for cancellations

---

## Conclusion

This guide provides you with the fundamentals for effective management of callbacks and policies in AskProAI.

**Next Steps:**

1. ✅ Read relevant sections for your business
2. ✅ Configure your first policies
3. ✅ Train your team
4. ✅ Test the workflow
5. ✅ Collect feedback and optimize

**Help & Resources:**

- 📚 **Complete Documentation**: https://docs.askproai.com
- 🎥 **Video Tutorials**: https://askproai.com/tutorials
- 💬 **Community Forum**: https://community.askproai.com
- 📧 **Support**: support@askproai.com

---

**Good luck with AskProAI!** 🚀

*Last Updated: October 2, 2025 | Version 1.0*
