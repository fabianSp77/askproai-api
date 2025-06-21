<x-mail::message>
# Monitoring Alert

<x-mail::panel>
**Alert Type:** {{ $alert['rule'] }}  
**Severity:** <span style="color: {{ $severityColor }}">{{ strtoupper($alert['severity']) }}</span>  
**Time:** {{ $alert['timestamp']->format('Y-m-d H:i:s') }}
</x-mail::panel>

## Alert Details

{{ $alert['message'] }}

@if (!empty($alert['data']))
### Additional Information

<x-mail::table>
| Key | Value |
| :-- | :---- |
@foreach ($alert['data'] as $key => $value)
| {{ ucfirst(str_replace('_', ' ', $key)) }} | {{ is_array($value) ? json_encode($value) : $value }} |
@endforeach
</x-mail::table>
@endif

## Required Action

Please investigate this alert immediately. High severity alerts may indicate:
- Service disruptions affecting customers
- Security breaches or attempts
- Critical system failures
- Payment processing issues

<x-mail::button :url="$actionUrl">
View Monitoring Dashboard
</x-mail::button>

## Alert Guidelines

@switch($alert['severity'])
@case('critical')
**CRITICAL**: Immediate action required. System functionality is severely impacted.
@break
@case('high')
**HIGH**: Urgent attention needed. Service degradation or security risk detected.
@break
@case('medium')
**MEDIUM**: Investigation recommended within 4 hours.
@break
@case('low')
**LOW**: Monitor the situation. No immediate action required.
@break
@endswitch

Thanks,<br>
{{ config('app.name') }} Monitoring System
</x-mail::message>