<x-filament-panels::page>
    <div class="space-y-6">
    <!-- Service Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $record->name }}</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $record->description }}</p>
                
                <div class="flex items-center space-x-3">
                    @if($record->is_active)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="-ml-1 mr-1.5 w-2 h-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <svg class="-ml-1 mr-1.5 w-2 h-2 text-red-400" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            Inactive
                        </span>
                    @endif
                
            
        
    

    <!-- Service Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Price -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Price</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        €{{ number_format($record->price_cents / 100, 2) }}
                    </p>
                
            
        

        <!-- Duration -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Duration</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->duration_minutes }} min</p>
                
            
        

        <!-- Total Bookings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Bookings</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->appointments()->count() }}</p>
                
            
        

        <!-- Revenue Generated -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        €{{ number_format(($record->appointments()->count() * $record->price_cents) / 100, 2) }}
                    </p>
                
            
        
    

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Service Details -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Service Details</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->category ?? 'General' }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Buffer Time</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->buffer_time_minutes ?? 0 }} minutes</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Max Bookings/Day</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->max_bookings_per_day ?? 'Unlimited' }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Booking Window</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->booking_window_days ?? 30 }} days</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->created_at->format('F j, Y') }}</dd>
                    
                </dl>
            

            <!-- Staff Members -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Staff Members</h3>
                @if($record->staff && $record->staff->count() > 0)
                    <div class="space-y-3">
                        @foreach($record->staff as $staff)
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                        {{ strtoupper(substr($staff->name, 0, 2)) }}
                                    
                                
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $staff->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $staff->email }}</p>
                                
                            
                        @endforeach
                    
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No staff members assigned</p>
                @endif
            
        

        <!-- Recent & Upcoming Appointments -->
        <div class="lg:col-span-2">
            <!-- Booking Trends -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Booking Trends (Last 7 Days)</h3>
                <div class="grid grid-cols-7 gap-2">
                    @for($i = 6; $i >= 0; $i--)
                        @php
                            $date = now()->subDays($i);
                            $count = $record->appointments()
                                ->whereDate('starts_at', $date->toDateString())
                                ->count();
                            $maxCount = 10;
                            $percentage = min(($count / max($maxCount, 1)) * 100, 100);
                        @endphp
                        <div class="flex flex-col items-center">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-24 flex flex-col justify-end">
                                <div class="bg-blue-600 dark:bg-blue-400 rounded-full" style="height: {{ $percentage }}%;"></div>
                            </div>
                            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">{{ $date->format('D') }}</p>
                            <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $count }}</p>
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Upcoming Appointments</h3>
                
                <div class="p-6">
                    @php
                        $upcomingAppointments = $record->appointments()
                            ->where('starts_at', '>=', now())
                            ->orderBy('starts_at')
                            ->with(['customer', 'staff'])
                            ->limit(5)
                            ->get();
                    @endphp
                    @if($upcomingAppointments->count() > 0)
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Staff</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($upcomingAppointments as $appointment)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $appointment->customer->name ?? 'Unknown' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $appointment->staff->name ?? 'Unassigned' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $appointment->starts_at->format('M j, g:i A') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($appointment->status === 'confirmed')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        Confirmed
                                                    </span>
                                                @elseif($appointment->status === 'pending')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                        Pending
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                                        {{ ucfirst($appointment->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No upcoming appointments</p>
                    @endif
                
            

            <!-- Performance Metrics -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mt-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Performance Metrics</h3>
                
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Completion Rate</p>
                            @php
                                $completed = $record->appointments()->where('status', 'completed')->count();
                                $total = $record->appointments()->count();
                                $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
                            @endphp
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $rate }}%</p>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $rate }}%"></div>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Average Rating</p>
                            @php
                                $avgRating = $record->appointments()->whereNotNull('rating')->avg('rating') ?? 0;
                            @endphp
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($avgRating, 1) }}/5.0</p>
                            <div class="mt-2 flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="h-5 w-5 {{ $i <= $avgRating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>
</x-filament-panels::page>
