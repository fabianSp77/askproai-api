# Model Reference

Generated on: 2025-06-23 16:14:16

## Model Hierarchy

```mermaid
graph TB
    AdditionalService --> HasOne
    AdditionalService --> HasOneThrough
    AdditionalService --> BelongsTo
    AdditionalService --> Model
    AdditionalService --> HasMany
    AdditionalService --> HasManyThrough
    AdditionalService --> MorphMany
    AdditionalService --> BelongsToMany
    AdditionalService --> MorphToMany
    Agent --> HasOne
    Agent --> HasOneThrough
    Agent --> BelongsTo
    Agent --> Model
    Agent --> HasMany
    Agent --> HasManyThrough
    Agent --> MorphMany
    Agent --> BelongsToMany
    Agent --> MorphToMany
    ApiCallLog --> HasOne
    ApiCallLog --> HasOneThrough
    ApiCallLog --> BelongsTo
    ApiCallLog --> Model
    ApiCallLog --> HasMany
    ApiCallLog --> HasManyThrough
    ApiCallLog --> MorphMany
    ApiCallLog --> BelongsToMany
    ApiCallLog --> MorphToMany
    ApiCredential --> HasOne
    ApiCredential --> HasOneThrough
    ApiCredential --> BelongsTo
    ApiCredential --> Model
    ApiCredential --> HasMany
    ApiCredential --> HasManyThrough
    ApiCredential --> MorphMany
    ApiCredential --> BelongsToMany
    ApiCredential --> MorphToMany
    Appointment --> HasOne
    Appointment --> HasOneThrough
    Appointment --> BelongsTo
    Appointment --> Model
    Appointment --> HasMany
    Appointment --> HasManyThrough
    Appointment --> MorphMany
    Appointment --> BelongsToMany
    Appointment --> MorphToMany
    AppointmentLock --> HasOne
    AppointmentLock --> HasOneThrough
    AppointmentLock --> BelongsTo
    AppointmentLock --> Model
    AppointmentLock --> HasMany
    AppointmentLock --> HasManyThrough
    AppointmentLock --> MorphMany
    AppointmentLock --> BelongsToMany
    AppointmentLock --> MorphToMany
    AvailabilityCache --> HasOne
    AvailabilityCache --> HasOneThrough
    AvailabilityCache --> BelongsTo
    AvailabilityCache --> Model
    AvailabilityCache --> HasMany
    AvailabilityCache --> HasManyThrough
    AvailabilityCache --> MorphMany
    AvailabilityCache --> BelongsToMany
    AvailabilityCache --> MorphToMany
    BillingPeriod --> HasOne
    BillingPeriod --> HasOneThrough
    BillingPeriod --> BelongsTo
    BillingPeriod --> Model
    BillingPeriod --> HasMany
    BillingPeriod --> HasManyThrough
    BillingPeriod --> MorphMany
    BillingPeriod --> BelongsToMany
    BillingPeriod --> MorphToMany
    Booking --> HasOne
    Booking --> HasOneThrough
    Booking --> BelongsTo
    Booking --> Model
    Booking --> HasMany
    Booking --> HasManyThrough
    Booking --> MorphMany
    Booking --> BelongsToMany
    Booking --> MorphToMany
    Branch --> HasOne
    Branch --> HasOneThrough
    Branch --> BelongsTo
    Branch --> Model
    Branch --> HasMany
    Branch --> HasManyThrough
    Branch --> MorphMany
    Branch --> BelongsToMany
    Branch --> MorphToMany
    BranchEventType --> HasOne
    BranchEventType --> HasOneThrough
    BranchEventType --> BelongsTo
    BranchEventType --> Model
    BranchEventType --> HasMany
    BranchEventType --> HasManyThrough
    BranchEventType --> MorphMany
    BranchEventType --> BelongsToMany
    BranchEventType --> MorphToMany
    BranchPricingOverride --> HasOne
    BranchPricingOverride --> HasOneThrough
    BranchPricingOverride --> BelongsTo
    BranchPricingOverride --> Model
    BranchPricingOverride --> HasMany
    BranchPricingOverride --> HasManyThrough
    BranchPricingOverride --> MorphMany
    BranchPricingOverride --> BelongsToMany
    BranchPricingOverride --> MorphToMany
    BranchServiceOverride --> HasOne
    BranchServiceOverride --> HasOneThrough
    BranchServiceOverride --> BelongsTo
    BranchServiceOverride --> Model
    BranchServiceOverride --> HasMany
    BranchServiceOverride --> HasManyThrough
    BranchServiceOverride --> MorphMany
    BranchServiceOverride --> BelongsToMany
    BranchServiceOverride --> MorphToMany
    Business --> HasOne
    Business --> HasOneThrough
    Business --> BelongsTo
    Business --> Model
    Business --> HasMany
    Business --> HasManyThrough
    Business --> MorphMany
    Business --> BelongsToMany
    Business --> MorphToMany
    BusinessHoursTemplate --> HasOne
    BusinessHoursTemplate --> HasOneThrough
    BusinessHoursTemplate --> BelongsTo
    BusinessHoursTemplate --> Model
    BusinessHoursTemplate --> HasMany
    BusinessHoursTemplate --> HasManyThrough
    BusinessHoursTemplate --> MorphMany
    BusinessHoursTemplate --> BelongsToMany
    BusinessHoursTemplate --> MorphToMany
    CalcomBooking --> HasOne
    CalcomBooking --> HasOneThrough
    CalcomBooking --> BelongsTo
    CalcomBooking --> Model
    CalcomBooking --> HasMany
    CalcomBooking --> HasManyThrough
    CalcomBooking --> MorphMany
    CalcomBooking --> BelongsToMany
    CalcomBooking --> MorphToMany
    CalcomEventType --> HasOne
    CalcomEventType --> HasOneThrough
    CalcomEventType --> BelongsTo
    CalcomEventType --> Model
    CalcomEventType --> HasMany
    CalcomEventType --> HasManyThrough
    CalcomEventType --> MorphMany
    CalcomEventType --> BelongsToMany
    CalcomEventType --> MorphToMany
    Calendar --> HasOne
    Calendar --> HasOneThrough
    Calendar --> BelongsTo
    Calendar --> Model
    Calendar --> HasMany
    Calendar --> HasManyThrough
    Calendar --> MorphMany
    Calendar --> BelongsToMany
    Calendar --> MorphToMany
    CalendarMapping --> HasOne
    CalendarMapping --> HasOneThrough
    CalendarMapping --> BelongsTo
    CalendarMapping --> Model
    CalendarMapping --> HasMany
    CalendarMapping --> HasManyThrough
    CalendarMapping --> MorphMany
    CalendarMapping --> BelongsToMany
    CalendarMapping --> MorphToMany
    Call --> HasOne
    Call --> HasOneThrough
    Call --> BelongsTo
    Call --> Model
    Call --> HasMany
    Call --> HasManyThrough
    Call --> MorphMany
    Call --> BelongsToMany
    Call --> MorphToMany
    CallLog --> HasOne
    CallLog --> HasOneThrough
    CallLog --> BelongsTo
    CallLog --> Model
    CallLog --> HasMany
    CallLog --> HasManyThrough
    CallLog --> MorphMany
    CallLog --> BelongsToMany
    CallLog --> MorphToMany
    CallbackRequest --> HasOne
    CallbackRequest --> HasOneThrough
    CallbackRequest --> BelongsTo
    CallbackRequest --> Model
    CallbackRequest --> HasMany
    CallbackRequest --> HasManyThrough
    CallbackRequest --> MorphMany
    CallbackRequest --> BelongsToMany
    CallbackRequest --> MorphToMany
    Company --> HasOne
    Company --> HasOneThrough
    Company --> BelongsTo
    Company --> Model
    Company --> HasMany
    Company --> HasManyThrough
    Company --> MorphMany
    Company --> BelongsToMany
    Company --> MorphToMany
    CompanyPricing --> HasOne
    CompanyPricing --> HasOneThrough
    CompanyPricing --> BelongsTo
    CompanyPricing --> Model
    CompanyPricing --> HasMany
    CompanyPricing --> HasManyThrough
    CompanyPricing --> MorphMany
    CompanyPricing --> BelongsToMany
    CompanyPricing --> MorphToMany
    CookieConsent --> HasOne
    CookieConsent --> HasOneThrough
    CookieConsent --> BelongsTo
    CookieConsent --> Model
    CookieConsent --> HasMany
    CookieConsent --> HasManyThrough
    CookieConsent --> MorphMany
    CookieConsent --> BelongsToMany
    CookieConsent --> MorphToMany
    Customer --> HasOne
    Customer --> HasOneThrough
    Customer --> BelongsTo
    Customer --> Model
    Customer --> HasMany
    Customer --> HasManyThrough
    Customer --> MorphMany
    Customer --> BelongsToMany
    Customer --> MorphToMany
    CustomerAuth --> HasOne
    CustomerAuth --> HasOneThrough
    CustomerAuth --> BelongsTo
    CustomerAuth --> Model
    CustomerAuth --> HasMany
    CustomerAuth --> HasManyThrough
    CustomerAuth --> MorphMany
    CustomerAuth --> BelongsToMany
    CustomerAuth --> MorphToMany
    CustomerService --> HasOne
    CustomerService --> HasOneThrough
    CustomerService --> BelongsTo
    CustomerService --> Model
    CustomerService --> HasMany
    CustomerService --> HasManyThrough
    CustomerService --> MorphMany
    CustomerService --> BelongsToMany
    CustomerService --> MorphToMany
    DashboardConfiguration --> HasOne
    DashboardConfiguration --> HasOneThrough
    DashboardConfiguration --> BelongsTo
    DashboardConfiguration --> Model
    DashboardConfiguration --> HasMany
    DashboardConfiguration --> HasManyThrough
    DashboardConfiguration --> MorphMany
    DashboardConfiguration --> BelongsToMany
    DashboardConfiguration --> MorphToMany
    Dienstleistung --> HasOne
    Dienstleistung --> HasOneThrough
    Dienstleistung --> BelongsTo
    Dienstleistung --> Model
    Dienstleistung --> HasMany
    Dienstleistung --> HasManyThrough
    Dienstleistung --> MorphMany
    Dienstleistung --> BelongsToMany
    Dienstleistung --> MorphToMany
    DummyCompany --> HasOne
    DummyCompany --> HasOneThrough
    DummyCompany --> BelongsTo
    DummyCompany --> Model
    DummyCompany --> HasMany
    DummyCompany --> HasManyThrough
    DummyCompany --> MorphMany
    DummyCompany --> BelongsToMany
    DummyCompany --> MorphToMany
    EventTypeImportLog --> HasOne
    EventTypeImportLog --> HasOneThrough
    EventTypeImportLog --> BelongsTo
    EventTypeImportLog --> Model
    EventTypeImportLog --> HasMany
    EventTypeImportLog --> HasManyThrough
    EventTypeImportLog --> MorphMany
    EventTypeImportLog --> BelongsToMany
    EventTypeImportLog --> MorphToMany
    FAQ --> HasOne
    FAQ --> HasOneThrough
    FAQ --> BelongsTo
    FAQ --> Model
    FAQ --> HasMany
    FAQ --> HasManyThrough
    FAQ --> MorphMany
    FAQ --> BelongsToMany
    FAQ --> MorphToMany
    Faq --> HasOne
    Faq --> HasOneThrough
    Faq --> BelongsTo
    Faq --> Model
    Faq --> HasMany
    Faq --> HasManyThrough
    Faq --> MorphMany
    Faq --> BelongsToMany
    Faq --> MorphToMany
    GdprRequest --> HasOne
    GdprRequest --> HasOneThrough
    GdprRequest --> BelongsTo
    GdprRequest --> Model
    GdprRequest --> HasMany
    GdprRequest --> HasManyThrough
    GdprRequest --> MorphMany
    GdprRequest --> BelongsToMany
    GdprRequest --> MorphToMany
    Integration --> HasOne
    Integration --> HasOneThrough
    Integration --> BelongsTo
    Integration --> Model
    Integration --> HasMany
    Integration --> HasManyThrough
    Integration --> MorphMany
    Integration --> BelongsToMany
    Integration --> MorphToMany
    Invoice --> HasOne
    Invoice --> HasOneThrough
    Invoice --> BelongsTo
    Invoice --> Model
    Invoice --> HasMany
    Invoice --> HasManyThrough
    Invoice --> MorphMany
    Invoice --> BelongsToMany
    Invoice --> MorphToMany
    InvoiceItem --> HasOne
    InvoiceItem --> HasOneThrough
    InvoiceItem --> BelongsTo
    InvoiceItem --> Model
    InvoiceItem --> HasMany
    InvoiceItem --> HasManyThrough
    InvoiceItem --> MorphMany
    InvoiceItem --> BelongsToMany
    InvoiceItem --> MorphToMany
    InvoiceItemFlexible --> HasOne
    InvoiceItemFlexible --> HasOneThrough
    InvoiceItemFlexible --> BelongsTo
    InvoiceItemFlexible --> Model
    InvoiceItemFlexible --> HasMany
    InvoiceItemFlexible --> HasManyThrough
    InvoiceItemFlexible --> MorphMany
    InvoiceItemFlexible --> BelongsToMany
    InvoiceItemFlexible --> MorphToMany
    KnowledgeAnalytic --> HasOne
    KnowledgeAnalytic --> HasOneThrough
    KnowledgeAnalytic --> BelongsTo
    KnowledgeAnalytic --> Model
    KnowledgeAnalytic --> HasMany
    KnowledgeAnalytic --> HasManyThrough
    KnowledgeAnalytic --> MorphMany
    KnowledgeAnalytic --> BelongsToMany
    KnowledgeAnalytic --> MorphToMany
    KnowledgeCategory --> HasOne
    KnowledgeCategory --> HasOneThrough
    KnowledgeCategory --> BelongsTo
    KnowledgeCategory --> Model
    KnowledgeCategory --> HasMany
    KnowledgeCategory --> HasManyThrough
    KnowledgeCategory --> MorphMany
    KnowledgeCategory --> BelongsToMany
    KnowledgeCategory --> MorphToMany
    KnowledgeCodeSnippet --> HasOne
    KnowledgeCodeSnippet --> HasOneThrough
    KnowledgeCodeSnippet --> BelongsTo
    KnowledgeCodeSnippet --> Model
    KnowledgeCodeSnippet --> HasMany
    KnowledgeCodeSnippet --> HasManyThrough
    KnowledgeCodeSnippet --> MorphMany
    KnowledgeCodeSnippet --> BelongsToMany
    KnowledgeCodeSnippet --> MorphToMany
    KnowledgeComment --> HasOne
    KnowledgeComment --> HasOneThrough
    KnowledgeComment --> BelongsTo
    KnowledgeComment --> Model
    KnowledgeComment --> HasMany
    KnowledgeComment --> HasManyThrough
    KnowledgeComment --> MorphMany
    KnowledgeComment --> BelongsToMany
    KnowledgeComment --> MorphToMany
    KnowledgeDocument --> HasOne
    KnowledgeDocument --> HasOneThrough
    KnowledgeDocument --> BelongsTo
    KnowledgeDocument --> Model
    KnowledgeDocument --> HasMany
    KnowledgeDocument --> HasManyThrough
    KnowledgeDocument --> MorphMany
    KnowledgeDocument --> BelongsToMany
    KnowledgeDocument --> MorphToMany
    KnowledgeFeedback --> HasOne
    KnowledgeFeedback --> HasOneThrough
    KnowledgeFeedback --> BelongsTo
    KnowledgeFeedback --> Model
    KnowledgeFeedback --> HasMany
    KnowledgeFeedback --> HasManyThrough
    KnowledgeFeedback --> MorphMany
    KnowledgeFeedback --> BelongsToMany
    KnowledgeFeedback --> MorphToMany
    KnowledgeNotebook --> HasOne
    KnowledgeNotebook --> HasOneThrough
    KnowledgeNotebook --> BelongsTo
    KnowledgeNotebook --> Model
    KnowledgeNotebook --> HasMany
    KnowledgeNotebook --> HasManyThrough
    KnowledgeNotebook --> MorphMany
    KnowledgeNotebook --> BelongsToMany
    KnowledgeNotebook --> MorphToMany
    KnowledgeNotebookEntry --> HasOne
    KnowledgeNotebookEntry --> HasOneThrough
    KnowledgeNotebookEntry --> BelongsTo
    KnowledgeNotebookEntry --> Model
    KnowledgeNotebookEntry --> HasMany
    KnowledgeNotebookEntry --> HasManyThrough
    KnowledgeNotebookEntry --> MorphMany
    KnowledgeNotebookEntry --> BelongsToMany
    KnowledgeNotebookEntry --> MorphToMany
    KnowledgeRelatedDocument --> HasOne
    KnowledgeRelatedDocument --> HasOneThrough
    KnowledgeRelatedDocument --> BelongsTo
    KnowledgeRelatedDocument --> Model
    KnowledgeRelatedDocument --> HasMany
    KnowledgeRelatedDocument --> HasManyThrough
    KnowledgeRelatedDocument --> MorphMany
    KnowledgeRelatedDocument --> BelongsToMany
    KnowledgeRelatedDocument --> MorphToMany
    KnowledgeRelationship --> HasOne
    KnowledgeRelationship --> HasOneThrough
    KnowledgeRelationship --> BelongsTo
    KnowledgeRelationship --> Model
    KnowledgeRelationship --> HasMany
    KnowledgeRelationship --> HasManyThrough
    KnowledgeRelationship --> MorphMany
    KnowledgeRelationship --> BelongsToMany
    KnowledgeRelationship --> MorphToMany
    KnowledgeSearchIndex --> HasOne
    KnowledgeSearchIndex --> HasOneThrough
    KnowledgeSearchIndex --> BelongsTo
    KnowledgeSearchIndex --> Model
    KnowledgeSearchIndex --> HasMany
    KnowledgeSearchIndex --> HasManyThrough
    KnowledgeSearchIndex --> MorphMany
    KnowledgeSearchIndex --> BelongsToMany
    KnowledgeSearchIndex --> MorphToMany
    KnowledgeTag --> HasOne
    KnowledgeTag --> HasOneThrough
    KnowledgeTag --> BelongsTo
    KnowledgeTag --> Model
    KnowledgeTag --> HasMany
    KnowledgeTag --> HasManyThrough
    KnowledgeTag --> MorphMany
    KnowledgeTag --> BelongsToMany
    KnowledgeTag --> MorphToMany
    KnowledgeVersion --> HasOne
    KnowledgeVersion --> HasOneThrough
    KnowledgeVersion --> BelongsTo
    KnowledgeVersion --> Model
    KnowledgeVersion --> HasMany
    KnowledgeVersion --> HasManyThrough
    KnowledgeVersion --> MorphMany
    KnowledgeVersion --> BelongsToMany
    KnowledgeVersion --> MorphToMany
    Kunde --> HasOne
    Kunde --> HasOneThrough
    Kunde --> BelongsTo
    Kunde --> Model
    Kunde --> HasMany
    Kunde --> HasManyThrough
    Kunde --> MorphMany
    Kunde --> BelongsToMany
    Kunde --> MorphToMany
    LegacyUser --> HasOne
    LegacyUser --> HasOneThrough
    LegacyUser --> BelongsTo
    LegacyUser --> Model
    LegacyUser --> HasMany
    LegacyUser --> HasManyThrough
    LegacyUser --> MorphMany
    LegacyUser --> BelongsToMany
    LegacyUser --> MorphToMany
    MCPMetric --> HasOne
    MCPMetric --> HasOneThrough
    MCPMetric --> BelongsTo
    MCPMetric --> Model
    MCPMetric --> HasMany
    MCPMetric --> HasManyThrough
    MCPMetric --> MorphMany
    MCPMetric --> BelongsToMany
    MCPMetric --> MorphToMany
    MasterService --> HasOne
    MasterService --> HasOneThrough
    MasterService --> BelongsTo
    MasterService --> Model
    MasterService --> HasMany
    MasterService --> HasManyThrough
    MasterService --> MorphMany
    MasterService --> BelongsToMany
    MasterService --> MorphToMany
    Mitarbeiter --> HasOne
    Mitarbeiter --> HasOneThrough
    Mitarbeiter --> BelongsTo
    Mitarbeiter --> Model
    Mitarbeiter --> HasMany
    Mitarbeiter --> HasManyThrough
    Mitarbeiter --> MorphMany
    Mitarbeiter --> BelongsToMany
    Mitarbeiter --> MorphToMany
    Note --> HasOne
    Note --> HasOneThrough
    Note --> BelongsTo
    Note --> Model
    Note --> HasMany
    Note --> HasManyThrough
    Note --> MorphMany
    Note --> BelongsToMany
    Note --> MorphToMany
    Payment --> HasOne
    Payment --> HasOneThrough
    Payment --> BelongsTo
    Payment --> Model
    Payment --> HasMany
    Payment --> HasManyThrough
    Payment --> MorphMany
    Payment --> BelongsToMany
    Payment --> MorphToMany
    PhoneNumber --> HasOne
    PhoneNumber --> HasOneThrough
    PhoneNumber --> BelongsTo
    PhoneNumber --> Model
    PhoneNumber --> HasMany
    PhoneNumber --> HasManyThrough
    PhoneNumber --> MorphMany
    PhoneNumber --> BelongsToMany
    PhoneNumber --> MorphToMany
    PremiumService --> HasOne
    PremiumService --> HasOneThrough
    PremiumService --> BelongsTo
    PremiumService --> Model
    PremiumService --> HasMany
    PremiumService --> HasManyThrough
    PremiumService --> MorphMany
    PremiumService --> BelongsToMany
    PremiumService --> MorphToMany
    RetellAgent --> HasOne
    RetellAgent --> HasOneThrough
    RetellAgent --> BelongsTo
    RetellAgent --> Model
    RetellAgent --> HasMany
    RetellAgent --> HasManyThrough
    RetellAgent --> MorphMany
    RetellAgent --> BelongsToMany
    RetellAgent --> MorphToMany
    RetellWebhook --> HasOne
    RetellWebhook --> HasOneThrough
    RetellWebhook --> BelongsTo
    RetellWebhook --> Model
    RetellWebhook --> HasMany
    RetellWebhook --> HasManyThrough
    RetellWebhook --> MorphMany
    RetellWebhook --> BelongsToMany
    RetellWebhook --> MorphToMany
    SecurityLog --> HasOne
    SecurityLog --> HasOneThrough
    SecurityLog --> BelongsTo
    SecurityLog --> Model
    SecurityLog --> HasMany
    SecurityLog --> HasManyThrough
    SecurityLog --> MorphMany
    SecurityLog --> BelongsToMany
    SecurityLog --> MorphToMany
    Service --> HasOne
    Service --> HasOneThrough
    Service --> BelongsTo
    Service --> Model
    Service --> HasMany
    Service --> HasManyThrough
    Service --> MorphMany
    Service --> BelongsToMany
    Service --> MorphToMany
    Staff --> HasOne
    Staff --> HasOneThrough
    Staff --> BelongsTo
    Staff --> Model
    Staff --> HasMany
    Staff --> HasManyThrough
    Staff --> MorphMany
    Staff --> BelongsToMany
    Staff --> MorphToMany
    StaffEventType --> HasOne
    StaffEventType --> HasOneThrough
    StaffEventType --> BelongsTo
    StaffEventType --> Model
    StaffEventType --> HasMany
    StaffEventType --> HasManyThrough
    StaffEventType --> MorphMany
    StaffEventType --> BelongsToMany
    StaffEventType --> MorphToMany
    StaffService --> HasOne
    StaffService --> HasOneThrough
    StaffService --> BelongsTo
    StaffService --> Model
    StaffService --> HasMany
    StaffService --> HasManyThrough
    StaffService --> MorphMany
    StaffService --> BelongsToMany
    StaffService --> MorphToMany
    StaffServiceAssignment --> HasOne
    StaffServiceAssignment --> HasOneThrough
    StaffServiceAssignment --> BelongsTo
    StaffServiceAssignment --> Model
    StaffServiceAssignment --> HasMany
    StaffServiceAssignment --> HasManyThrough
    StaffServiceAssignment --> MorphMany
    StaffServiceAssignment --> BelongsToMany
    StaffServiceAssignment --> MorphToMany
    TaxRate --> HasOne
    TaxRate --> HasOneThrough
    TaxRate --> BelongsTo
    TaxRate --> Model
    TaxRate --> HasMany
    TaxRate --> HasManyThrough
    TaxRate --> MorphMany
    TaxRate --> BelongsToMany
    TaxRate --> MorphToMany
    Telefonnummer --> HasOne
    Telefonnummer --> HasOneThrough
    Telefonnummer --> BelongsTo
    Telefonnummer --> Model
    Telefonnummer --> HasMany
    Telefonnummer --> HasManyThrough
    Telefonnummer --> MorphMany
    Telefonnummer --> BelongsToMany
    Telefonnummer --> MorphToMany
    Tenant --> HasOne
    Tenant --> HasOneThrough
    Tenant --> BelongsTo
    Tenant --> Model
    Tenant --> HasMany
    Tenant --> HasManyThrough
    Tenant --> MorphMany
    Tenant --> BelongsToMany
    Tenant --> MorphToMany
    Termin --> HasOne
    Termin --> HasOneThrough
    Termin --> BelongsTo
    Termin --> Model
    Termin --> HasMany
    Termin --> HasManyThrough
    Termin --> MorphMany
    Termin --> BelongsToMany
    Termin --> MorphToMany
    UnifiedEventType --> HasOne
    UnifiedEventType --> HasOneThrough
    UnifiedEventType --> BelongsTo
    UnifiedEventType --> Model
    UnifiedEventType --> HasMany
    UnifiedEventType --> HasManyThrough
    UnifiedEventType --> MorphMany
    UnifiedEventType --> BelongsToMany
    UnifiedEventType --> MorphToMany
    User --> HasOne
    User --> HasOneThrough
    User --> BelongsTo
    User --> Model
    User --> HasMany
    User --> HasManyThrough
    User --> MorphMany
    User --> BelongsToMany
    User --> MorphToMany
    ValidationResult --> HasOne
    ValidationResult --> HasOneThrough
    ValidationResult --> BelongsTo
    ValidationResult --> Model
    ValidationResult --> HasMany
    ValidationResult --> HasManyThrough
    ValidationResult --> MorphMany
    ValidationResult --> BelongsToMany
    ValidationResult --> MorphToMany
    WebhookEvent --> HasOne
    WebhookEvent --> HasOneThrough
    WebhookEvent --> BelongsTo
    WebhookEvent --> Model
    WebhookEvent --> HasMany
    WebhookEvent --> HasManyThrough
    WebhookEvent --> MorphMany
    WebhookEvent --> BelongsToMany
    WebhookEvent --> MorphToMany
    WorkingHour --> HasOne
    WorkingHour --> HasOneThrough
    WorkingHour --> BelongsTo
    WorkingHour --> Model
    WorkingHour --> HasMany
    WorkingHour --> HasManyThrough
    WorkingHour --> MorphMany
    WorkingHour --> BelongsToMany
    WorkingHour --> MorphToMany
    WorkingHours --> HasOne
    WorkingHours --> HasOneThrough
    WorkingHours --> BelongsTo
    WorkingHours --> Model
    WorkingHours --> HasMany
    WorkingHours --> HasManyThrough
    WorkingHours --> MorphMany
    WorkingHours --> BelongsToMany
    WorkingHours --> MorphToMany
```

## AdditionalService

**Table**: `additional_services`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `name`
- `description`
- `type`
- `price`
- `unit`
- `is_active`
- `stripe_price_id`
- `metadata`

**Attribute Casts**:
- `id`: int
- `metadata`: array
- `price`: decimal:2
- `is_active`: boolean

**Relationships**:
- `customerServices()`: HasMany

**Scopes**:
- `active()`
- `oneTime()`
- `recurring()`
- `allTenants()`
- `forTenant()`

---

## Agent

**Table**: `agents`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `name`
- `agent_id`
- `type`
- `config`
- `active`

**Attribute Casts**:
- `id`: int
- `config`: array
- `active`: boolean

**Relationships**:

**Scopes**:

---

## ApiCallLog

**Table**: `api_call_logs`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `service`
- `endpoint`
- `method`
- `request_headers`
- `request_body`
- `response_status`
- `response_headers`
- `response_body`
- `duration_ms`
- `correlation_id`
- `company_id`
- `user_id`
- `ip_address`
- `user_agent`
- `error_message`
- `requested_at`
- `responded_at`

**Attribute Casts**:
- `id`: int
- `request_headers`: array
- `request_body`: array
- `response_headers`: array
- `response_body`: array
- `duration_ms`: float
- `requested_at`: datetime
- `responded_at`: datetime

**Relationships**:
- `company()`: BelongsTo

**Scopes**:
- `forService()`
- `forEndpoint()`
- `successful()`
- `failed()`
- `correlated()`
- `dateRange()`

---

## ApiCredential

**Table**: `api_credentials`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `credentialable_id`
- `credentialable_type`
- `service`
- `key_type`
- `value`
- `is_inherited`
- `inherited_from_id`
- `inherited_from_type`

**Attribute Casts**:
- `id`: int
- `is_inherited`: boolean

**Relationships**:

**Scopes**:
- `forService()`
- `ofType()`

---

## Appointment

**Table**: `appointments`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `customer_id`
- `branch_id`
- `staff_id`
- `service_id`
- `calcom_event_type_id`
- `calcom_booking_id`
- `starts_at`
- `ends_at`
- `status`
- `notes`
- `metadata`
- `price`
- `call_id`
- `tenant_id`
- `company_id`
- `external_id`
- `calcom_v2_booking_id`
- `reminder_24h_sent_at`
- `reminder_2h_sent_at`
- `reminder_30m_sent_at`
- `payload`
- `version`
- `lock_expires_at`
- `lock_token`

**Attribute Casts**:
- `id`: int
- `starts_at`: datetime
- `ends_at`: datetime
- `metadata`: array
- `payload`: array
- `reminder_24h_sent_at`: datetime
- `reminder_2h_sent_at`: datetime
- `reminder_30m_sent_at`: datetime
- `lock_expires_at`: datetime
- `version`: integer

**Relationships**:
- `customer()`: BelongsTo
- `branch()`: BelongsTo
- `staff()`: BelongsTo
- `company()`: BelongsTo
- `service()`: BelongsTo
- `calcomBooking()`: BelongsTo
- `calcomEventType()`: BelongsTo
- `call()`: BelongsTo

**Scopes**:
- `upcoming()`
- `today()`
- `dateRange()`
- `byStatus()`
- `scheduled()`
- `completed()`
- `forCompany()`
- `forBranch()`
- `forStaff()`
- `forCustomer()`
- `withRelations()`
- `needingReminders()`
- `overdue()`

---

## AppointmentLock

**Table**: `appointment_locks`

**Primary Key**: `id`

**Timestamps**: No

**Fillable Attributes**:
- `branch_id`
- `staff_id`
- `starts_at`
- `ends_at`
- `lock_token`
- `lock_expires_at`

**Attribute Casts**:
- `id`: int
- `starts_at`: datetime
- `ends_at`: datetime
- `lock_expires_at`: datetime
- `created_at`: datetime

**Relationships**:
- `branch()`: BelongsTo
- `staff()`: BelongsTo

**Scopes**:
- `active()`
- `expired()`
- `forTimeRange()`

---

## AvailabilityCache

**Table**: `availability_cache`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `staff_id`
- `event_type_id`
- `date`
- `slots`
- `cache_key`
- `cached_at`
- `expires_at`
- `is_valid`

**Attribute Casts**:
- `id`: int
- `date`: date
- `slots`: array
- `cached_at`: datetime
- `expires_at`: datetime
- `is_valid`: boolean

**Relationships**:
- `staff()`: BelongsTo
- `eventType()`: BelongsTo

**Scopes**:
- `valid()`
- `expired()`
- `invalid()`

---

## BillingPeriod

**Table**: `billing_periods`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `branch_id`
- `period_start`
- `period_end`
- `total_minutes`
- `included_minutes`
- `overage_minutes`
- `total_cost`
- `total_revenue`
- `margin`
- `margin_percentage`
- `is_invoiced`
- `invoiced_at`

**Attribute Casts**:
- `id`: int
- `period_start`: date
- `period_end`: date
- `total_minutes`: integer
- `included_minutes`: integer
- `overage_minutes`: integer
- `total_cost`: decimal:2
- `total_revenue`: decimal:2
- `margin`: decimal:2
- `margin_percentage`: decimal:2
- `is_invoiced`: boolean
- `invoiced_at`: datetime

**Relationships**:
- `company()`: BelongsTo
- `branch()`: BelongsTo
- `invoice()`: BelongsTo

**Scopes**:
- `uninvoiced()`
- `currentMonth()`

---

## Booking

**Table**: `bookings`

**Primary Key**: `id`

**Timestamps**: Yes

**Attribute Casts**:
- `id`: int

**Relationships**:

**Scopes**:

---

## Branch

**Table**: `branches`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `customer_id`
- `name`
- `slug`
- `phone_number`
- `notification_email`
- `address`
- `city`
- `postal_code`
- `country`
- `website`
- `business_hours`
- `retell_agent_id`
- `retell_agent_data`
- `retell_synced_at`
- `retell_agent_status`
- `retell_agent_created_at`
- `settings`
- `calcom_api_key`
- `calcom_event_type_id`
- `calcom_team_slug`
- `calendar_mode`
- `active`
- `notify_on_booking`
- `deleted_at`
- `features`

**Attribute Casts**:
- `is_main`: boolean
- `active`: boolean
- `invoice_recipient`: boolean
- `notify_on_booking`: boolean
- `calendar_mapping`: array
- `integration_status`: array
- `opening_hours`: array
- `created_at`: datetime
- `updated_at`: datetime
- `deleted_at`: datetime
- `integrations_tested_at`: datetime
- `retell_agent_cache`: array
- `retell_last_sync`: datetime
- `configuration_status`: array
- `parent_settings`: array
- `business_hours`: array
- `services_override`: array
- `settings`: array
- `features`: array
- `retell_agent_data`: array
- `retell_agent_created_at`: datetime
- `retell_synced_at`: datetime
- `coordinates`: array
- `transport_info`: array

**Relationships**:

**Scopes**:
- `active()`
- `main()`
- `forCompany()`
- `currentCompany()`

---

## BranchEventType

**Table**: `branch_event_types`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `branch_id`
- `event_type_id`
- `is_primary`

**Attribute Casts**:
- `is_primary`: boolean

**Relationships**:

**Scopes**:

---

## BranchPricingOverride

**Table**: `branch_pricing_overrides`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_pricing_id`
- `branch_id`
- `price_per_minute`
- `included_minutes`
- `overage_price_per_minute`
- `is_active`

**Attribute Casts**:
- `id`: int
- `price_per_minute`: decimal:4
- `included_minutes`: integer
- `overage_price_per_minute`: decimal:4
- `is_active`: boolean

**Relationships**:
- `pricing()`: BelongsTo
- `branch()`: BelongsTo

**Scopes**:
- `active()`

---

## BranchServiceOverride

**Table**: `branch_service_overrides`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `branch_id`
- `master_service_id`
- `custom_duration`
- `custom_price`
- `custom_calcom_event_type_id`
- `active`

**Attribute Casts**:
- `custom_duration`: integer
- `custom_price`: decimal:2
- `active`: boolean

**Relationships**:
- `branch()`: BelongsTo
- `masterService()`: BelongsTo

**Scopes**:

---

## Business

**Table**: `businesses`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `name`
- `adresse`
- `telefon`
- `email`
- `website`
- `oeffnungszeiten`
- `api_key`
- `cal_com_user_id`

**Attribute Casts**:
- `id`: int
- `oeffnungszeiten`: array

**Relationships**:
- `staff()`: HasMany
- `services()`: HasMany
- `appointments()`: HasMany
- `calls()`: HasMany
- `faqs()`: HasMany

**Scopes**:

---

## BusinessHoursTemplate

**Table**: `business_hours_templates`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `name`
- `description`
- `hours`

**Attribute Casts**:
- `id`: int
- `hours`: array

**Relationships**:

**Scopes**:

---

## CalcomBooking

**Table**: `calcom_bookings`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `calcom_uid`
- `appointment_id`
- `status`
- `raw_payload`

**Attribute Casts**:
- `id`: int
- `raw_payload`: array

**Relationships**:
- `appointment()`: BelongsTo

**Scopes**:

---

## CalcomEventType

**Table**: `calcom_event_types`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `branch_id`
- `staff_id`
- `service_id`
- `calcom_event_type_id`
- `duration`
- `calendar`
- `tenant_id`
- `name`
- `slug`
- `calcom_numeric_event_type_id`
- `duration_minutes`
- `description`
- `price`
- `is_active`
- `is_team_event`
- `requires_confirmation`
- `booking_limits`
- `sync_status`
- `sync_error`
- `last_synced_at`
- `metadata`
- `minimum_booking_notice`
- `booking_future_limit`
- `time_slot_interval`
- `buffer_before`
- `buffer_after`
- `locations`
- `custom_fields`
- `max_bookings_per_day`
- `seats_per_time_slot`
- `schedule_id`
- `recurring_config`
- `setup_status`
- `setup_checklist`
- `webhook_settings`
- `calcom_url`

**Attribute Casts**:
- `id`: int
- `is_active`: boolean
- `price`: decimal:2
- `last_synced_at`: datetime
- `metadata`: array
- `locations`: array
- `custom_fields`: array
- `recurring_config`: array
- `setup_checklist`: array
- `webhook_settings`: array
- `is_team_event`: boolean
- `requires_confirmation`: boolean

**Relationships**:
- `assignedStaff()`: BelongsToMany
- `staff()`: BelongsToMany
- `company()`: BelongsTo
- `branch()`: BelongsTo
- `branches()`: BelongsToMany
- `primaryBranches()`: BelongsToMany
- `bookings()`: HasMany

**Scopes**:
- `active()`
- `synced()`

---

## Calendar

**Table**: `calendars`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `staff_id`
- `provider`
- `api_key`
- `event_type_id`
- `external_user_id`
- `validated_at`

**Attribute Casts**:
- `validated_at`: datetime
- `deleted_at`: datetime

**Relationships**:

**Scopes**:

---

## CalendarMapping

**Table**: `calendar_mappings`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `branch_id`
- `staff_id`
- `calendar_type`
- `calendar_details`

**Attribute Casts**:
- `id`: int
- `calendar_details`: array

**Relationships**:

**Scopes**:

---

## Call

**Table**: `calls`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `call_id`
- `caller`
- `from_number`
- `to_number`
- `retell_call_id`
- `retell_agent_id`
- `call_status`
- `transcript`
- `transcription_id`
- `recording_url`
- `call_type`
- `audio_url`
- `video_url`
- `duration_sec`
- `duration_minutes`
- `cost`
- `cost_cents`
- `customer_id`
- `appointment_id`
- `agent_id`
- `company_id`
- `branch_id`
- `staff_id`
- `analysis`
- `webhook_data`
- `raw`
- `raw_data`
- `notes`
- `metadata`
- `tags`
- `sentiment`
- `start_timestamp`
- `end_timestamp`
- `direction`
- `disconnection_reason`
- `transcript_object`
- `transcript_with_tools`
- `latency_metrics`
- `cost_breakdown`
- `llm_usage`
- `public_log_url`
- `retell_dynamic_variables`
- `opt_out_sensitive_data`
- `details`
- `external_id`
- `phone_number`
- `conversation_id`
- `call_successful`
- `tmp_call_id`
- `agent_version`
- `retell_cost`
- `custom_sip_headers`

**Attribute Casts**:
- `id`: int
- `analysis`: array
- `webhook_data`: array
- `raw`: array
- `raw_data`: array
- `metadata`: array
- `tags`: array
- `cost`: decimal:2
- `duration_minutes`: decimal:2
- `details`: array
- `transcript_object`: array
- `transcript_with_tools`: array
- `latency_metrics`: array
- `cost_breakdown`: array
- `llm_usage`: array
- `retell_dynamic_variables`: array
- `retell_llm_dynamic_variables`: array
- `custom_sip_headers`: array
- `start_timestamp`: datetime
- `end_timestamp`: datetime
- `opt_out_sensitive_data`: boolean
- `call_successful`: boolean
- `agent_version`: integer
- `retell_cost`: decimal:4

**Relationships**:
- `customer()`: BelongsTo
- `appointment()`: BelongsTo
- `company()`: BelongsTo
- `branch()`: BelongsTo
- `staff()`: BelongsTo

**Scopes**:
- `recent()`
- `today()`
- `dateRange()`
- `byStatus()`
- `successful()`
- `failed()`
- `fromNumber()`
- `forCompany()`
- `withCustomer()`
- `withoutCustomer()`
- `withAppointment()`
- `withRelations()`
- `longDuration()`
- `highCost()`
- `currentCompany()`

---

## CallLog

**Table**: `call_logs`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `call_id`
- `caller_number`
- `start_time`
- `end_time`
- `duration`
- `transcript`
- `intent`
- `extracted_data`
- `appointment_id`

**Attribute Casts**:
- `id`: int
- `extracted_data`: array
- `start_time`: datetime
- `end_time`: datetime

**Relationships**:

**Scopes**:

---

## CallbackRequest

**Table**: `callback_requests`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `branch_id`
- `call_id`
- `customer_phone`
- `customer_name`
- `requested_service`
- `requested_date`
- `requested_time`
- `reason`
- `error_details`
- `call_summary`
- `priority`
- `status`
- `assigned_to`
- `completed_by`
- `completion_notes`
- `auto_close_after_hours`
- `processed_at`
- `auto_closed_at`

**Attribute Casts**:
- `id`: int
- `error_details`: array
- `requested_date`: date
- `requested_time`: time
- `processed_at`: datetime
- `auto_closed_at`: datetime

**Relationships**:
- `call()`: BelongsTo
- `branch()`: BelongsTo
- `assignedUser()`: BelongsTo
- `completedByUser()`: BelongsTo

**Scopes**:
- `pending()`
- `overdue()`
- `today()`
- `priority()`
- `forCompany()`
- `currentCompany()`

---

## Company

**Table**: `companies`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `name`
- `slug`
- `industry`
- `website`
- `email`
- `phone`
- `address`
- `city`
- `state`
- `postal_code`
- `country`
- `timezone`
- `currency`
- `logo`
- `settings`
- `metadata`
- `is_active`
- `trial_ends_at`
- `subscription_status`
- `subscription_plan`
- `retell_api_key`
- `retell_agent_id`
- `calcom_api_key`
- `calcom_team_slug`
- `calcom_user_id`
- `google_calendar_credentials`
- `stripe_customer_id`
- `stripe_subscription_id`
- `tax_number`
- `vat_id`
- `is_small_business`
- `small_business_threshold_date`
- `tax_configuration`
- `invoice_prefix`
- `next_invoice_number`
- `payment_terms`
- `auto_invoice`
- `invoice_day_of_month`
- `credit_limit`
- `revenue_ytd`
- `revenue_previous_year`
- `subscription_started_at`
- `subscription_current_period_end`

**Hidden Attributes**:
- `retell_api_key`
- `calcom_api_key`
- `google_calendar_credentials`
- `stripe_customer_id`

**Attribute Casts**:
- `id`: int
- `settings`: array
- `metadata`: array
- `tax_configuration`: array
- `google_calendar_credentials`: encrypted:array
- `is_active`: boolean
- `is_small_business`: boolean
- `auto_invoice`: boolean
- `trial_ends_at`: datetime
- `small_business_threshold_date`: date
- `next_invoice_number`: integer
- `invoice_day_of_month`: integer
- `credit_limit`: decimal:2
- `revenue_ytd`: decimal:2
- `revenue_previous_year`: decimal:2
- `subscription_started_at`: datetime
- `subscription_current_period_end`: datetime
- `deleted_at`: datetime

**Relationships**:
- `branches()`: HasMany
- `staff()`: HasMany
- `customers()`: HasMany
- `appointments()`: HasMany
- `calls()`: HasMany
- `services()`: HasMany
- `users()`: HasMany
- `invoices()`: HasMany
- `taxRates()`: HasMany
- `payments()`: HasMany
- `eventTypes()`: HasMany
- `phoneNumbers()`: HasMany

**Scopes**:

---

## CompanyPricing

**Table**: `company_pricing`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `price_per_minute`
- `setup_fee`
- `monthly_base_fee`
- `included_minutes`
- `overage_price_per_minute`
- `is_active`
- `valid_from`
- `valid_until`
- `notes`

**Attribute Casts**:
- `id`: int
- `price_per_minute`: decimal:4
- `setup_fee`: decimal:2
- `monthly_base_fee`: decimal:2
- `overage_price_per_minute`: decimal:4
- `included_minutes`: integer
- `is_active`: boolean
- `valid_from`: date
- `valid_until`: date

**Relationships**:
- `company()`: BelongsTo
- `branchOverrides()`: HasMany

**Scopes**:
- `active()`

---

## CookieConsent

**Table**: `cookie_consents`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `customer_id`
- `session_id`
- `ip_address`
- `user_agent`
- `necessary_cookies`
- `functional_cookies`
- `analytics_cookies`
- `marketing_cookies`
- `consent_details`
- `consented_at`
- `withdrawn_at`

**Attribute Casts**:
- `id`: int
- `necessary_cookies`: boolean
- `functional_cookies`: boolean
- `analytics_cookies`: boolean
- `marketing_cookies`: boolean
- `consent_details`: array
- `consented_at`: datetime
- `withdrawn_at`: datetime

**Relationships**:
- `customer()`: BelongsTo

**Scopes**:

---

## Customer

**Table**: `customers`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `name`
- `email`
- `phone`
- `birthdate`
- `tags`
- `notes`
- `password`
- `preferred_language`
- `preferred_branch_id`
- `preferred_staff_id`
- `location_data`
- `portal_enabled`
- `portal_access_token`
- `portal_token_expires_at`
- `last_portal_login_at`

**Hidden Attributes**:
- `password`
- `remember_token`
- `portal_access_token`

**Attribute Casts**:
- `id`: int
- `birthdate`: date
- `tags`: array
- `location_data`: array
- `portal_enabled`: boolean
- `portal_token_expires_at`: datetime
- `last_portal_login_at`: datetime
- `email_verified_at`: datetime

**Relationships**:
- `branches()`: HasMany
- `appointments()`: HasMany
- `calls()`: HasMany
- `notes()`: HasMany

**Scopes**:
- `active()`
- `withAppointments()`
- `byPhone()`
- `byEmail()`
- `forCompany()`
- `withAppointmentCount()`
- `recentlyActive()`
- `withNoShows()`
- `withNoShowCount()`
- `search()`

---

## CustomerAuth

**Table**: `customers`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `branch_id`
- `first_name`
- `last_name`
- `email`
- `phone`
- `password`
- `portal_enabled`
- `portal_access_token`
- `portal_token_expires_at`
- `last_portal_login_at`
- `preferred_language`
- `email_verified_at`

**Hidden Attributes**:
- `password`
- `remember_token`
- `portal_access_token`

**Attribute Casts**:
- `id`: int
- `email_verified_at`: datetime
- `portal_token_expires_at`: datetime
- `last_portal_login_at`: datetime
- `portal_enabled`: boolean

**Relationships**:
- `company()`: BelongsTo
- `branch()`: BelongsTo
- `appointments()`: HasMany
- `calls()`: HasMany

**Scopes**:

---

## CustomerService

**Table**: `customer_services`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `branch_id`
- `service_id`
- `invoice_id`
- `quantity`
- `unit_price`
- `total_price`
- `service_date`
- `status`
- `notes`
- `created_by`

**Attribute Casts**:
- `id`: int
- `quantity`: decimal:2
- `unit_price`: decimal:2
- `total_price`: decimal:2
- `service_date`: date

**Relationships**:
- `company()`: BelongsTo
- `branch()`: BelongsTo
- `service()`: BelongsTo
- `invoice()`: BelongsTo
- `creator()`: BelongsTo

**Scopes**:
- `pending()`
- `invoiced()`
- `inDateRange()`

---

## DashboardConfiguration

**Table**: `dashboard_configurations`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `user_id`
- `widget_settings`
- `layout_settings`

**Attribute Casts**:
- `id`: int
- `widget_settings`: array
- `layout_settings`: array

**Relationships**:
- `user()`: BelongsTo

**Scopes**:

---

## Dienstleistung

**Table**: `dienstleistungen`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `kunde_id`
- `name`
- `dauer_minuten`
- `preis`
- `cal_com_event_type_id`
- `beschreibung`
- `aktiv`

**Attribute Casts**:
- `id`: int
- `preis`: decimal:2
- `aktiv`: boolean

**Relationships**:
- `kunde()`: BelongsTo
- `mitarbeiter()`: BelongsToMany

**Scopes**:

---

## DummyCompany

**Table**: `dummy_companies`

**Primary Key**: `id`

**Timestamps**: Yes

**Attribute Casts**:
- `id`: int

**Relationships**:

**Scopes**:

---

## EventTypeImportLog

**Table**: `event_type_import_logs`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `branch_id`
- `user_id`
- `import_type`
- `total_found`
- `total_imported`
- `total_skipped`
- `total_errors`
- `import_details`
- `error_details`
- `status`
- `started_at`
- `completed_at`

**Attribute Casts**:
- `id`: int
- `import_details`: array
- `error_details`: array
- `started_at`: datetime
- `completed_at`: datetime

**Relationships**:
- `company()`: BelongsTo
- `branch()`: BelongsTo
- `user()`: BelongsTo

**Scopes**:
- `successful()`
- `failed()`

---

## FAQ

**Table**: `faqs`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `question`
- `answer`
- `active`

**Attribute Casts**:
- `id`: int
- `active`: boolean

**Relationships**:

**Scopes**:

---

## Faq

**Table**: `faqs`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `question`
- `answer`
- `active`

**Attribute Casts**:
- `id`: int
- `active`: boolean

**Relationships**:

**Scopes**:

---

## GdprRequest

**Table**: `gdpr_requests`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `customer_id`
- `company_id`
- `type`
- `status`
- `reason`
- `admin_notes`
- `exported_data`
- `export_file_path`
- `requested_at`
- `processed_at`
- `completed_at`
- `processed_by`

**Attribute Casts**:
- `id`: int
- `exported_data`: array
- `requested_at`: datetime
- `processed_at`: datetime
- `completed_at`: datetime

**Relationships**:
- `customer()`: BelongsTo
- `company()`: BelongsTo
- `processedBy()`: BelongsTo

**Scopes**:

---

## Integration

**Table**: `integrations`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `customer_id`
- `system`
- `credentials`
- `active`

**Attribute Casts**:
- `id`: int
- `credentials`: array
- `active`: boolean

**Relationships**:
- `customer()`: BelongsTo

**Scopes**:

---

## Invoice

**Table**: `invoices`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `branch_id`
- `stripe_invoice_id`
- `invoice_number`
- `status`
- `creation_mode`
- `subtotal`
- `tax_amount`
- `total`
- `currency`
- `invoice_date`
- `due_date`
- `paid_date`
- `payment_method`
- `payment_terms`
- `stripe_payment_intent_id`
- `pdf_url`
- `metadata`
- `notes`
- `billing_reason`
- `auto_advance`
- `tax_configuration`
- `is_reverse_charge`
- `customer_vat_id`
- `invoice_type`
- `original_invoice_id`
- `finalized_at`
- `tax_note`
- `is_tax_exempt`
- `period_start`
- `period_end`

**Attribute Casts**:
- `id`: int
- `metadata`: array
- `tax_configuration`: array
- `invoice_date`: date
- `due_date`: date
- `paid_date`: date
- `period_start`: date
- `period_end`: date
- `finalized_at`: datetime
- `auto_advance`: boolean
- `is_reverse_charge`: boolean
- `is_tax_exempt`: boolean
- `subtotal`: decimal:2
- `tax_amount`: decimal:2
- `total`: decimal:2

**Relationships**:
- `company()`: BelongsTo
- `branch()`: BelongsTo
- `items()`: HasMany
- `flexibleItems()`: HasMany
- `payments()`: HasMany
- `billingPeriod()`: HasOne
- `usageItems()`: HasMany
- `serviceItems()`: HasMany

**Scopes**:
- `open()`
- `paid()`
- `overdue()`

---

## InvoiceItem

**Table**: `invoice_items`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `invoice_id`
- `stripe_invoice_item_id`
- `type`
- `description`
- `quantity`
- `unit`
- `unit_price`
- `amount`
- `tax_rate`
- `tax_rate_id`
- `tax_code`
- `tax_amount`
- `metadata`
- `pricing_model_id`
- `period_start`
- `period_end`

**Attribute Casts**:
- `id`: int
- `metadata`: array
- `quantity`: decimal:2
- `unit_price`: decimal:4
- `amount`: decimal:2
- `tax_rate`: decimal:2
- `tax_amount`: decimal:2
- `period_start`: date
- `period_end`: date

**Relationships**:
- `invoice()`: BelongsTo
- `pricingModel()`: BelongsTo

**Scopes**:
- `usage()`
- `service()`

---

## InvoiceItemFlexible

**Table**: `invoice_items_flexible`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `invoice_id`
- `stripe_invoice_item_id`
- `type`
- `description`
- `quantity`
- `unit`
- `unit_price`
- `amount`
- `tax_rate`
- `tax_rate_id`
- `period_start`
- `period_end`
- `metadata`
- `sort_order`

**Attribute Casts**:
- `id`: int
- `quantity`: decimal:2
- `unit_price`: decimal:2
- `amount`: decimal:2
- `tax_rate`: decimal:2
- `period_start`: date
- `period_end`: date
- `metadata`: array

**Relationships**:
- `invoice()`: BelongsTo
- `taxRate()`: BelongsTo

**Scopes**:
- `ordered()`

---

## KnowledgeAnalytic

**Table**: `knowledge_analytics`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `document_id`
- `user_id`
- `event_type`
- `event_data`
- `session_id`
- `referrer`
- `ip_address`
- `user_agent`

**Attribute Casts**:
- `id`: int
- `event_data`: array

**Relationships**:
- `document()`: BelongsTo
- `user()`: BelongsTo

**Scopes**:
- `ofType()`
- `inDateRange()`
- `byUser()`
- `inSession()`

---

## KnowledgeCategory

**Table**: `knowledge_categories`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `name`
- `slug`
- `icon`
- `color`
- `description`
- `parent_id`
- `order`

**Attribute Casts**:
- `id`: int
- `order`: integer

**Relationships**:
- `parent()`: BelongsTo
- `children()`: HasMany
- `documents()`: HasMany

**Scopes**:
- `ordered()`
- `root()`

---

## KnowledgeCodeSnippet

**Table**: `knowledge_code_snippets`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `document_id`
- `language`
- `title`
- `code`
- `description`
- `is_executable`
- `execution_config`
- `usage_count`

**Attribute Casts**:
- `id`: int
- `is_executable`: boolean
- `execution_config`: array

**Relationships**:
- `document()`: BelongsTo

**Scopes**:
- `executable()`
- `ofLanguage()`
- `popular()`

---

## KnowledgeComment

**Table**: `knowledge_comments`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `document_id`
- `parent_id`
- `user_id`
- `content`
- `status`
- `position`

**Attribute Casts**:
- `id`: int
- `position`: array

**Relationships**:
- `document()`: BelongsTo
- `parent()`: BelongsTo
- `replies()`: HasMany
- `user()`: BelongsTo

**Scopes**:
- `active()`
- `resolved()`
- `root()`

---

## KnowledgeDocument

**Table**: `knowledge_documents`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `title`
- `slug`
- `excerpt`
- `content`
- `raw_content`
- `file_path`
- `file_type`
- `category_id`
- `metadata`
- `status`
- `order`
- `view_count`
- `helpful_count`
- `not_helpful_count`
- `last_indexed_at`
- `file_modified_at`

**Attribute Casts**:
- `id`: int
- `metadata`: array
- `last_indexed_at`: datetime
- `file_modified_at`: datetime
- `view_count`: integer
- `helpful_count`: integer
- `not_helpful_count`: integer
- `order`: integer
- `company_id`: integer

**Relationships**:
- `company()`: BelongsTo
- `category()`: BelongsTo
- `tags()`: BelongsToMany
- `versions()`: HasMany
- `searchIndexes()`: HasMany
- `codeSnippets()`: HasMany
- `sourceRelationships()`: HasMany
- `targetRelationships()`: HasMany
- `feedback()`: HasMany
- `comments()`: HasMany
- `analytics()`: HasMany
- `creator()`: BelongsTo
- `updater()`: BelongsTo

**Scopes**:
- `published()`
- `ofType()`

---

## KnowledgeFeedback

**Table**: `knowledge_feedback`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `document_id`
- `user_id`
- `session_id`
- `is_helpful`
- `comment`
- `ip_address`
- `user_agent`

**Attribute Casts**:
- `id`: int
- `is_helpful`: boolean

**Relationships**:
- `document()`: BelongsTo
- `user()`: BelongsTo

**Scopes**:
- `helpful()`
- `notHelpful()`
- `withComments()`

---

## KnowledgeNotebook

**Table**: `knowledge_notebooks`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `user_id`
- `title`
- `slug`
- `description`
- `is_public`
- `metadata`

**Attribute Casts**:
- `id`: int
- `is_public`: boolean
- `metadata`: array

**Relationships**:
- `user()`: BelongsTo
- `entries()`: HasMany

**Scopes**:
- `public()`
- `ownedBy()`

---

## KnowledgeNotebookEntry

**Table**: `knowledge_notebook_entries`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `notebook_id`
- `title`
- `content`
- `tags`
- `order`

**Attribute Casts**:
- `id`: int
- `tags`: array

**Relationships**:
- `notebook()`: BelongsTo

**Scopes**:
- `withTag()`
- `withAnyTag()`

---

## KnowledgeRelatedDocument

**Table**: `knowledge_related_documents`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `document_id`
- `related_document_id`
- `relevance_score`
- `relation_type`

**Attribute Casts**:
- `id`: int
- `relevance_score`: float

**Relationships**:
- `document()`: BelongsTo
- `relatedDocument()`: BelongsTo

**Scopes**:
- `ofType()`
- `highRelevance()`

---

## KnowledgeRelationship

**Table**: `knowledge_relationships`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `source_document_id`
- `target_document_id`
- `relationship_type`
- `strength`
- `is_auto_detected`

**Attribute Casts**:
- `id`: int
- `strength`: float
- `is_auto_detected`: boolean

**Relationships**:
- `sourceDocument()`: BelongsTo
- `targetDocument()`: BelongsTo

**Scopes**:
- `strong()`
- `manual()`
- `autoDetected()`

---

## KnowledgeSearchIndex

**Table**: `knowledge_search_index`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `document_id`
- `section_title`
- `content_chunk`
- `embedding`
- `keywords`
- `relevance_score`

**Attribute Casts**:
- `id`: int
- `embedding`: array
- `keywords`: array
- `relevance_score`: float

**Relationships**:
- `document()`: BelongsTo

**Scopes**:
- `withKeywords()`
- `search()`

---

## KnowledgeTag

**Table**: `knowledge_tags`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `name`
- `slug`
- `color`

**Attribute Casts**:
- `id`: int

**Relationships**:
- `documents()`: BelongsToMany

**Scopes**:
- `popular()`

---

## KnowledgeVersion

**Table**: `knowledge_versions`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `document_id`
- `version_number`
- `title`
- `content`
- `metadata`
- `diff`
- `commit_message`
- `change_summary`
- `created_by`

**Attribute Casts**:
- `id`: int
- `metadata`: array
- `version_number`: integer
- `created_by`: integer

**Relationships**:
- `document()`: BelongsTo
- `creator()`: BelongsTo

**Scopes**:

---

## Kunde

**Table**: `kunden`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `name`
- `email`
- `telefonnummer`
- `notizen`

**Attribute Casts**:
- `id`: int

**Relationships**:
- `calls()`: HasMany
- `appointments()`: HasMany

**Scopes**:

---

## LegacyUser

**Table**: `users`

**Primary Key**: `user_id`

**Timestamps**: No

**Fillable Attributes**:
- `fname`
- `lname`
- `name`
- `email`
- `password`
- `username`
- `organization`
- `position`
- `phone`

**Attribute Casts**:
- `user_id`: int

**Relationships**:

**Scopes**:

---

## MCPMetric

**Table**: `mcp_metrics`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `service`
- `operation`
- `status`
- `response_time`
- `error_message`
- `metadata`

**Attribute Casts**:
- `id`: int
- `metadata`: array
- `response_time`: float
- `created_at`: datetime
- `updated_at`: datetime

**Relationships**:

**Scopes**:
- `forService()`
- `inTimeRange()`
- `successful()`
- `failed()`

---

## MasterService

**Table**: `master_services`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `name`
- `description`
- `base_duration`
- `base_price`
- `calcom_event_type_id`
- `retell_service_identifier`
- `active`

**Attribute Casts**:
- `base_duration`: integer
- `base_price`: decimal:2
- `active`: boolean

**Relationships**:
- `company()`: BelongsTo
- `branchOverrides()`: HasMany
- `staffAssignments()`: HasMany

**Scopes**:

---

## Mitarbeiter

**Table**: `mitarbeiters`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `kunden_id`
- `vorname`
- `nachname`
- `email`
- `telefonnummer`
- `kalender_verfuegbarkeit`

**Attribute Casts**:
- `id`: int
- `kalender_verfuegbarkeit`: array

**Relationships**:
- `kunde()`: BelongsTo

**Scopes**:

---

## Note

**Table**: `notes`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `customer_id`
- `user_id`
- `title`
- `content`
- `type`
- `is_pinned`
- `sort_order`

**Attribute Casts**:
- `id`: int
- `is_pinned`: boolean

**Relationships**:
- `customer()`: BelongsTo
- `user()`: BelongsTo

**Scopes**:

---

## Payment

**Table**: `payments`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `invoice_id`
- `stripe_payment_id`
- `payment_method`
- `amount`
- `currency`
- `status`
- `payment_date`
- `reference_number`
- `metadata`

**Attribute Casts**:
- `id`: int
- `metadata`: array
- `amount`: decimal:2
- `payment_date`: date

**Relationships**:
- `invoice()`: BelongsTo

**Scopes**:
- `successful()`
- `pending()`
- `failed()`

---

## PhoneNumber

**Table**: `phone_numbers`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `id`
- `company_id`
- `branch_id`
- `number`
- `type`
- `routing_config`
- `agent_id`
- `is_active`
- `description`
- `is_primary`
- `sms_enabled`
- `whatsapp_enabled`
- `retell_phone_id`
- `retell_agent_id`
- `retell_agent_version`
- `capabilities`
- `metadata`

**Attribute Casts**:
- `routing_config`: array
- `is_active`: boolean
- `is_primary`: boolean
- `sms_enabled`: boolean
- `whatsapp_enabled`: boolean
- `capabilities`: array
- `metadata`: array

**Relationships**:

**Scopes**:
- `active()`
- `hotlines()`
- `direct()`

---

## PremiumService

**Table**: `premium_services`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `name`
- `description`
- `price`
- `duration`
- `active`

**Attribute Casts**:
- `id`: int
- `price`: decimal:2
- `active`: boolean

**Relationships**:

**Scopes**:

---

## RetellAgent

**Table**: `retell_agents`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `phone_number_id`
- `agent_id`
- `name`
- `settings`
- `active`

**Attribute Casts**:
- `id`: int
- `settings`: array
- `active`: boolean

**Relationships**:
- `company()`: BelongsTo
- `phoneNumber()`: BelongsTo

**Scopes**:

---

## RetellWebhook

**Table**: `webhook_events`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `event_type`
- `call_id`
- `payload`
- `provider`

**Attribute Casts**:
- `id`: int
- `payload`: array

**Relationships**:

**Scopes**:
- `retell()`

---

## SecurityLog

**Table**: `security_logs`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `type`
- `ip_address`
- `user_agent`
- `user_id`
- `company_id`
- `url`
- `method`
- `data`
- `created_at`

**Attribute Casts**:
- `id`: int
- `data`: array
- `created_at`: datetime

**Relationships**:

**Scopes**:
- `ofType()`
- `fromIp()`
- `inLastHours()`

---

## Service

**Table**: `services`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `branch_id`
- `tenant_id`
- `name`
- `description`
- `price`
- `default_duration_minutes`
- `active`
- `category`
- `sort_order`
- `min_staff_required`
- `max_bookings_per_day`
- `buffer_time_minutes`
- `is_online_bookable`
- `calcom_event_type_id`
- `duration`

**Attribute Casts**:
- `id`: int
- `price`: decimal:2
- `active`: boolean
- `is_online_bookable`: boolean
- `default_duration_minutes`: integer
- `min_staff_required`: integer
- `max_bookings_per_day`: integer
- `buffer_time_minutes`: integer
- `sort_order`: integer
- `duration`: integer
- `deleted_at`: datetime

**Relationships**:

**Scopes**:
- `forCompany()`
- `currentCompany()`

---

## Staff

**Table**: `staff`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `branch_id`
- `home_branch_id`
- `name`
- `email`
- `phone`
- `external_id`
- `active`
- `is_bookable`
- `calendar_mode`
- `calcom_user_id`
- `calcom_event_type_id`
- `calcom_calendar_link`
- `availability_mode`
- `workable_branches`
- `notes`
- `external_calendar_id`
- `calendar_provider`

**Attribute Casts**:
- `active`: boolean
- `is_bookable`: boolean
- `workable_branches`: array
- `deleted_at`: datetime

**Relationships**:

**Scopes**:
- `active()`
- `bookable()`
- `forCompany()`
- `available()`
- `forBranch()`
- `withServices()`
- `withAppointmentsInRange()`
- `withCalendarConfig()`
- `withoutCalendarConfig()`
- `withAppointmentCount()`
- `withRelations()`
- `currentCompany()`

---

## StaffEventType

**Table**: `staff_event_types`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `staff_id`
- `event_type_id`
- `calcom_user_id`
- `is_primary`
- `custom_duration`
- `custom_price`
- `availability_override`

**Attribute Casts**:
- `is_primary`: boolean
- `custom_duration`: integer
- `custom_price`: decimal:2
- `availability_override`: array

**Relationships**:

**Scopes**:

---

## StaffService

**Table**: `staff_service`

**Primary Key**: `id`

**Timestamps**: Yes

**Attribute Casts**:
- `id`: int

**Relationships**:

**Scopes**:

---

## StaffServiceAssignment

**Table**: `staff_service_assignments`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `staff_id`
- `master_service_id`
- `branch_id`
- `calcom_user_id`
- `availability_rules`
- `active`

**Attribute Casts**:
- `availability_rules`: array
- `active`: boolean

**Relationships**:
- `staff()`: BelongsTo
- `masterService()`: BelongsTo
- `branch()`: BelongsTo

**Scopes**:

---

## TaxRate

**Table**: `tax_rates`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `company_id`
- `name`
- `rate`
- `is_default`
- `is_system`
- `description`
- `valid_from`
- `valid_until`
- `stripe_tax_rate_id`

**Attribute Casts**:
- `id`: int
- `rate`: decimal:2
- `is_default`: boolean
- `is_system`: boolean
- `valid_from`: date
- `valid_until`: date

**Relationships**:
- `company()`: BelongsTo

**Scopes**:
- `active()`
- `system()`
- `forCompany()`

---

## Telefonnummer

**Table**: `telefonnummern`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `kunde_id`
- `nummer`
- `beschreibung`
- `aktiv`

**Attribute Casts**:
- `id`: int
- `aktiv`: boolean

**Relationships**:
- `kunde()`: BelongsTo

**Scopes**:

---

## Tenant

**Table**: `tenants`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `name`
- `slug`
- `api_key`

**Relationships**:
- `users()`: HasMany

**Scopes**:

---

## Termin

**Table**: `termine`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `kunde_id`
- `mitarbeiter_id`
- `call_id`
- `anrufer_name`
- `anrufer_telefon`
- `anrufer_email`
- `datum`
- `uhrzeit`
- `dauer_minuten`
- `dienstleistung`
- `notizen`
- `cal_com_event_id`
- `status`
- `erinnerung_gesendet`

**Attribute Casts**:
- `id`: int
- `datum`: date
- `uhrzeit`: datetime
- `erinnerung_gesendet`: boolean

**Relationships**:
- `kunde()`: BelongsTo
- `mitarbeiter()`: BelongsTo
- `call()`: BelongsTo

**Scopes**:

---

## UnifiedEventType

**Table**: `unified_event_types`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `branch_id`
- `company_id`
- `service_id`
- `provider`
- `external_id`
- `name`
- `slug`
- `description`
- `duration_minutes`
- `price`
- `provider_data`
- `conflict_data`
- `is_active`
- `assignment_status`
- `import_status`
- `imported_at`
- `assigned_at`
- `calcom_event_type_id`
- `duration`
- `raw_data`
- `last_imported_at`

**Attribute Casts**:
- `id`: int
- `is_active`: boolean
- `provider_data`: array
- `conflict_data`: array
- `imported_at`: datetime
- `assigned_at`: datetime
- `last_imported_at`: datetime
- `price`: decimal:2
- `raw_data`: array
- `duration`: integer
- `duration_minutes`: integer
- `calcom_event_type_id`: integer

**Relationships**:

**Scopes**:
- `assigned()`
- `unassigned()`
- `duplicates()`
- `pendingReview()`
- `active()`

---

## User

**Table**: `users`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `fname`
- `lname`
- `name`
- `email`
- `password`
- `username`
- `tenant_id`
- `company_id`
- `date_created`
- `date_updated`
- `email_verified_at`

**Hidden Attributes**:
- `password`
- `remember_token`
- `salt`
- `legacypassword`

**Attribute Casts**:
- `id`: int
- `email_verified_at`: datetime

**Relationships**:
- `tenant()`: BelongsTo
- `roles()`: BelongsToMany
- `permissions()`: BelongsToMany

**Scopes**:
- `role()`
- `withoutRole()`
- `permission()`
- `withoutPermission()`

---

## ValidationResult

**Table**: `validation_results`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `entity_type`
- `entity_id`
- `test_type`
- `status`
- `results`
- `tested_at`
- `expires_at`

**Attribute Casts**:
- `id`: int
- `results`: array
- `tested_at`: datetime
- `expires_at`: datetime

**Relationships**:

**Scopes**:

---

## WebhookEvent

**Table**: `webhook_events`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `provider`
- `event_type`
- `event_id`
- `idempotency_key`
- `payload`
- `status`
- `processed_at`
- `error_message`
- `retry_count`
- `correlation_id`

**Attribute Casts**:
- `id`: int
- `payload`: array
- `processed_at`: datetime

**Relationships**:

**Scopes**:

---

## WorkingHour

**Table**: `working_hours`

**Primary Key**: `id`

**Timestamps**: Yes

**Attribute Casts**:
- `id`: int

**Relationships**:
- `staff()`: BelongsTo

**Scopes**:

---

## WorkingHours

**Table**: `working_hours`

**Primary Key**: `id`

**Timestamps**: Yes

**Fillable Attributes**:
- `staff_id`
- `weekday`
- `start`
- `end`

**Attribute Casts**:
- `id`: int

**Relationships**:

**Scopes**:

---

