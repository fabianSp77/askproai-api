@extends('portal.simple-layout')

@section('title', 'Dashboard')

@section('content')
        
        <main class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h2 class="text-2xl font-bold mb-4">Dashboard Without JavaScript</h2>
                        <p class="text-gray-600 mb-4">This dashboard has no JavaScript to test if redirects are coming from JS.</p>
                        
                        <div class="bg-green-100 p-4 rounded mb-4">
                            <p class="font-semibold">✅ You are authenticated!</p>
                            <p>User ID: {{ Auth::guard('portal')->user()->id }}</p>
                            <p>Email: {{ Auth::guard('portal')->user()->email }}</p>
                            <p>Company ID: {{ Auth::guard('portal')->user()->company_id }}</p>
                        </div>
                        
                        <div class="mt-6">
                            <h3 class="font-semibold mb-2">Navigation Links:</h3>
                            <ul class="space-y-2">
                                <li><a href="/business/calls" class="text-blue-600 hover:underline">View Calls</a></li>
                                <li><a href="/business/appointments" class="text-blue-600 hover:underline">View Appointments</a></li>
                                <li><a href="/business/customers" class="text-blue-600 hover:underline">View Customers</a></li>
                                <li><a href="/business/settings" class="text-blue-600 hover:underline">Settings</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>