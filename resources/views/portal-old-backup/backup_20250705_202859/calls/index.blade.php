@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">Anrufe</h1>
                <p class="mt-2 text-sm text-gray-700">
                    Liste aller eingegangenen Anrufe mit Status und Details
                </p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('business.calls.export') }}" 
                   class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    CSV Export
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="mt-8 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" action="{{ route('business.calls.index') }}" class="space-y-4 sm:space-y-0 sm:flex sm:items-center sm:space-x-4">
                    <!-- Status Filter -->
                    <div class="sm:w-48">
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

                    <!-- Date Range -->
                    <div class="sm:w-48">
                        <label for="date_from" class="block text-sm font-medium text-gray-700">Von</label>
                        <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                               class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    </div>
                    <div class="sm:w-48">
                        <label for="date_to" class="block text-sm font-medium text-gray-700">Bis</label>
                        <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                               class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    </div>

                    <!-- Search -->
                    <div class="sm:flex-1">
                        <label for="search" class="block text-sm font-medium text-gray-700">Suche</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                               placeholder="Telefonnummer oder Name"
                               class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-end space-x-2">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Filtern
                        </button>
                        <a href="{{ route('business.calls.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Zurücksetzen
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Calls Table -->
        <div class="mt-8 flex flex-col">
            <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Datum/Zeit
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Telefonnummer
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Kunde
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Firma
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Anrufgrund
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Dringlichkeit
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Status
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Dauer
                                    </th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                        Zugewiesen an
                                    </th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Aktionen</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($calls as $call)
                                    <tr>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                            {{ $call->created_at ? $call->created_at->format('d.m.Y H:i') : '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                            {{ $call->phone_number }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                            <x-customer-name :call="$call" />
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                            @if(isset($call->metadata['customer_data']['company_name']) && $call->metadata['customer_data']['company_name'])
                                                {{ $call->metadata['customer_data']['company_name'] }}
                                            @elseif($call->customer && $call->customer->company_name)
                                                {{ $call->customer->company_name }}
                                            @else
                                                <span class="text-gray-500">-</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-4 text-sm text-gray-900">
                                            @if($call->reason_for_visit)
                                                <span class="block truncate max-w-xs" title="{{ $call->reason_for_visit }}">
                                                    {{ Str::limit($call->reason_for_visit, 30) }}
                                                </span>
                                            @else
                                                <span class="text-gray-500">-</span>
                                            @endif
                                            @if($call->appointment_requested)
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded mt-1">
                                                    Terminwunsch
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                                            @php
                                                $urgency = $call->urgency_level ?? $call->metadata['customer_data']['urgency'] ?? null;
                                            @endphp
                                            @if($urgency)
                                                <x-customer-data-badge :customerData="['urgency' => $urgency]" field="urgency" type="urgency" />
                                            @else
                                                <span class="text-gray-500">-</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                                            @php
                                                $status = $call->callPortalData->status ?? 'new';
                                            @endphp
                                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                                @if($status === 'completed') bg-green-100 text-green-800
                                                @elseif($status === 'new') bg-blue-100 text-blue-800
                                                @elseif($status === 'in_progress') bg-yellow-100 text-yellow-800
                                                @elseif($status === 'callback_scheduled') bg-purple-100 text-purple-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                            @if($call->duration_sec)
                                                {{ gmdate('i:s', $call->duration_sec) }}
                                            @else
                                                <span class="text-gray-500">-</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                            @if($call->callPortalData && $call->callPortalData->assignedTo)
                                                {{ $call->callPortalData->assignedTo->name }}
                                            @else
                                                <span class="text-gray-500">Nicht zugewiesen</span>
                                            @endif
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <a href="{{ route('business.calls.show', $call->id) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">
                                                Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-3 py-4 text-sm text-gray-500 text-center">
                                            Keine Anrufe gefunden
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $calls->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection