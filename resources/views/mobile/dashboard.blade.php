<x-mobile.layout>
    <x-slot name="pageTitle">Dashboard</x-slot>
    
    <x-slot name="headerRight">
        <button class="touch-target p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
        </button>
    </x-slot>
    
    <div class="mobile-content-container">
        <!-- Stats Cards (Swipeable) -->
        <div class="mobile-scroll-x flex gap-4 p-4 -mx-4 px-4">
            <!-- Today's Appointments -->
            <div class="mobile-card min-w-[280px] bg-gradient-to-br from-amber-400 to-amber-600 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-amber-100 text-sm">Today's Appointments</p>
                        <p class="text-3xl font-bold mt-2">12</p>
                        <p class="text-amber-100 text-sm mt-1">3 upcoming</p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Calls Today -->
            <div class="mobile-card min-w-[280px] bg-gradient-to-br from-blue-400 to-blue-600 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm">Calls Today</p>
                        <p class="text-3xl font-bold mt-2">24</p>
                        <p class="text-blue-100 text-sm mt-1">18 answered</p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- New Customers -->
            <div class="mobile-card min-w-[280px] bg-gradient-to-br from-green-400 to-green-600 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm">New Customers</p>
                        <p class="text-3xl font-bold mt-2">8</p>
                        <p class="text-green-100 text-sm mt-1">This week</p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="p-4">
            <h2 class="text-lg font-semibold mb-3">Quick Actions</h2>
            <div class="grid grid-cols-2 gap-3">
                <button class="mobile-button mobile-button-primary flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Appointment
                </button>
                
                <button class="mobile-button mobile-button-secondary flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    View Calls
                </button>
            </div>
        </div>
        
        <!-- Today's Schedule -->
        <div class="p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold">Today's Schedule</h2>
                <a href="/mobile/appointments" class="text-amber-600 text-sm font-medium">View All</a>
            </div>
            
            <div class="mobile-list bg-white rounded-lg overflow-hidden">
                @foreach(range(1, 5) as $i)
                <div class="mobile-list-item">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-amber-600 font-semibold">{{ substr(fake()->name(), 0, 1) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ fake()->name() }}</p>
                            <p class="text-sm text-gray-500">{{ fake()->randomElement(['Haircut', 'Color', 'Styling', 'Treatment']) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">{{ fake()->time('H:i') }}</p>
                            <p class="text-xs text-gray-500">30 min</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- Recent Calls -->
        <div class="p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold">Recent Calls</h2>
                <a href="/mobile/calls" class="text-amber-600 text-sm font-medium">View All</a>
            </div>
            
            <div class="space-y-3">
                @foreach(range(1, 3) as $i)
                <div class="mobile-card p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                <p class="font-medium text-gray-900">{{ fake()->phoneNumber() }}</p>
                            </div>
                            <p class="text-sm text-gray-600">{{ fake()->randomElement(['Appointment booked', 'General inquiry', 'Rescheduled']) }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ fake()->time('H:i') }} - Duration: {{ fake()->numberBetween(1, 5) }}:{{ fake()->numberBetween(10, 59) }}</p>
                        </div>
                        <button class="touch-target p-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Floating Action Button -->
    <x-slot name="fab">
        <button class="fab" onclick="openNewAppointmentModal()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
        </button>
    </x-slot>
    
    <x-slot name="scripts">
        <script>
            function openNewAppointmentModal() {
                // Mobile-optimized modal logic
                console.log('Opening new appointment modal');
            }
        </script>
    </x-slot>
</x-mobile.layout>