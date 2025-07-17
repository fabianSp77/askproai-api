@extends('portal.layouts.app')

@push('scripts')
<script>
// CRITICAL FIX: Prevent auto-opening dropdowns - must run BEFORE Alpine
(function() {
    console.log('🛡️ Dropdown Protection Active - BLOCKING DropdownManager');
    
    // Create a dummy dropdown manager that does nothing
    window.DropdownManager = class {
        constructor() {
            console.log('🚫 Dummy DropdownManager created');
        }
        init() {
            console.log('🚫 Dummy init - doing nothing');
        }
        closeAll() {
            console.log('🚫 Dummy closeAll - doing nothing');
        }
        registerDropdown() {}
        toggleDropdown() {}
        openDropdown() {}
        closeDropdown() {}
        initializeDropdowns() {}
        initializeDropdown() {}
        isDropdown() { return false; }
        positionDropdown() {}
        setupEventListeners() {}
        setupMutationObserver() {}
        destroy() {}
    };
    
    // Prevent the real dropdown manager from loading
    Object.defineProperty(window, 'dropdownManager', {
        get: function() {
            return new window.DropdownManager();
        },
        set: function(value) {
            console.log('🚫 Blocked real DropdownManager');
            // Do nothing - prevent the real one from being set
        }
    });
    
    // Store original Alpine.data if it exists
    if (window.Alpine && window.Alpine.data) {
        const originalData = window.Alpine.data.bind(window.Alpine);
        
        // Override Alpine.data to intercept dropdown components
        window.Alpine.data = function(name, callback) {
            console.log('Alpine.data called with:', name);
            
            // Block the dropdown component from dropdown-manager.js
            if (name === 'dropdown' || name === 'branchSelector') {
                console.log('🚫 BLOCKING dropdown component:', name);
                
                // Return a simple non-opening dropdown
                return () => ({
                    open: false,
                    isOpen: false,
                    dropdownOpen: false,
                    init() {
                        this.open = false;
                        this.isOpen = false;
                        this.dropdownOpen = false;
                    },
                    toggle() {
                        // Allow manual toggle
                        this.open = !this.open;
                    },
                    show() {
                        this.open = true;
                    },
                    close() {
                        this.open = false;
                    }
                });
            }
            
            return originalData(name, callback);
        };
    }
})();
</script>
@endpush

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
                        {{-- Meaningful Title: Company was called by Customer --}}
                        <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">
                            @php
                                $companyName = $call->company->name ?? 'Unternehmen';
                                $customerName = $call->extracted_name ?? 
                                               ($call->customer ? $call->customer->name : null) ?? 
                                               ($call->metadata['customer_data']['full_name'] ?? null);
                                $phoneNumber = $call->phone_number ?? $call->from_number;
                            @endphp
                            @if($customerName)
                                {{ $companyName }} wurde angerufen von {{ $customerName }}
                            @else
                                {{ $companyName }} - Anruf von {{ $phoneNumber }}
                            @endif
                        </h1>
                        
                        {{-- Meta Information - Simplified --}}
                        <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
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
                        
                        <span class="ml-3 flex items-center space-x-2">
                            <x-copy-call-simple :call="$call" />
                            <x-call-email-actions :call="$call" />
                            {{-- PDF Export --}}
                            <a href="{{ route('business.calls.export-pdf', $call->id) }}" 
                               id="export_pdf_link"
                               name="export_pdf"
                               class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                               title="Als PDF exportieren">
                                <svg class="-ml-0.5 mr-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h2l1 2h2l-3-4h2" />
                                </svg>
                                PDF
                            </a>
                            {{-- Print Button --}}
                            <button onclick="window.print()" 
                                    id="print_button"
                                    name="print"
                                    type="button"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    title="Drucken">
                                <svg class="-ml-0.5 mr-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Drucken
                            </button>
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
                    
                    {{-- Costs (only for management) with detailed tooltip - PROMINENT DISPLAY --}}
                    @if(session('is_admin_viewing') || (Auth::guard('portal')->user() && Auth::guard('portal')->user()->hasPermission('billing.view')))
                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg p-3 relative border border-indigo-200">
                        <dt class="text-xs font-medium text-indigo-700 uppercase tracking-wider">Kosten</dt>
                        <dd class="mt-1">
                            @php
                                $cost = 0;
                                $ratePerMinute = 0;
                                $billingIncrement = null;
                                
                                if ($call->charge) {
                                    $cost = $call->charge->amount_charged;
                                    $ratePerMinute = $call->charge->rate_per_minute;
                                } elseif ($call->duration_sec) {
                                    $pricing = \App\Models\CompanyPricing::getCurrentForCompany($call->company_id);
                                    if ($pricing) {
                                        $cost = $pricing->calculatePrice($call->duration_sec);
                                        $ratePerMinute = $pricing->price_per_minute;
                                    } else {
                                        $billingRate = \App\Models\BillingRate::where('company_id', $call->company_id)->active()->first();
                                        if ($billingRate) {
                                            $cost = $billingRate->calculateCharge($call->duration_sec);
                                            $ratePerMinute = $billingRate->rate_per_minute;
                                            $billingIncrement = $billingRate->billing_increment_label;
                                        }
                                    }
                                }
                            @endphp
                            <span class="flex items-center text-lg font-bold text-indigo-900">
                                {{ number_format($cost, 2, ',', '.') }} €
                                <svg class="ml-1 h-4 w-4 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </dd>
                        
                        {{-- Cost details shown inline instead of tooltip --}}
                        <div class="mt-2 text-xs text-gray-600">
                            <span>{{ gmdate('i:s', $call->duration_sec ?? 0) }} Min.</span>
                            <span class="mx-1">•</span>
                            <span>{{ number_format($ratePerMinute, 2, ',', '.') }} €/Min.</span>
                        </div>
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

                {{-- Collected Customer Data - IMMER anzeigen, wenn wir eine Telefonnummer haben --}}
                @if($call->phone_number || $call->from_number || (isset($call->metadata['customer_data']) && !empty($call->metadata['customer_data'])))
                <div class="bg-white shadow rounded-lg overflow-hidden" x-data="{ 
                    copyToClipboard(text, message) {
                        navigator.clipboard.writeText(text).then(() => {
                            window.dispatchEvent(new CustomEvent('notify', { 
                                detail: { message: message, type: 'success' } 
                            }));
                        }).catch(err => {
                            // Fallback für ältere Browser
                            const textArea = document.createElement('textarea');
                            textArea.value = text;
                            textArea.style.position = 'fixed';
                            textArea.style.left = '-999999px';
                            document.body.appendChild(textArea);
                            textArea.focus();
                            textArea.select();
                            try {
                                document.execCommand('copy');
                                window.dispatchEvent(new CustomEvent('notify', { 
                                    detail: { message: message, type: 'success' } 
                                }));
                            } catch (err) {
                                console.error('Kopieren fehlgeschlagen:', err);
                            }
                            document.body.removeChild(textArea);
                        });
                    }
                }">
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
                            $customerData = $call->metadata['customer_data'] ?? [];
                            $phoneNumber = $call->phone_number ?? $call->from_number;
                        @endphp
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            {{-- Kundenname links, Anrufer-Telefonnummer rechts in der ersten Zeile --}}
                            @if(!empty($customerData['full_name']) || $call->extracted_name)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Kundenname</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-semibold">
                                    {{ $customerData['full_name'] ?? $call->extracted_name }}
                                </dd>
                            </div>
                            @endif
                            
                            {{-- Anrufer Telefonnummer --}}
                            <div class="{{ empty($customerData['full_name']) && !$call->extracted_name ? 'sm:col-span-2' : '' }}">
                                <dt class="text-sm font-medium text-gray-500">Anrufer-Telefonnummer</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-semibold">
                                    <div class="flex items-center space-x-2">
                                        <a href="tel:{{ $phoneNumber }}" 
                                           class="text-indigo-600 hover:text-indigo-800 hover:underline"
                                           title="Anrufen">
                                            {{ $phoneNumber }}
                                        </a>
                                        <button id="copy_phone_customer_button"
                                                name="copy_phone_customer"
                                                type="button"
                                                class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors" 
                                                @click="copyToClipboard('{{ $phoneNumber }}', 'Telefonnummer kopiert!')"
                                                title="Telefonnummer kopieren">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                        {{-- Rückruf Button --}}
                                        <button type="button" 
                                                id="callback_phone_button"
                                                name="callback_phone"
                                                onclick="document.getElementById('schedule-callback-form').classList.toggle('hidden')"
                                                class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
                                                title="Rückruf planen">
                                            <svg class="h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            Rückruf planen
                                        </button>
                                    </div>
                                </dd>
                            </div>
                            
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
                            
                            {{-- Email from customer data --}}
                            @if(!empty($customerData['email']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">E-Mail</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="mailto:{{ $customerData['email'] }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $customerData['email'] }}
                                    </a>
                                </dd>
                            </div>
                            @elseif($call->extracted_email)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">E-Mail</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="mailto:{{ $call->extracted_email }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $call->extracted_email }}
                                    </a>
                                </dd>
                            </div>
                            @endif
                            
                            {{-- Additional email if different --}}
                            @if(!empty($customerData['email_address']) && $customerData['email_address'] !== ($customerData['email'] ?? $call->extracted_email))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Weitere E-Mail</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="mailto:{{ $customerData['email_address'] }}" class="text-indigo-600 hover:text-indigo-500">
                                        {{ $customerData['email_address'] }}
                                    </a>
                                </dd>
                            </div>
                            @endif
                            
                            {{-- Alternative phone number --}}
                            @if(!empty($customerData['alternative_phone']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Alternative Telefonnummer</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $customerData['alternative_phone'] }}</dd>
                            </div>
                            @endif
                            
                            {{-- Mobile phone if different --}}
                            @if(!empty($customerData['mobile_phone']))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Mobiltelefon</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $customerData['mobile_phone'] }}</dd>
                            </div>
                            @endif
                            
                            {{-- Anrufzeitpunkt --}}
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Anrufzeitpunkt</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @php
                                        $timeClass = \App\Helpers\TimeHelper::getAgeClass($call->created_at);
                                    @endphp
                                    <span class="{{ $timeClass }}">{!! \App\Helpers\TimeHelper::formatTimeSince($call->created_at) !!}</span>
                                    <span class="block text-xs text-gray-500 mt-1">{{ $call->created_at ? $call->created_at->format('d.m.Y H:i') : '-' }}</span>
                                </dd>
                            </div>
                            
                            {{-- Anrufdauer --}}
                            @if($call->duration_sec)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Anrufdauer</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ gmdate('i:s', $call->duration_sec) }} Minuten</dd>
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


                {{-- Debug Info --}}
                @if(config('app.debug'))
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <h3 class="text-sm font-bold text-yellow-800 mb-2">Debug Info:</h3>
                    <p class="text-xs text-yellow-700">
                        Transcript vorhanden: {{ $call->transcript ? 'Ja' : 'Nein' }}<br>
                        Transcript Typ: {{ gettype($call->transcript) }}<br>
                        Transcript Länge: {{ $call->transcript ? strlen($call->transcript) : 0 }}<br>
                        Transcript Object: {{ $call->transcript_object ? 'Ja' : 'Nein' }}<br>
                        Transcript With Tools: {{ $call->transcript_with_tools ? 'Ja' : 'Nein' }}<br>
                        Summary vorhanden: {{ $call->summary ? 'Ja' : 'Nein' }}<br>
                        Summary Länge: {{ $call->summary ? strlen($call->summary) : 0 }}<br>
                        Call Summary vorhanden: {{ $call->call_summary ? 'Ja' : 'Nein' }}<br>
                        Call Summary Länge: {{ $call->call_summary ? strlen($call->call_summary) : 0 }}<br>
                        Webhook Data vorhanden: {{ isset($call->webhook_data) ? 'Ja' : 'Nein' }}<br>
                        Webhook Call Summary: {{ isset($call->webhook_data['call_analysis']['call_summary']) ? 'Ja' : 'Nein' }}<br>
                        Metadata Transcript: {{ isset($call->metadata['transcript']) ? 'Ja' : 'Nein' }}<br>
                        Metadata Messages: {{ isset($call->metadata['messages']) ? 'Ja' : 'Nein' }}
                    </p>
                    @php
                        // Additional debug for transcript structure
                        $debugTranscript = null;
                        if ($call->transcript) {
                            $debugTranscript = is_string($call->transcript) ? json_decode($call->transcript, true) : $call->transcript;
                        }
                    @endphp
                    @if($debugTranscript)
                        <p class="text-xs text-yellow-700 mt-2">
                            Decoded Transcript Type: {{ gettype($debugTranscript) }}<br>
                            @if(is_array($debugTranscript))
                                Array Count: {{ count($debugTranscript) }}<br>
                                @if(count($debugTranscript) > 0)
                                    First Item Keys: {{ implode(', ', array_keys($debugTranscript[0] ?? [])) }}<br>
                                @endif
                            @elseif(is_string($debugTranscript))
                                String Length: {{ strlen($debugTranscript) }}<br>
                                First 100 chars: {{ substr($debugTranscript, 0, 100) }}...<br>
                            @endif
                        </p>
                    @endif
                    <p class="text-xs text-yellow-700 mt-2">
                        Transcript Object Type: {{ $call->transcript_object ? gettype($call->transcript_object) : 'null' }}<br>
                        Transcript With Tools Type: {{ $call->transcript_with_tools ? gettype($call->transcript_with_tools) : 'null' }}<br>
                        @if($call->transcript && strlen($call->transcript) > 0)
                            Transcript Sample (first 200 chars): <br>
                            <code class="text-xs bg-yellow-100 p-1 block mt-1">{{ substr($call->transcript, 0, 200) }}...</code>
                        @endif
                    </p>
                </div>
                @endif

                {{-- Transcript with Summary --}}
                @if($call->transcript || $call->transcript_object || $call->transcript_with_tools || $call->summary || $call->call_summary || isset($call->webhook_data['call_analysis']['call_summary']) || isset($call->metadata['transcript']) || isset($call->metadata['messages']))
                <div class="bg-white shadow rounded-lg overflow-hidden" x-data="callTranscriptViewer">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Gesprächsverlauf</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- Debug at the very beginning --}}
                        @if(config('app.debug'))
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded">
                            <strong class="text-red-800">Initial Transcript Check:</strong><br>
                            <span class="text-sm text-red-700">
                                $call->transcript exists: {{ $call->transcript ? 'YES' : 'NO' }}<br>
                                Type: {{ gettype($call->transcript) }}<br>
                                Length: {{ $call->transcript ? strlen($call->transcript) : 0 }}<br>
                                First 50 chars: {{ $call->transcript ? substr($call->transcript, 0, 50) : 'NULL' }}
                            </span>
                        </div>
                        @endif
                        
                        {{-- Summary Section within Transcript --}}
                        @php
                            $summaryText = $call->summary ?? 
                                          $call->call_summary ?? 
                                          ($call->webhook_data['call_analysis']['call_summary'] ?? null);
                        @endphp
                        @if($summaryText)
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-900">Zusammenfassung des Gesprächs</h3>
                                <button 
                                    id="translate_summary_button"
                                    name="translate_summary"
                                    type="button"
                                    @click="translateSummary()"
                                    :disabled="translating || translated"
                                    class="inline-flex items-center px-2.5 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="{ 'animate-pulse': translating }">
                                    <svg class="mr-1 h-3.5 w-3.5" :class="{ 'animate-spin': translating }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                    </svg>
                                    <span x-show="!translating && !translated">Übersetzen</span>
                                    <span x-show="translating">Übersetze...</span>
                                    <span x-show="translated && !translating">Übersetzt</span>
                                </button>
                            </div>
                            <div x-show="translated" class="mb-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Aus dem Englischen übersetzt
                                </span>
                            </div>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap" x-text="translated ? translatedText : originalText">{{ $summaryText }}</p>
                            <div x-show="translated" class="mt-2">
                                <button 
                                    id="show_original_summary_button"
                                    name="show_original_summary"
                                    type="button"
                                    @click="translated = false"
                                    class="text-xs text-indigo-600 hover:text-indigo-500">
                                    Original anzeigen
                                </button>
                            </div>
                        </div>
                        @endif
                        
                        {{-- Transcript Messages --}}
                        @php
                            // Try to get transcript from various sources (prioritize JSON formats)
                            $transcriptData = null;
                            $transcriptSource = 'none';
                            
                            // Try to get transcript from various sources
                            // Check the main transcript field first since debug shows it has data
                            if ($call->transcript) {
                                if (is_string($call->transcript)) {
                                    // Try to decode as JSON first
                                    $decoded = json_decode($call->transcript, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                        $transcriptData = $decoded;
                                        $transcriptSource = 'transcript (decoded JSON)';
                                    } else {
                                        // Keep as plain text - this is likely Retell.ai format
                                        $transcriptData = $call->transcript;
                                        $transcriptSource = 'transcript (plain text)';
                                    }
                                } else {
                                    $transcriptData = $call->transcript;
                                    $transcriptSource = 'transcript (raw data)';
                                }
                            } elseif ($call->transcript_object) {
                                $transcriptData = $call->transcript_object;
                                $transcriptSource = 'transcript_object';
                            } elseif ($call->transcript_with_tools) {
                                $transcriptData = $call->transcript_with_tools;
                                $transcriptSource = 'transcript_with_tools';
                            } elseif (isset($call->metadata['transcript'])) {
                                $transcriptData = $call->metadata['transcript'];
                                $transcriptSource = 'metadata.transcript';
                            } elseif (isset($call->metadata['messages'])) {
                                $transcriptData = $call->metadata['messages'];
                                $transcriptSource = 'metadata.messages';
                            }
                            
                            // Ensure transcript data is properly decoded if it's a JSON string
                            if (is_string($transcriptData) && !in_array($transcriptSource, ['transcript (plain text)', 'transcript (raw)'])) {
                                $decoded = json_decode($transcriptData, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $transcriptData = $decoded;
                                    $transcriptSource .= ' (string decoded)';
                                }
                            }
                        @endphp
                        
                        {{-- Debug: Show what transcript data we found --}}
                        @if(config('app.debug'))
                        <div class="mb-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                            <strong>Transcript Debug:</strong><br>
                            Source: {{ $transcriptSource }}<br>
                            Data Type: {{ gettype($transcriptData) }}<br>
                            @if(is_string($transcriptData))
                                String Length: {{ strlen($transcriptData) }}<br>
                                First 100 chars: {{ substr($transcriptData, 0, 100) }}...
                            @elseif(is_array($transcriptData))
                                Array Count: {{ count($transcriptData) }}<br>
                                @if(count($transcriptData) > 0)
                                    First Item Type: {{ gettype($transcriptData[0] ?? null) }}<br>
                                    @if(isset($transcriptData[0]) && is_array($transcriptData[0]))
                                        First Item Keys: {{ implode(', ', array_keys($transcriptData[0])) }}
                                    @endif
                                @endif
                            @else
                                Value: {{ var_export($transcriptData, true) }}
                            @endif
                        </div>
                        @endif
                        
                        @if($transcriptData || $call->transcript)
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">Detaillierter Gesprächsverlauf</h3>
                            <div class="bg-gray-50 rounded-lg p-4 space-y-3 max-h-96 overflow-y-auto">
                                @php
                                    // Fallback if transcriptData is somehow empty but transcript exists
                                    if (!$transcriptData && $call->transcript) {
                                        $transcriptData = $call->transcript;
                                        $transcriptSource = 'transcript (fallback)';
                                    }
                                @endphp
                                @if(is_array($transcriptData))
                                    @php
                                        $hasValidMessages = false;
                                        // Check if it's a messages array with role/content
                                        foreach($transcriptData as $message) {
                                            if(isset($message['role']) && isset($message['content'])) {
                                                $hasValidMessages = true;
                                                break;
                                            }
                                        }
                                    @endphp
                                    
                                    @if($hasValidMessages)
                                        {{-- Standard message format --}}
                                        @foreach($transcriptData as $message)
                                            @if(isset($message['role']) && isset($message['content']))
                                            <div class="flex {{ $message['role'] === 'agent' ? 'justify-start' : 'justify-end' }}">
                                                <div class="max-w-xs lg:max-w-md {{ $message['role'] === 'agent' ? 'bg-white' : 'bg-blue-100' }} rounded-lg px-4 py-2 shadow">
                                                    <p class="text-sm font-medium {{ $message['role'] === 'agent' ? 'text-gray-900' : 'text-blue-900' }} mb-1">
                                                        {{ $message['role'] === 'agent' ? 'Agent' : 'Kunde' }}
                                                    </p>
                                                    <p class="text-sm text-gray-700">{{ $message['content'] ?? '' }}</p>
                                                </div>
                                            </div>
                                            @endif
                                        @endforeach
                                    @else
                                        {{-- Alternative array format or Retell format --}}
                                        @if(isset($transcriptData[0]) && is_string($transcriptData[0]))
                                            {{-- Array of strings format --}}
                                            @foreach($transcriptData as $index => $line)
                                                <div class="flex {{ $index % 2 == 0 ? 'justify-start' : 'justify-end' }}">
                                                    <div class="max-w-xs lg:max-w-md {{ $index % 2 == 0 ? 'bg-white' : 'bg-blue-100' }} rounded-lg px-4 py-2 shadow">
                                                        <p class="text-sm font-medium {{ $index % 2 == 0 ? 'text-gray-900' : 'text-blue-900' }} mb-1">
                                                            {{ $index % 2 == 0 ? 'Agent' : 'Kunde' }}
                                                        </p>
                                                        <p class="text-sm text-gray-700">{{ $line }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            {{-- Unknown array format - display as JSON --}}
                                            <div class="bg-white rounded-lg p-4">
                                                <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ json_encode($transcriptData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        @endif
                                    @endif
                                @elseif(is_string($transcriptData))
                                    {{-- Display plain text transcript --}}
                                    @php
                                        // Try to parse Retell.ai format (Speaker X: text)
                                        $lines = explode("\n", $transcriptData);
                                        $parsedMessages = [];
                                        foreach($lines as $line) {
                                            $line = trim($line);
                                            if(empty($line)) continue;
                                            
                                            // Check for "Speaker X:" or "Agent:" or "Customer:" format
                                            if(preg_match('/^(Speaker \d+|Agent|Customer|Kunde|User):\s*(.+)$/i', $line, $matches)) {
                                                $speaker = $matches[1];
                                                $content = $matches[2];
                                                $isAgent = stripos($speaker, 'agent') !== false || stripos($speaker, 'speaker 0') !== false;
                                                $parsedMessages[] = [
                                                    'speaker' => $speaker,
                                                    'content' => $content,
                                                    'isAgent' => $isAgent
                                                ];
                                            } else {
                                                // Line without speaker prefix - add to previous message or create new
                                                if(!empty($parsedMessages)) {
                                                    $parsedMessages[count($parsedMessages) - 1]['content'] .= ' ' . $line;
                                                } else {
                                                    $parsedMessages[] = [
                                                        'speaker' => 'Unbekannt',
                                                        'content' => $line,
                                                        'isAgent' => false
                                                    ];
                                                }
                                            }
                                        }
                                    @endphp
                                    
                                    @if(count($parsedMessages) > 0)
                                        {{-- Display parsed messages --}}
                                        @foreach($parsedMessages as $msg)
                                            <div class="flex {{ $msg['isAgent'] ? 'justify-start' : 'justify-end' }}">
                                                <div class="max-w-xs lg:max-w-md {{ $msg['isAgent'] ? 'bg-white' : 'bg-blue-100' }} rounded-lg px-4 py-2 shadow">
                                                    <p class="text-sm font-medium {{ $msg['isAgent'] ? 'text-gray-900' : 'text-blue-900' }} mb-1">
                                                        {{ $msg['isAgent'] ? 'Agent' : 'Kunde' }}
                                                    </p>
                                                    <p class="text-sm text-gray-700">{{ $msg['content'] }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        {{-- Fallback: Display as plain text --}}
                                        <div class="bg-white rounded-lg p-4">
                                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $transcriptData }}</p>
                                        </div>
                                    @endif
                                @else
                                    <p class="text-sm text-gray-500">Transkript-Format nicht erkannt ({{ gettype($transcriptData) }})</p>
                                @endif
                            </div>
                            
                            {{-- Debug info for transcript source --}}
                            @if(config('app.debug'))
                            <div class="mt-2 text-xs text-gray-500">
                                Transcript source: {{ $transcriptSource }}
                            </div>
                            @endif
                        </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-500">Kein Gesprächsverlauf verfügbar</p>
                                @if(config('app.debug'))
                                    <p class="text-xs text-gray-400 mt-2">
                                        Debug: transcript field has {{ $call->transcript ? strlen($call->transcript) : 0 }} chars
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Audio Player --}}
                <x-call-audio-player :call="$call" />
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
                                id="add_note_button"
                                name="add_note"
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
                                id="schedule_callback_button"
                                name="schedule_callback"
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

                {{-- Enhanced Notes System --}}
                @if($call->callNotes && $call->callNotes->count() > 0)
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Notizen & Aktivitäten</h3>
                    </div>
                    <div class="p-6">
                        @php
                            // Separate regular notes from activity tracking
                            $regularNotes = $call->callNotes->filter(function($note) {
                                return !str_starts_with($note->content, '[Portal-Aktivität]');
                            });
                            $activityNotes = $call->callNotes->filter(function($note) {
                                return str_starts_with($note->content, '[Portal-Aktivität]');
                            });
                        @endphp
                        
                        {{-- Tabs for notes and activities --}}
                        <div x-data="{ activeTab: 'notes' }" class="space-y-4">
                            <div class="border-b border-gray-200">
                                <nav class="-mb-px flex space-x-8">
                                    <button id="notes_tab_button"
                                            name="notes_tab"
                                            type="button"
                                            @click="activeTab = 'notes'"
                                            :class="activeTab === 'notes' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                        Notizen ({{ $regularNotes->count() }})
                                    </button>
                                    <button id="activities_tab_button"
                                            name="activities_tab"
                                            type="button"
                                            @click="activeTab = 'activities'"
                                            :class="activeTab === 'activities' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                        Portal-Aktivitäten ({{ $activityNotes->count() }})
                                    </button>
                                </nav>
                            </div>
                            
                            {{-- Regular Notes --}}
                            <div x-show="activeTab === 'notes'" class="space-y-3">
                                @if($regularNotes->count() > 0)
                                    @foreach($regularNotes as $note)
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
                                            @if($note->type && $note->type !== 'general')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-800 mt-2">
                                                    {{ match($note->type) {
                                                        'customer_feedback' => 'Kundenfeedback',
                                                        'internal' => 'Intern',
                                                        'action_required' => 'Aktion erforderlich',
                                                        'status_change' => 'Statusänderung',
                                                        'assignment' => 'Zuweisung',
                                                        'callback_scheduled' => 'Rückruf geplant',
                                                        default => ucfirst($note->type)
                                                    } }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-500">Keine Notizen vorhanden</p>
                                @endif
                            </div>
                            
                            {{-- Activity Tracking --}}
                            <div x-show="activeTab === 'activities'" class="space-y-3">
                                @if($activityNotes->count() > 0)
                                    @foreach($activityNotes->sortByDesc('created_at') as $activity)
                                        <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                                            <div class="flex items-center justify-between mb-1">
                                                <p class="text-sm font-medium text-blue-900">
                                                    {{ optional($activity->user)->name ?? 'System' }}
                                                </p>
                                                <p class="text-xs text-blue-600">
                                                    {{ $activity->created_at ? $activity->created_at->format('d.m.Y H:i:s') : '' }}
                                                </p>
                                            </div>
                                            <p class="text-sm text-blue-800">
                                                {{ str_replace('[Portal-Aktivität] ', '', $activity->content) }}
                                            </p>
                                            @if($activity->metadata)
                                                <div class="mt-2 text-xs text-blue-600 space-y-0.5">
                                                    @if(isset($activity->metadata['ip_address']))
                                                        <p>IP: {{ $activity->metadata['ip_address'] }}</p>
                                                    @endif
                                                    @if(isset($activity->metadata['user_agent']))
                                                        <p class="truncate" title="{{ $activity->metadata['user_agent'] }}">
                                                            Browser: {{ Str::limit($activity->metadata['user_agent'], 50) }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-500">Keine Portal-Aktivitäten aufgezeichnet</p>
                                @endif
                            </div>
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
                
                {{-- Activity Timeline --}}
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Aktivitätsverlauf</h3>
                    </div>
                    <div class="p-4 max-h-96 overflow-y-auto" style="max-height: 400px;">
                        @php
                            // Collect all activities for timeline
                            $activities = collect();
                            
                            // Call created
                            $activities->push((object)[
                                'type' => 'call_created',
                                'title' => 'Anruf eingegangen',
                                'description' => 'Anruf von ' . ($call->phone_number ?? $call->from_number),
                                'timestamp' => $call->created_at,
                                'icon' => 'phone',
                                'color' => 'blue'
                            ]);
                            
                            // Call ended
                            if ($call->duration_sec) {
                                $activities->push((object)[
                                    'type' => 'call_ended',
                                    'title' => 'Anruf beendet',
                                    'description' => 'Dauer: ' . gmdate('i:s', $call->duration_sec) . ' Minuten',
                                    'timestamp' => $call->created_at->addSeconds($call->duration_sec),
                                    'icon' => 'phone-x',
                                    'color' => 'gray'
                                ]);
                            }
                            
                            // Status changes - initial status
                            if ($call->callPortalData) {
                                $activities->push((object)[
                                    'type' => 'status_change',
                                    'title' => 'Status gesetzt',
                                    'description' => 'Status: Neu',
                                    'timestamp' => $call->callPortalData->created_at ?? $call->created_at,
                                    'icon' => 'status',
                                    'color' => 'blue'
                                ]);
                            }
                            
                            // Assignment
                            if ($call->callPortalData && $call->callPortalData->assigned_to) {
                                $activities->push((object)[
                                    'type' => 'assigned',
                                    'title' => 'Zugewiesen',
                                    'description' => 'An ' . ($call->callPortalData->assignedTo->name ?? 'Unbekannt'),
                                    'timestamp' => $call->callPortalData->updated_at ?? $call->created_at,
                                    'icon' => 'user',
                                    'color' => 'green'
                                ]);
                            }
                            
                            // Notes added
                            if ($call->callNotes) {
                                foreach ($call->callNotes as $note) {
                                    $activities->push((object)[
                                        'type' => 'note_added',
                                        'title' => 'Notiz hinzugefügt',
                                        'description' => Str::limit($note->content, 100),
                                        'by' => optional($note->user)->name ?? 'System',
                                        'timestamp' => $note->created_at,
                                        'icon' => 'note',
                                        'color' => 'yellow'
                                    ]);
                                }
                            }
                            
                            // Appointment requested
                            if ($call->appointment_requested) {
                                $activities->push((object)[
                                    'type' => 'appointment_requested',
                                    'title' => 'Terminwunsch geäußert',
                                    'description' => 'Kunde möchte einen Termin vereinbaren',
                                    'timestamp' => $call->created_at->addSeconds(30), // Approximate
                                    'icon' => 'calendar',
                                    'color' => 'purple'
                                ]);
                            }
                            
                            // Portal access
                            $activities->push((object)[
                                'type' => 'portal_viewed',
                                'title' => 'Im Portal angezeigt',
                                'description' => 'Anruf wurde im Business Portal aufgerufen',
                                'timestamp' => now(),
                                'icon' => 'eye',
                                'color' => 'gray'
                            ]);
                            
                            // Sort by timestamp descending
                            $activities = $activities->sortByDesc('timestamp');
                        @endphp
                        
                        @if($activities->count() > 0)
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($activities as $index => $activity)
                                <li>
                                    <div class="relative pb-6">
                                        @if(!$loop->last)
                                        <span class="absolute left-4 top-8 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div class="flex-shrink-0">
                                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white {{ 
                                                    $activity->color === 'blue' ? 'bg-blue-500' : 
                                                    ($activity->color === 'green' ? 'bg-green-500' : 
                                                    ($activity->color === 'yellow' ? 'bg-yellow-500' : 
                                                    ($activity->color === 'purple' ? 'bg-purple-500' : 
                                                    ($activity->color === 'red' ? 'bg-red-500' : 'bg-gray-400')))) 
                                                }}">
                                                    @switch($activity->icon)
                                                        @case('phone')
                                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                            </svg>
                                                            @break
                                                        @case('phone-x')
                                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                            </svg>
                                                            @break
                                                        @case('user')
                                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                            </svg>
                                                            @break
                                                        @case('note')
                                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                            </svg>
                                                            @break
                                                        @case('calendar')
                                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            @break
                                                        @case('eye')
                                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                            @break
                                                        @default
                                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                    @endswitch
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-0.5">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">{{ $activity->title }}</p>
                                                    <p class="text-sm text-gray-500">{{ $activity->description }}</p>
                                                    @if(isset($activity->by))
                                                    <p class="text-xs text-gray-400 mt-0.5">von {{ $activity->by }}</p>
                                                    @endif
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    <time datetime="{{ $activity->timestamp ? $activity->timestamp->toIso8601String() : '' }}">
                                                        {{ $activity->timestamp ? $activity->timestamp->diffForHumans() : '' }}
                                                        <span class="text-gray-400">• {{ $activity->timestamp ? $activity->timestamp->format('d.m.Y H:i') : '' }}</span>
                                                    </time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @else
                        <p class="text-sm text-gray-500 text-center">Keine Aktivitäten vorhanden</p>
                        @endif
                    </div>
                </div>
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
                                id="cancel_note_button"
                                name="cancel_note"
                                onclick="document.getElementById('add-note-form').classList.add('hidden')"
                                class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Abbrechen
                        </button>
                        <button type="submit" 
                                id="submit_note_button"
                                name="submit_note"
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
                                      placeholder="Notizen zum geplanten Rückruf..."></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" 
                                id="cancel_callback_button"
                                name="cancel_callback"
                                onclick="document.getElementById('schedule-callback-form').classList.add('hidden')"
                                class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Abbrechen
                        </button>
                        <button type="submit" 
                                id="submit_callback_button"
                                name="submit_callback"
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

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('callTranscriptViewer', () => ({
        translating: false,
        translated: false,
        translatedText: '',
        originalText: @js($summaryText ?? ''),
        
        async translateSummary() {
            this.translating = true;
            try {
                const response = await fetch('{{ route('business.calls.translate', $call->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        text: this.originalText,
                        target_language: 'de'
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.translatedText = data.translated_text;
                    this.translated = true;
                } else {
                    throw new Error('Translation failed');
                }
            } catch (error) {
                console.error('Translation error:', error);
                window.dispatchEvent(new CustomEvent('notify', { 
                    detail: { message: 'Übersetzung fehlgeschlagen. Bitte versuchen Sie es später erneut.', type: 'error' } 
                }));
            } finally {
                this.translating = false;
            }
        }
    }));
});
</script>
@endpush

@push('styles')
<style>
@media print {
    /* Hide navigation and non-essential elements */
    nav, 
    .no-print,
    button,
    form,
    .bg-gradient-to-r,
    [x-data],
    [x-show],
    svg {
        display: none !important;
    }
    
    /* Show print-specific elements */
    .print-only {
        display: block !important;
    }
    
    /* Reset backgrounds and colors for better printing */
    body {
        background: white !important;
        color: black !important;
    }
    
    .bg-white,
    .bg-gray-50,
    .bg-gray-100 {
        background: white !important;
    }
    
    /* Better text contrast */
    .text-gray-500,
    .text-gray-600,
    .text-gray-700,
    .text-gray-900 {
        color: black !important;
    }
    
    /* Page breaks */
    .page-break-before {
        page-break-before: always;
    }
    
    .page-break-after {
        page-break-after: always;
    }
    
    .avoid-break {
        page-break-inside: avoid;
    }
    
    /* Layout adjustments */
    .max-w-7xl {
        max-width: 100% !important;
    }
    
    .shadow,
    .shadow-sm,
    .shadow-md,
    .shadow-lg,
    .shadow-xl {
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
    }
    
    /* Headers */
    h1, h2, h3 {
        color: black !important;
        font-weight: bold !important;
    }
    
    /* Cost display - keep visible and clear */
    .bg-gradient-to-r.from-indigo-50.to-blue-50 {
        display: block !important;
        background: #f3f4f6 !important;
        border: 1px solid #000 !important;
        padding: 10px !important;
    }
    
    /* Hide tooltips */
    [x-show="showTooltip"] {
        display: none !important;
    }
    
    /* Show phone numbers as text */
    a[href^="tel:"] {
        text-decoration: none !important;
        color: black !important;
    }
    
    /* Status badges */
    .bg-green-100,
    .bg-blue-100,
    .bg-yellow-100,
    .bg-purple-100,
    .bg-red-100 {
        background: white !important;
        border: 1px solid #000 !important;
        color: black !important;
        padding: 2px 8px !important;
    }
    
    /* Ensure transcript is visible */
    .prose,
    .whitespace-pre-wrap {
        white-space: pre-wrap !important;
        max-height: none !important;
        overflow: visible !important;
    }
    
    /* Page margins */
    @page {
        margin: 2cm;
        size: A4;
    }
    
    /* Header info */
    .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }
}

/* Add print header that's hidden on screen */
.print-header {
    display: none;
}
</style>

<script>
// Fix for auto-opening dropdowns and tooltips
document.addEventListener('DOMContentLoaded', () => {
    console.log('🔧 Post-load dropdown fix running...');
    
    // Function to close all dropdowns
    const closeAllDropdowns = () => {
        console.log('🔍 Looking for dropdowns to close...');
        
        // Find all Alpine components
        document.querySelectorAll('[x-data]').forEach(el => {
            if (el.__x && el.__x.$data) {
                const data = el.__x.$data;
                let changed = false;
                
                // Close any open states
                if ('showTooltip' in data && data.showTooltip) {
                    data.showTooltip = false;
                    changed = true;
                    console.log('❌ Closed tooltip');
                }
                if ('isOpen' in data && data.isOpen) {
                    data.isOpen = false;
                    changed = true;
                    console.log('❌ Closed isOpen dropdown');
                }
                if ('dropdownOpen' in data && data.dropdownOpen) {
                    data.dropdownOpen = false;
                    changed = true;
                    console.log('❌ Closed dropdownOpen dropdown');
                }
                if ('open' in data && data.open) {
                    data.open = false;
                    changed = true;
                    console.log('❌ Closed open dropdown');
                }
                
                if (changed) {
                    console.log('Closed element:', el);
                }
            }
        });
        
        // Also force close any visible dropdowns via DOM
        document.querySelectorAll('[x-show*="dropdown"], [x-show*="tooltip"]').forEach(el => {
            if (el.style.display !== 'none') {
                el.style.display = 'none';
                console.log('❌ Force hid element via DOM:', el);
            }
        });
    };
    
    // Run multiple times to catch late initializations
    closeAllDropdowns();
    setTimeout(closeAllDropdowns, 100);
    setTimeout(closeAllDropdowns, 300);
    setTimeout(closeAllDropdowns, 500);
    setTimeout(closeAllDropdowns, 1000);
});

// Also listen for Alpine initialization
document.addEventListener('alpine:initialized', () => {
    console.log('🔧 Alpine initialized - closing all dropdowns');
    setTimeout(() => {
        document.querySelectorAll('[x-data]').forEach(el => {
            if (el.__x && el.__x.$data) {
                const data = el.__x.$data;
                if ('showTooltip' in data) data.showTooltip = false;
                if ('isOpen' in data) data.isOpen = false;
                if ('dropdownOpen' in data) data.dropdownOpen = false;
                if ('open' in data) data.open = false;
            }
        });
    }, 50);
});
</script>
@endpush