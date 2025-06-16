<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">User & Tenant Information</h2>
            <div class="space-y-2">
                <p><strong>Current User:</strong> {{ $user?->email ?? 'Not logged in' }}</p>
                <p><strong>User ID:</strong> {{ $userId ?? 'N/A' }}</p>
                <p><strong>User Tenant ID:</strong> {{ $user?->tenant_id ?? 'N/A' }}</p>
                <p><strong>Current Tenant (from app):</strong> {{ $currentTenant ?? 'NULL' }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Data Counts Comparison</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">With Scopes</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Without Scopes</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Difference</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">Customers</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $customerCount }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $rawCustomerCount }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $rawCustomerCount - $customerCount }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">Appointments</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $appointmentCount }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $rawAppointmentCount }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $rawAppointmentCount - $appointmentCount }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">Calls</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $callCount }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $rawCallCount }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $rawCallCount - $callCount }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">Companies</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $companyCount }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $rawCompanyCount }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $rawCompanyCount - $companyCount }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Customers by Company</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company ID</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($customersByCompany as $data)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $data['company_id'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $data['count'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Applied Global Scopes</h2>
            @if(empty($appliedScopes))
                <p class="text-gray-500">No global scopes are being applied.</p>
            @else
                <ul class="list-disc list-inside">
                    @foreach($appliedScopes as $scope)
                        <li>{{ $scope }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-filament-panels::page>