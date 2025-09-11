<x-filament-panels::page>
    <div class="space-y-6">
    <!-- Branch Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="p-4 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                            <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        
                    
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $record->name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $record->address }}</p>
                    
                
                <div class="flex items-center space-x-3">
                    @if($record->is_main)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            Main Branch
                        </span>
                    @endif
                    @if($record->is_active)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="-ml-1 mr-1.5 w-2 h-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            Active
                        </span>
                    @endif
                
            
        
    

    <!-- Branch Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Staff Count -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Staff Members</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->staff()->count() }}</p>
                
            
        

        <!-- Total Appointments -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Appointments</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->appointments()->count() }}</p>
                
            
        

        <!-- This Week -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">This Week</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $record->appointments()->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count() }}
                    </p>
                
            
        

        <!-- Revenue -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                    @php
                        $revenue = $record->appointments()
                            ->join('services', 'appointments.service_id', '=', 'services.id')
                            ->sum('services.price_cents');
                    @endphp
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        €{{ number_format($revenue / 100, 2) }}
                    </p>
                
            
        
    

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Branch Information -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Contact Information</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                        <dd class="mt-1">
                            <a href="tel:{{ $record->phone }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ $record->phone }}
                            </a>
                        </dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="mt-1">
                            @if($record->email)
                                <a href="mailto:{{ $record->email }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $record->email }}
                                </a>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">Not provided</span>
                            @endif
                        </dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Full Address</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $record->address }}<br>
                            {{ $record->city ?? '' }} {{ $record->postal_code ?? '' }}<br>
                            {{ $record->country ?? '' }}
                        </dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Operating Hours</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $record->operating_hours ?? 'Mon-Fri: 9:00 AM - 6:00 PM' }}
                        </dd>
                    
                </dl>
            

            <!-- Company -->
            @if($record->company)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Company</h3>
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr($record->company->name, 0, 2)) }}
                        
                    
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->company->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->company->industry ?? 'Service Industry' }}</p>
                    
                
            
            @endif
        

        <!-- Staff & Appointments -->
        <div class="lg:col-span-2">
            <!-- Staff Members -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Staff Members</h3>
                
                <div class="p-6">
                    @if($record->staff && $record->staff->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($record->staff as $staff)
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                            {{ strtoupper(substr($staff->name, 0, 2)) }}
                                        
                                    
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $staff->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $staff->email }}</p>
                                    
                                    @if($staff->is_active)
                                        <span class="flex-shrink-0 w-2 h-2 bg-green-400 rounded-full"></span>
                                    @endif
                                
                            @endforeach
                        
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No staff members assigned</p>
                    @endif
                
            

            <!-- Today's Schedule -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Today's Schedule</h3>
                
                <div class="p-6">
                    @php
                        $todayAppointments = $record->appointments()
                            ->whereDate('starts_at', today())
                            ->orderBy('starts_at')
                            ->with(['customer', 'service', 'staff'])
                            ->get();
                    @endphp
                    @if($todayAppointments->count() > 0)
                        <div class="space-y-3">
                            @foreach($todayAppointments as $appointment)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-4">
                                        <div class="text-center">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $appointment->starts_at->format('g:i') }}</p>
                                            <p class="text-xs font-medium text-gray-900 dark:text-gray-100">{{ $appointment->starts_at->format('A') }}</p>
                                        
                                        <div class="border-l border-gray-300 dark:border-gray-600 h-12">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $appointment->customer->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $appointment->service->name }} • {{ $appointment->staff->name ?? 'Unassigned' }}</p>
                                        
                                    
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        {{ ucfirst($appointment->status) }}
                                    </span>
                                
                            @endforeach
                        
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No appointments scheduled for today</p>
                    @endif
                
            
        
    

    </div>
</x-filament-panels::page>
