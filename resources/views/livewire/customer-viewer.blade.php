<div class="fi-page-content">
    <div class="space-y-6">
        <!-- Customer Header -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                                {{ $customer->name }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Customer ID: #{{ $customer->id }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($customer->status === 'active')
                                <span class="fi-badge flex items-center gap-x-1 rounded-md text-xs font-medium px-2 py-1 bg-success-50 text-success-600 dark:bg-success-400/10 dark:text-success-400">
                                    Active
                                </span>
                            @else
                                <span class="fi-badge flex items-center gap-x-1 rounded-md text-xs font-medium px-2 py-1 bg-gray-50 text-gray-600 dark:bg-gray-400/10 dark:text-gray-400">
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="fi-tabs-ctn">
            <nav class="fi-tabs flex gap-x-6 border-b border-gray-200 dark:border-gray-700">
                <button 
                    wire:click="setActiveTab('overview')"
                    class="fi-tabs-item group flex items-center gap-x-2 pb-2 text-sm font-medium transition {{ $activeTab === 'overview' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    Overview
                </button>
                <button 
                    wire:click="setActiveTab('appointments')"
                    class="fi-tabs-item group flex items-center gap-x-2 pb-2 text-sm font-medium transition {{ $activeTab === 'appointments' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    Appointments ({{ $customer->appointments->count() }})
                </button>
                <button 
                    wire:click="setActiveTab('calls')"
                    class="fi-tabs-item group flex items-center gap-x-2 pb-2 text-sm font-medium transition {{ $activeTab === 'calls' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    Calls ({{ $customer->calls->count() }})
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    @if($activeTab === 'overview')
                        <!-- Overview Tab -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-4">Contact Information</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            <a href="mailto:{{ $customer->email }}" class="text-primary-600 hover:text-primary-500">
                                                {{ $customer->email }}
                                            </a>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            <a href="tel:{{ $customer->phone }}" class="text-primary-600 hover:text-primary-500">
                                                {{ $customer->phone ?: 'Not provided' }}
                                            </a>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ $customer->address ?: 'Not provided' }}
                                        </dd>
                                    </div>
                                    @if($customer->birthdate)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Birthdate</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($customer->birthdate)->format('d.m.Y') }}
                                        </dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                            
                            <div>
                                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-4">Additional Details</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ $customer->created_at->format('d.m.Y H:i') }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ $customer->updated_at->format('d.m.Y H:i') }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    @elseif($activeTab === 'appointments')
                        <!-- Appointments Tab -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Appointments</h3>
                            @if($customer->appointments->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($customer->appointments as $appointment)
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                    {{ $appointment->starts_at ? $appointment->starts_at->format('d.m.Y H:i') : 'N/A' }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                    {{ $appointment->service->name ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-2 text-sm">
                                                    <span class="fi-badge flex items-center gap-x-1 rounded-md text-xs font-medium px-2 py-1 
                                                        {{ $appointment->status === 'confirmed' ? 'bg-success-50 text-success-600 dark:bg-success-400/10 dark:text-success-400' : 
                                                           ($appointment->status === 'cancelled' ? 'bg-danger-50 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400' : 
                                                            'bg-gray-50 text-gray-600 dark:bg-gray-400/10 dark:text-gray-400') }}">
                                                        {{ ucfirst($appointment->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                    {{ $appointment->duration ?? 30 }} min
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No appointments found.</p>
                            @endif
                        </div>
                    @elseif($activeTab === 'calls')
                        <!-- Calls Tab -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Calls</h3>
                            @if($customer->calls->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Direction</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($customer->calls as $call)
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                    {{ $call->created_at->format('d.m.Y H:i') }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                    {{ ucfirst($call->direction ?? 'inbound') }}
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                    {{ gmdate('H:i:s', $call->duration_sec ?? 0) }}
                                                </td>
                                                <td class="px-4 py-2 text-sm">
                                                    <span class="fi-badge flex items-center gap-x-1 rounded-md text-xs font-medium px-2 py-1 
                                                        {{ $call->status === 'completed' ? 'bg-success-50 text-success-600 dark:bg-success-400/10 dark:text-success-400' : 
                                                           'bg-gray-50 text-gray-600 dark:bg-gray-400/10 dark:text-gray-400' }}">
                                                        {{ ucfirst($call->status ?? 'unknown') }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No calls found.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>