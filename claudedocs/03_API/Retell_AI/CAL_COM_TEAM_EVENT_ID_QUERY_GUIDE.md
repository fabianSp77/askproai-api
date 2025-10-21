# Cal.com Team Event-ID Query Guide

**Last Updated:** 2025-10-21
**Status:** 🔴 CRITICAL - API Deprecation Alert
**Author:** Claude Code Session 2025-10-21

---

## ⚠️ CRITICAL: API Version Deprecation

**Cal.com API v1 is being phased out and will NO LONGER BE AVAILABLE after 2025.**

**All new code MUST use Cal.com API v2.**

---

## 🎯 Purpose

When working with Cal.com integration:
1. **Always query Team-specific Event-IDs first**
2. DO NOT assume all events in Cal.com belong to a team
3. Use the correct API endpoint to get Team-scoped events only
4. Verify Event-IDs are actually assigned to the Team

---

## 📋 Quick Reference

| Team | Team ID | Endpoint | Event-IDs |
|------|---------|----------|-----------|
| AskProAI | 39203 | `/v1/teams/39203/event-types` | 3664712, 2563193 |
| Friseur 1 | 34209 | `/v1/teams/34209/event-types` | 2942413, 3672814 |

---

## 🔍 How to Query Team Event-IDs

### Problem: Wrong Endpoints Lead to Wrong Results

❌ **WRONG - Returns all events (not team-scoped):**
```
GET /v1/event-types?apiKey={key}&teamId={teamId}
```
- Returns 15 global events
- NOT necessarily assigned to the team
- These are just available in Cal.com system

✅ **CORRECT - Returns only team-scoped events:**
```
GET /v1/teams/{teamId}/event-types?apiKey={apiKey}
```
- Returns ONLY events assigned to this specific team
- This is the authoritative source
- Always use this endpoint for multi-tenant isolation

---

## 📝 Code Examples

### PHP/Laravel Implementation

```php
<?php
// CORRECT: Query team-specific event types
$apiKey = config('services.calcom.api_key');
$teamId = 39203; // AskProAI

$client = new \GuzzleHttp\Client();

$response = $client->request('GET', "https://api.cal.com/v1/teams/{$teamId}/event-types", [
    'query' => [
        'apiKey' => $apiKey,
    ]
]);

$data = json_decode($response->getBody(), true);

// $data['event_types'] contains ONLY this team's events
foreach ($data['event_types'] as $event) {
    echo "Event ID: {$event['id']} | Title: {$event['title']}\n";
}
?>
```

### V2 API (Future Implementation)

```php
<?php
// Cal.com V2 API (when migrating)
$apiKey = config('services.calcom.api_key');
$teamId = 39203;
$apiVersion = config('services.calcom.api_version'); // e.g., '2024-08-13'

$client = new \GuzzleHttp\Client();

$response = $client->request('GET', "https://api.cal.com/v2/teams/{$teamId}/event-types", [
    'headers' => [
        'Authorization' => "Bearer {$apiKey}",
        'cal-api-version' => $apiVersion,
    ]
]);

$data = json_decode($response->getBody(), true);
// V2 returns data in different structure - verify when migrating
?>
```

---

## 🗂️ Real Data Reference

### AskProAI (Team ID: 39203)

**Team-Scoped Event-IDs:**
- `3664712` → "15 Minuten Schnellberatung"
- `2563193` → "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7"

**Global Events NOT assigned to this team:**
- 3670487-3670503 (Friseur events) - these are available in Cal.com but NOT assigned to AskProAI

### Friseur 1 (Team ID: 34209)

**Team-Scoped Event-IDs:**
- `2942413` → "Damenhaarschnitt"
- `3672814` → "Herrenhaarschnitt"

**Global Events NOT assigned to this team:**
- 3670487-3670503 (Different Friseur events) - these are in Cal.com but NOT assigned to Friseur 1

---

## 🔒 Multi-Tenant Security

**CRITICAL for data isolation:**

When validating Event-IDs, ALWAYS:

1. Get the Team-Scoped events using `/v1/teams/{teamId}/event-types`
2. Verify Event-ID exists in that specific team's list
3. Store both `company_id` and `calcom_event_type_id` in mappings
4. Use security check like `calcom_event_mappings` table

**Example Security Validation (from Service model):**

```php
// In Service.php - validate ownership
static::saving(function ($service) {
    if ($service->isDirty('calcom_event_type_id') && $service->calcom_event_type_id) {
        $isValid = DB::table('calcom_event_mappings')
            ->where('calcom_event_type_id', (string)$service->calcom_event_type_id)
            ->where('company_id', $service->company_id)
            ->exists();

        if (!$isValid) {
            throw new Exception(
                "Security: Event-Type does not belong to this company's Cal.com team"
            );
        }
    }
});
```

---

## 📊 Database Structure

### calcom_event_mappings table

```
id | company_id | calcom_event_type_id | created_at | updated_at
---|------------|----------------------|------------|------------
1  | 15         | 3664712              | ...        | ...
2  | 15         | 2563193              | ...        | ...
3  | 1          | 2942413              | ...        | ...
4  | 1          | 3672814              | ...        | ...
```

**Purpose:** Maintain 1:1 mapping of Cal.com events to Laravel companies for multi-tenant isolation.

---

## 🚨 Common Mistakes

### ❌ Mistake 1: Using wrong endpoint

```php
// WRONG - will return 15 events, not team-scoped
$url = "https://api.cal.com/v1/event-types?apiKey={$key}&teamId={$teamId}";
```

### ❌ Mistake 2: Assuming all returned events belong to team

Cal.com returns shared/global events that may not be assigned to the specific team.

### ❌ Mistake 3: Not checking API version

```php
// WRONG - V1 will be deprecated
// RIGHT - Use V2 when fully migrated
```

---

## ✅ Checklist: Before Using Event-IDs

- [ ] Query using `/v1/teams/{teamId}/event-types` endpoint
- [ ] Verify Event-ID is in the response (team-scoped)
- [ ] Check `calcom_event_mappings` table for company ownership
- [ ] Confirm `company_id` matches the team's company in database
- [ ] For V2 migration: Use Bearer token + api-version header
- [ ] Log the team ID when querying for audit trail

---

## 📌 For Future V2 Migration

When Cal.com deprecates V1 API (end of 2025):

1. Update all endpoints from `/v1/` to `/v2/`
2. Change authorization: `Authorization: Bearer {apiKey}` (instead of query param)
3. Add header: `cal-api-version: {version}` (e.g., 2024-08-13)
4. Test response structure changes (V2 may return different JSON structure)
5. Update `config/services.php` Cal.com base URL
6. Search codebase for all Cal.com API calls and migrate them
7. Remove all V1 API usage

---

## 🔗 Related Files

- **Config:** `/var/www/api-gateway/config/services.php`
- **Security:** `/var/www/api-gateway/app/Models/Service.php` (saving hook)
- **Service:** `/var/www/api-gateway/app/Services/CalcomV2Service.php`
- **Mappings Table:** `calcom_event_mappings` (database)

---

## 📞 Questions & Troubleshooting

**Q: Why don't I see all Cal.com events when I query my team?**
A: Only events explicitly assigned to that team appear. Global events are separate.

**Q: How do I add a new Event-ID to a team?**
A: Create it in Cal.com UI, then insert into `calcom_event_mappings` table with `company_id` + `calcom_event_type_id`.

**Q: What if Event-ID exists but isn't in my team?**
A: It's a global event. Add it to your team in Cal.com, then create mapping.

---

**Next Review:** 2025-11-01 or when Cal.com V2 migration begins
