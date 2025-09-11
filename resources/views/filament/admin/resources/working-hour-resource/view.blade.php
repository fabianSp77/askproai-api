<x-filament-panels::page>
    <div class="space-y-6">
    <!-- Working Hour Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $record->staff->name ?? 'Working Hours' }} - {{ $record->day_of_week }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ date('g:i A', strtotime($record->start_time)) }} - {{ date('g:i A', strtotime($record->end_time)) }}
                    </p>
                
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
                            Day Off
                        </span>
                    @endif
                
            
        
    

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Schedule Details -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Schedule Details</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Day</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $record->day_of_week }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Time</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ date('g:i A', strtotime($record->start_time)) }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">End Time</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ date('g:i A', strtotime($record->end_time)) }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</dt>
                        @php
                            $start = \Carbon\Carbon::parse($record->start_time);
                            $end = \Carbon\Carbon::parse($record->end_time);
                            $duration = $start->diff($end);
                        @endphp
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $duration->h }} hours {{ $duration->i }} minutes</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Break Time</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $record->break_duration_minutes ?? 0 }} minutes
                        </dd>
                    
                </dl>
            

            <!-- Staff Information -->
            @if($record->staff)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Staff Member</h3>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr($record->staff->name, 0, 2)) }}
                        
                    
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->staff->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->staff->email }}</p>
                    
                
            
            @endif
        

        <!-- Weekly Schedule -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Weekly Schedule Overview</h3>
                
                <div class="p-6">
                    @if($record->staff && $record->staff->workingHours)
                        <div class="grid grid-cols-7 gap-2">
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                @php
                                    $daySchedule = $record->staff->workingHours->where('day_of_week', $day)->first();
                                @endphp
                                <div class="text-center {{ $daySchedule && $daySchedule->id === $record->id ? 'ring-2 ring-blue-500 rounded-lg' : '' }}">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">{{ substr($day, 0, 3) }}</p>
                                    @if($daySchedule && $daySchedule->is_active)
                                        <div class="bg-green-100 dark:bg-green-900 rounded px-2 py-2">
                                            <p class="text-xs text-green-800 dark:text-green-200 font-medium">{{ date('H:i', strtotime($daySchedule->start_time)) }}</p>
                                            <p class="text-xs text-green-800 dark:text-green-200">to</p>
                                            <p class="text-xs text-green-800 dark:text-green-200 font-medium">{{ date('H:i', strtotime($daySchedule->end_time)) }}</p>
                                        
                                    @else
                                        <div class="bg-gray-100 dark:bg-gray-700 rounded px-2 py-2 h-16 flex items-center justify-center">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Off</p>
                                        
                                    @endif
                                
                            @endforeach
                        

                        <!-- Weekly Stats -->
                        <div class="mt-6 grid grid-cols-3 gap-4">
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Hours/Week</p>
                                @php
                                    $totalMinutes = 0;
                                    foreach($record->staff->workingHours as $wh) {
                                        if($wh->is_active) {
                                            $start = \Carbon\Carbon::parse($wh->start_time);
                                            $end = \Carbon\Carbon::parse($wh->end_time);
                                            $totalMinutes += $end->diffInMinutes($start) - ($wh->break_duration_minutes ?? 0);
                                        }
                                    }
                                    $totalHours = floor($totalMinutes / 60);
                                    $remainingMinutes = $totalMinutes % 60;
                                @endphp
                                <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $totalHours }}h {{ $remainingMinutes }}m
                                </p>
                            
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Working Days</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $record->staff->workingHours->where('is_active', true)->count() }}
                                </p>
                            
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Days Off</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ 7 - $record->staff->workingHours->where('is_active', true)->count() }}
                                </p>
                            
                        
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No weekly schedule available</p>
                    @endif
                
            

            <!-- Appointments on this Day -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mt-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Upcoming Appointments on {{ $record->day_of_week }}s</h3>
                
                <div class="p-6">
                    @if($record->staff)
                        @php
                            $dayNumber = array_search($record->day_of_week, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
                            $appointments = $record->staff->appointments()
                                ->where('starts_at', '>=', now())
                                ->whereRaw('DAYOFWEEK(starts_at) = ?', [$dayNumber + 1])
                                ->orderBy('starts_at')
                                ->limit(5)
                                ->get();
                        @endphp
                        @if($appointments->count() > 0)
                            <div class="space-y-3">
                                @foreach($appointments as $appointment)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $appointment->customer->name ?? 'Unknown' }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $appointment->service->name ?? 'Unknown Service' }} • {{ $appointment->starts_at->format('M j, g:i A') }}
                                            </p>
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ ucfirst($appointment->status) }}
                                        </span>
                                    
                                @endforeach
                            
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No upcoming appointments on {{ $record->day_of_week }}s</p>
                        @endif
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No staff member assigned</p>
                    @endif
                
            
        
    

    </div>
</x-filament-panels::page>
