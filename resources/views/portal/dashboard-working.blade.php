<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Business Portal Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    @if(!Auth::guard('portal')->check())
        <script>window.location.href = '/business/login';</script>
    @else
        <div class="min-h-screen">
            <nav class="bg-white shadow">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <h1 class="text-xl font-semibold">Business Portal</h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-700">{{ Auth::guard('portal')->user()->email }}</span>
                            <form method="POST" action="/business/logout" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
            
            <main class="py-10">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <h2 class="text-2xl font-bold mb-4">Welcome to your Dashboard</h2>
                            <p class="text-gray-600 mb-8">You are successfully logged in as {{ Auth::guard('portal')->user()->email }}</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="bg-blue-50 p-6 rounded-lg">
                                    <h3 class="text-lg font-semibold text-blue-900">Total Calls</h3>
                                    <p class="text-3xl font-bold text-blue-600 mt-2">0</p>
                                </div>
                                
                                <div class="bg-green-50 p-6 rounded-lg">
                                    <h3 class="text-lg font-semibold text-green-900">Appointments</h3>
                                    <p class="text-3xl font-bold text-green-600 mt-2">0</p>
                                </div>
                                
                                <div class="bg-purple-50 p-6 rounded-lg">
                                    <h3 class="text-lg font-semibold text-purple-900">Customers</h3>
                                    <p class="text-3xl font-bold text-purple-600 mt-2">0</p>
                                </div>
                            </div>
                            
                            <div class="mt-8">
                                <h3 class="text-lg font-semibold mb-4">Navigation</h3>
                                <nav class="space-y-2">
                                    <a href="/business/calls" class="block text-blue-600 hover:text-blue-800">→ View Calls</a>
                                    <a href="/business/appointments" class="block text-blue-600 hover:text-blue-800">→ View Appointments</a>
                                    <a href="/business/customers" class="block text-blue-600 hover:text-blue-800">→ View Customers</a>
                                    <a href="/business/settings" class="block text-blue-600 hover:text-blue-800">→ Settings</a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    @endif
</body>
</html>