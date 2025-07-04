<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Configuration Status -->
        @if($this->configStatus)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium mb-4">Cal.com Configuration Status</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="flex items-center space-x-2">
                    @if($this->configStatus['api_key'])
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                        <span class="text-sm">API Key Configured</span>
                    @else
                        <x-heroicon-o-x-circle class="w-5 h-5 text-red-500" />
                        <span class="text-sm">API Key Missing</span>
                    @endif
                </div>
                
                <div class="flex items-center space-x-2">
                    @if($this->configStatus['api_connected'] ?? false)
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                        <span class="text-sm">API Connected</span>
                    @else
                        <x-heroicon-o-x-circle class="w-5 h-5 text-red-500" />
                        <span class="text-sm">API Not Connected</span>
                    @endif
                </div>
                
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-calendar class="w-5 h-5 text-blue-500" />
                    <span class="text-sm">{{ $this->configStatus['event_types'] }} Event Types</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-building-office class="w-5 h-5 text-blue-500" />
                    <span class="text-sm">{{ $this->configStatus['branches_configured'] }} Branches Configured</span>
                </div>
            </div>
            
            @if(isset($this->configStatus['api_error']))
            <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <p class="text-sm text-red-800 dark:text-red-200">
                    <strong>API Error:</strong> {{ $this->configStatus['api_error'] }}
                </p>
            </div>
            @endif
            
            <div class="mt-4 text-xs text-gray-500">
                <p>Team Slug: {{ $this->configStatus['team_slug'] }}</p>
                <p>Base URL: {{ $this->configStatus['base_url'] }}</p>
                <p>V2 API: {{ $this->configStatus['v2_enabled'] ? 'Enabled' : 'Disabled' }}</p>
            </div>
        </div>
        @endif
        
        <!-- Main Form -->
        <form wire:submit.prevent="createBooking">
            {{ $this->form }}
        </form>
        
        <!-- Available Slots -->
        @if($this->availableSlots)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium mb-4">Available Time Slots</h3>
            
            @if(isset($this->availableSlots['error']))
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <p class="text-sm text-red-800 dark:text-red-200">
                        Error: {{ $this->availableSlots['error'] }}
                    </p>
                </div>
            @elseif(isset($this->availableSlots['days']))
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @foreach($this->availableSlots['days'] as $day)
                        @if(isset($day['slots']) && count($day['slots']) > 0)
                        <div>
                            <h4 class="font-medium text-sm mb-2">{{ \Carbon\Carbon::parse($day['day'])->format('l, F j, Y') }}</h4>
                            <div class="grid grid-cols-4 md:grid-cols-6 gap-2">
                                @foreach($day['slots'] as $slot)
                                <button 
                                    type="button"
                                    wire:click="$set('startDateTime', '{{ \Carbon\Carbon::parse($slot['time'])->format('Y-m-d H:i') }}')"
                                    class="px-3 py-2 text-xs rounded-md bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 cursor-pointer"
                                >
                                    {{ \Carbon\Carbon::parse($slot['time'])->format('H:i') }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No availability data available</p>
            @endif
        </div>
        @endif
        
        <!-- Booking Result -->
        @if($this->bookingResult)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-medium mb-4">Booking Result</h3>
            
            @if($this->bookingResult['test_mode'] ?? false)
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg mb-4">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>Test Mode:</strong> No actual booking was created
                    </p>
                </div>
            @endif
            
            @if($this->bookingResult['success'] ?? false)
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="text-sm text-green-800 dark:text-green-200 font-medium mb-2">
                        ✅ Booking created successfully!
                    </p>
                    <dl class="space-y-1">
                        <div>
                            <dt class="inline-block text-sm font-medium text-gray-500 w-24">Booking ID:</dt>
                            <dd class="inline-block text-sm">{{ $this->bookingResult['booking']['id'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="inline-block text-sm font-medium text-gray-500 w-24">UID:</dt>
                            <dd class="inline-block text-sm font-mono">{{ $this->bookingResult['booking']['uid'] ?? 'N/A' }}</dd>
                        </div>
                        @if(isset($this->bookingResult['booking']['startTime']))
                        <div>
                            <dt class="inline-block text-sm font-medium text-gray-500 w-24">Start Time:</dt>
                            <dd class="inline-block text-sm">{{ \Carbon\Carbon::parse($this->bookingResult['booking']['startTime'])->format('d.m.Y H:i') }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            @else
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <p class="text-sm text-red-800 dark:text-red-200">
                        <strong>Error:</strong> {{ $this->bookingResult['error'] ?? 'Unknown error' }}
                    </p>
                </div>
            @endif
            
            @if($this->bookingResult['would_book'] ?? false)
                <div class="mt-4">
                    <h4 class="text-sm font-medium mb-2">Booking Data (Test Mode):</h4>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-3 rounded overflow-x-auto">{{ json_encode($this->bookingResult['would_book'], JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
        @endif
        
        <!-- Instructions -->
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Testing Instructions</h3>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li>Select a branch and service (optional)</li>
                <li>Choose a Cal.com event type</li>
                <li>Click "Check Availability" to see available time slots</li>
                <li>Click on a time slot to select it</li>
                <li>Enter customer information</li>
                <li>Toggle "Test Mode" off to create real bookings</li>
                <li>Click "Create Booking" to test the integration</li>
            </ol>
            
            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>Note:</strong> In Test Mode, bookings are validated but not actually created in Cal.com. 
                    Disable Test Mode to create real bookings.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>