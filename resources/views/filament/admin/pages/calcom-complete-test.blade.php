<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Test Form -->
        <form wire:submit="save">
            {{ $this->form }}
        </form>
        
        <!-- Test Actions Grid -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Individual Test Functions</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($this->getTestActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
        </div>
        
        <!-- Test Results Display -->
        @if(!empty($this->testResults))
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Test Results</h3>
                    
                    <div class="space-y-4">
                        @foreach($this->testResults as $testName => $result)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                                    {{ ucfirst(str_replace('_', ' ', $testName)) }}
                                </h4>
                                <pre class="text-xs bg-gray-50 dark:bg-gray-800 p-3 rounded overflow-x-auto">{{ json_encode($result, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Test Flow Documentation -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Test Flow & Documentation</h3>
            
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <h4>Complete Test Workflow:</h4>
                <ol>
                    <li><strong>Test API Connection:</strong> Verifies API key validity for v1 or v2</li>
                    <li><strong>Get Event Types:</strong> Retrieves all available appointment types and populates the dropdown</li>
                    <li><strong>Get Team/Staff:</strong> Lists all team members/hosts who can receive bookings</li>
                    <li><strong>Check Availability:</strong> Shows available time slots for selected date and event type</li>
                    <li><strong>Create Test Booking:</strong> Books an appointment with the test customer data</li>
                    <li><strong>Get Recent Bookings:</strong> Retrieves latest bookings to verify creation</li>
                    <li><strong>Update Booking:</strong> Tests modification of existing booking</li>
                    <li><strong>Cancel Booking:</strong> Tests booking cancellation</li>
                    <li><strong>Full Sync:</strong> Imports all Cal.com bookings into the system</li>
                </ol>
                
                <h4>API Version Differences:</h4>
                <table class="table-auto w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Feature</th>
                            <th class="text-left">API v1</th>
                            <th class="text-left">API v2</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Authentication</td>
                            <td>Query parameter (?apiKey=)</td>
                            <td>Bearer token (Authorization header)</td>
                        </tr>
                        <tr>
                            <td>Event Types</td>
                            <td>/v1/event-types</td>
                            <td>/v2/event-types</td>
                        </tr>
                        <tr>
                            <td>Bookings</td>
                            <td>/v1/bookings</td>
                            <td>/v2/bookings</td>
                        </tr>
                        <tr>
                            <td>Availability</td>
                            <td>/v1/availability</td>
                            <td>/v2/slots/available</td>
                        </tr>
                    </tbody>
                </table>
                
                <h4>Troubleshooting:</h4>
                <ul>
                    <li><strong>403 Errors:</strong> Usually means the API key is invalid or lacks permissions</li>
                    <li><strong>No Event Types:</strong> Check if your Cal.com account has active event types</li>
                    <li><strong>No Available Slots:</strong> Verify the event type has availability configured</li>
                    <li><strong>Booking Fails:</strong> Check if all required fields are provided and valid</li>
                </ul>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        // Auto-refresh form when event type changes
        window.addEventListener('livewire:updated', () => {
            Livewire.on('form-updated', () => {
                // Form will auto-update
            });
        });
    </script>
    @endpush
</x-filament-panels::page>