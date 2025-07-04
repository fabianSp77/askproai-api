@component('mail::message')
# {{ $alert->title }}

{{ $alert->message }}

@if($alert->alert_type === 'usage_limit' && isset($alert->data['used_minutes']))
## Current Usage Details
- **Used Minutes**: {{ number_format($alert->data['used_minutes'], 1) }}
- **Included Minutes**: {{ number_format($alert->data['included_minutes'], 0) }}
- **Remaining Minutes**: {{ number_format($alert->data['remaining_minutes'], 1) }}
@endif

@if($alert->alert_type === 'payment_reminder' && isset($alert->data['invoice_number']))
## Invoice Details
- **Invoice Number**: {{ $alert->data['invoice_number'] }}
- **Amount Due**: €{{ number_format($alert->data['amount'], 2) }}
- **Due Date**: {{ \Carbon\Carbon::parse($alert->data['due_date'])->format('F j, Y') }}

@component('mail::button', ['url' => route('admin.invoices.show', $alert->data['invoice_id'])])
View Invoice
@endcomponent
@endif

@if($alert->alert_type === 'subscription_renewal' && isset($alert->data['renewal_date']))
## Subscription Details
- **Renewal Date**: {{ \Carbon\Carbon::parse($alert->data['renewal_date'])->format('F j, Y') }}
- **Amount**: €{{ number_format($alert->data['amount'], 2) }}

@component('mail::button', ['url' => route('admin.billing')])
Manage Subscription
@endcomponent
@endif

@if($alert->alert_type === 'budget_exceeded' && isset($alert->data['budget']))
## Budget Status
- **Monthly Budget**: €{{ number_format($alert->data['budget'], 2) }}
- **Current Charges**: €{{ number_format($alert->data['current_cost'], 2) }}
- **Remaining Budget**: €{{ number_format($alert->data['remaining_budget'], 2) }}
- **Usage**: {{ number_format($alert->data['usage_percentage'], 1) }}%
@endif

@if($alert->alert_type === 'payment_failed')
## Action Required
Your recent payment has failed. Please update your payment method to avoid service interruption.

@component('mail::button', ['url' => route('admin.payment-methods')])
Update Payment Method
@endcomponent
@endif

---

@component('mail::subcopy')
You are receiving this notification because you have billing alerts enabled for your account. 
To manage your notification preferences, please visit your [account settings]({{ route('admin.notifications') }}).

Alert ID: {{ $alert->id }}
@endcomponent

Best regards,<br>
{{ config('app.name') }} Team
@endcomponent