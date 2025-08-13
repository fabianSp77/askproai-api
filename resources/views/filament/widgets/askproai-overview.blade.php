<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- Today's Calls Card -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-sky-500 to-sky-600 p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-sky-100">Heutige Anrufe</p>
                        <p class="mt-2 text-3xl font-bold">{{ $todayCalls }}</p>
                    </div>
                    <svg class="h-12 w-12 text-sky-200 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <span>+{{ $weeklyGrowth }}% diese Woche</span>
                </div>
            </div>

            <!-- Today's Appointments Card -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-100">Heutige Termine</p>
                        <p class="mt-2 text-3xl font-bold">{{ $todayAppointments }}</p>
                    </div>
                    <svg class="h-12 w-12 text-emerald-200 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Bestätigt & bereit</span>
                </div>
            </div>

            <!-- Total Customers Card -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-purple-100">Kunden gesamt</p>
                        <p class="mt-2 text-3xl font-bold">{{ $totalCustomers }}</p>
                    </div>
                    <svg class="h-12 w-12 text-purple-200 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Aktive Nutzer</span>
                </div>
            </div>

            <!-- AI Performance Card -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-500 to-rose-600 p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-rose-100">AI Erfolgsrate</p>
                        <p class="mt-2 text-3xl font-bold">94%</p>
                    </div>
                    <svg class="h-12 w-12 text-rose-200 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Retell.ai aktiv</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>