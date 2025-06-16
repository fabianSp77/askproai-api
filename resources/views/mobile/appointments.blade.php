<x-mobile.layout>
    <x-slot name="pageTitle">Appointments</x-slot>
    
    <x-slot name="headerLeft">
        <button class="touch-target p-2" onclick="previousWeek()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>
    </x-slot>
    
    <x-slot name="headerRight">
        <button class="touch-target p-2" onclick="nextWeek()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </x-slot>
    
    <div class="mobile-content-container">
        <!-- Calendar View Toggle -->
        <div class="p-4 pb-0">
            <div class="bg-gray-100 rounded-lg p-1 flex">
                <button class="flex-1 py-2 px-4 rounded-md transition-all calendar-view-btn active" data-view="week">
                    Week
                </button>
                <button class="flex-1 py-2 px-4 rounded-md transition-all calendar-view-btn" data-view="day">
                    Day
                </button>
                <button class="flex-1 py-2 px-4 rounded-md transition-all calendar-view-btn" data-view="list">
                    List
                </button>
            </div>
        </div>
        
        <!-- Week View -->
        <div id="weekView" class="calendar-view">
            <!-- Week Header -->
            <div class="p-4">
                <p class="text-center text-gray-600 mb-3" id="currentWeek">{{ now()->format('M d - ') . now()->addDays(6)->format('M d, Y') }}</p>
                
                <!-- Days of Week -->
                <div class="grid grid-cols-7 gap-1 mb-3">
                    @foreach(['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $day)
                    <div class="text-center text-xs text-gray-500 font-medium">{{ $day }}</div>
                    @endforeach
                </div>
                
                <!-- Date Grid -->
                <div class="grid grid-cols-7 gap-1">
                    @foreach(range(0, 6) as $i)
                    <button class="aspect-square rounded-lg border {{ $i === now()->dayOfWeek ? 'bg-amber-500 text-white border-amber-500' : 'bg-white border-gray-200' }} flex flex-col items-center justify-center touch-target relative">
                        <span class="text-sm font-medium">{{ now()->startOfWeek()->addDays($i)->format('d') }}</span>
                        @if($i % 3 === 0)
                        <span class="absolute bottom-1 w-1 h-1 bg-amber-600 rounded-full"></span>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
            
            <!-- Appointments for Selected Day -->
            <div class="px-4 pb-4">
                <h3 class="font-semibold mb-3">Today's Appointments</h3>
                <div class="space-y-2">
                    @foreach(range(1, 4) as $i)
                    <div class="swipeable-item">
                        <div class="swipeable-actions bg-red-500 text-white">
                            <button class="px-4">Cancel</button>
                        </div>
                        <div class="swipeable-content bg-white rounded-lg p-4 border border-gray-200 touch-ripple">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ fake()->name() }}</p>
                                    <p class="text-sm text-gray-600">{{ fake()->randomElement(['Haircut', 'Color', 'Styling']) }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ fake()->time('H:i') }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            {{ fake()->randomElement(['John', 'Sarah', 'Mike']) }}
                                        </span>
                                    </div>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full {{ $i === 1 ? 'bg-green-100 text-green-800' : ($i === 2 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ $i === 1 ? 'Confirmed' : ($i === 2 ? 'Pending' : 'Completed') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- Day View -->
        <div id="dayView" class="calendar-view hidden">
            <div class="p-4">
                <p class="text-center text-lg font-semibold mb-4">{{ now()->format('l, F d, Y') }}</p>
                
                <!-- Time Slots -->
                <div class="space-y-1">
                    @foreach(range(8, 19) as $hour)
                    <div class="flex">
                        <div class="w-16 text-xs text-gray-500 py-2">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00</div>
                        <div class="flex-1 border-t border-gray-200 relative min-h-[60px]">
                            @if($hour === 10 || $hour === 14 || $hour === 16)
                            <div class="absolute inset-0 bg-amber-100 border-l-4 border-amber-500 p-2">
                                <p class="text-sm font-medium">{{ fake()->name() }}</p>
                                <p class="text-xs text-gray-600">{{ fake()->randomElement(['Haircut', 'Color']) }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- List View -->
        <div id="listView" class="calendar-view hidden">
            <div class="p-4">
                @foreach(range(0, 6) as $day)
                <div class="mb-6">
                    <p class="font-semibold text-gray-900 mb-2">{{ now()->addDays($day)->format('l, M d') }}</p>
                    <div class="space-y-2">
                        @if($day % 2 === 0)
                        @foreach(range(1, rand(2, 4)) as $i)
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium">{{ fake()->name() }}</p>
                                    <p class="text-sm text-gray-600">{{ fake()->time('H:i') }} - {{ fake()->randomElement(['Haircut', 'Color', 'Styling']) }}</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                        @endforeach
                        @else
                        <p class="text-gray-500 text-sm py-4 text-center">No appointments</p>
                        @endif
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
            // Calendar view switching
            document.querySelectorAll('.calendar-view-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Update active button
                    document.querySelectorAll('.calendar-view-btn').forEach(b => {
                        b.classList.remove('active', 'bg-white', 'shadow-sm');
                    });
                    this.classList.add('active', 'bg-white', 'shadow-sm');
                    
                    // Show corresponding view
                    document.querySelectorAll('.calendar-view').forEach(view => {
                        view.classList.add('hidden');
                    });
                    document.getElementById(this.dataset.view + 'View').classList.remove('hidden');
                });
            });
            
            // Swipe to delete appointments
            let startX = null;
            let currentX = null;
            let swipeItem = null;
            
            document.querySelectorAll('.swipeable-item').forEach(item => {
                const content = item.querySelector('.swipeable-content');
                
                content.addEventListener('touchstart', function(e) {
                    startX = e.touches[0].clientX;
                    swipeItem = item;
                    item.classList.add('swiping');
                });
                
                content.addEventListener('touchmove', function(e) {
                    if (!startX) return;
                    
                    currentX = e.touches[0].clientX;
                    const diff = startX - currentX;
                    
                    if (diff > 0 && diff < 100) {
                        content.style.transform = `translateX(-${diff}px)`;
                    }
                });
                
                content.addEventListener('touchend', function(e) {
                    if (!startX || !currentX) return;
                    
                    const diff = startX - currentX;
                    
                    if (diff > 50) {
                        content.style.transform = 'translateX(-100px)';
                    } else {
                        content.style.transform = 'translateX(0)';
                    }
                    
                    item.classList.remove('swiping');
                    startX = null;
                    currentX = null;
                });
            });
            
            function previousWeek() {
                console.log('Previous week');
            }
            
            function nextWeek() {
                console.log('Next week');
            }
            
            function openNewAppointmentModal() {
                console.log('Opening new appointment modal');
            }
        </script>
        
        <style>
            .calendar-view-btn.active {
                background-color: white;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            }
        </style>
    </x-slot>
</x-mobile.layout>