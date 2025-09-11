<x-filament-panels::page>
    <div class="space-y-6">
    <!-- Staff Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr($record->name, 0, 2)) }}
                        
                    
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $record->name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Staff ID: #{{ str_pad($record->id, 5, '0', STR_PAD_LEFT) }}</p>
                    
                
                <div class="flex items-center space-x-3">
                    @if($record->is_active)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="-ml-1 mr-1.5 w-2 h-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                            <svg class="-ml-1 mr-1.5 w-2 h-2 text-gray-400" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            Inactive
                        </span>
                    @endif
                
            
        
    

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Total Appointments -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Appointments</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->appointments()->count() }}</p>
                
            
        

        <!-- This Week -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">This Week</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $record->appointments()->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count() }}
                    </p>
                
            
        

        <!-- Average Rating -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Rating</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->rating ?? 'N/A' }}</p>
                
            
        

        <!-- Revenue -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        €{{ number_format($record->appointments()->join('services', 'appointments.service_id', '=', 'services.id')->sum('services.price_cents') / 100, 2) }}
                    </p>
                
            
        
    

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Contact Information -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Contact Information</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="mt-1">
                            <a href="mailto:{{ $record->email }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ $record->email }}
                            </a>
                        </dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                        <dd class="mt-1">
                            <a href="tel:{{ $record->phone }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ $record->phone }}
                            </a>
                        </dd>
                    
                    @if($record->home_branch)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Home Branch</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $record->home_branch->name }}
                        </dd>
                    
                    @endif
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Joined</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $record->created_at->format('F j, Y') }}
                        </dd>
                    
                </dl>
            

            <!-- Services -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Services</h3>
                @if($record->services && $record->services->count() > 0)
                    <div class="space-y-2">
                        @foreach($record->services as $service)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $service->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $service->duration_minutes }} min</p>
                                
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    €{{ number_format($service->price_cents / 100, 2) }}
                                </span>
                            
                        @endforeach
                    
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No services assigned</p>
                @endif
            
        

        <!-- Schedule & Appointments -->
        <div class="lg:col-span-2">
            <!-- Working Hours -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Working Hours</h3>
                @if($record->workingHours && $record->workingHours->count() > 0)
                    <div class="grid grid-cols-7 gap-2">
                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                            @php
                                $hours = $record->workingHours->where('day_of_week', $day)->first();
                            @endphp
                            <div class="text-center">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ substr($day, 0, 3) }}</p>
                                @if($hours)
                                    <div class="bg-green-100 dark:bg-green-900 rounded px-2 py-1">
                                        <p class="text-xs text-green-800 dark:text-green-200">{{ date('H:i', strtotime($hours->start_time)) }}</p>
                                        <p class="text-xs text-green-800 dark:text-green-200">{{ date('H:i', strtotime($hours->end_time)) }}</p>
                                    
                                @else
                                    <div class="bg-gray-100 dark:bg-gray-700 rounded px-2 py-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Off</p>
                                    
                                @endif
                            
                        @endforeach
                    
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No working hours defined</p>
                @endif
            

            <!-- Upcoming Appointments -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Upcoming Appointments</h3>
                
                <div class="p-6">
                    @php
                        $upcomingAppointments = $record->appointments()
                            ->where('starts_at', '>=', now())
                            ->orderBy('starts_at')
                            ->limit(5)
                            ->get();
                    @endphp
                    @if($upcomingAppointments->count() > 0)
                        <div class="space-y-4">
                            @foreach($upcomingAppointments as $appointment)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            
                                        
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $appointment->customer->name ?? 'Unknown Customer' }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $appointment->service->name ?? 'Unknown Service' }}
                                            </p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                                {{ $appointment->starts_at->format('M j, Y - g:i A') }}
                                            </p>
                                        
                                    
                                    <div class="text-right">
                                        @if($appointment->status === 'confirmed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Confirmed
                                            </span>
                                        @elseif($appointment->status === 'pending')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                Pending
                                            </span>
                                        @endif
                                    
                                
                            @endforeach
                        
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No upcoming appointments</p>
                    @endif
                
            

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mt-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Activity</h3>
                
                <div class="p-6">
                    @php
                        $recentAppointments = $record->appointments()
                            ->where('ends_at', '<=', now())
                            ->orderBy('ends_at', 'desc')
                            ->limit(5)
                            ->get();
                    @endphp
                    @if($recentAppointments->count() > 0)
                        <div class="flow-root">
                            <ul class="-mb-8">
                                @foreach($recentAppointments as $index => $appointment)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </span>
                                                
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            Completed appointment with 
                                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                                {{ $appointment->customer->name ?? 'Unknown' }}
                                                            </span>
                                                        </p>
                                                    
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                        <time datetime="{{ $appointment->ends_at }}">
                                                            {{ $appointment->ends_at->diffForHumans() }}
                                                        </time>
                                                    
                                                
                                            
                                        
                                    </li>
                                @endforeach
                            </ul>
                        
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No recent activity</p>
                    @endif
                
            
        
    

    </div>
</x-filament-panels::page>
