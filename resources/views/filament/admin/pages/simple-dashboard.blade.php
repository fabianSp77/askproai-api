<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Appointments Today --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Termine heute</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $appointmentsToday }}</p>
                </div>
                <x-heroicon-o-calendar class="w-8 h-8 text-primary-500" />
            </div>
        </x-filament::section>
        
        {{-- Total Customers --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kunden gesamt</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $totalCustomers }}</p>
                </div>
                <x-heroicon-o-users class="w-8 h-8 text-success-500" />
            </div>
        </x-filament::section>
        
        {{-- Calls Today --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Anrufe heute</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $activeCalls }}</p>
                </div>
                <x-heroicon-o-phone class="w-8 h-8 text-warning-500" />
            </div>
        </x-filament::section>
        
        {{-- Active Staff --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktive Mitarbeiter</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $activeStaff }}</p>
                </div>
                <x-heroicon-o-user-group class="w-8 h-8 text-info-500" />
            </div>
        </x-filament::section>
    </div>
    
    {{-- Quick Links --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Schnellzugriff
        </x-slot>
        
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <a href="/admin/appointments" class="text-center p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <x-heroicon-o-calendar-days class="w-8 h-8 mx-auto mb-2 text-primary-500" />
                <span class="text-sm font-medium">Termine</span>
            </a>
            
            <a href="/admin/customers" class="text-center p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <x-heroicon-o-user-plus class="w-8 h-8 mx-auto mb-2 text-success-500" />
                <span class="text-sm font-medium">Kunden</span>
            </a>
            
            <a href="/admin/staff" class="text-center p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <x-heroicon-o-user-group class="w-8 h-8 mx-auto mb-2 text-info-500" />
                <span class="text-sm font-medium">Mitarbeiter</span>
            </a>
            
            <a href="/admin/calls" class="text-center p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <x-heroicon-o-phone class="w-8 h-8 mx-auto mb-2 text-warning-500" />
                <span class="text-sm font-medium">Anrufe</span>
            </a>
            
            <a href="/admin/companies" class="text-center p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <x-heroicon-o-building-office class="w-8 h-8 mx-auto mb-2 text-gray-500" />
                <span class="text-sm font-medium">Unternehmen</span>
            </a>
            
            <a href="/admin/services" class="text-center p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <x-heroicon-o-briefcase class="w-8 h-8 mx-auto mb-2 text-purple-500" />
                <span class="text-sm font-medium">Services</span>
            </a>
        </div>
    </x-filament::section>
</x-filament-panels::page>