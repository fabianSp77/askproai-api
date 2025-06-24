<x-filament-panels::page>
    @vite(['resources/css/filament/admin/ultra-appointments.css'])
    
    {{-- Header with View Switcher --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📅 Appointment Command Center</h1>
            <div class="flex items-center gap-2">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-1 dark:bg-gray-800 dark:border-gray-700">
                    <button onclick="switchView('calendar')" class="px-3 py-1.5 text-sm font-medium rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 view-btn active" data-view="calendar">
                        📅 Calendar
                    </button>
                    <button onclick="switchView('list')" class="px-3 py-1.5 text-sm font-medium rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 view-btn" data-view="list">
                        📋 List
                    </button>
                    <button onclick="switchView('timeline')" class="px-3 py-1.5 text-sm font-medium rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 view-btn" data-view="timeline">
                        📊 Timeline
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Statistics Cards --}}
    <div class="ultra-appointment-analytics">
        <div class="ultra-analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500">Today's Appointments</div>
                    <div class="mt-1 text-3xl font-bold">{{ $todayCount ?? 24 }}</div>
                    <div class="mt-2 flex items-center text-sm">
                        <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="text-green-600">+15% from yesterday</span>
                    </div>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="ultra-analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500">This Week</div>
                    <div class="mt-1 text-3xl font-bold">{{ $weekCount ?? 156 }}</div>
                    <div class="mt-2 flex items-center text-sm">
                        <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="text-green-600">+8% from last week</span>
                    </div>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="ultra-analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500">Confirmation Rate</div>
                    <div class="mt-1 text-3xl font-bold">{{ $confirmationRate ?? 92 }}%</div>
                    <div class="mt-2 flex items-center text-sm">
                        <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="text-green-600">+2% improvement</span>
                    </div>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="ultra-analytics-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500">Cancellation Rate</div>
                    <div class="mt-1 text-3xl font-bold">{{ $cancellationRate ?? 3.2 }}%</div>
                    <div class="mt-2 flex items-center text-sm">
                        <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                        <span class="text-green-600">-0.5% reduction</span>
                    </div>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Quick Actions Bar --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <button class="ultra-action-button primary" onclick="openNewAppointmentModal()">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New Appointment
        </button>
        <button class="ultra-action-button" onclick="importAppointments()">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            Import
        </button>
        <button class="ultra-action-button" onclick="exportAppointments()">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            Export
        </button>
        <button class="ultra-action-button" onclick="syncCalendar()">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Sync Calendar
        </button>
    </div>
    
    {{-- Main Content Area --}}
    <div id="content-area">
        {{-- Calendar View (Default) --}}
        <div id="calendar-view" class="view-content">
            <div class="ultra-calendar-container">
                <div class="ultra-calendar-header">
                    <div class="flex items-center gap-4">
                        <button class="ultra-calendar-nav-button" onclick="navigateCalendar('prev')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <h2 class="text-xl font-semibold">{{ now()->format('F Y') }}</h2>
                        <button class="ultra-calendar-nav-button" onclick="navigateCalendar('next')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                    <button class="ultra-calendar-nav-button" onclick="navigateCalendar('today')">
                        Today
                    </button>
                </div>
                
                {{-- Calendar Grid --}}
                <div class="p-4">
                    <div id="calendar-grid" class="ultra-calendar-grid">
                        <!-- Calendar will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
        
        {{-- List View --}}
        <div id="list-view" class="view-content hidden">
            <div class="space-y-4">
                @forelse($appointments ?? [] as $appointment)
                    <div class="ultra-appointment-card status-{{ $appointment->status ?? 'scheduled' }}">
                        <div class="ultra-appointment-header">
                            <div>
                                <div class="ultra-appointment-time">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $appointment->starts_at?->format('H:i') ?? '00:00' }} - {{ $appointment->ends_at?->format('H:i') ?? '00:00' }}
                                </div>
                                <div class="ultra-appointment-service mt-2">
                                    {{ $appointment->service?->name ?? 'Service' }}
                                </div>
                            </div>
                            <span class="ultra-status-badge {{ $appointment->status ?? 'scheduled' }}">
                                {{ ucfirst($appointment->status ?? 'scheduled') }}
                            </span>
                        </div>
                        
                        <div class="ultra-appointment-details">
                            <div class="ultra-appointment-detail-item">
                                <svg class="ultra-appointment-detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                {{ $appointment->customer?->name ?? 'Unknown Customer' }}
                            </div>
                            <div class="ultra-appointment-detail-item">
                                <svg class="ultra-appointment-detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                {{ $appointment->customer?->phone ?? 'No phone' }}
                            </div>
                            <div class="ultra-appointment-detail-item">
                                <svg class="ultra-appointment-detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ $appointment->branch?->name ?? 'Main Branch' }}
                            </div>
                            <div class="ultra-appointment-detail-item">
                                <svg class="ultra-appointment-detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                €{{ number_format($appointment->service?->price ?? 0, 2) }}
                            </div>
                        </div>
                        
                        <div class="ultra-appointment-actions">
                            <button class="ultra-action-button" onclick="rescheduleAppointment({{ $appointment->id }})">
                                Reschedule
                            </button>
                            <button class="ultra-action-button" onclick="cancelAppointment({{ $appointment->id }})">
                                Cancel
                            </button>
                            <button class="ultra-action-button primary" onclick="checkInAppointment({{ $appointment->id }})">
                                Check-in
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="ultra-empty-state">
                        <svg class="ultra-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="text-lg font-medium mb-1">No appointments scheduled</h3>
                        <p class="text-sm">Get started by creating a new appointment.</p>
                        <button class="ultra-action-button primary mt-4" onclick="openNewAppointmentModal()">
                            Create Appointment
                        </button>
                    </div>
                @endforelse
            </div>
        </div>
        
        {{-- Timeline View --}}
        <div id="timeline-view" class="view-content hidden">
            <div class="ultra-timeline-container">
                <div class="ultra-timeline-line"></div>
                @foreach($appointments ?? [] as $appointment)
                    <div class="ultra-timeline-item">
                        <div class="ultra-timeline-marker"></div>
                        <div class="ultra-appointment-card">
                            <h4 class="font-semibold text-lg mb-1">
                                {{ $appointment->starts_at?->format('H:i') }} - {{ $appointment->service?->name }}
                            </h4>
                            <p class="text-gray-600">{{ $appointment->customer?->name }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    {{-- Pagination --}}
    @if(method_exists($this, 'getTableRecords') && $this->getTableRecords()->hasPages())
        <div class="mt-6">
            {{ $this->getTableRecords()->links() }}
        </div>
    @endif
    
    @push('scripts')
    <script>
        // View Switcher
        function switchView(view) {
            // Hide all views
            document.querySelectorAll('.view-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.view-btn').forEach(el => el.classList.remove('active'));
            
            // Show selected view
            document.getElementById(view + '-view').classList.remove('hidden');
            document.querySelector(`[data-view="${view}"]`).classList.add('active');
            
            // Save preference
            localStorage.setItem('preferred-appointment-view', view);
        }
        
        // Calendar Functions
        function generateCalendar() {
            const grid = document.getElementById('calendar-grid');
            const today = new Date();
            const year = today.getFullYear();
            const month = today.getMonth();
            
            // Clear existing content
            grid.innerHTML = '';
            
            // Add day headers
            const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayHeaders.forEach(day => {
                const header = document.createElement('div');
                header.className = 'ultra-calendar-day-header text-center font-semibold p-2';
                header.textContent = day;
                grid.appendChild(header);
            });
            
            // Get first day of month
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            // Add empty cells for days before month starts
            for (let i = 0; i < firstDay; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'ultra-calendar-day';
                grid.appendChild(emptyDay);
            }
            
            // Add days of month
            for (let day = 1; day <= daysInMonth; day++) {
                const dayEl = document.createElement('div');
                dayEl.className = 'ultra-calendar-day';
                
                // Check if today
                if (day === today.getDate() && month === today.getMonth()) {
                    dayEl.classList.add('today');
                }
                
                // Check if weekend
                const dayOfWeek = new Date(year, month, day).getDay();
                if (dayOfWeek === 0 || dayOfWeek === 6) {
                    dayEl.classList.add('weekend');
                }
                
                dayEl.innerHTML = `
                    <div class="ultra-calendar-day-header">${day}</div>
                    <div class="ultra-drop-zone" ondrop="dropAppointment(event)" ondragover="allowDrop(event)" data-date="${year}-${month+1}-${day}">
                        Drop appointment here
                    </div>
                `;
                
                grid.appendChild(dayEl);
            }
        }
        
        // Quick Actions
        function openNewAppointmentModal() {
            // Implementation for new appointment modal
            alert('New appointment modal would open here');
        }
        
        function rescheduleAppointment(id) {
            // Implementation for rescheduling
            alert('Reschedule appointment ' + id);
        }
        
        function cancelAppointment(id) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                // Implementation for cancellation
                alert('Cancel appointment ' + id);
            }
        }
        
        function checkInAppointment(id) {
            // Implementation for check-in
            alert('Check-in appointment ' + id);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Generate calendar
            generateCalendar();
            
            // Restore preferred view
            const preferredView = localStorage.getItem('preferred-appointment-view') || 'calendar';
            switchView(preferredView);
        });
    </script>
    
    <style>
        .view-btn.active {
            background-color: #3B82F6;
            color: white;
        }
    </style>
    @endpush
</x-filament-panels::page>