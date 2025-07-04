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
                @if(Auth::guard('portal')->user()->hasPermission('billing.view') || session('is_admin_viewing'))
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

        {{-- Erweiterte Filter --}}
        <div class="bg-white shadow sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" action="{{ route('business.calls.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {{-- Status Filter --}}
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="status" name="status" 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Alle Status</option>
                                <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>Neu</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Bearbeitung</option>
                                <option value="callback_scheduled" {{ request('status') == 'callback_scheduled' ? 'selected' : '' }}>Rückruf geplant</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Abgeschlossen</option>
                            </select>
                        </div>

                        {{-- Dringlichkeit Filter --}}
                        <div>
                            <label for="urgency" class="block text-sm font-medium text-gray-700">Dringlichkeit</label>
                            <select id="urgency" name="urgency" 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Alle Dringlichkeiten</option>
                                <option value="high" {{ request('urgency') == 'high' ? 'selected' : '' }}>Hoch</option>
                                <option value="medium" {{ request('urgency') == 'medium' ? 'selected' : '' }}>Mittel</option>
                                <option value="low" {{ request('urgency') == 'low' ? 'selected' : '' }}>Niedrig</option>
                            </select>
                        </div>

                        {{-- Zeit seit Anruf Filter --}}
                        <div>
                            <label for="age" class="block text-sm font-medium text-gray-700">Alter</label>
                            <select id="age" name="age" 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Alle Zeiten</option>
                                <option value="1h" {{ request('age') == '1h' ? 'selected' : '' }}>Letzte Stunde</option>
                                <option value="4h" {{ request('age') == '4h' ? 'selected' : '' }}>Letzte 4 Stunden</option>
                                <option value="24h" {{ request('age') == '24h' ? 'selected' : '' }}>Letzte 24 Stunden</option>
                                <option value="48h" {{ request('age') == '48h' ? 'selected' : '' }}>Letzte 48 Stunden</option>
                                <option value="7d" {{ request('age') == '7d' ? 'selected' : '' }}>Letzte 7 Tage</option>
                            </select>
                        </div>

                        {{-- Dauer Filter --}}
                        <div>
                            <label for="duration" class="block text-sm font-medium text-gray-700">Anrufdauer</label>
                            <select id="duration" name="duration" 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Alle Dauern</option>
                                <option value="short" {{ request('duration') == 'short' ? 'selected' : '' }}>< 1 Minute</option>
                                <option value="medium" {{ request('duration') == 'medium' ? 'selected' : '' }}>1-5 Minuten</option>
                                <option value="long" {{ request('duration') == 'long' ? 'selected' : '' }}>5-10 Minuten</option>
                                <option value="very_long" {{ request('duration') == 'very_long' ? 'selected' : '' }}>> 10 Minuten</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        {{-- Datum von/bis --}}
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700">Von</label>
                            <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                                   class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        </div>
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700">Bis</label>
                            <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                                   class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        </div>
                        
                        {{-- Suche --}}
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Suche</label>
                            <input type="text" id="search" name="search" value="{{ request('search') }}"
                                   placeholder="Telefonnummer, Name oder Anliegen"
                                   class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        </div>
                    </div>

                    {{-- Filter Buttons --}}
                    <div class="flex justify-end space-x-2">
                        <a href="{{ route('business.calls.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Zurücksetzen
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Filter anwenden
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Calls Table --}}
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="relative px-6 py-3">
                            <input type="checkbox" 
                                   @change="toggleAllCalls"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Zeit
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Anrufer
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Kunde
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Firma
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
                        @if(Auth::guard('portal')->user()->hasPermission('billing.view') || session('is_admin_viewing'))
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Kosten
                        </th>
                        @endif
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Zugewiesen
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Aktionen</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($calls as $call)
                        @php
                            $timeClass = \App\Helpers\TimeHelper::getAgeClass($call->created_at);
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" 
                                       value="{{ $call->id }}"
                                       @change="toggleCall({{ $call->id }})"
                                       :checked="selectedCalls.includes({{ $call->id }})"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm {{ $timeClass }}">
                                {!! \App\Helpers\TimeHelper::formatTimeSince($call->created_at) !!}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $call->phone_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($call->extracted_name || $call->customer)
                                    <div class="text-sm text-gray-900 font-medium">
                                        {{ $call->extracted_name ?? $call->customer->name }}
                                    </div>
                                    @if($call->extracted_email || $call->customer?->email)
                                        <div class="text-sm text-gray-500">
                                            {{ $call->extracted_email ?? $call->customer->email }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-500">Unbekannt</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if(isset($call->metadata['customer_data']['company']) && $call->metadata['customer_data']['company'])
                                    <span title="{{ $call->metadata['customer_data']['company'] }}">
                                        {{ Str::limit($call->metadata['customer_data']['company'], 20) }}
                                    </span>
                                @elseif($call->customer?->company_name)
                                    <span title="{{ $call->customer->company_name }}">
                                        {{ Str::limit($call->customer->company_name, 20) }}
                                    </span>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                @if($call->reason_for_visit)
                                    <span class="block truncate max-w-xs cursor-help" 
                                          title="{{ $call->reason_for_visit }}"
                                          @click="showTooltip($event, '{{ addslashes($call->reason_for_visit) }}')">
                                        {{ Str::limit($call->reason_for_visit, 30) }}
                                    </span>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @php
                                    $urgency = $call->urgency_level ?? $call->metadata['customer_data']['urgency'] ?? null;
                                @endphp
                                @if($urgency)
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
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $status = $call->callPortalData->status ?? 'new';
                                @endphp
                                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                    @if($status === 'completed') bg-green-100 text-green-800
                                    @elseif($status === 'new') bg-blue-100 text-blue-800
                                    @elseif($status === 'in_progress') bg-yellow-100 text-yellow-800
                                    @elseif($status === 'callback_scheduled') bg-purple-100 text-purple-800
                                    @elseif($status === 'requires_action') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    @switch($status)
                                        @case('new')
                                            Neu
                                            @break
                                        @case('in_progress')
                                            In Bearbeitung
                                            @break
                                        @case('callback_scheduled')
                                            Rückruf geplant
                                            @break
                                        @case('completed')
                                            Abgeschlossen
                                            @break
                                        @case('requires_action')
                                            Aktion erforderlich
                                            @break
                                        @default
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    @endswitch
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($call->duration_sec)
                                    {{ gmdate('i:s', $call->duration_sec) }}
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            @if(Auth::guard('portal')->user()->hasPermission('billing.view') || session('is_admin_viewing'))
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($call->charge)
                                    <span class="font-medium">{{ $call->charge->formatted_amount }}</span>
                                @elseif($call->duration_sec)
                                    @php
                                        $pricing = \App\Models\CompanyPricing::getCurrentForCompany($call->company_id);
                                        $cost = $pricing ? $pricing->calculatePrice($call->duration_sec) : 0;
                                    @endphp
                                    <span class="text-gray-600">{{ number_format($cost, 2, ',', '.') }} €</span>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($call->callPortalData?->assignedTo)
                                    {{ $call->callPortalData->assignedTo->name }}
                                @else
                                    <span class="text-gray-500">Nicht zugewiesen</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <x-call-email-actions :call="$call" />
                                <a href="{{ route('business.calls.show', $call->id) }}" 
                                   class="text-indigo-600 hover:text-indigo-900">
                                    Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ Auth::guard('portal')->user()->hasPermission('billing.view') || session('is_admin_viewing') ? '12' : '11' }}" 
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
         class="fixed z-10 inset-0 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showBulkExportModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 @click="showBulkExportModal = false"></div>

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
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Export-Optionen
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Wählen Sie das gewünschte Export-Format für die <span x-text="selectedCalls.length"></span> ausgewählten Anrufe.
                                </p>
                            </div>
                            <div class="mt-4 space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="export_format" value="csv" x-model="exportFormat" 
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">CSV (für Excel)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="export_format" value="pdf" x-model="exportFormat" 
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">PDF (für Druck/Archiv)</span>
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
    <div x-show="tooltipOpen" x-cloak
         :style="`position: fixed; left: ${tooltipX}px; top: ${tooltipY}px;`"
         class="z-50 bg-gray-900 text-white text-sm rounded-lg py-2 px-3 max-w-xs"
         @click.away="tooltipOpen = false">
        <span x-text="tooltipContent"></span>
    </div>
</div>

{{-- Toast Notification --}}
<div x-data="{ show: false, message: '', type: 'success' }"
     x-show="show"
     x-transition:enter="transform ease-out duration-300 transition"
     x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
     x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @show-toast.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 3000)"
     class="fixed bottom-0 right-0 mb-4 mr-4 z-50">
    <div class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg x-show="type === 'success'" class="h-6 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="type === 'error'" class="h-6 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium text-gray-900" x-text="message"></p>
                </div>
            </div>
        </div>
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