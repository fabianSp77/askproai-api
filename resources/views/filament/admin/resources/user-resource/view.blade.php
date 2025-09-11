<x-filament-panels::page>
    <div class="space-y-6">
    <!-- User Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr($record->name, 0, 2)) }}
                        
                    
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $record->name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $record->email }}</p>
                    
                
                <div class="flex items-center space-x-3">
                    @if($record->email_verified_at)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            <svg class="-ml-1 mr-1.5 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Verified
                        </span>
                    @endif
                    @if($record->is_active ?? true)
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
                
            
        
    

    <!-- User Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Last Login -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Last Login</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $record->last_login_at ? $record->last_login_at->diffForHumans() : 'Never' }}
                    </p>
                
            
        

        <!-- Account Age -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Member Since</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $record->created_at->format('M Y') }}
                    </p>
                
            
        

        <!-- Total Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Actions</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $record->activities()->count() ?? 0 }}</p>
                
            
        

        <!-- Security Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    
                
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">2FA Status</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $record->two_factor_secret ? 'Enabled' : 'Disabled' }}
                    </p>
                
            
        
    

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Details -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Account Details</h3>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="mt-1">
                            <a href="mailto:{{ $record->email }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ $record->email }}
                            </a>
                        </dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $record->phone ?? 'Not provided' }}
                        </dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tenant</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $record->tenant->name ?? 'No tenant' }}
                        </dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $record->created_at->format('F j, Y g:i A') }}
                        </dd>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $record->updated_at->format('F j, Y g:i A') }}
                        </dd>
                    
                </dl>
            

            <!-- Roles & Permissions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Roles & Permissions</h3>
                @if($record->roles && $record->roles->count() > 0)
                    <div class="space-y-2">
                        @foreach($record->roles as $role)
                            <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 mr-2">
                                {{ $role->name }}
                            
                        @endforeach
                    
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No roles assigned</p>
                @endif
                
                @if($record->permissions && $record->permissions->count() > 0)
                    <div class="mt-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Direct Permissions</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($record->permissions as $permission)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ $permission->name }}
                                </span>
                            @endforeach
                        
                    
                @endif
            
        

        <!-- Activity & Sessions -->
        <div class="lg:col-span-2">
            <!-- Active Sessions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Active Sessions</h3>
                
                <div class="p-6">
                    @if($record->sessions && $record->sessions->count() > 0)
                        <div class="space-y-4">
                            @foreach($record->sessions as $session)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            @if(str_contains($session->user_agent ?? '', 'Mobile'))
                                                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zM9 14a1 1 0 100 2 1 1 0 000-2z"></path>
                                                </svg>
                                            @else
                                                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endif
                                        
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $session->ip_address }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($session->user_agent ?? 'Unknown device', 50) }}</p>
                                        
                                    
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Last active</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}
                                        </p>
                                    
                                
                            @endforeach
                        
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No active sessions</p>
                    @endif
                
            

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Activity</h3>
                
                <div class="p-6">
                    @if($record->activities && $record->activities()->latest()->limit(10)->get()->count() > 0)
                        <div class="flow-root">
                            <ul class="-mb-8">
                                @foreach($record->activities()->latest()->limit(10)->get() as $activity)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </span>
                                                
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ $activity->description }}
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
