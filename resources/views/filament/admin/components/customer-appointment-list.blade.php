@php
    $customer = $getRecord();
    $appointments = $customer->appointments()
        ->with(['service', 'staff', 'branch'])
        ->orderBy('starts_at', 'desc')
        ->paginate(10);
    
    $stats = [
        'total' => $customer->appointments()->count(),
        'completed' => $customer->appointments()->where('status', 'completed')->count(),
        'upcoming' => $customer->appointments()->where('starts_at', '>', now())->whereIn('status', ['scheduled', 'confirmed'])->count(),
        'cancelled' => $customer->appointments()->where('status', 'cancelled')->count(),
        'no_shows' => $customer->appointments()->where('status', 'no_show')->count(),
    ];
@endphp

<div class="customer-appointment-list">
    <!-- Quick Stats -->
    <div class="grid grid-cols-5 gap-3 mb-6">
        <div class="stat-card text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-500">Total</div>
        </div>
        <div class="stat-card text-center p-3 bg-success-50 dark:bg-success-900/20 rounded-lg">
            <div class="text-2xl font-bold text-success-600">{{ $stats['completed'] }}</div>
            <div class="text-xs text-gray-500">Completed</div>
        </div>
        <div class="stat-card text-center p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
            <div class="text-2xl font-bold text-primary-600">{{ $stats['upcoming'] }}</div>
            <div class="text-xs text-gray-500">Upcoming</div>
        </div>
        <div class="stat-card text-center p-3 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
            <div class="text-2xl font-bold text-warning-600">{{ $stats['cancelled'] }}</div>
            <div class="text-xs text-gray-500">Cancelled</div>
        </div>
        <div class="stat-card text-center p-3 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
            <div class="text-2xl font-bold text-danger-600">{{ $stats['no_shows'] }}</div>
            <div class="text-xs text-gray-500">No Shows</div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2 mb-4" x-data="{ activeTab: 'all' }">
        <button @click="activeTab = 'all'" 
                :class="activeTab === 'all' ? 'bg-primary-500 text-white' : 'bg-gray-200 dark:bg-gray-700'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">
            All ({{ $stats['total'] }})
        </button>
        <button @click="activeTab = 'upcoming'" 
                :class="activeTab === 'upcoming' ? 'bg-primary-500 text-white' : 'bg-gray-200 dark:bg-gray-700'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">
            Upcoming ({{ $stats['upcoming'] }})
        </button>
        <button @click="activeTab = 'completed'" 
                :class="activeTab === 'completed' ? 'bg-primary-500 text-white' : 'bg-gray-200 dark:bg-gray-700'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition">
            Completed ({{ $stats['completed'] }})
        </button>
    </div>

    <!-- Appointments List -->
    <div class="space-y-3">
        @forelse($appointments as $appointment)
            <div class="appointment-card bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow"
                 x-show="activeTab === 'all' || 
                        (activeTab === 'upcoming' && '{{ $appointment->starts_at }}' > '{{ now() }}' && ['scheduled', 'confirmed'].includes('{{ $appointment->status }}')) ||
                        (activeTab === 'completed' && '{{ $appointment->status }}' === 'completed')"
                 x-transition>
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                {{ $appointment->service->name ?? 'Service' }}
                            </h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ match($appointment->status) {
                                    'completed' => 'bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300',
                                    'confirmed' => 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300',
                                    'scheduled' => 'bg-info-100 text-info-800 dark:bg-info-900/30 dark:text-info-300',
                                    'cancelled' => 'bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-300',
                                    'no_show' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                } }}">
                                {{ ucfirst($appointment->status) }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Date & Time</p>
                                <p class="font-medium">{{ $appointment->starts_at->format('M j, Y') }}</p>
                                <p class="text-gray-600 dark:text-gray-400">{{ $appointment->starts_at->format('g:i A') }}</p>
                            </div>
                            
                            <div>
                                <p class="text-gray-500">Staff</p>
                                <p class="font-medium">{{ $appointment->staff->name ?? 'Unassigned' }}</p>
                            </div>
                            
                            <div>
                                <p class="text-gray-500">Branch</p>
                                <p class="font-medium">{{ $appointment->branch->name ?? 'N/A' }}</p>
                            </div>
                            
                            <div>
                                <p class="text-gray-500">Price</p>
                                <p class="font-medium text-gray-900 dark:text-white">€{{ number_format($appointment->price ?? 0, 2) }}</p>
                            </div>
                        </div>
                        
                        @if($appointment->notes)
                        <div class="mt-3 p-2 bg-gray-50 dark:bg-gray-900 rounded">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Notes:</span> {{ Str::limit($appointment->notes, 100) }}
                            </p>
                        </div>
                        @endif
                    </div>
                    
                    <div class="flex flex-col gap-2 ml-4">
                        <a href="{{ route('filament.admin.resources.ultimate-appointments.view', $appointment) }}" 
                           class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                            View
                        </a>
                        @if($appointment->starts_at->isFuture() && in_array($appointment->status, ['scheduled', 'confirmed']))
                        <button class="text-warning-600 hover:text-warning-700 text-sm font-medium">
                            Reschedule
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8">
                <x-heroicon-o-calendar class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <p class="text-gray-500">No appointments found</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($appointments->hasPages())
    <div class="mt-6">
        {{ $appointments->links() }}
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="mt-6 flex gap-3">
        <a href="{{ route('filament.admin.resources.ultimate-appointments.create', ['customer_id' => $customer->id]) }}" 
           class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition text-center font-medium">
            <x-heroicon-o-plus class="w-4 h-4 inline mr-2" />
            Book New Appointment
        </a>
        <button class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
            <x-heroicon-o-arrow-down-tray class="w-4 h-4 inline mr-2" />
            Export History
        </button>
    </div>
</div>