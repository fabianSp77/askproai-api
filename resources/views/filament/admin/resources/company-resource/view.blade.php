<x-filament-panels::page>
    <div class="space-y-6">
    <!-- Company Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-sm mb-6 text-white">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ $record->name }}</h1>
                    <p class="mt-2 text-blue-100">{{ $record->tagline ?? 'Your trusted service provider' }}</p>
                
                <div class="flex items-center space-x-3">
                    @if($record->is_active)
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-white/20 backdrop-blur">
                            <svg class="-ml-1 mr-1.5 w-2 h-2 text-green-300" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
                            Active Company
                        </span>
                    @endif
                
            
        
    

    <!-- Company KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <!-- Total Staff -->
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
                
            
        

        <!-- Total Services -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Services</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->services()->count() }}</p>
                
            
        

        <!-- Total Branches -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Branches</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->branches()->count() }}</p>
                
            
        

        <!-- Total Customers -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Customers</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->customers()->count() }}</p>
                
            
        

        <!-- Monthly Revenue -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">This Month</p>
                    @php
                        $monthlyRevenue = \App\Models\Appointment::where('tenant_id', $record->tenant_id)
                            ->whereMonth('starts_at', now()->month)
                            ->join('services', 'appointments.service_id', '=', 'services.id')
                            ->sum('services.price_cents');
                    @endphp
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        €{{ number_format($monthlyRevenue / 100, 2) }}
                    </p>
                
            
        
    

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Company Information -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Company Details</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Registration Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->registration_number ?? 'Not provided' }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tax ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->tax_id ?? 'Not provided' }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Industry</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->industry ?? 'Service Industry' }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Founded</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->founded_year ?? 'Not specified' }}</dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Website</dt>
                        <dd class="mt-1">
                            @if($record->website)
                                <a href="{{ $record->website }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $record->website }}
                                </a>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">Not provided</span>
                            @endif
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
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                        <dd class="mt-1">
                            @if($record->phone)
                                <a href="tel:{{ $record->phone }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $record->phone }}
                                </a>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">Not provided</span>
                            @endif
                        </dd>
                    
                </dl>
            

            <!-- Primary Contact -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Primary Contact</h3>
                @if($record->primaryContact)
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr($record->primaryContact->name, 0, 2)) }}
                            
                        
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->primaryContact->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $record->primaryContact->position ?? 'Manager' }}</p>
                            <p class="text-xs text-blue-600 dark:text-blue-400">{{ $record->primaryContact->email }}</p>
                        
                    
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No primary contact assigned</p>
                @endif
            
        

        <!-- Branches & Performance -->
        <div class="lg:col-span-2">
            <!-- Branches -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Branches</h3>
                
                <div class="p-6">
                    @if($record->branches && $record->branches->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($record->branches as $branch)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $branch->name }}</h4>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $branch->address }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $branch->phone }}</p>
                                        
                                        @if($branch->is_main)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                Main
                                            </span>
                                        @endif
                                    
                                    <div class="mt-3 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ $branch->staff()->count() }} Staff</span>
                                        <span>•</span>
                                        <span>{{ $branch->appointments()->count() }} Appointments</span>
                                    
                                
                            @endforeach
                        
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No branches registered</p>
                    @endif
                
            

            <!-- Performance Overview -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Performance Overview</h3>
                
                <div class="p-6">
                    <!-- Monthly Trend -->
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Monthly Appointments (Last 6 Months)</h4>
                        <div class="flex items-end space-x-2" style="height: 100px;">
                            @for($i = 5; $i >= 0; $i--)
                                @php
                                    $month = now()->subMonths($i);
                                    $count = \App\Models\Appointment::where('tenant_id', $record->tenant_id)
                                        ->whereMonth('starts_at', $month->month)
                                        ->whereYear('starts_at', $month->year)
                                        ->count();
                                    $maxHeight = 100;
                                    $height = $count > 0 ? min(($count / 50) * 100, 100) : 5;
                                @endphp
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="w-full bg-blue-500 dark:bg-blue-400 rounded-t" style="height: {{ $height }}%;">
                                    <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">{{ $month->format('M') }}</p>
                                    <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">{{ $count }}</p>
                                
                            @endfor
                        
                    

                    <!-- Top Services -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Top Services</h4>
                        @php
                            $topServices = \App\Models\Service::where('tenant_id', $record->tenant_id)
                                ->withCount('appointments')
                                ->orderBy('appointments_count', 'desc')
                                ->limit(5)
                                ->get();
                        @endphp
                        @if($topServices->count() > 0)
                            <div class="space-y-2">
                                @foreach($topServices as $service)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $service->name }}</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $service->appointments_count }} bookings</span>
                                    
                                @endforeach
                            
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No services data available</p>
                        @endif
                    
                
            

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mt-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Activity</h3>
                
                <div class="p-6">
                    @php
                        $recentActivity = \App\Models\Appointment::where('tenant_id', $record->tenant_id)
                            ->orderBy('created_at', 'desc')
                            ->with(['customer', 'service', 'staff'])
                            ->limit(5)
                            ->get();
                    @endphp
                    @if($recentActivity->count() > 0)
                        <div class="flow-root">
                            <ul class="-mb-8">
                                @foreach($recentActivity as $activity)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </span>
                                                
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            New appointment: <span class="font-medium text-gray-900 dark:text-gray-100">{{ $activity->service->name }}</span>
                                                            for <span class="font-medium text-gray-900 dark:text-gray-100">{{ $activity->customer->name }}</span>
                                                        </p>
                                                    
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                        {{ $activity->created_at->diffForHumans() }}
                                                    
                                                
                                            
                                        
                                    </li>
                                @endforeach
                            </ul>
                        
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No recent activity</p>
                    @endif
                
            
        
    

    </div>
</x-filament-panels::page>
