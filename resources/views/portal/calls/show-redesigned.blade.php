@extends('portal.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    {{-- Header mit wichtigsten Informationen --}}
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                {{-- Back Button --}}
                <div class="mb-4">
                    <a href="{{ route('business.calls.index') }}" 
                       class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                        <svg class="mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Zurück zur Übersicht
                    </a>
                </div>

                {{-- Header Content --}}
                <div class="lg:flex lg:items-center lg:justify-between">
                    <div class="flex-1 min-w-0">
                        {{-- Customer Name & Phone --}}
                        <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">
                            @if($call->extracted_name || $call->customer)
                                {{ $call->extracted_name ?? $call->customer->name }}
                            @else
                                {{ $call->phone_number }}
                            @endif
                        </h1>
                        
                        {{-- Meta Information --}}
                        <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                </svg>
                                {{ $call->phone_number }}
                            </div>
                            
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                @php
                                    $timeClass = \App\Helpers\TimeHelper::getAgeClass($call->created_at);
                                @endphp
                                <span class="{{ $timeClass }}">{!! \App\Helpers\TimeHelper::formatTimeSince($call->created_at) !!}</span>
                                <span class="mx-1">•</span>
                                {{ $call->created_at ? $call->created_at->format('d.m.Y H:i') : '-' }}
                            </div>
                            
                            @if($call->duration_sec)
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                {{ gmdate('i:s', $call->duration_sec) }} Minuten
                            </div>
                            @endif
                            
                            @if($call->branch)
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                                {{ $call->branch->name }}
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Actions & Status --}}
                    <div class="mt-5 flex lg:mt-0 lg:ml-4">
                        @php
                            $status = optional($call->callPortalData)->status ?? 'new';
                        @endphp
                        <span class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium
                            @if($status === 'completed') bg-green-100 text-green-800
                            @elseif($status === 'new') bg-blue-100 text-blue-800
                            @elseif($status === 'in_progress') bg-yellow-100 text-yellow-800
                            @elseif($status === 'callback_scheduled') bg-purple-100 text-purple-800
                            @elseif($status === 'requires_action') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            @switch($status)
                                @case('new')
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Neu
                                    @break
                                @case('in_progress')
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    </svg>
                                    In Bearbeitung
                                    @break
                                @case('callback_scheduled')
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                    </svg>
                                    Rückruf geplant
                                    @break
                                @case('completed')
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Abgeschlossen
                                    @break
                                @case('requires_action')
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    Aktion erforderlich
                                    @break
                                @default
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                            @endswitch
                        </span>
                        
                        <span class="ml-3">
                            <x-call-email-actions :call="$call" />
                        </span>
                    </div>
                </div>
                
                {{-- Quick Info Bar --}}
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    {{-- Urgency --}}
                    @if($call->urgency_level || $call->metadata['customer_data']['urgency'] ?? null)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Dringlichkeit</dt>
                        <dd class="mt-1">
                            @php
                                $urgency = $call->urgency_level ?? $call->metadata['customer_data']['urgency'] ?? null;
                            @endphp
                            @php
                                $urgencyLevel = strtolower($urgency);
                                $urgencyColor = match($urgencyLevel) {
                                    'high', 'hoch' => 'red',
                                    'medium', 'mittel' => 'yellow',
                                    'low', 'niedrig' => 'gray',
                                    default => 'gray'
                                };
                                $urgencyText = match($urgencyLevel) {
                                    'high' => 'Hoch',
                                    'medium' => 'Mittel',
                                    'low' => 'Niedrig',
                                    'hoch' => 'Hoch',
                                    'mittel' => 'Mittel',
                                    'niedrig' => 'Niedrig',
                                    default => ucfirst($urgency)
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $urgencyColor }}-100 text-{{ $urgencyColor }}-800">
                                {{ $urgencyText }}
                            </span>
                        </dd>
                    </div>
                    @endif
                    
                    {{-- Company --}}
                    @if($call->metadata['customer_data']['company'] ?? $call->customer?->company_name)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Firma</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $call->metadata['customer_data']['company'] ?? $call->customer->company_name }}
                        </dd>
                    </div>
                    @endif
                    
                    {{-- Costs (only for management) --}}
                    @if(Auth::guard('portal')->user()->hasPermission('billing.view') || session('is_admin_viewing'))
                    <div class="bg-gray-50 rounded-lg p-3">
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Kosten</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            @if($call->charge)
                                {{ $call->charge->formatted_amount }}
                            @elseif($call->duration_sec)
                                @php
                                    $pricing = \App\Models\CompanyPricing::getCurrentForCompany($call->company_id);
                                    $cost = $pricing ? $pricing->calculatePrice($call->duration_sec) : 0;
                                @endphp
                                {{ number_format($cost, 2, ',', '.') }} €
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    @endif
                    
                    {{-- Assigned To --}}
                    <div class="bg-gray-50 rounded-lg p-3">
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bearbeiter</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $call->callPortalData?->assignedTo?->name ?? 'Nicht zugewiesen' }}
                        </dd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            {{-- Left Column - Main Information --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Customer Request/Reason Card --}}
                @if($call->reason_for_visit || (isset($call->metadata['customer_data']['request']) && $call->metadata['customer_data']['request']))
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Kundenanliegen</h2>
                    </div>
                    <div class="p-6">
                        <div class="prose max-w-none text-gray-700">
                            {{ $call->reason_for_visit ?? $call->metadata['customer_data']['request'] }}
                        </div>
                        
                        @if($call->appointment_requested)
                        <div class="mt-4 flex items-center text-sm text-yellow-600 bg-yellow-50 rounded-lg p-3">
                            <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            Kunde hat einen Terminwunsch geäußert
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Collected Customer Data --}}
                @if(isset($call->metadata['customer_data']) && !empty($call->metadata['customer_data']))
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-teal-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900">Erfasste Kundendaten</h2>
                            @if(isset($call->metadata['collection_timestamp']))
                            <span class="text-xs text-gray-500">
                                Erfasst: {{ \Carbon\Carbon::parse($call->metadata['collection_timestamp'])->format('d.m.Y H:i') }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="p-6">
                        @php
                            $customerData = $call->metadata['customer_data'];
                        @endphp
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            @if(!empty($customerData['full_name']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ $customerData['full_name'] }}</dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['company']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Firma</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $customerData['company'] }}</dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['customer_number']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Kundennummer</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $customerData['customer_number'] }}</dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['phone_primary']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Haupttelefon</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $customerData['phone_primary'] }}</dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['phone_secondary']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Zweittelefon</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $customerData['phone_secondary'] }}</dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['email']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">E-Mail</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="mailto:{{ $customerData['email'] }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $customerData['email'] }}
                                    </a>
                                </dd>
                            </div>
                            @endif
                            
                            @if(!empty($customerData['notes']))
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Zusätzliche Notizen</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $customerData['notes'] }}</dd>
                            </div>
                            @endif
                            
                            @if(isset($customerData['consent']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Datenspeicherung zugestimmt</dt>
                                <dd class="mt-1">
                                    @if($customerData['consent'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Ja
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                            Nein
                                        </span>
                                    @endif
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
                @endif

                {{-- Cost Details (only for management) --}}
                @if((Auth::guard('portal')->user()->hasPermission('billing.view') || session('is_admin_viewing')) && $call->duration_sec)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-50 to-green-50 px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Kostenberechnung</h2>
                    </div>
                    <div class="p-6">
                        @php
                            $pricing = \App\Models\CompanyPricing::getCurrentForCompany($call->company_id);
                            $billingRate = \App\Models\BillingRate::where('company_id', $call->company_id)->active()->first();
                            $cost = 0;
                            $ratePerMinute = 0;
                            
                            if ($call->charge) {
                                $cost = $call->charge->amount_charged;
                                $ratePerMinute = $call->charge->rate_per_minute;
                            } elseif ($pricing) {
                                $cost = $pricing->calculatePrice($call->duration_sec);
                                $ratePerMinute = $pricing->price_per_minute;
                            } elseif ($billingRate) {
                                $cost = $billingRate->calculateCharge($call->duration_sec);
                                $ratePerMinute = $billingRate->rate_per_minute;
                            }
                        @endphp
                        
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <dt class="text-sm font-medium text-gray-500">Anrufdauer</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ gmdate('i:s', $call->duration_sec) }}
                                </dd>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4">
                                <dt class="text-sm font-medium text-gray-500">Minutenpreis</dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                    {{ number_format($ratePerMinute, 2, ',', '.') }} €
                                </dd>
                            </div>
                            
                            <div class="bg-indigo-50 rounded-lg p-4">
                                <dt class="text-sm font-medium text-indigo-600">Gesamtkosten</dt>
                                <dd class="mt-1 text-2xl font-semibold text-indigo-600">
                                    {{ number_format($cost, 2, ',', '.') }} €
                                </dd>
                            </div>
                        </dl>
                        
                        @if($billingRate && $billingRate->billing_increment > 1)
                        <p class="mt-4 text-xs text-gray-500">
                            * Abrechnung im {{ $billingRate->billing_increment_label }}
                        </p>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Summary --}}
                @if($call->summary)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Zusammenfassung</h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $call->summary }}</p>
                    </div>
                </div>
                @endif

                {{-- Transcript --}}
                @if($call->transcript)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Gesprächsverlauf</h2>
                    </div>
                    <div class="p-6">
                        <div class="bg-gray-50 rounded-lg p-4 space-y-3 max-h-96 overflow-y-auto">
                            @php
                                $transcript = is_string($call->transcript) ? json_decode($call->transcript, true) : $call->transcript;
                            @endphp
                            @if(is_array($transcript))
                                @foreach($transcript as $message)
                                    <div class="flex {{ $message['role'] === 'agent' ? 'justify-start' : 'justify-end' }}">
                                        <div class="max-w-xs lg:max-w-md {{ $message['role'] === 'agent' ? 'bg-white' : 'bg-blue-100' }} rounded-lg px-4 py-2 shadow">
                                            <p class="text-sm font-medium {{ $message['role'] === 'agent' ? 'text-gray-900' : 'text-blue-900' }} mb-1">
                                                {{ $message['role'] === 'agent' ? 'Agent' : 'Kunde' }}
                                            </p>
                                            <p class="text-sm text-gray-700">{{ $message['content'] ?? '' }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-500">Kein Transkript verfügbar</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Right Column - Actions & Meta --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Quick Actions --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Aktionen</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        {{-- Status Update --}}
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                Status ändern
                            </label>
                            <form method="POST" action="{{ route('business.calls.update-status', $call->id) }}">
                                @csrf
                                <select name="status" id="status" 
                                        onchange="this.form.submit()"
                                        class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    @php
                                        $currentStatus = optional($call->callPortalData)->status ?? 'new';
                                    @endphp
                                    <option value="new" {{ $currentStatus === 'new' ? 'selected' : '' }}>Neu</option>
                                    <option value="in_progress" {{ $currentStatus === 'in_progress' ? 'selected' : '' }}>In Bearbeitung</option>
                                    <option value="callback_scheduled" {{ $currentStatus === 'callback_scheduled' ? 'selected' : '' }}>Rückruf geplant</option>
                                    <option value="requires_action" {{ $currentStatus === 'requires_action' ? 'selected' : '' }}>Aktion erforderlich</option>
                                    <option value="completed" {{ $currentStatus === 'completed' ? 'selected' : '' }}>Abgeschlossen</option>
                                </select>
                            </form>
                        </div>

                        {{-- Assign Call --}}
                        @if(session('is_admin_viewing') || (Auth::guard('portal')->user() && Auth::guard('portal')->user()->hasPermission('calls.edit_all')))
                        <div>
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">
                                Zuweisen an
                            </label>
                            <form method="POST" action="{{ route('business.calls.assign', $call->id) }}">
                                @csrf
                                <select name="assigned_to" id="assigned_to" 
                                        onchange="this.form.submit()"
                                        class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    <option value="">Nicht zugewiesen</option>
                                    @foreach($teamMembers as $member)
                                        <option value="{{ $member->id }}" 
                                            {{ optional($call->callPortalData)->assigned_to == $member->id ? 'selected' : '' }}>
                                            {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        @endif

                        {{-- Add Note Button --}}
                        <button type="button" 
                                onclick="document.getElementById('add-note-form').classList.toggle('hidden')"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            Notiz hinzufügen
                        </button>

                        {{-- Schedule Callback --}}
                        @if(optional($call->callPortalData)->status !== 'completed')
                        <button type="button" 
                                onclick="document.getElementById('schedule-callback-form').classList.toggle('hidden')"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Rückruf planen
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Internal Notes --}}
                @if($call->callPortalData && $call->callPortalData->internal_notes)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Interne Notizen</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $call->callPortalData->internal_notes }}</p>
                    </div>
                </div>
                @endif

                {{-- Call Notes --}}
                @if($call->callNotes && $call->callNotes->count() > 0)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Notizen</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($call->callNotes as $note)
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ optional($note->user)->name ?? 'System' }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $note->created_at ? $note->created_at->format('d.m.Y H:i') : '' }}
                                        </p>
                                    </div>
                                    <p class="text-sm text-gray-700">{{ $note->content }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- Call History --}}
                @if($customerCallHistory && $customerCallHistory->count() > 0)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Anrufhistorie</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            @foreach($customerCallHistory as $historyCall)
                            <li>
                                <a href="{{ route('business.calls.show', $historyCall->id) }}" 
                                   class="block hover:bg-gray-50 -mx-3 px-3 py-2 rounded-lg transition duration-150">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $historyCall->created_at ? $historyCall->created_at->format('d.m.Y H:i') : '-' }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ gmdate('i:s', $historyCall->duration_sec ?? 0) }} - 
                                                @php
                                                    $historyStatus = optional($historyCall->callPortalData)->status ?? 'new';
                                                @endphp
                                                @switch($historyStatus)
                                                    @case('new') Neu @break
                                                    @case('in_progress') In Bearbeitung @break
                                                    @case('completed') Abgeschlossen @break
                                                    @default {{ ucfirst($historyStatus) }}
                                                @endswitch
                                            </p>
                                        </div>
                                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Hidden Forms --}}
    {{-- Add Note Form --}}
    <div id="add-note-form" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 overflow-y-auto z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <form method="POST" action="{{ route('business.calls.add-note', $call->id) }}">
                    @csrf
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Neue Notiz hinzufügen</h3>
                    </div>
                    <div class="p-6">
                        <label for="note_content" class="block text-sm font-medium text-gray-700 mb-1">
                            Notiz
                        </label>
                        <textarea name="content" id="note_content" rows="4" 
                                  class="block w-full shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 border-gray-300 rounded-md"
                                  placeholder="Ihre Notiz hier eingeben..." required></textarea>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" 
                                onclick="document.getElementById('add-note-form').classList.add('hidden')"
                                class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Abbrechen
                        </button>
                        <button type="submit" 
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Notiz speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Schedule Callback Form --}}
    <div id="schedule-callback-form" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 overflow-y-auto z-50">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <form method="POST" action="{{ route('business.calls.schedule-callback', $call->id) }}">
                    @csrf
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Rückruf planen</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="callback_date" class="block text-sm font-medium text-gray-700 mb-1">
                                Datum
                            </label>
                            <input type="date" name="callback_date" id="callback_date" 
                                   min="{{ now()->addDay()->format('Y-m-d') }}"
                                   class="block w-full shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 border-gray-300 rounded-md"
                                   required>
                        </div>
                        <div>
                            <label for="callback_time" class="block text-sm font-medium text-gray-700 mb-1">
                                Uhrzeit
                            </label>
                            <input type="time" name="callback_time" id="callback_time" 
                                   class="block w-full shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 border-gray-300 rounded-md"
                                   required>
                        </div>
                        <div>
                            <label for="callback_notes" class="block text-sm font-medium text-gray-700 mb-1">
                                Notizen (optional)
                            </label>
                            <textarea name="callback_notes" id="callback_notes" rows="2" 
                                      class="block w-full shadow-sm sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 border-gray-300 rounded-md"
                                      placeholder="Notizen zum Rückruf..."></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" 
                                onclick="document.getElementById('schedule-callback-form').classList.add('hidden')"
                                class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Abbrechen
                        </button>
                        <button type="submit" 
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Rückruf planen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection