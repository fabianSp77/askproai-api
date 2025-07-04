# Stripe Subscription Implementation

## Overview
A complete Stripe subscription and billing system has been implemented for AskProAI, providing subscription management, billing, and webhook handling capabilities.

## Components Implemented

### 1. Database Models
- **Subscription Model** (`app/Models/Subscription.php`)
  - Tracks subscription status, periods, trial info
  - Relationships with Company model
  - Status helper methods (active(), onTrial(), canceled(), etc.)
  - Scopes for querying (active, needsAttention)

- **SubscriptionItem Model** (`app/Models/SubscriptionItem.php`)
  - Tracks individual subscription line items
  - Syncs with Stripe subscription items

### 2. Service Layer
- **StripeSubscriptionService** (`app/Services/Billing/StripeSubscriptionService.php`)
  - Create, update, cancel, resume subscriptions
  - Stripe customer management
  - Usage tracking and reporting
  - Webhook data synchronization

### 3. Webhook Handling
- **StripeWebhookHandler** (`app/Services/Webhooks/StripeWebhookHandler.php`)
  - Enhanced with subscription lifecycle event handlers:
    - `customer.subscription.created`
    - `customer.subscription.updated`
    - `customer.subscription.deleted`
    - `customer.subscription.trial_will_end`
    - `invoice.upcoming`
    - `payment_method.attached`
  - Automatic company status updates based on subscription status
  - Invoice and payment tracking

### 4. API Endpoints
- **StripeBillingController** (`app/Http/Controllers/StripeBillingController.php`)
  - `GET /api/billing/plans` - Get available pricing plans
  - `POST /api/billing/subscriptions` - Create new subscription
  - `PUT /api/billing/subscriptions/{id}` - Update subscription
  - `DELETE /api/billing/subscriptions/{id}` - Cancel subscription
  - `POST /api/billing/subscriptions/{id}/resume` - Resume canceled subscription
  - `GET /api/billing/subscriptions/{id}/usage` - Get usage metrics
  - `POST /api/billing/portal-session` - Create Stripe billing portal session
  - `POST /api/billing/checkout-session` - Create Stripe checkout session

### 5. Admin UI Components
- **SubscriptionResource** (`app/Filament/Admin/Resources/SubscriptionResource.php`)
  - Full CRUD interface for subscriptions
  - Status badges with color coding
  - Actions: Cancel, Resume, View in Stripe
  - Filters: Status, Needs Attention, Expiring Soon
  - Related subscription items management

- **SubscriptionStatusWidget** (`app/Filament/Admin/Widgets/SubscriptionStatusWidget.php`)
  - Dashboard widget showing:
    - Active subscriptions count
    - Monthly Recurring Revenue (MRR)
    - Subscriptions needing attention
    - Churn rate

### 6. Database Migration
- **Subscriptions Table** (`database/migrations/2025_06_27_170000_create_subscriptions_table.php`)
  - SQLite-compatible migration using CompatibleMigration base class
  - Tracks all subscription metadata from Stripe

## Key Features

### Subscription Lifecycle Management
- Automatic status synchronization with Stripe
- Grace period handling for canceled subscriptions
- Trial period tracking and notifications
- Past due payment handling

### Company Integration
- Companies can have multiple subscriptions
- Active subscription checking
- Automatic access control based on subscription status

### Webhook Processing
- Signature verification for security
- Idempotent processing to prevent duplicates
- Automatic data synchronization
- Company status updates based on subscription events

### Admin Features
- Visual subscription management
- Quick actions (cancel, resume)
- Direct links to Stripe dashboard
- Real-time status updates
- MRR and churn tracking

## Configuration Required

### Environment Variables
```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### Stripe Dashboard Setup
1. Create products and prices in Stripe
2. Configure webhook endpoint: `https://api.askproai.de/api/stripe/webhook`
3. Enable webhook events:
   - `customer.subscription.*`
   - `invoice.*`
   - `payment_intent.*`
   - `payment_method.*`
   - `checkout.session.completed`

## Usage Examples

### Creating a Subscription
```php
$company = Company::find(1);
$subscription = app(StripeSubscriptionService::class)->createSubscription(
    $company,
    'price_xxx', // Stripe price ID
    ['trial_period_days' => 14]
);
```

### Checking Subscription Status
```php
if ($company->hasActiveSubscription()) {
    $subscription = $company->activeSubscription();
    echo "Status: " . $subscription->status_label;
    echo "Renews in: " . $subscription->daysUntilRenewal() . " days";
}
```

### Handling Webhook Events
Webhooks are automatically processed and will:
- Update subscription status
- Create/update local subscription records
- Update company access status
- Track invoices and payments

## Security Considerations
- All API keys are encrypted in the database
- Webhook signatures are verified
- Proper authentication required for all billing endpoints
- Stripe customer IDs are hidden from API responses

## Next Steps
1. Configure Stripe products and prices
2. Set up webhook endpoint in Stripe dashboard
3. Test subscription flow in staging environment
4. Configure email notifications for subscription events
5. Set up usage-based billing if needed