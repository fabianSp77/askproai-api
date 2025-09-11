<div>
    <!-- Tabs Navigation -->
    <div class="fi-tabs flex gap-x-1 overflow-x-auto mx-auto rounded-xl bg-white p-2 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <button
            wire:click="setTab('overview')"
            type="button"
            @class([
                'fi-tabs-item group flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium outline-none transition duration-75',
                'fi-active fi-tabs-item-active bg-gray-50 dark:bg-white/5' => $activeTab === 'overview',
                'hover:bg-gray-50 dark:hover:bg-white/5 focus-visible:bg-gray-50 dark:focus-visible:bg-white/5' => $activeTab !== 'overview',
            ])
        >
            <x-heroicon-o-information-circle class="h-5 w-5" />
            <span>Overview</span>
        </button>
        
        <button
            wire:click="setTab('calcom')"
            type="button"
            @class([
                'fi-tabs-item group flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium outline-none transition duration-75',
                'fi-active fi-tabs-item-active bg-gray-50 dark:bg-white/5' => $activeTab === 'calcom',
                'hover:bg-gray-50 dark:hover:bg-white/5 focus-visible:bg-gray-50 dark:focus-visible:bg-white/5' => $activeTab !== 'calcom',
            ])
        >
            <x-heroicon-o-globe-alt class="h-5 w-5" />
            <span>Cal.com Integration</span>
        </button>
        
        <button
            wire:click="setTab('notes')"
            type="button"
            @class([
                'fi-tabs-item group flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium outline-none transition duration-75',
                'fi-active fi-tabs-item-active bg-gray-50 dark:bg-white/5' => $activeTab === 'notes',
                'hover:bg-gray-50 dark:hover:bg-white/5 focus-visible:bg-gray-50 dark:focus-visible:bg-white/5' => $activeTab !== 'notes',
            ])
        >
            <x-heroicon-o-document-text class="h-5 w-5" />
            <span>Notes & Activity</span>
        </button>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
        @if($activeTab === 'overview')
            <!-- Overview Tab -->
            <div class="space-y-6">
                <!-- Appointment Information -->
                <x-filament::section icon="heroicon-o-calendar">
                    <x-slot name="heading">
                        Appointment Information
                    </x-slot>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Booking ID</dt>
                            <dd class="mt-1">
                                <x-filament::badge color="primary">
                                    {{ $formattedData['booking_id'] }}
                                </x-filament::badge>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                <x-filament::badge 
                                    :color="match($formattedData['status']) {
                                        'accepted', 'confirmed', 'completed' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }">
                                    {{ ucfirst($formattedData['status']) }}
                                </x-filament::badge>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Source</dt>
                            <dd class="mt-1">
                                <x-filament::badge 
                                    :color="match($formattedData['source']) {
                                        'cal.com' => 'primary',
                                        'manual' => 'secondary',
                                        'api' => 'success',
                                        default => 'gray',
                                    }">
                                    {{ ucfirst($formattedData['source']) }}
                                </x-filament::badge>
                            </dd>
                        </div>
                    </div>
                </x-filament::section>

                <!-- Schedule -->
                <x-filament::section icon="heroicon-o-clock">
                    <x-slot name="heading">
                        Schedule
                    </x-slot>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Time</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <x-heroicon-m-calendar-days class="h-5 w-5 text-success-500" />
                                <span class="text-sm">{{ $formattedData['starts_at'] }}</span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">End Time</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <x-heroicon-m-calendar-days class="h-5 w-5 text-danger-500" />
                                <span class="text-sm">{{ $formattedData['ends_at'] }}</span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <x-heroicon-m-clock class="h-5 w-5 text-info-500" />
                                <span class="text-sm">{{ $formattedData['duration'] }}</span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Time Until</dt>
                            <dd class="mt-1">
                                <x-filament::badge :color="$appointment->starts_at?->isFuture() ? 'success' : 'gray'">
                                    {{ $formattedData['time_until'] }}
                                </x-filament::badge>
                            </dd>
                        </div>
                    </div>
                </x-filament::section>

                <!-- Participants -->
                <x-filament::section icon="heroicon-o-users">
                    <x-slot name="heading">
                        Participants
                    </x-slot>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <x-heroicon-m-user class="h-5 w-5 text-primary-500" />
                                <span class="text-sm font-medium">{{ $formattedData['customer_name'] }}</span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <x-heroicon-m-envelope class="h-5 w-5 text-gray-500" />
                                <a href="mailto:{{ $formattedData['customer_email'] }}" class="text-sm text-primary-600 hover:underline">
                                    {{ $formattedData['customer_email'] }}
                                </a>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <x-heroicon-m-phone class="h-5 w-5 text-gray-500" />
                                @if($formattedData['customer_phone'] !== '-')
                                    <a href="tel:{{ $formattedData['customer_phone'] }}" class="text-sm text-primary-600 hover:underline">
                                        {{ $formattedData['customer_phone'] }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-500">{{ $formattedData['customer_phone'] }}</span>
                                @endif
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Staff Member</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <x-heroicon-m-user-circle class="h-5 w-5 text-success-500" />
                                <span class="text-sm">{{ $formattedData['staff_name'] }}</span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Service</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <x-heroicon-m-briefcase class="h-5 w-5 text-info-500" />
                                <span class="text-sm">{{ $formattedData['service_name'] }}</span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Branch</dt>
                            <dd class="mt-1 flex items-center gap-2">
                                <x-heroicon-m-building-office class="h-5 w-5 text-warning-500" />
                                <span class="text-sm">{{ $formattedData['branch_name'] }}</span>
                            </dd>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        @elseif($activeTab === 'calcom')
            <!-- Cal.com Integration Tab -->
            <div class="space-y-6">
                <!-- Cal.com Details -->
                <x-filament::section icon="heroicon-o-link">
                    <x-slot name="heading">
                        Cal.com Details
                    </x-slot>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cal.com UID</dt>
                            <dd class="mt-1">
                                @if($formattedData['calcom_uid'] !== '-')
                                    <x-filament::badge color="gray">
                                        {{ $formattedData['calcom_uid'] }}
                                    </x-filament::badge>
                                @else
                                    <span class="text-sm text-gray-500">Not from Cal.com</span>
                                @endif
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Event Type</dt>
                            <dd class="mt-1">
                                <x-filament::badge color="info">
                                    {{ $formattedData['event_type'] }}
                                </x-filament::badge>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Location Type</dt>
                            <dd class="mt-1">
                                <x-filament::badge 
                                    :color="match($appointment->location_type) {
                                        'video' => 'success',
                                        'phone' => 'warning',
                                        'inPerson' => 'primary',
                                        default => 'gray'
                                    }">
                                    {{ $formattedData['location_type'] }}
                                </x-filament::badge>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Location Details</dt>
                            <dd class="mt-1 text-sm">{{ $formattedData['location_value'] }}</dd>
                        </div>
                        
                        @if($formattedData['meeting_url'])
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Meeting URL</dt>
                                <dd class="mt-1 flex items-center gap-2">
                                    <x-heroicon-m-video-camera class="h-5 w-5 text-success-500" />
                                    <a href="{{ $formattedData['meeting_url'] }}" target="_blank" class="text-sm text-primary-600 hover:underline">
                                        {{ $formattedData['meeting_url'] }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reschedule Link</dt>
                            <dd class="mt-1 text-sm">{{ $formattedData['reschedule_uid'] }}</dd>
                        </div>
                    </div>
                </x-filament::section>

                @if(count($formattedData['attendees']) > 0)
                    <!-- Attendees -->
                    <x-filament::section icon="heroicon-o-user-group">
                        <x-slot name="heading">
                            Attendees
                        </x-slot>
                        
                        <div class="space-y-4">
                            @foreach($formattedData['attendees'] as $attendee)
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-m-user class="h-4 w-4" />
                                        <span class="text-sm font-medium">{{ $attendee['name'] ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-m-envelope class="h-4 w-4" />
                                        <a href="mailto:{{ $attendee['email'] ?? '' }}" class="text-sm text-primary-600 hover:underline">
                                            {{ $attendee['email'] ?? 'N/A' }}
                                        </a>
                                    </div>
                                    <div>
                                        <x-filament::badge color="gray">
                                            {{ $attendee['timezone'] ?? 'N/A' }}
                                        </x-filament::badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif

                @if(count($formattedData['responses']) > 0)
                    <!-- Booking Form Responses -->
                    <x-filament::section icon="heroicon-o-clipboard-document-list">
                        <x-slot name="heading">
                            Booking Form Responses
                        </x-slot>
                        
                        <dl class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($formattedData['responses'] as $key => $value)
                                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $key }}</dt>
                                    <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-filament::section>
                @endif
            </div>
        @else
            <!-- Notes & Activity Tab -->
            <div class="space-y-6">
                <!-- Notes -->
                <x-filament::section icon="heroicon-o-document-text">
                    <x-slot name="heading">
                        Notes
                    </x-slot>
                    
                    @if(!empty($formattedData['notes']))
                        <div class="prose prose-sm max-w-none dark:prose-invert">
                            {!! Str::markdown($formattedData['notes']) !!}
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No notes available</p>
                    @endif
                </x-filament::section>

                <!-- Timeline -->
                <x-filament::section icon="heroicon-o-clock">
                    <x-slot name="heading">
                        Timeline
                    </x-slot>
                    
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-plus-circle class="h-5 w-5 text-success-500" />
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                                <dd class="text-sm">{{ $formattedData['created_at'] }}</dd>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-pencil-square class="h-5 w-5 text-warning-500" />
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                                <dd class="text-sm">{{ $formattedData['updated_at'] }}</dd>
                            </div>
                        </div>
                    </div>
                </x-filament::section>

                @if($formattedData['cancellation_reason'] || $formattedData['rejected_reason'])
                    <!-- Cancellation/Rejection -->
                    <x-filament::section icon="heroicon-o-x-circle">
                        <x-slot name="heading">
                            Cancellation/Rejection
                        </x-slot>
                        
                        <div class="space-y-4">
                            @if($formattedData['cancellation_reason'])
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cancellation Reason</dt>
                                    <dd class="mt-1 text-sm">{{ $formattedData['cancellation_reason'] }}</dd>
                                </div>
                            @endif
                            
                            @if($formattedData['rejected_reason'])
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rejection Reason</dt>
                                    <dd class="mt-1 text-sm">{{ $formattedData['rejected_reason'] }}</dd>
                                </div>
                            @endif
                        </div>
                    </x-filament::section>
                @endif
            </div>
        @endif
    </div>
</div>