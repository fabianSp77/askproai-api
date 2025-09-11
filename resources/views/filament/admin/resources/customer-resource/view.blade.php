<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Customer Profile Card with Flowbite --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center space-x-6">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold">
                            {{ substr($record->name ?? 'U', 0, 1) }}
                        </div>
                    </div>
                    
                    {{-- Customer Info --}}
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ $record->name }}
                        </h2>
                        <div class="flex flex-wrap gap-4 text-sm">
                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ $record->email ?? 'No email' }}
                            </div>
                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ $record->phone ?? 'No phone' }}
                            </div>
                            @if($record->birthdate)
                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $record->birthdate->format('Y-m-d') }} ({{ $record->birthdate->age }} years)
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Quick Stats --}}
                    <div class="flex-shrink-0">
                        <div class="flex space-x-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ $record->appointments()->count() }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Appointments</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ $record->calls()->count() }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Calls</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Customer Activity Timeline --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    Recent Activity
                </h3>
                
                <ol class="relative border-l border-gray-200 dark:border-gray-700">
                    @forelse($record->calls()->latest()->limit(5)->get() as $call)
                    <li class="mb-6 ml-4">
                        <div class="absolute w-3 h-3 bg-blue-600 rounded-full mt-1.5 -left-1.5 border border-white dark:border-gray-900"></div>
                        <time class="mb-1 text-sm font-normal leading-none text-gray-400 dark:text-gray-500">
                            {{ $call->start_timestamp?->format('Y-m-d H:i') ?? 'Unknown date' }}
                        </time>
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                            Call {{ $call->call_successful ? 'Completed' : 'Failed' }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Duration: {{ gmdate('H:i:s', $call->duration_sec ?? 0) }}
                            @if($call->agent)
                                • Agent: {{ $call->agent->name }}
                            @endif
                        </p>
                    </li>
                    @empty
                    <li class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
                    </li>
                    @endforelse
                </ol>
            </div>
        </div>

        {{-- Appointments Table with Flowbite --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    Appointments
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Date & Time</th>
                                <th scope="col" class="px-6 py-3">Service</th>
                                <th scope="col" class="px-6 py-3">Staff</th>
                                <th scope="col" class="px-6 py-3">Branch</th>
                                <th scope="col" class="px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($record->appointments()->latest()->limit(10)->get() as $appointment)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $appointment->starts_at?->format('Y-m-d H:i') ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $appointment->service?->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $appointment->staff?->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $appointment->branch?->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        {{ $appointment->status === 'scheduled' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : '' }}
                                        {{ $appointment->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                        {{ $appointment->status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}">
                                        {{ ucfirst($appointment->status ?? 'unknown') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No appointments found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Notes Section --}}
        @if($record->notes ?? false)
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Notes</h3>
                <div class="prose dark:prose-invert max-w-none">
                    {{ $record->notes }}
                </div>
            </div>
        </div>
        @endif
    </div>
    
    {{-- Initialize Flowbite components --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.initFlowbite === 'function') {
                window.initFlowbite();
            }
        });
    </script>
</x-filament-panels::page>