<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a simple request
$request = Illuminate\Http\Request::create('/');
$response = $kernel->handle($request);

// Check if we have a logged in user
$user = null;
if (auth('web')->check()) {
    $user = auth('web')->user();
} else {
    // Try to log in as admin
    $adminUser = \App\Models\User::where('email', 'admin@askproai.de')->first();
    if ($adminUser) {
        auth('web')->login($adminUser);
        $user = $adminUser;
    }
}

// Handle actions
$action = $_GET['action'] ?? null;
$message = null;
$messageType = 'info';

if ($action === 'logout') {
    auth('web')->logout();
    header('Location: /admin-enhanced.php');
    exit;
}

// Get statistics
$stats = [
    'total_companies' => \App\Models\Company::count(),
    'active_companies' => \App\Models\Company::where('is_active', true)->count(),
    'total_calls' => \App\Models\Call::count(),
    'calls_today' => \App\Models\Call::whereDate('created_at', today())->count(),
    'total_appointments' => \App\Models\Appointment::count(),
    'upcoming_appointments' => \App\Models\Appointment::where('starts_at', '>=', now())->count(),
    'total_users' => \App\Models\User::count(),
    'portal_users' => \App\Models\PortalUser::count(),
];

// Get filter parameters
$filterCompany = $_GET['company'] ?? null;
$filterDate = $_GET['date'] ?? 'today';
$searchTerm = $_GET['search'] ?? '';

// Date range calculation
$dateRange = match($filterDate) {
    'today' => [now()->startOfDay(), now()->endOfDay()],
    'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
    'week' => [now()->startOfWeek(), now()->endOfWeek()],
    'month' => [now()->startOfMonth(), now()->endOfMonth()],
    default => [now()->startOfDay(), now()->endOfDay()],
};

// Get companies for filter
$companies = \App\Models\Company::where('is_active', true)->orderBy('name')->get();

// Get filtered calls
$callsQuery = \App\Models\Call::whereBetween('created_at', $dateRange);
if ($filterCompany) {
    $callsQuery->where('company_id', $filterCompany);
}
if ($searchTerm) {
    $callsQuery->where(function($q) use ($searchTerm) {
        $q->where('from_number', 'like', "%{$searchTerm}%")
          ->orWhere('to_number', 'like', "%{$searchTerm}%")
          ->orWhere('id', 'like', "%{$searchTerm}%");
    });
}
$recentCalls = $callsQuery->latest()->limit(20)->get();

// Get appointments
$appointmentsQuery = \App\Models\Appointment::whereBetween('starts_at', $dateRange);
if ($filterCompany) {
    $appointmentsQuery->where('company_id', $filterCompany);
}
$appointments = $appointmentsQuery->orderBy('starts_at')->limit(20)->get();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Enhanced</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen" x-data="{ sidebarOpen: false }">
        <!-- Header -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <h1 class="text-xl font-bold text-gray-800">Admin Portal Enhanced</h1>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <?php if ($user): ?>
                            <span class="text-sm text-gray-600">
                                <?php echo htmlspecialchars($user->email); ?>
                            </span>
                            <a href="?action=logout" class="text-sm text-red-600 hover:text-red-800">
                                Logout
                            </a>
                        <?php else: ?>
                            <a href="/admin/login" class="text-sm text-blue-600 hover:text-blue-800">
                                Login
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex">
            <!-- Sidebar -->
            <div class="w-64 bg-gray-800 min-h-screen">
                <nav class="mt-5 px-2">
                    <a href="#dashboard" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-white bg-gray-900">
                        <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </a>
                    <a href="#calls" class="mt-1 group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                        <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Calls
                    </a>
                    <a href="#appointments" class="mt-1 group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                        <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Appointments
                    </a>
                    <a href="#companies" class="mt-1 group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                        <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Companies
                    </a>
                    <a href="#users" class="mt-1 group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                        <svg class="mr-4 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Users
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <main class="flex-1 relative overflow-y-auto focus:outline-none">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <!-- Filters -->
                        <div class="bg-white rounded-lg shadow p-4 mb-6">
                            <form method="GET" class="flex flex-wrap gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Company</label>
                                    <select name="company" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">All Companies</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?php echo $company->id; ?>" <?php echo $filterCompany == $company->id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($company->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date Range</label>
                                    <select name="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="today" <?php echo $filterDate === 'today' ? 'selected' : ''; ?>>Today</option>
                                        <option value="yesterday" <?php echo $filterDate === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                        <option value="week" <?php echo $filterDate === 'week' ? 'selected' : ''; ?>>This Week</option>
                                        <option value="month" <?php echo $filterDate === 'month' ? 'selected' : ''; ?>>This Month</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Search</label>
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                           placeholder="Phone, ID..."
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Apply Filters
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Dashboard Stats -->
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4" id="dashboard">
                            <!-- Companies Card -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Total Companies</dt>
                                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_companies']; ?></dd>
                                                <dd class="text-sm text-gray-500"><?php echo $stats['active_companies']; ?> active</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Calls Card -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Total Calls</dt>
                                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_calls']; ?></dd>
                                                <dd class="text-sm text-gray-500"><?php echo $stats['calls_today']; ?> today</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Appointments Card -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Appointments</dt>
                                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_appointments']; ?></dd>
                                                <dd class="text-sm text-gray-500"><?php echo $stats['upcoming_appointments']; ?> upcoming</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Users Card -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_users']; ?></dd>
                                                <dd class="text-sm text-gray-500"><?php echo $stats['portal_users']; ?> portal users</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Calls -->
                        <div class="mt-8" id="calls">
                            <h2 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Calls</h2>
                            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recentCalls as $call): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars(substr($call->id, 0, 8)); ?>...
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $call->company ? htmlspecialchars($call->company->name) : 'N/A'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($call->from_number ?? 'N/A'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($call->to_number ?? 'N/A'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $call->duration_sec ? gmdate("i:s", $call->duration_sec) : 'N/A'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $call->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo htmlspecialchars($call->status ?? 'unknown'); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $call->created_at ? $call->created_at->format('Y-m-d H:i') : 'N/A'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="/admin/calls/<?php echo $call->id; ?>" class="text-indigo-600 hover:text-indigo-900">View</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Appointments -->
                        <div class="mt-8" id="appointments">
                            <h2 class="text-lg leading-6 font-medium text-gray-900 mb-4">Appointments</h2>
                            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo $appointment->id; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $appointment->company ? htmlspecialchars($appointment->company->name) : 'N/A'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $appointment->customer ? htmlspecialchars($appointment->customer->name) : 'N/A'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $appointment->service ? htmlspecialchars($appointment->service->name) : 'N/A'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $appointment->starts_at ? $appointment->starts_at->format('Y-m-d H:i') : 'N/A'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $appointment->status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo htmlspecialchars($appointment->status); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="/admin/appointments/<?php echo $appointment->id; ?>" class="text-indigo-600 hover:text-indigo-900">View</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div class="mt-8 bg-gray-50 p-4 rounded">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Quick Links</h3>
                            <div class="grid grid-cols-4 gap-4">
                                <a href="/admin" class="text-blue-600 hover:underline">Filament Admin (500 Error)</a>
                                <a href="/business" class="text-blue-600 hover:underline">Business Portal</a>
                                <a href="/admin-working.php" class="text-blue-600 hover:underline">Simple Admin</a>
                                <a href="/horizon" class="text-blue-600 hover:underline">Horizon Queue Monitor</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>