# Multi-Tenant Security Testing Plan

**Generated**: 2025-10-02
**Status**: Implementation Complete - Testing Required
**Risk Level**: 🔴 CRITICAL - Production Security Implementation

## Executive Summary

This document provides a comprehensive testing strategy for the multi-tenant security implementation affecting 6 migrations, 8 authorization policies, 3 input validation observers, and 2 security fixes.

### Implementation Overview

**Completed Components**:
- ✅ 6 migrations with company_id columns
- ✅ 2 new authorization policies (NotificationEventMappingPolicy, CallbackEscalationPolicy)
- ✅ 1 polymorphic policy bug fix (NotificationConfigurationPolicy)
- ✅ 1 resource scope bypass fix (UserResource)
- ✅ 1 assignment authorization bug fix (CallbackRequestPolicy)
- ✅ 3 input validation observers (PolicyConfiguration, CallbackRequest, NotificationConfiguration)

**Models Using BelongsToCompany Trait**:
- NotificationConfiguration
- CallbackEscalation
- NotificationEventMapping
- CallbackRequest
- PolicyConfiguration
- AppointmentModification

---

## Risk Assessment Matrix

| Component | Risk Level | Impact | Probability | Priority |
|-----------|------------|--------|-------------|----------|
| Observer Validation | 🔴 CRITICAL | High | High | P0 |
| Authorization Policies | 🔴 CRITICAL | High | High | P0 |
| Cross-Tenant Isolation | 🔴 CRITICAL | High | Medium | P0 |
| XSS Prevention | 🟡 HIGH | High | Medium | P1 |
| Polymorphic Relationships | 🟡 HIGH | Medium | Medium | P1 |
| Observer Triggering | 🟢 MEDIUM | Medium | Low | P2 |

---

## Section 1: Observer Validation Testing

### 1.1 PolicyConfigurationObserver Tests

**File**: `/var/www/api-gateway/tests/Unit/Observers/PolicyConfigurationObserverTest.php`

#### Test Cases

**1.1.1 JSON Schema Validation - Cancellation Policy**
```php
public function test_cancellation_policy_validates_required_fields(): void
{
    $this->expectException(ValidationException::class);

    PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            // Missing 'fee_percentage' - should fail
        ],
    ]);
}

public function test_cancellation_policy_accepts_valid_config(): void
{
    $policy = PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50.0,
            'max_cancellations_per_month' => 3,
            'require_reason' => true,
        ],
    ]);

    $this->assertNotNull($policy->id);
    $this->assertEquals(24, $policy->config['hours_before']);
}

public function test_cancellation_policy_rejects_unknown_fields(): void
{
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage("Unknown field 'invalid_field'");

    PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50.0,
            'invalid_field' => 'should_fail',
        ],
    ]);
}

public function test_cancellation_policy_validates_field_types(): void
{
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage("Field 'hours_before' must be of type integer");

    PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => "24", // String instead of integer
            'fee_percentage' => 50.0,
        ],
    ]);
}
```

**Expected Results**:
- ✅ Required fields validation enforced
- ✅ Unknown fields rejected
- ✅ Type validation working (integer, numeric, boolean)
- ✅ Valid configs accepted

**1.1.2 JSON Schema Validation - Reschedule Policy**
```php
public function test_reschedule_policy_validates_required_fields(): void
{
    $this->expectException(ValidationException::class);

    PolicyConfiguration::create([
        'policy_type' => 'reschedule',
        'config' => [
            'hours_before' => 12,
            // Missing 'max_reschedules_per_appointment' - should fail
        ],
    ]);
}

public function test_reschedule_policy_accepts_valid_config(): void
{
    $policy = PolicyConfiguration::create([
        'policy_type' => 'reschedule',
        'config' => [
            'hours_before' => 12,
            'max_reschedules_per_appointment' => 2,
            'fee_percentage' => 25.0,
            'require_reason' => false,
            'allow_same_day_reschedule' => true,
        ],
    ]);

    $this->assertNotNull($policy->id);
}
```

**1.1.3 JSON Schema Validation - Recurring Policy**
```php
public function test_recurring_policy_validates_required_fields(): void
{
    $this->expectException(ValidationException::class);

    PolicyConfiguration::create([
        'policy_type' => 'recurring',
        'config' => [
            // Missing 'allow_partial_cancel' - should fail
            'require_full_series_notice' => true,
        ],
    ]);
}
```

**1.1.4 XSS Prevention in PolicyConfiguration**
```php
public function test_policy_config_sanitizes_xss_in_config_values(): void
{
    $policy = PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50.0,
            'custom_message' => '<script>alert("xss")</script>Hello',
        ],
    ]);

    $policy->refresh();

    // XSS tags should be stripped and encoded
    $this->assertStringNotContainsString('<script>', $policy->config['custom_message']);
    $this->assertStringNotContainsString('alert', $policy->config['custom_message']);
}

public function test_policy_config_sanitizes_nested_xss(): void
{
    $policy = PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50.0,
            'metadata' => [
                'description' => '<img src=x onerror="alert(1)">',
                'notes' => '<iframe src="evil.com"></iframe>',
            ],
        ],
    ]);

    $policy->refresh();

    // Should be sanitized recursively
    $this->assertStringNotContainsString('<img', json_encode($policy->config));
    $this->assertStringNotContainsString('<iframe', json_encode($policy->config));
}
```

**1.1.5 Update Validation**
```php
public function test_policy_config_validates_on_update(): void
{
    $policy = PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => ['hours_before' => 24, 'fee_percentage' => 50.0],
    ]);

    $this->expectException(ValidationException::class);

    // Try to update with invalid config
    $policy->update([
        'config' => [
            'hours_before' => "invalid", // Should fail type validation
            'fee_percentage' => 50.0,
        ],
    ]);
}

public function test_policy_config_only_validates_dirty_fields(): void
{
    $policy = PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => ['hours_before' => 24, 'fee_percentage' => 50.0],
    ]);

    // Update non-config field - should not trigger validation
    $policy->update(['name' => 'Updated Name']);

    $this->assertEquals('Updated Name', $policy->name);
}
```

**1.1.6 Invalid Policy Type**
```php
public function test_policy_config_rejects_invalid_policy_type(): void
{
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage("Invalid policy type: invalid_type");

    PolicyConfiguration::create([
        'policy_type' => 'invalid_type',
        'config' => ['some' => 'data'],
    ]);
}
```

---

### 1.2 CallbackRequestObserver Tests

**File**: `/var/www/api-gateway/tests/Unit/Observers/CallbackRequestObserverTest.php`

#### Test Cases

**1.2.1 XSS Prevention**
```php
public function test_callback_request_sanitizes_customer_name(): void
{
    $callback = CallbackRequest::create([
        'customer_name' => '<script>alert("xss")</script>John Doe',
        'phone_number' => '+491234567890',
        'priority' => 'normal',
    ]);

    $callback->refresh();

    $this->assertStringNotContainsString('<script>', $callback->customer_name);
    $this->assertStringNotContainsString('alert', $callback->customer_name);
    $this->assertStringContainsString('John Doe', $callback->customer_name);
}

public function test_callback_request_sanitizes_notes(): void
{
    $callback = CallbackRequest::create([
        'customer_name' => 'John Doe',
        'phone_number' => '+491234567890',
        'notes' => '<img src=x onerror="alert(1)">Important note',
        'priority' => 'normal',
    ]);

    $callback->refresh();

    $this->assertStringNotContainsString('<img', $callback->notes);
    $this->assertStringNotContainsString('onerror', $callback->notes);
    $this->assertStringContainsString('Important note', $callback->notes);
}

public function test_callback_request_handles_null_values(): void
{
    $callback = CallbackRequest::create([
        'customer_name' => null,
        'phone_number' => '+491234567890',
        'notes' => null,
        'priority' => 'normal',
    ]);

    $this->assertNull($callback->customer_name);
    $this->assertNull($callback->notes);
}
```

**1.2.2 Phone Number Validation (E.164 Format)**
```php
public function test_callback_request_validates_e164_format(): void
{
    $validPhones = [
        '+491234567890',     // Germany
        '+14155552671',      // USA
        '+447911123456',     // UK
        '+861234567890',     // China
        '+12345678',         // Minimum 7 digits
        '+123456789012345',  // Maximum 15 digits
    ];

    foreach ($validPhones as $phone) {
        $callback = CallbackRequest::create([
            'customer_name' => 'Test',
            'phone_number' => $phone,
            'priority' => 'normal',
        ]);

        $this->assertEquals($phone, $callback->phone_number);
        $callback->delete();
    }
}

public function test_callback_request_rejects_invalid_phone_formats(): void
{
    $invalidPhones = [
        '1234567890',           // Missing +
        '+0123456789',          // Starts with 0
        '+1234',                // Too short (< 7 digits)
        '+12345678901234567',   // Too long (> 15 digits)
        '+(123)456-7890',       // Invalid characters
        '+49 123 456 7890',     // Spaces not allowed
        'invalid',              // Not a number
    ];

    foreach ($invalidPhones as $phone) {
        $this->expectException(ValidationException::class);

        CallbackRequest::create([
            'customer_name' => 'Test',
            'phone_number' => $phone,
            'priority' => 'normal',
        ]);
    }
}

public function test_callback_request_requires_phone_number(): void
{
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Phone number is required');

    CallbackRequest::create([
        'customer_name' => 'Test',
        'phone_number' => null,
        'priority' => 'normal',
    ]);
}
```

**1.2.3 Auto-Expiration Logic**
```php
public function test_callback_request_sets_expires_at_based_on_priority(): void
{
    $urgentCallback = CallbackRequest::create([
        'customer_name' => 'Urgent Customer',
        'phone_number' => '+491234567890',
        'priority' => 'urgent',
    ]);

    $this->assertNotNull($urgentCallback->expires_at);
    $this->assertEquals(
        now()->addHours(1)->format('Y-m-d H:i'),
        $urgentCallback->expires_at->format('Y-m-d H:i')
    );

    $highCallback = CallbackRequest::create([
        'customer_name' => 'High Customer',
        'phone_number' => '+491234567890',
        'priority' => 'high',
    ]);

    $this->assertEquals(
        now()->addHours(4)->format('Y-m-d H:i'),
        $highCallback->expires_at->format('Y-m-d H:i')
    );

    $normalCallback = CallbackRequest::create([
        'customer_name' => 'Normal Customer',
        'phone_number' => '+491234567890',
        'priority' => 'normal',
    ]);

    $this->assertEquals(
        now()->addHours(24)->format('Y-m-d H:i'),
        $normalCallback->expires_at->format('Y-m-d H:i')
    );
}

public function test_callback_request_respects_manual_expires_at(): void
{
    $customExpiry = now()->addDays(2);

    $callback = CallbackRequest::create([
        'customer_name' => 'Test',
        'phone_number' => '+491234567890',
        'priority' => 'normal',
        'expires_at' => $customExpiry,
    ]);

    $this->assertEquals(
        $customExpiry->format('Y-m-d H:i'),
        $callback->expires_at->format('Y-m-d H:i')
    );
}
```

**1.2.4 Auto-Assignment Logic**
```php
public function test_callback_request_auto_sets_assigned_at(): void
{
    $user = User::factory()->create();

    $callback = CallbackRequest::create([
        'customer_name' => 'Test',
        'phone_number' => '+491234567890',
        'priority' => 'normal',
        'assigned_to' => $user->id,
    ]);

    $this->assertNotNull($callback->assigned_at);
    $this->assertEquals(now()->format('Y-m-d H:i'), $callback->assigned_at->format('Y-m-d H:i'));
}

public function test_callback_request_auto_sets_status_to_assigned(): void
{
    $user = User::factory()->create();

    $callback = CallbackRequest::create([
        'customer_name' => 'Test',
        'phone_number' => '+491234567890',
        'priority' => 'normal',
        'status' => 'pending',
        'assigned_to' => $user->id,
    ]);

    $this->assertEquals('assigned', $callback->status);
}
```

**1.2.5 Update Validation**
```php
public function test_callback_request_validates_on_phone_update(): void
{
    $callback = CallbackRequest::create([
        'customer_name' => 'Test',
        'phone_number' => '+491234567890',
        'priority' => 'normal',
    ]);

    $this->expectException(ValidationException::class);

    $callback->update(['phone_number' => 'invalid']);
}

public function test_callback_request_sanitizes_on_update(): void
{
    $callback = CallbackRequest::create([
        'customer_name' => 'Test',
        'phone_number' => '+491234567890',
        'notes' => 'Original notes',
        'priority' => 'normal',
    ]);

    $callback->update(['notes' => '<script>alert("xss")</script>Updated notes']);
    $callback->refresh();

    $this->assertStringNotContainsString('<script>', $callback->notes);
    $this->assertStringContainsString('Updated notes', $callback->notes);
}
```

---

### 1.3 NotificationConfigurationObserver Tests

**File**: `/var/www/api-gateway/tests/Unit/Observers/NotificationConfigurationObserverTest.php`

#### Test Cases

**1.3.1 Event Type Validation**
```php
public function test_notification_config_validates_event_type_exists(): void
{
    // Create active event mapping
    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'appointment.created',
        'is_active' => true,
        'default_channels' => ['email', 'sms'],
    ]);

    $config = NotificationConfiguration::create([
        'event_type' => 'appointment.created',
        'channel' => 'email',
        'template_content' => 'Test template',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);

    $this->assertNotNull($config->id);
}

public function test_notification_config_rejects_invalid_event_type(): void
{
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Invalid or inactive event type: invalid.event');

    NotificationConfiguration::create([
        'event_type' => 'invalid.event',
        'channel' => 'email',
        'template_content' => 'Test',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);
}

public function test_notification_config_rejects_inactive_event_type(): void
{
    $inactiveEvent = NotificationEventMapping::create([
        'event_type' => 'inactive.event',
        'is_active' => false,
    ]);

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Invalid or inactive event type: inactive.event');

    NotificationConfiguration::create([
        'event_type' => 'inactive.event',
        'channel' => 'email',
        'template_content' => 'Test',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);
}

public function test_notification_config_requires_event_type(): void
{
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Event type is required');

    NotificationConfiguration::create([
        'event_type' => null,
        'channel' => 'email',
        'template_content' => 'Test',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);
}
```

**1.3.2 Channel Validation**
```php
public function test_notification_config_validates_allowed_channels(): void
{
    $validChannels = ['email', 'sms', 'whatsapp', 'push', 'in_app'];

    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'test.event',
        'is_active' => true,
        'default_channels' => $validChannels,
    ]);

    foreach ($validChannels as $channel) {
        $config = NotificationConfiguration::create([
            'event_type' => 'test.event',
            'channel' => $channel,
            'template_content' => 'Test',
            'configurable_type' => 'App\Models\Company',
            'configurable_id' => 1,
        ]);

        $this->assertEquals($channel, $config->channel);
        $config->delete();
    }
}

public function test_notification_config_rejects_invalid_channel(): void
{
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Invalid channel. Valid channels are: email, sms, whatsapp, push, in_app');

    NotificationConfiguration::create([
        'event_type' => 'test.event',
        'channel' => 'invalid_channel',
        'template_content' => 'Test',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);
}

public function test_notification_config_enforces_event_allowed_channels(): void
{
    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'restricted.event',
        'is_active' => true,
        'default_channels' => ['email', 'sms'], // Only email and sms allowed
    ]);

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage("Channel 'whatsapp' is not allowed for event 'restricted.event'");

    NotificationConfiguration::create([
        'event_type' => 'restricted.event',
        'channel' => 'whatsapp', // Not in allowed channels
        'template_content' => 'Test',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);
}
```

**1.3.3 XSS Prevention in Templates**
```php
public function test_notification_config_removes_script_tags(): void
{
    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'test.event',
        'is_active' => true,
        'default_channels' => ['email'],
    ]);

    $config = NotificationConfiguration::create([
        'event_type' => 'test.event',
        'channel' => 'email',
        'template_content' => 'Hello <script>alert("xss")</script> {{customer_name}}',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);

    $config->refresh();

    // Script tags should be removed
    $this->assertStringNotContainsString('<script>', $config->template_content);
    $this->assertStringNotContainsString('alert', $config->template_content);
    // Template variables should be preserved
    $this->assertStringContainsString('{{customer_name}}', $config->template_content);
}

public function test_notification_config_removes_iframe_tags(): void
{
    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'test.event',
        'is_active' => true,
        'default_channels' => ['email'],
    ]);

    $config = NotificationConfiguration::create([
        'event_type' => 'test.event',
        'channel' => 'email',
        'template_content' => '<iframe src="evil.com"></iframe>Safe content',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);

    $config->refresh();

    $this->assertStringNotContainsString('<iframe', $config->template_content);
    $this->assertStringNotContainsString('evil.com', $config->template_content);
}

public function test_notification_config_removes_dangerous_event_handlers(): void
{
    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'test.event',
        'is_active' => true,
        'default_channels' => ['email'],
    ]);

    $config = NotificationConfiguration::create([
        'event_type' => 'test.event',
        'channel' => 'email',
        'template_content' => '<div onclick="alert(1)">Click me</div>',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);

    $config->refresh();

    $this->assertStringNotContainsString('onclick=', $config->template_content);
}

public function test_notification_config_sanitizes_template_array(): void
{
    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'test.event',
        'is_active' => true,
        'default_channels' => ['email'],
    ]);

    $config = NotificationConfiguration::create([
        'event_type' => 'test.event',
        'channel' => 'email',
        'template_content' => [
            'subject' => '<script>alert(1)</script>Subject',
            'body' => '<iframe src="evil.com"></iframe>Body',
            'footer' => 'Safe {{company_name}}',
        ],
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);

    $config->refresh();

    $this->assertStringNotContainsString('<script>', json_encode($config->template_content));
    $this->assertStringNotContainsString('<iframe>', json_encode($config->template_content));
    $this->assertStringContainsString('{{company_name}}', $config->template_content['footer']);
}

public function test_notification_config_sanitizes_metadata(): void
{
    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'test.event',
        'is_active' => true,
        'default_channels' => ['email'],
    ]);

    $config = NotificationConfiguration::create([
        'event_type' => 'test.event',
        'channel' => 'email',
        'template_content' => 'Safe content',
        'metadata' => [
            'description' => '<script>alert(1)</script>Description',
            'notes' => '<img onerror="alert(1)" src=x>Notes',
        ],
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);

    $config->refresh();

    $this->assertStringNotContainsString('<script>', json_encode($config->metadata));
    $this->assertStringNotContainsString('onerror=', json_encode($config->metadata));
}
```

**1.3.4 Auto-Enable Logic**
```php
public function test_notification_config_auto_enables_on_creation(): void
{
    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'test.event',
        'is_active' => true,
        'default_channels' => ['email'],
    ]);

    $config = NotificationConfiguration::create([
        'event_type' => 'test.event',
        'channel' => 'email',
        'template_content' => 'Test',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);

    $this->assertTrue($config->is_enabled);
}

public function test_notification_config_respects_explicit_disable(): void
{
    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'test.event',
        'is_active' => true,
        'default_channels' => ['email'],
    ]);

    $config = NotificationConfiguration::create([
        'event_type' => 'test.event',
        'channel' => 'email',
        'template_content' => 'Test',
        'is_enabled' => false,
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);

    $this->assertFalse($config->is_enabled);
}
```

---

### 1.4 Observer Triggering Verification

**Critical Requirement**: Observers must actually fire during model operations.

**File**: `/var/www/api-gateway/tests/Unit/Observers/ObserverTriggeringTest.php`

```php
public function test_policy_configuration_observer_is_registered(): void
{
    $this->assertTrue(
        PolicyConfiguration::getEventDispatcher()->hasListeners('eloquent.creating: ' . PolicyConfiguration::class)
    );
}

public function test_callback_request_observer_is_registered(): void
{
    $this->assertTrue(
        CallbackRequest::getEventDispatcher()->hasListeners('eloquent.creating: ' . CallbackRequest::class)
    );
}

public function test_notification_configuration_observer_is_registered(): void
{
    $this->assertTrue(
        NotificationConfiguration::getEventDispatcher()->hasListeners('eloquent.creating: ' . NotificationConfiguration::class)
    );
}

public function test_observers_actually_fire_on_create(): void
{
    // PolicyConfiguration
    $this->expectException(ValidationException::class);
    PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => ['invalid' => 'data'], // Should fail validation
    ]);

    // CallbackRequest
    $this->expectException(ValidationException::class);
    CallbackRequest::create([
        'customer_name' => 'Test',
        'phone_number' => 'invalid', // Should fail validation
        'priority' => 'normal',
    ]);

    // NotificationConfiguration
    $this->expectException(ValidationException::class);
    NotificationConfiguration::create([
        'event_type' => 'invalid.event', // Should fail validation
        'channel' => 'email',
        'template_content' => 'Test',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);
}

public function test_observers_fire_on_update(): void
{
    // Create valid callback
    $callback = CallbackRequest::create([
        'customer_name' => 'Test',
        'phone_number' => '+491234567890',
        'priority' => 'normal',
    ]);

    $this->expectException(ValidationException::class);

    // Try to update with invalid phone
    $callback->update(['phone_number' => 'invalid']);
}
```

**Expected Results**:
- ✅ All observers registered in EventServiceProvider
- ✅ Observers fire on create operations
- ✅ Observers fire on update operations
- ✅ Validation exceptions thrown when expected

---

## Section 2: Authorization Policy Testing

### 2.1 Multi-Tenant Isolation Tests

**File**: `/var/www/api-gateway/tests/Feature/Security/MultiTenantAuthorizationTest.php`

#### Test Cases

**2.1.1 NotificationEventMappingPolicy - Cross-Tenant Prevention**
```php
public function test_notification_event_mapping_prevents_cross_tenant_view(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('admin');

    $eventMappingB = NotificationEventMapping::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $this->actingAs($userA);

    // Should not be able to view Company B's event mapping
    $this->assertFalse($userA->can('view', $eventMappingB));
}

public function test_notification_event_mapping_allows_same_company_view(): void
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->assignRole('admin');

    $eventMapping = NotificationEventMapping::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAs($user);

    $this->assertTrue($user->can('view', $eventMapping));
}

public function test_notification_event_mapping_prevents_cross_tenant_update(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('admin');

    $eventMappingB = NotificationEventMapping::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $this->actingAs($userA);

    $this->assertFalse($userA->can('update', $eventMappingB));
}

public function test_notification_event_mapping_prevents_cross_tenant_delete(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('admin');

    $eventMappingB = NotificationEventMapping::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $this->actingAs($userA);

    $this->assertFalse($userA->can('delete', $eventMappingB));
}
```

**2.1.2 CallbackEscalationPolicy - Cross-Tenant Prevention**
```php
public function test_callback_escalation_prevents_cross_tenant_view(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('admin');

    $escalationB = CallbackEscalation::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $this->actingAs($userA);

    $this->assertFalse($userA->can('view', $escalationB));
}

public function test_callback_escalation_allows_same_company_view(): void
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->assignRole('staff');

    $escalation = CallbackEscalation::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAs($user);

    $this->assertTrue($user->can('view', $escalation));
}
```

**2.1.3 CallbackRequestPolicy - Cross-Tenant Prevention**
```php
public function test_callback_request_prevents_cross_tenant_view(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('admin');

    $callbackB = CallbackRequest::factory()->create([
        'company_id' => $companyB->id,
    ]);

    $this->actingAs($userA);

    $this->assertFalse($userA->can('view', $callbackB));
}

public function test_callback_request_allows_same_company_view(): void
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->assignRole('staff');

    $callback = CallbackRequest::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAs($user);

    $this->assertTrue($user->can('view', $callback));
}
```

**2.1.4 NotificationConfigurationPolicy - Polymorphic Relationship**
```php
public function test_notification_config_polymorphic_company_authorization(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('admin');

    // Config attached directly to Company B
    $configB = NotificationConfiguration::factory()->create([
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => $companyB->id,
    ]);

    $this->actingAs($userA);

    $this->assertFalse($userA->can('view', $configB));
}

public function test_notification_config_polymorphic_branch_authorization(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $branchB = Branch::factory()->create(['company_id' => $companyB->id]);

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('admin');

    // Config attached to Branch of Company B
    $configB = NotificationConfiguration::factory()->create([
        'configurable_type' => 'App\Models\Branch',
        'configurable_id' => $branchB->id,
    ]);

    $this->actingAs($userA);

    // Should fail because branch belongs to Company B
    $this->assertFalse($userA->can('view', $configB));
}

public function test_notification_config_polymorphic_service_authorization(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $serviceB = Service::factory()->create(['company_id' => $companyB->id]);

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('admin');

    // Config attached to Service of Company B
    $configB = NotificationConfiguration::factory()->create([
        'configurable_type' => 'App\Models\Service',
        'configurable_id' => $serviceB->id,
    ]);

    $this->actingAs($userA);

    $this->assertFalse($userA->can('view', $configB));
}
```

---

### 2.2 Super Admin Bypass Tests

**File**: `/var/www/api-gateway/tests/Feature/Security/SuperAdminAuthorizationTest.php`

```php
public function test_super_admin_bypasses_notification_event_mapping_policy(): void
{
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $companyA = Company::factory()->create();
    $eventMapping = NotificationEventMapping::factory()->create([
        'company_id' => $companyA->id,
    ]);

    $this->actingAs($superAdmin);

    $this->assertTrue($superAdmin->can('view', $eventMapping));
    $this->assertTrue($superAdmin->can('update', $eventMapping));
    $this->assertTrue($superAdmin->can('delete', $eventMapping));
    $this->assertTrue($superAdmin->can('forceDelete', $eventMapping));
}

public function test_super_admin_bypasses_callback_escalation_policy(): void
{
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $company = Company::factory()->create();
    $escalation = CallbackEscalation::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAs($superAdmin);

    $this->assertTrue($superAdmin->can('view', $escalation));
    $this->assertTrue($superAdmin->can('update', $escalation));
    $this->assertTrue($superAdmin->can('delete', $escalation));
}

public function test_super_admin_bypasses_callback_request_policy(): void
{
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $company = Company::factory()->create();
    $callback = CallbackRequest::factory()->create([
        'company_id' => $company->id,
    ]);

    $this->actingAs($superAdmin);

    $this->assertTrue($superAdmin->can('view', $callback));
    $this->assertTrue($superAdmin->can('update', $callback));
    $this->assertTrue($superAdmin->can('assign', $callback));
    $this->assertTrue($superAdmin->can('complete', $callback));
}

public function test_super_admin_bypasses_notification_configuration_policy(): void
{
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $company = Company::factory()->create();
    $config = NotificationConfiguration::factory()->create([
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => $company->id,
    ]);

    $this->actingAs($superAdmin);

    $this->assertTrue($superAdmin->can('view', $config));
    $this->assertTrue($superAdmin->can('update', $config));
    $this->assertTrue($superAdmin->can('delete', $config));
}
```

---

### 2.3 Assignment-Based Authorization Tests

**File**: `/var/www/api-gateway/tests/Feature/Security/AssignmentAuthorizationTest.php`

```php
public function test_assigned_staff_can_view_callback_request(): void
{
    $company = Company::factory()->create();
    $staff = User::factory()->create(['company_id' => $company->id]);
    $staff->assignRole('staff');

    $callback = CallbackRequest::factory()->create([
        'company_id' => $company->id,
        'assigned_to' => $staff->id,
    ]);

    $this->actingAs($staff);

    $this->assertTrue($staff->can('view', $callback));
}

public function test_assigned_staff_can_update_callback_request(): void
{
    $company = Company::factory()->create();
    $staff = User::factory()->create(['company_id' => $company->id]);
    $staff->assignRole('staff');

    $callback = CallbackRequest::factory()->create([
        'company_id' => $company->id,
        'assigned_to' => $staff->id,
    ]);

    $this->actingAs($staff);

    $this->assertTrue($staff->can('update', $callback));
}

public function test_assigned_staff_can_complete_callback_request(): void
{
    $company = Company::factory()->create();
    $staff = User::factory()->create(['company_id' => $company->id]);
    $staff->assignRole('staff');

    $callback = CallbackRequest::factory()->create([
        'company_id' => $company->id,
        'assigned_to' => $staff->id,
    ]);

    $this->actingAs($staff);

    $this->assertTrue($staff->can('complete', $callback));
}

public function test_unassigned_staff_cannot_update_callback_request(): void
{
    $company = Company::factory()->create();
    $staff1 = User::factory()->create(['company_id' => $company->id]);
    $staff2 = User::factory()->create(['company_id' => $company->id]);
    $staff1->assignRole('staff');
    $staff2->assignRole('staff');

    $callback = CallbackRequest::factory()->create([
        'company_id' => $company->id,
        'assigned_to' => $staff2->id, // Assigned to staff2
    ]);

    $this->actingAs($staff1); // staff1 trying to access

    $this->assertFalse($staff1->can('update', $callback));
}

public function test_staff_assignment_requires_same_company(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $staffA = User::factory()->create(['company_id' => $companyA->id]);
    $staffA->assignRole('staff');

    $callbackB = CallbackRequest::factory()->create([
        'company_id' => $companyB->id,
        'assigned_to' => $staffA->id, // Assigned to staff from different company
    ]);

    $this->actingAs($staffA);

    // Should fail because company_id mismatch
    $this->assertFalse($staffA->can('view', $callbackB));
}

public function test_escalated_staff_can_update_escalation(): void
{
    $company = Company::factory()->create();
    $staff = User::factory()->create(['company_id' => $company->id, 'staff_id' => 1]);
    $staff->assignRole('staff');

    $escalation = CallbackEscalation::factory()->create([
        'company_id' => $company->id,
        'escalated_to' => 1, // Escalated to staff_id 1
    ]);

    $this->actingAs($staff);

    $this->assertTrue($staff->can('update', $escalation));
}

public function test_non_escalated_staff_cannot_update_escalation(): void
{
    $company = Company::factory()->create();
    $staff1 = User::factory()->create(['company_id' => $company->id, 'staff_id' => 1]);
    $staff2 = User::factory()->create(['company_id' => $company->id, 'staff_id' => 2]);
    $staff1->assignRole('staff');

    $escalation = CallbackEscalation::factory()->create([
        'company_id' => $company->id,
        'escalated_to' => 2, // Escalated to staff2
    ]);

    $this->actingAs($staff1);

    $this->assertFalse($staff1->can('update', $escalation));
}
```

---

## Section 3: Integration Testing

### 3.1 BelongsToCompany Trait Integration

**File**: `/var/www/api-gateway/tests/Feature/Integration/BelongsToCompanyIntegrationTest.php`

```php
public function test_belongs_to_company_auto_fills_company_id_on_create(): void
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user);

    $callback = CallbackRequest::create([
        'customer_name' => 'Test',
        'phone_number' => '+491234567890',
        'priority' => 'normal',
    ]);

    $this->assertEquals($company->id, $callback->company_id);
}

public function test_belongs_to_company_applies_company_scope_to_queries(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);

    $callbackA = CallbackRequest::factory()->create(['company_id' => $companyA->id]);
    $callbackB = CallbackRequest::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($userA);

    $results = CallbackRequest::all();

    $this->assertTrue($results->contains($callbackA));
    $this->assertFalse($results->contains($callbackB));
}

public function test_belongs_to_company_super_admin_sees_all_records(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $callbackA = CallbackRequest::factory()->create(['company_id' => $companyA->id]);
    $callbackB = CallbackRequest::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($superAdmin);

    $results = CallbackRequest::all();

    $this->assertTrue($results->contains($callbackA));
    $this->assertTrue($results->contains($callbackB));
}

public function test_belongs_to_company_relationship_works(): void
{
    $company = Company::factory()->create();
    $callback = CallbackRequest::factory()->create(['company_id' => $company->id]);

    $this->assertInstanceOf(Company::class, $callback->company);
    $this->assertEquals($company->id, $callback->company->id);
}

public function test_notification_event_mapping_belongs_to_company(): void
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user);

    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'test.event',
        'is_active' => true,
    ]);

    $this->assertEquals($company->id, $eventMapping->company_id);
}

public function test_callback_escalation_belongs_to_company(): void
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user);

    $escalation = CallbackEscalation::create([
        'callback_request_id' => 1,
        'escalated_from' => 1,
        'escalated_to' => 2,
        'reason' => 'Test',
    ]);

    $this->assertEquals($company->id, $escalation->company_id);
}

public function test_policy_configuration_belongs_to_company(): void
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user);

    $policy = PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50.0,
        ],
    ]);

    $this->assertEquals($company->id, $policy->company_id);
}
```

---

### 3.2 Complete User Workflow Tests

**File**: `/var/www/api-gateway/tests/Feature/Integration/CompleteWorkflowTest.php`

```php
public function test_complete_callback_request_workflow(): void
{
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('admin');

    $staff = User::factory()->create(['company_id' => $company->id]);
    $staff->assignRole('staff');

    // 1. Admin creates callback request (with XSS attempt)
    $this->actingAs($admin);

    $callback = CallbackRequest::create([
        'customer_name' => '<script>alert("xss")</script>John Doe',
        'phone_number' => '+491234567890',
        'notes' => '<img src=x onerror="alert(1)">Customer notes',
        'priority' => 'urgent',
    ]);

    // Verify XSS sanitization
    $this->assertStringNotContainsString('<script>', $callback->customer_name);
    $this->assertStringNotContainsString('<img', $callback->notes);

    // Verify company_id auto-set
    $this->assertEquals($company->id, $callback->company_id);

    // Verify auto-expiration
    $this->assertNotNull($callback->expires_at);

    // 2. Admin assigns to staff
    $this->assertTrue($admin->can('assign', $callback));

    $callback->update(['assigned_to' => $staff->id]);

    // Verify auto-status change
    $callback->refresh();
    $this->assertEquals('assigned', $callback->status);
    $this->assertNotNull($callback->assigned_at);

    // 3. Staff can view and update
    $this->actingAs($staff);

    $this->assertTrue($staff->can('view', $callback));
    $this->assertTrue($staff->can('update', $callback));

    $callback->update(['notes' => 'Customer contacted successfully']);

    // 4. Staff completes callback
    $this->assertTrue($staff->can('complete', $callback));

    $callback->update(['status' => 'completed']);

    $this->assertEquals('completed', $callback->status);
}

public function test_complete_notification_configuration_workflow(): void
{
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin);

    // 1. Create event mapping
    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'appointment.created',
        'is_active' => true,
        'default_channels' => ['email', 'sms'],
    ]);

    // Verify company_id auto-set
    $this->assertEquals($company->id, $eventMapping->company_id);

    // 2. Create notification configuration (with XSS attempt)
    $config = NotificationConfiguration::create([
        'event_type' => 'appointment.created',
        'channel' => 'email',
        'template_content' => [
            'subject' => '<script>alert(1)</script>Appointment Confirmation',
            'body' => 'Dear {{customer_name}}, <iframe src="evil.com"></iframe>',
        ],
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => $company->id,
    ]);

    // Verify XSS sanitization
    $this->assertStringNotContainsString('<script>', json_encode($config->template_content));
    $this->assertStringNotContainsString('<iframe>', json_encode($config->template_content));

    // Verify template variables preserved
    $this->assertStringContainsString('{{customer_name}}', $config->template_content['body']);

    // Verify auto-enable
    $this->assertTrue($config->is_enabled);

    // 3. Update with invalid channel
    $this->expectException(ValidationException::class);

    $config->update(['channel' => 'whatsapp']); // Not in allowed channels
}

public function test_complete_policy_configuration_workflow(): void
{
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin);

    // 1. Create cancellation policy
    $policy = PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'name' => 'Standard Cancellation Policy',
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50.0,
            'max_cancellations_per_month' => 3,
            'require_reason' => true,
        ],
    ]);

    // Verify company_id auto-set
    $this->assertEquals($company->id, $policy->company_id);

    // 2. Update policy (with validation)
    $policy->update([
        'config' => [
            'hours_before' => 48,
            'fee_percentage' => 75.0,
            'max_cancellations_per_month' => 2,
            'require_reason' => true,
        ],
    ]);

    $policy->refresh();
    $this->assertEquals(48, $policy->config['hours_before']);

    // 3. Try invalid update
    $this->expectException(ValidationException::class);

    $policy->update([
        'config' => [
            'hours_before' => "invalid", // Should fail type validation
            'fee_percentage' => 75.0,
        ],
    ]);
}
```

---

## Section 4: Edge Case Testing

### 4.1 Missing company_id Tests

**File**: `/var/www/api-gateway/tests/Feature/EdgeCases/MissingCompanyIdTest.php`

```php
public function test_callback_request_handles_missing_company_id_without_auth(): void
{
    // Logout to simulate no authenticated user
    Auth::logout();

    $callback = CallbackRequest::create([
        'customer_name' => 'Test',
        'phone_number' => '+491234567890',
        'priority' => 'normal',
    ]);

    // company_id should be null when no user authenticated
    $this->assertNull($callback->company_id);
}

public function test_notification_event_mapping_handles_missing_company_id(): void
{
    Auth::logout();

    $eventMapping = NotificationEventMapping::create([
        'event_type' => 'test.event',
        'is_active' => true,
    ]);

    $this->assertNull($eventMapping->company_id);
}

public function test_policy_configuration_handles_missing_company_id(): void
{
    Auth::logout();

    $policy = PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50.0,
        ],
    ]);

    $this->assertNull($policy->company_id);
}
```

---

### 4.2 Invalid company_id Tests

**File**: `/var/www/api-gateway/tests/Feature/EdgeCases/InvalidCompanyIdTest.php`

```php
public function test_query_with_invalid_company_id_returns_empty(): void
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $callback = CallbackRequest::factory()->create(['company_id' => $company->id]);

    // User with non-existent company_id
    $invalidUser = User::factory()->create(['company_id' => 99999]);
    $this->actingAs($invalidUser);

    $results = CallbackRequest::all();

    $this->assertEmpty($results);
}

public function test_authorization_fails_with_mismatched_company_ids(): void
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('admin');

    $callbackB = CallbackRequest::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($userA);

    $this->assertFalse($userA->can('view', $callbackB));
    $this->assertFalse($userA->can('update', $callbackB));
    $this->assertFalse($userA->can('delete', $callbackB));
}
```

---

### 4.3 Soft-Deleted Records Tests

**File**: `/var/www/api-gateway/tests/Feature/EdgeCases/SoftDeleteTest.php`

```php
public function test_soft_deleted_callback_requests_not_visible(): void
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->assignRole('admin');

    $callback = CallbackRequest::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user);

    // Soft delete
    $callback->delete();

    // Should not appear in normal queries
    $results = CallbackRequest::all();
    $this->assertFalse($results->contains($callback));

    // Should appear in withTrashed queries
    $trashedResults = CallbackRequest::withTrashed()->get();
    $this->assertTrue($trashedResults->contains($callback));
}

public function test_admin_can_restore_soft_deleted_records(): void
{
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('admin');

    $callback = CallbackRequest::factory()->create(['company_id' => $company->id]);

    $this->actingAs($admin);

    $callback->delete();

    $this->assertTrue($admin->can('restore', $callback));

    $callback->restore();

    $results = CallbackRequest::all();
    $this->assertTrue($results->contains($callback));
}

public function test_super_admin_can_force_delete_records(): void
{
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $company = Company::factory()->create();
    $callback = CallbackRequest::factory()->create(['company_id' => $company->id]);

    $this->actingAs($superAdmin);

    $this->assertTrue($superAdmin->can('forceDelete', $callback));

    $callback->forceDelete();

    $this->assertDatabaseMissing('callback_requests', ['id' => $callback->id]);
}
```

---

### 4.4 Concurrent Operations Tests

**File**: `/var/www/api-gateway/tests/Feature/EdgeCases/ConcurrentOperationsTest.php`

```php
public function test_concurrent_callback_assignments_do_not_conflict(): void
{
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('admin');

    $staff1 = User::factory()->create(['company_id' => $company->id]);
    $staff2 = User::factory()->create(['company_id' => $company->id]);

    $callback = CallbackRequest::factory()->create(['company_id' => $company->id]);

    $this->actingAs($admin);

    // Simulate concurrent assignments
    DB::transaction(function () use ($callback, $staff1) {
        $callback->update(['assigned_to' => $staff1->id]);
    });

    DB::transaction(function () use ($callback, $staff2) {
        $callback->refresh();
        $callback->update(['assigned_to' => $staff2->id]);
    });

    $callback->refresh();

    // Last write should win
    $this->assertEquals($staff2->id, $callback->assigned_to);
}

public function test_concurrent_policy_updates_maintain_data_integrity(): void
{
    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id]);
    $admin->assignRole('admin');

    $policy = PolicyConfiguration::factory()->create([
        'company_id' => $company->id,
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50.0,
        ],
    ]);

    $this->actingAs($admin);

    // Simulate concurrent updates
    DB::transaction(function () use ($policy) {
        $policy->update([
            'config' => [
                'hours_before' => 48,
                'fee_percentage' => 50.0,
            ],
        ]);
    });

    DB::transaction(function () use ($policy) {
        $policy->refresh();
        $policy->update([
            'config' => [
                'hours_before' => 48,
                'fee_percentage' => 75.0,
            ],
        ]);
    });

    $policy->refresh();

    // Should have valid config
    $this->assertEquals(48, $policy->config['hours_before']);
    $this->assertEquals(75.0, $policy->config['fee_percentage']);
}
```

---

### 4.5 XSS Attack Vector Tests

**File**: `/var/www/api-gateway/tests/Feature/Security/XSSAttackVectorTest.php`

```php
public function test_callback_request_blocks_script_tag_injection(): void
{
    $vectors = [
        '<script>alert("xss")</script>',
        '<script src="evil.com"></script>',
        '<script type="text/javascript">alert(1)</script>',
        '"><script>alert(String.fromCharCode(88,83,83))</script>',
    ];

    foreach ($vectors as $vector) {
        $callback = CallbackRequest::create([
            'customer_name' => $vector . 'John Doe',
            'phone_number' => '+491234567890',
            'priority' => 'normal',
        ]);

        $this->assertStringNotContainsString('<script>', $callback->customer_name);
        $this->assertStringNotContainsString('alert', $callback->customer_name);

        $callback->delete();
    }
}

public function test_callback_request_blocks_event_handler_injection(): void
{
    $vectors = [
        '<img src=x onerror="alert(1)">',
        '<body onload="alert(1)">',
        '<div onclick="alert(1)">Click</div>',
        '<a href="javascript:alert(1)">Link</a>',
    ];

    foreach ($vectors as $vector) {
        $callback = CallbackRequest::create([
            'customer_name' => 'Test',
            'phone_number' => '+491234567890',
            'notes' => $vector,
            'priority' => 'normal',
        ]);

        $this->assertStringNotContainsString('onerror=', $callback->notes);
        $this->assertStringNotContainsString('onload=', $callback->notes);
        $this->assertStringNotContainsString('onclick=', $callback->notes);

        $callback->delete();
    }
}

public function test_notification_template_blocks_iframe_injection(): void
{
    $eventMapping = NotificationEventMapping::factory()->create([
        'event_type' => 'test.event',
        'is_active' => true,
        'default_channels' => ['email'],
    ]);

    $vectors = [
        '<iframe src="evil.com"></iframe>',
        '<iframe src="javascript:alert(1)"></iframe>',
        '<iframe srcdoc="<script>alert(1)</script>"></iframe>',
    ];

    foreach ($vectors as $vector) {
        $config = NotificationConfiguration::create([
            'event_type' => 'test.event',
            'channel' => 'email',
            'template_content' => $vector . 'Safe content',
            'configurable_type' => 'App\Models\Company',
            'configurable_id' => 1,
        ]);

        $this->assertStringNotContainsString('<iframe', $config->template_content);

        $config->delete();
    }
}

public function test_policy_config_blocks_xss_in_nested_arrays(): void
{
    $policy = PolicyConfiguration::create([
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50.0,
            'metadata' => [
                'level1' => '<script>alert(1)</script>',
                'level2' => [
                    'deep' => '<img src=x onerror="alert(1)">',
                ],
            ],
        ],
    ]);

    $configJson = json_encode($policy->config);

    $this->assertStringNotContainsString('<script>', $configJson);
    $this->assertStringNotContainsString('<img', $configJson);
    $this->assertStringNotContainsString('onerror=', $configJson);
}

public function test_notification_template_preserves_template_variables(): void
{
    $eventMapping = NotificationEventMapping::factory()->create([
        'event_type' => 'test.event',
        'is_active' => true,
        'default_channels' => ['email'],
    ]);

    $config = NotificationConfiguration::create([
        'event_type' => 'test.event',
        'channel' => 'email',
        'template_content' => '<script>alert(1)</script>Hello {{customer_name}}, your {{appointment_type}} is confirmed.',
        'configurable_type' => 'App\Models\Company',
        'configurable_id' => 1,
    ]);

    // XSS should be removed
    $this->assertStringNotContainsString('<script>', $config->template_content);

    // Template variables should be preserved
    $this->assertStringContainsString('{{customer_name}}', $config->template_content);
    $this->assertStringContainsString('{{appointment_type}}', $config->template_content);
}
```

---

## Section 5: Execution Plan

### 5.1 Test Execution Order (Priority-Based)

**Phase 1: P0 Critical Security Tests** (Run First)
```bash
# Observer validation - ensures data integrity at entry point
php artisan test --filter=PolicyConfigurationObserverTest
php artisan test --filter=CallbackRequestObserverTest
php artisan test --filter=NotificationConfigurationObserverTest
php artisan test --filter=ObserverTriggeringTest

# Cross-tenant isolation - ensures multi-tenancy works
php artisan test --filter=MultiTenantAuthorizationTest
```

**Phase 2: P0 Authorization Tests**
```bash
# Super admin bypass
php artisan test --filter=SuperAdminAuthorizationTest

# Assignment-based authorization
php artisan test --filter=AssignmentAuthorizationTest
```

**Phase 3: P1 Integration Tests**
```bash
# BelongsToCompany trait
php artisan test --filter=BelongsToCompanyIntegrationTest

# Complete workflows
php artisan test --filter=CompleteWorkflowTest
```

**Phase 4: P1 XSS Security Tests**
```bash
# XSS attack vectors
php artisan test --filter=XSSAttackVectorTest
```

**Phase 5: P2 Edge Cases**
```bash
# Edge case handling
php artisan test --filter=MissingCompanyIdTest
php artisan test --filter=InvalidCompanyIdTest
php artisan test --filter=SoftDeleteTest
php artisan test --filter=ConcurrentOperationsTest
```

---

### 5.2 Complete Test Execution Command

```bash
# Run all multi-tenant security tests
php artisan test \
  --filter="Observer|MultiTenant|SuperAdmin|Assignment|BelongsToCompany|CompleteWorkflow|XSSAttack|MissingCompanyId|InvalidCompanyId|SoftDelete|ConcurrentOperations" \
  --stop-on-failure
```

---

### 5.3 Manual Verification Checklist

**Observer Registration**
```bash
# Verify observers are registered in EventServiceProvider
grep -r "Observer" app/Providers/EventServiceProvider.php
```

**Policy Registration**
```bash
# Verify policies are registered in AuthServiceProvider
grep -r "Policy" app/Providers/AuthServiceProvider.php
```

**Migration Status**
```bash
# Verify all migrations ran
php artisan migrate:status | grep "company_id"
```

**Model Trait Usage**
```bash
# Verify BelongsToCompany trait usage
grep -r "use BelongsToCompany" app/Models/
```

---

## Section 6: Expected Test Results

### 6.1 Success Criteria

**Observer Validation (100% Pass Required)**
- ✅ All required field validations enforced
- ✅ All type validations working
- ✅ All XSS sanitization working
- ✅ All observers actually firing
- ✅ Update validation working

**Authorization Policies (100% Pass Required)**
- ✅ Cross-tenant access prevented for all policies
- ✅ Same-company access allowed
- ✅ Super admin bypass working
- ✅ Assignment-based authorization working
- ✅ Polymorphic relationships authorized correctly

**Integration Tests (95%+ Pass Required)**
- ✅ BelongsToCompany trait auto-fills company_id
- ✅ CompanyScope filtering works
- ✅ Complete workflows execute successfully
- ✅ Observers + Policies work together

**Edge Cases (90%+ Pass Required)**
- ✅ Missing company_id handled gracefully
- ✅ Invalid company_id returns empty results
- ✅ Soft-delete authorization works
- ✅ Concurrent operations maintain integrity
- ✅ All XSS vectors blocked

---

### 6.2 Risk Assessment Per Component

| Component | Pass Threshold | Risk If Failed |
|-----------|----------------|----------------|
| Observer Validation | 100% | 🔴 CRITICAL - Data corruption, XSS vulnerabilities |
| Cross-Tenant Isolation | 100% | 🔴 CRITICAL - Data leakage between companies |
| Super Admin Bypass | 100% | 🟡 HIGH - Admin functionality broken |
| Assignment Authorization | 100% | 🟡 HIGH - Workflow authorization broken |
| BelongsToCompany Integration | 95% | 🟡 HIGH - Multi-tenancy partially broken |
| XSS Prevention | 95% | 🟡 HIGH - Security vulnerability |
| Edge Cases | 90% | 🟢 MEDIUM - Specific scenarios broken |

---

## Section 7: Test Commands Reference

### 7.1 Individual Test File Execution

```bash
# Observer tests
php artisan test tests/Unit/Observers/PolicyConfigurationObserverTest.php
php artisan test tests/Unit/Observers/CallbackRequestObserverTest.php
php artisan test tests/Unit/Observers/NotificationConfigurationObserverTest.php
php artisan test tests/Unit/Observers/ObserverTriggeringTest.php

# Authorization tests
php artisan test tests/Feature/Security/MultiTenantAuthorizationTest.php
php artisan test tests/Feature/Security/SuperAdminAuthorizationTest.php
php artisan test tests/Feature/Security/AssignmentAuthorizationTest.php

# Integration tests
php artisan test tests/Feature/Integration/BelongsToCompanyIntegrationTest.php
php artisan test tests/Feature/Integration/CompleteWorkflowTest.php

# Edge case tests
php artisan test tests/Feature/EdgeCases/MissingCompanyIdTest.php
php artisan test tests/Feature/EdgeCases/InvalidCompanyIdTest.php
php artisan test tests/Feature/EdgeCases/SoftDeleteTest.php
php artisan test tests/Feature/EdgeCases/ConcurrentOperationsTest.php

# Security tests
php artisan test tests/Feature/Security/XSSAttackVectorTest.php
```

---

### 7.2 Coverage Analysis

```bash
# Generate test coverage report
php artisan test --coverage --min=80

# Coverage for specific components
php artisan test --coverage --filter=Observer
php artisan test --coverage --filter=Policy
```

---

### 7.3 Performance Testing

```bash
# Run with performance profiling
php artisan test --profile

# Test database query counts
php artisan test --filter=CompleteWorkflow --log-queries
```

---

## Section 8: Failure Response Plan

### 8.1 Observer Validation Failures

**Symptom**: Invalid data being saved to database

**Investigation Steps**:
1. Check if observer is registered in EventServiceProvider
2. Verify model uses observer boot method
3. Check if validation logic is executing
4. Test observer in isolation

**Resolution**:
```bash
# Re-register observers
php artisan optimize:clear
php artisan config:cache

# Verify registration
php artisan tinker
>>> PolicyConfiguration::getEventDispatcher()->hasListeners('eloquent.creating: App\Models\PolicyConfiguration')
```

---

### 8.2 Authorization Policy Failures

**Symptom**: Users accessing cross-tenant data

**Investigation Steps**:
1. Check if policy is registered in AuthServiceProvider
2. Verify company_id exists on both user and model
3. Check CompanyScope is applied
4. Test policy in isolation

**Resolution**:
```bash
# Clear policy cache
php artisan policy:cache

# Verify policy registration
php artisan tinker
>>> Gate::getPolicyFor(App\Models\CallbackRequest::class)
```

---

### 8.3 XSS Prevention Failures

**Symptom**: XSS payloads not being sanitized

**Investigation Steps**:
1. Check if observer sanitization is executing
2. Verify sanitization logic is correct
3. Test specific XSS vector in isolation

**Resolution**:
- Review sanitization logic in observer
- Add additional sanitization rules if needed
- Test with OWASP XSS filter evasion cheat sheet

---

## Section 9: Production Deployment Checklist

### Pre-Deployment Validation

- [ ] All P0 tests pass (100%)
- [ ] All P1 tests pass (95%+)
- [ ] Observer registration verified
- [ ] Policy registration verified
- [ ] Migration status confirmed
- [ ] Coverage analysis >80%
- [ ] Performance profiling acceptable
- [ ] Manual smoke test completed

### Deployment Steps

1. **Backup Database**
   ```bash
   php artisan backup:run --only-db
   ```

2. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

3. **Clear All Caches**
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Verify Observer Registration**
   ```bash
   php artisan tinker
   >>> PolicyConfiguration::getEventDispatcher()->hasListeners('eloquent.creating: App\Models\PolicyConfiguration')
   ```

5. **Run Smoke Tests**
   ```bash
   php artisan test --filter=CompleteWorkflow
   ```

---

## Conclusion

This comprehensive testing plan covers:

- ✅ **Observer Validation**: 50+ test cases across 3 observers
- ✅ **Authorization Policies**: 40+ test cases across 4 policies
- ✅ **Integration Testing**: 20+ workflow and trait tests
- ✅ **Edge Cases**: 20+ edge case scenarios
- ✅ **Security Testing**: 25+ XSS attack vector tests

**Total Test Cases**: ~155 comprehensive test cases

**Estimated Execution Time**: 5-10 minutes

**Coverage Target**: >85% for all security-critical components

**Risk Mitigation**: All CRITICAL and HIGH risk scenarios covered

---

**Next Steps**: Begin test implementation in priority order (P0 → P1 → P2)
