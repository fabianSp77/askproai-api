<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Welcome to Simple Dashboard</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded">
                    <h3 class="font-semibold text-blue-900">Auth Status</h3>
                    <p class="text-sm mt-2">
                        @if(auth()->check())
                            Logged in as: {{ auth()->user()->email }}
                        @else
                            Not authenticated
                        @endif
                    </p>
                </div>
                
                <div class="bg-green-50 p-4 rounded">
                    <h3 class="font-semibold text-green-900">User ID</h3>
                    <p class="text-sm mt-2">
                        {{ auth()->id() ?? "N/A" }}
                    </p>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded">
                    <h3 class="font-semibold text-yellow-900">Company ID</h3>
                    <p class="text-sm mt-2">
                        {{ auth()->user()->company_id ?? "N/A" }}
                    </p>
                </div>
            </div>
            
            <div class="mt-6">
                <h3 class="font-semibold mb-2">Quick Links</h3>
                <div class="space-x-4">
                    <a href="/admin" class="text-blue-600 hover:underline">Main Dashboard</a>
                    <a href="/admin/calls" class="text-blue-600 hover:underline">Calls</a>
                    <a href="/admin/companies" class="text-blue-600 hover:underline">Companies</a>
                    <a href="/business" class="text-blue-600 hover:underline">Business Portal</a>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
