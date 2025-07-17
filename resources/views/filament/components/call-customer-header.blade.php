@php
    $record = $record ?? null;
    $customer = $record?->customer;
    $customerName = $customer?->name ?? $record->extracted_name ?? 'Unbekannter Anrufer';
    $phone = $record->from_number;
    $email = $customer?->email;
    
    // Get initials for avatar
    $nameParts = explode(' ', $customerName);
    $initials = '';
    if (count($nameParts) >= 2) {
        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
    } else {
        $initials = strtoupper(substr($customerName, 0, 2));
    }
    
    // Customer stats
    $totalCalls = $customer ? $customer->calls()->count() : 1;
    $totalAppointments = $customer ? $customer->appointments()->count() : 0;
    $customerSince = $customer?->created_at;
    $isNewCustomer = !$customer || $customer->created_at->diffInDays(now()) < 30;
@endphp

<div class="customer-header-modern bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    {{-- Main Header Section --}}
    <div class="p-6">
        <div class="flex items-start gap-6">
            {{-- Customer Avatar --}}
            <div class="flex-shrink-0">
                <div class="relative">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white shadow-lg">
                        <span class="text-2xl font-semibold">{{ $initials }}</span>
                    </div>
                    @if($isNewCustomer)
                        <div class="absolute -top-1 -right-1">
                            <span class="flex h-6 w-6 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-6 w-6 bg-green-500 items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </span>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Customer Info --}}
            <div class="flex-1">
                {{-- Name and Status --}}
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $customerName }}
                    </h1>
                    @if($isNewCustomer)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Neukunde
                        </span>
                    @endif
                </div>
                
                {{-- Contact Info --}}
                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-3">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <span class="font-medium">{{ $phone }}</span>
                    </div>
                    @if($email)
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span class="font-medium">{{ $email }}</span>
                        </div>
                    @endif
                    @if($customerSince)
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Kunde seit {{ $customerSince->format('d.m.Y') }}</span>
                        </div>
                    @endif
                </div>
                
                {{-- Stats --}}
                <div class="flex items-center gap-6">
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalCalls }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $totalCalls === 1 ? 'Anruf' : 'Anrufe' }}</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalAppointments }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $totalAppointments === 1 ? 'Termin' : 'Termine' }}</p>
                    </div>
                    @if($customer)
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($customer->lifetime_value ?? 0, 2) }}€
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Kundenwert</p>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Quick Actions --}}
            <div class="flex-shrink-0" x-data="{ showMenu: false }">
                <div class="flex flex-col gap-2">
                    {{-- Primary Actions --}}
                    <div class="flex items-center gap-2">
                        {{-- Call Button --}}
                        <a href="tel:{{ $phone }}" 
                           class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-500 hover:bg-green-600 text-white shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                           title="Anrufen">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </a>
                        
                        {{-- Email Button --}}
                        @if($email)
                            <a href="mailto:{{ $email }}" 
                               class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-500 hover:bg-blue-600 text-white shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                               title="E-Mail senden">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </a>
                        @endif
                        
                        {{-- Appointment Button --}}
                        <button onclick="window.livewire.emit('openModal', 'create-appointment-modal', {{ json_encode(['customer_id' => $customer?->id, 'phone' => $phone]) }})"
                                class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-500 hover:bg-purple-600 text-white shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                                title="Termin erstellen">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                    
                    {{-- Secondary Actions Menu --}}
                    <div class="relative">
                        <button @click="showMenu = !showMenu"
                                class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-colors duration-200"
                                title="Weitere Aktionen">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                            </svg>
                        </button>
                        
                        {{-- Dropdown Menu --}}
                        <div x-show="showMenu"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             @click.away="showMenu = false"
                             class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                @if($customer)
                                    <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', [$customer]) }}"
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Kundenprofil anzeigen
                                    </a>
                                    <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('edit', [$customer]) }}"
                                       class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Kunde bearbeiten
                                    </a>
                                @else
                                    <button onclick="window.livewire.emit('openModal', 'create-customer-modal', {{ json_encode(['phone' => $phone, 'name' => $customerName]) }})"
                                            class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                        </svg>
                                        Kunde anlegen
                                    </button>
                                @endif
                                
                                <hr class="my-1 border-gray-200 dark:border-gray-700">
                                
                                <button onclick="navigator.clipboard.writeText('{{ $phone }}')"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Nummer kopieren
                                </button>
                                
                                @if($email)
                                    <button onclick="navigator.clipboard.writeText('{{ $email }}')"
                                            class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                        E-Mail kopieren
                                    </button>
                                @endif
                                
                                <hr class="my-1 border-gray-200 dark:border-gray-700">
                                
                                <button onclick="window.livewire.emit('openModal', 'add-note-modal', {{ json_encode(['customer_id' => $customer?->id]) }})"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 w-full text-left">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Notiz hinzufügen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Tags/Labels Section --}}
    @if($customer && ($customer->tags->count() > 0 || $customer->labels->count() > 0))
        <div class="px-6 pb-4">
            <div class="flex items-center gap-2 flex-wrap">
                @foreach($customer->tags as $tag)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                        {{ $tag->name }}
                    </span>
                @endforeach
                @foreach($customer->labels as $label)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                          style="background-color: {{ $label->color }}20; color: {{ $label->color }};">
                        {{ $label->name }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>

<style>
.customer-header-modern {
    position: relative;
    overflow: visible;
}

.customer-header-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 120px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0.1;
    z-index: 0;
}

.customer-header-modern > * {
    position: relative;
    z-index: 1;
}
</style>