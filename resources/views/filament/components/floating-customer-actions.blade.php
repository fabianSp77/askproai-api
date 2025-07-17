@php
    $record = $record ?? null;
    $customer = $customer ?? $record?->customer ?? null;
    $phone = $phone ?? $customer?->phone ?? $record?->from_number ?? null;
    $email = $email ?? $customer?->email ?? null;
    $customerId = $customer?->id ?? null;
    $customerName = $customer?->name ?? $record?->extracted_name ?? 'Unbekannter Kunde';
@endphp

@if($phone || $email || $customerId)
<div x-data="floatingActions()" 
     x-show="showActions"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-x-full"
     x-transition:enter-end="opacity-100 transform translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform translate-x-0"
     x-transition:leave-end="opacity-0 transform translate-x-full"
     class="fixed bottom-6 right-6 flex flex-col gap-3 z-40"
     @scroll.window="handleScroll">
     
    {{-- Minimize/Maximize Button --}}
    <button @click="toggleMinimized"
            class="self-end w-10 h-10 rounded-full bg-gray-800 dark:bg-gray-700 text-white flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-200"
            :title="minimized ? 'Aktionen anzeigen' : 'Aktionen minimieren'">
        <svg x-show="!minimized" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
        <svg x-show="minimized" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
        </svg>
    </button>
    
    <div x-show="!minimized"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="flex flex-col gap-3">
         
        {{-- Customer Info Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-3 min-w-[200px]">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Schnellaktionen für</p>
            <p class="font-medium text-gray-900 dark:text-white">{{ $customerName }}</p>
        </div>
        
        {{-- Action Buttons --}}
        @if($phone)
            <div class="relative group">
                <a href="tel:{{ $phone }}" 
                   class="w-14 h-14 rounded-full bg-green-500 hover:bg-green-600 text-white shadow-lg hover:shadow-xl transform hover:scale-110 transition-all duration-200 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                </a>
                <span class="absolute right-full mr-3 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    Anrufen
                </span>
            </div>
        @endif
        
        @if($email)
            <div class="relative group">
                <a href="mailto:{{ $email }}" 
                   class="w-14 h-14 rounded-full bg-blue-500 hover:bg-blue-600 text-white shadow-lg hover:shadow-xl transform hover:scale-110 transition-all duration-200 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </a>
                <span class="absolute right-full mr-3 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    E-Mail senden
                </span>
            </div>
        @endif
        
        <div class="relative group">
            <button onclick="window.livewire.emit('openModal', 'create-appointment-modal', {{ json_encode(['customer_id' => $customerId, 'phone' => $phone]) }})"
                    class="w-14 h-14 rounded-full bg-purple-500 hover:bg-purple-600 text-white shadow-lg hover:shadow-xl transform hover:scale-110 transition-all duration-200 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </button>
            <span class="absolute right-full mr-3 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                Termin erstellen
            </span>
        </div>
        
        @if($customer)
            <div class="relative group">
                <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', [$customer]) }}"
                   class="w-14 h-14 rounded-full bg-indigo-500 hover:bg-indigo-600 text-white shadow-lg hover:shadow-xl transform hover:scale-110 transition-all duration-200 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </a>
                <span class="absolute right-full mr-3 top-1/2 -translate-y-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    Kundenprofil
                </span>
            </div>
        @endif
    </div>
</div>

<script>
function floatingActions() {
    return {
        showActions: true,
        minimized: false,
        lastScrollY: 0,
        
        toggleMinimized() {
            this.minimized = !this.minimized;
            localStorage.setItem('floatingActionsMinimized', this.minimized);
        },
        
        handleScroll() {
            const currentScrollY = window.scrollY;
            
            // Hide when scrolling down, show when scrolling up
            if (currentScrollY > this.lastScrollY && currentScrollY > 100) {
                this.showActions = false;
            } else {
                this.showActions = true;
            }
            
            this.lastScrollY = currentScrollY;
        },
        
        init() {
            // Restore minimized state
            this.minimized = localStorage.getItem('floatingActionsMinimized') === 'true';
        }
    }
}
</script>
@endif