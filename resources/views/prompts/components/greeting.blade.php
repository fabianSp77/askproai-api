{{-- Standard Greeting Component --}}
@props(['formal' => true, 'includeCompany' => true])

@if($formal)
{{ $includeCompany ? $company_name . ', ' : '' }}guten Tag. Mein Name ist {{ $agent_name ?? 'Sarah' }}. Wie kann ich Ihnen helfen?
@else
Hallo! Hier ist {{ $includeCompany ? $company_name . '. ' : '' }}Ich bin {{ $agent_name ?? 'Sarah' }}, wie kann ich dir helfen?
@endif