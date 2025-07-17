@extends('portal.layouts.app')

@section('content')
<div class="py-12" x-data="callsTable()">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        {{-- Header mit Statistiken --}}
        <div class="mb-8">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Anrufe</h1>
                    <p class="mt-2 text-sm text-gray-700">
                        Übersicht aller eingegangenen Anrufe mit detaillierten Informationen
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 flex space-x-3">
                    {{-- Bulk Export Button (wird nur angezeigt wenn Items ausgewählt sind) --}}
                    <div x-show="selectedCalls.length > 0" x-cloak>
                        <button 
                            @click="showBulkExportModal = true"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span x-text="`${selectedCalls.length} ausgewählte exportieren`"></span>
                        </button>
                    </div>
                    
                    {{-- Standard Export Button --}}
                    <a href="{{ route('business.calls.export') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Alle exportieren
                    </a>
                </div>
            </div>
            
            {{-- Statistik-Karten --}}
            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Anrufe heute --}}
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Anrufe heute
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ $stats['total_today'] ?? 0 }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Neue Anrufe --}}
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Neue Anrufe
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ $stats['new'] ?? 0 }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Aktion erforderlich --}}
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Aktion erforderlich
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ $stats['requires_action'] ?? 0 }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kosten heute (nur für Management) --}}
                @if(session('is_admin_viewing') || (Auth::guard('portal')->user() && Auth::guard('portal')->user()->hasPermission('billing.view')))
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Kosten heute
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        {{ number_format($stats['costs_today'] ?? 0, 2, ',', '.') }} €
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Filter --}}
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-3 sm:p-6">
                <form method="GET" action="{{ route('business.calls.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {{-- Status Filter --}}
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Alle</option>
                                <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>Neu</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Bearbeitung</option>
                                <option value="requires_action" {{ request('status') == 'requires_action' ? 'selected' : '' }}>Aktion erforderlich</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Abgeschlossen</option>
                                <option value="callback_scheduled" {{ request('status') == 'callback_scheduled' ? 'selected' : '' }}>Rückruf geplant</option>
                            </select>
                        </div>

                        {{-- Dringlichkeit Filter --}}
                        <div>
                            <label for="urgency" class="block text-sm font-medium text-gray-700">Dringlichkeit</label>
                            <select id="urgency" name="urgency" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Alle</option>
                                <option value="high" {{ request('urgency') == 'high' ? 'selected' : '' }}>Hoch</option>
                                <option value="medium" {{ request('urgency') == 'medium' ? 'selected' : '' }}>Mittel</option>
                                <option value="low" {{ request('urgency') == 'low' ? 'selected' : '' }}>Niedrig</option>
                            </select>
                        </div>

                        {{-- Alter Filter --}}
                        <div>
                            <label for="age" class="block text-sm font-medium text-gray-700">Alter</label>
                            <select id="age" name="age" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Alle</option>
                                <option value="1h" {{ request('age') == '1h' ? 'selected' : '' }}>Letzte Stunde</option>
                                <option value="4h" {{ request('age') == '4h' ? 'selected' : '' }}>Letzte 4 Stunden</option>
                                <option value="24h" {{ request('age') == '24h' ? 'selected' : '' }}>Letzte 24 Stunden</option>
                                <option value="48h" {{ request('age') == '48h' ? 'selected' : '' }}>Letzte 48 Stunden</option>
                                <option value="7d" {{ request('age') == '7d' ? 'selected' : '' }}>Letzte 7 Tage</option>
                            </select>
                        </div>

                        {{-- Dauer Filter --}}
                        <div>
                            <label for="duration" class="block text-sm font-medium text-gray-700">Gesprächsdauer</label>
                            <select id="duration" name="duration" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Alle</option>
                                <option value="short" {{ request('duration') == 'short' ? 'selected' : '' }}>Kurz (&lt; 1 Min)</option>
                                <option value="medium" {{ request('duration') == 'medium' ? 'selected' : '' }}>Mittel (1-5 Min)</option>
                                <option value="long" {{ request('duration') == 'long' ? 'selected' : '' }}>Lang (5-10 Min)</option>
                                <option value="very_long" {{ request('duration') == 'very_long' ? 'selected' : '' }}>Sehr lang (&gt; 10 Min)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('business.calls.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Zurücksetzen
                        </a>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Filter anwenden
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabelle --}}
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left">
                            <input type="checkbox" @change="toggleAllCalls($event)" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Zeit seit Anruf
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Anrufer / Kunde
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Anliegen
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dringlichkeit
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dauer
                        </th>
                        @if(session('is_admin_viewing') || (Auth::guard('portal')->user() && Auth::guard('portal')->user()->hasPermission('billing.view')))
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Kosten
                        </th>
                        @endif
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Zugewiesen
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aktionen
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($calls as $call)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <input type="checkbox" 
                                       value="{{ $call->id }}" 
                                       @change="toggleCall({{ $call->id }})"
                                       :checked="selectedCalls.includes({{ $call->id }})"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                @php
                                    $timeSince = \App\Helpers\TimeHelper::formatTimeSince($call->created_at);
                                    $ageClass = \App\Helpers\TimeHelper::getAgeClass($call->created_at);
                                @endphp
                                <span class="text-sm {{ $ageClass }}" 
                                      title="{{ $call->created_at->format('d.m.Y H:i:s') }}"
                                      @mouseenter="showTooltip($event, '{{ $call->created_at->format('d.m.Y H:i:s') }}')"
                                      @mouseleave="tooltipOpen = false">
                                    {!! $timeSince !!}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="space-y-1">
                                    {{-- Customer Name with Copy Button --}}
                                    @php
                                        $customerName = $call->extracted_name ?? 
                                                       ($call->customer ? $call->customer->name : null) ?? 
                                                       ($call->metadata['customer_data']['full_name'] ?? null);
                                    @endphp
                                    @if($customerName)
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-900">{{ $customerName }}</span>
                                        <button @click="copyToClipboard('{{ $customerName }}', 'Name kopiert!')"
                                                class="text-gray-400 hover:text-gray-600 focus:outline-none"
                                                title="Name kopieren">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    @endif
                                    
                                    {{-- Primary Phone Number --}}
                                    @php
                                        $primaryPhone = $call->phone_number ?? $call->from_number;
                                        $additionalPhones = [];
                                        if (isset($call->metadata['customer_data'])) {
                                            $customerData = $call->metadata['customer_data'];
                                            if (!empty($customerData['phone_primary']) && $customerData['phone_primary'] !== $primaryPhone) {
                                                $additionalPhones[] = $customerData['phone_primary'];
                                            }
                                            if (!empty($customerData['phone_secondary'])) {
                                                $additionalPhones[] = $customerData['phone_secondary'];
                                            }
                                            if (!empty($customerData['mobile_phone'])) {
                                                $additionalPhones[] = $customerData['mobile_phone'];
                                            }
                                            if (!empty($customerData['alternative_phone'])) {
                                                $additionalPhones[] = $customerData['alternative_phone'];
                                            }
                                        }
                                        // Remove duplicates
                                        $additionalPhones = array_unique(array_filter($additionalPhones));
                                    @endphp
                                    <div class="flex items-center space-x-2">
                                        <a href="tel:{{ $primaryPhone }}" 
                                           class="text-sm text-indigo-600 hover:text-indigo-900 hover:underline">
                                            {{ $primaryPhone }}
                                        </a>
                                        <button @click="copyToClipboard('{{ $primaryPhone }}', 'Telefonnummer kopiert!')"
                                                class="text-gray-400 hover:text-gray-600 focus:outline-none"
                                                title="Telefonnummer kopieren">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    {{-- Additional Phone Numbers --}}
                                    @foreach($additionalPhones as $phone)
                                    <div class="flex items-center space-x-2">
                                        <a href="tel:{{ $phone }}" 
                                           class="text-sm text-gray-600 hover:text-indigo-600 hover:underline">
                                            {{ $phone }}
                                        </a>
                                        <button @click="copyToClipboard('{{ $phone }}', 'Telefonnummer kopiert!')"
                                                class="text-gray-400 hover:text-gray-600 focus:outline-none"
                                                title="Telefonnummer kopieren">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    @endforeach
                                    
                                    {{-- If no name, show phone as primary --}}
                                    @if(!$customerName)
                                    <span class="text-sm text-gray-500">Unbekannter Anrufer</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="text-sm text-gray-900">
                                    @if($call->reason_for_visit)
                                        <span title="{{ $call->reason_for_visit }}">
                                            {{ Str::limit($call->reason_for_visit, 40) }}
                                        </span>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                @if($call->urgency_level || isset($call->metadata['customer_data']['urgency']))
                                    <x-customer-data-badge 
                                        :customerData="['urgency' => $call->urgency_level ?? $call->metadata['customer_data']['urgency'] ?? null]" 
                                        field="urgency" 
                                        type="urgency" />
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                @php
                                    $status = $call->callPortalData->status ?? 'new';
                                    $statusLabels = [
                                        'new' => 'Neu',
                                        'in_progress' => 'In Bearbeitung',
                                        'requires_action' => 'Aktion erforderlich',
                                        'completed' => 'Abgeschlossen',
                                        'callback_scheduled' => 'Rückruf geplant'
                                    ];
                                    $statusColors = [
                                        'new' => 'bg-blue-100 text-blue-800',
                                        'in_progress' => 'bg-yellow-100 text-yellow-800',
                                        'requires_action' => 'bg-red-100 text-red-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'callback_scheduled' => 'bg-purple-100 text-purple-800'
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $statusLabels[$status] ?? $status }}
                                </span>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-900">
                                {{ gmdate('H:i:s', $call->duration_sec ?? 0) }}
                            </td>
                            @if(session('is_admin_viewing') || (Auth::guard('portal')->user() && Auth::guard('portal')->user()->hasPermission('billing.view')))
                            <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-900">
                                @php
                                    $cost = 0;
                                    if ($call->charge) {
                                        $cost = $call->charge->amount_charged;
                                    } elseif ($call->duration_sec) {
                                        $pricing = \App\Models\CompanyPricing::getCurrentForCompany($call->company_id);
                                        if ($pricing) {
                                            $cost = $pricing->calculatePrice($call->duration_sec);
                                        } else {
                                            $billingRate = \App\Models\BillingRate::where('company_id', $call->company_id)->active()->first();
                                            if ($billingRate) {
                                                $cost = $billingRate->calculateCharge($call->duration_sec);
                                            }
                                        }
                                    }
                                @endphp
                                {{ number_format($cost, 2, ',', '.') }} €
                            </td>
                            @endif
                            <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-500">
                                {{ $call->callPortalData->assignedTo->name ?? 'Nicht zugewiesen' }}
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <x-copy-call-quick :call="$call" />
                                    <x-call-email-actions :call="$call" />
                                    <a href="{{ route('business.calls.show', $call->id) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        Details
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ (session('is_admin_viewing') || (Auth::guard('portal')->user() && Auth::guard('portal')->user()->hasPermission('billing.view'))) ? '12' : '11' }}" 
                                class="px-6 py-8 text-center text-sm text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <p class="mt-2">Keine Anrufe gefunden</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $calls->withQueryString()->links() }}
        </div>
    </div>

    {{-- Bulk Export Modal --}}
    <div x-show="showBulkExportModal" x-cloak
         @click.away="showBulkExportModal = false"
         class="fixed z-10 inset-0 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showBulkExportModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showBulkExportModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Export-Format wählen
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Wählen Sie das gewünschte Format für den Export der <span x-text="selectedCalls.length"></span> ausgewählten Anrufe.
                                </p>
                            </div>
                            <div class="mt-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" x-model="exportFormat" value="csv" class="form-radio h-4 w-4 text-indigo-600">
                                    <span class="ml-2">CSV (Excel-kompatibel)</span>
                                </label>
                                <label class="inline-flex items-center ml-6">
                                    <input type="radio" x-model="exportFormat" value="pdf" class="form-radio h-4 w-4 text-indigo-600">
                                    <span class="ml-2">PDF (Druckformat)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                            @click="exportSelected()"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Exportieren
                    </button>
                    <button type="button" 
                            @click="showBulkExportModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Abbrechen
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tooltip --}}
    <div x-show="tooltipOpen" 
         x-cloak
         :style="`position: fixed; left: ${tooltipX}px; top: ${tooltipY}px; z-index: 50;`"
         class="bg-gray-900 text-white text-xs rounded py-1 px-2 pointer-events-none">
        <p x-text="tooltipContent"></p>
    </div>
</div>

<script>
function callsTable() {
    return {
        selectedCalls: [],
        showBulkExportModal: false,
        exportFormat: 'csv',
        tooltipOpen: false,
        tooltipContent: '',
        tooltipX: 0,
        tooltipY: 0,
        
        copyToClipboard(text, message) {
            navigator.clipboard.writeText(text).then(() => {
                this.$dispatch('notify', { message: message, type: 'success' });
            });
        },
        
        toggleAllCalls(event) {
            if (event.target.checked) {
                // Select all visible calls
                const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
                this.selectedCalls = Array.from(checkboxes).map(cb => parseInt(cb.value));
            } else {
                this.selectedCalls = [];
            }
        },
        
        toggleCall(callId) {
            const index = this.selectedCalls.indexOf(callId);
            if (index > -1) {
                this.selectedCalls.splice(index, 1);
            } else {
                this.selectedCalls.push(callId);
            }
        },
        
        showTooltip(event, content) {
            this.tooltipContent = content;
            this.tooltipX = event.clientX + 10;
            this.tooltipY = event.clientY + 10;
            this.tooltipOpen = true;
        },
        
        exportSelected() {
            // Erstelle ein Form für den Export
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("business.calls.export.bulk") }}';
            
            // CSRF Token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            // Export Format
            const formatInput = document.createElement('input');
            formatInput.type = 'hidden';
            formatInput.name = 'format';
            formatInput.value = this.exportFormat;
            form.appendChild(formatInput);
            
            // Selected IDs
            this.selectedCalls.forEach(id => {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'call_ids[]';
                idInput.value = id;
                form.appendChild(idInput);
            });
            
            document.body.appendChild(form);
            form.submit();
            
            this.showBulkExportModal = false;
        }
    }
}
</script>
@endsection