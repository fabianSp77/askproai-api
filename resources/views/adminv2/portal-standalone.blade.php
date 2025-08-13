<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin v2 Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .dashboard-container {
            display: none;
        }
        .dashboard-container.active {
            display: block;
        }
        .login-container.hidden {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Login Section -->
    <div id="loginContainer" class="login-container">
        <div class="w-full max-w-md p-8 bg-white rounded-lg shadow-lg">
            <h2 class="text-2xl font-bold text-center mb-6">Admin v2 Login</h2>
            
            <div id="errorAlert" class="hidden mb-4 p-4 text-sm text-red-800 bg-red-100 rounded-lg"></div>
            <div id="successAlert" class="hidden mb-4 p-4 text-sm text-green-800 bg-green-100 rounded-lg"></div>
            
            <form id="loginForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" value="fabian@askproai.de" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition">
                    Login
                </button>
            </form>
        </div>
    </div>

    <!-- Dashboard Section -->
    <div id="dashboardContainer" class="dashboard-container">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200 px-4 py-2.5">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-xl font-semibold">Admin v2 Dashboard</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span id="userEmail" class="text-sm text-gray-600"></span>
                    <button onclick="logout()" class="text-sm text-red-600 hover:text-red-800">Logout</button>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex">
            <!-- Sidebar -->
            <aside class="w-64 bg-gray-800 min-h-screen">
                <div class="p-4">
                    <ul class="space-y-2">
                        <li>
                            <a href="#" onclick="loadPage('dashboard')" class="flex items-center p-2 text-white hover:bg-gray-700 rounded">
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="#" onclick="loadPage('calls')" class="flex items-center p-2 text-white hover:bg-gray-700 rounded">
                                Calls
                            </a>
                        </li>
                        <li>
                            <a href="#" onclick="loadPage('appointments')" class="flex items-center p-2 text-white hover:bg-gray-700 rounded">
                                Appointments
                            </a>
                        </li>
                        <li>
                            <a href="#" onclick="loadPage('customers')" class="flex items-center p-2 text-white hover:bg-gray-700 rounded">
                                Customers
                            </a>
                        </li>
                        <li>
                            <a href="#" onclick="loadPage('companies')" class="flex items-center p-2 text-white hover:bg-gray-700 rounded">
                                Companies
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Content Area -->
            <main class="flex-1 p-6">
                <div id="contentArea">
                    <!-- Dynamic content loaded here -->
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.4.1/dist/flowbite.min.js"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let currentUser = null;
        let authToken = null;

        // Check authentication on load
        window.addEventListener('load', checkAuth);

        async function checkAuth() {
            try {
                const response = await fetch('/admin-v2/api/check', {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.authenticated) {
                    currentUser = data.user;
                    showDashboard();
                }
            } catch (error) {
                console.log('Not authenticated');
            }
        }

        // Login form handler
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            try {
                const response = await fetch('/admin-v2/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    currentUser = data.user;
                    authToken = data.token;
                    
                    document.getElementById('successAlert').textContent = 'Login successful!';
                    document.getElementById('successAlert').classList.remove('hidden');
                    
                    setTimeout(() => {
                        showDashboard();
                    }, 500);
                } else {
                    throw new Error(data.message || 'Login failed');
                }
            } catch (error) {
                document.getElementById('errorAlert').textContent = error.message;
                document.getElementById('errorAlert').classList.remove('hidden');
            }
        });

        function showDashboard() {
            document.getElementById('loginContainer').classList.add('hidden');
            document.getElementById('dashboardContainer').classList.add('active');
            document.getElementById('userEmail').textContent = currentUser.email;
            loadPage('dashboard');
        }

        async function loadPage(page) {
            const contentArea = document.getElementById('contentArea');
            
            // Show loading state
            contentArea.innerHTML = '<div class="text-center py-8">Loading...</div>';
            
            try {
                // For now, show static content
                // In production, this would fetch from API endpoints
                let content = '';
                
                switch(page) {
                    case 'dashboard':
                        content = `
                            <h1 class="text-2xl font-bold mb-6">Dashboard</h1>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="bg-white p-6 rounded-lg shadow">
                                    <h3 class="text-sm text-gray-500 mb-2">Total Calls</h3>
                                    <p class="text-2xl font-bold">1,234</p>
                                </div>
                                <div class="bg-white p-6 rounded-lg shadow">
                                    <h3 class="text-sm text-gray-500 mb-2">Appointments</h3>
                                    <p class="text-2xl font-bold">567</p>
                                </div>
                                <div class="bg-white p-6 rounded-lg shadow">
                                    <h3 class="text-sm text-gray-500 mb-2">Customers</h3>
                                    <p class="text-2xl font-bold">890</p>
                                </div>
                                <div class="bg-white p-6 rounded-lg shadow">
                                    <h3 class="text-sm text-gray-500 mb-2">Revenue</h3>
                                    <p class="text-2xl font-bold">€12,345</p>
                                </div>
                            </div>
                        `;
                        break;
                        
                    case 'calls':
                        content = `
                            <h1 class="text-2xl font-bold mb-6">Calls</h1>
                            <div class="bg-white rounded-lg shadow p-6">
                                <p>Call management interface would load here.</p>
                                <p class="mt-2 text-sm text-gray-600">This bypasses the 405 error by using API endpoints.</p>
                            </div>
                        `;
                        break;
                        
                    case 'appointments':
                        content = `
                            <h1 class="text-2xl font-bold mb-6">Appointments</h1>
                            <div class="bg-white rounded-lg shadow p-6">
                                <p>Appointment management interface would load here.</p>
                            </div>
                        `;
                        break;
                        
                    case 'customers':
                        content = `
                            <h1 class="text-2xl font-bold mb-6">Customers</h1>
                            <div class="bg-white rounded-lg shadow p-6">
                                <p>Customer management interface would load here.</p>
                            </div>
                        `;
                        break;
                        
                    case 'companies':
                        content = `
                            <h1 class="text-2xl font-bold mb-6">Companies</h1>
                            <div class="bg-white rounded-lg shadow p-6">
                                <p>Company management interface would load here.</p>
                            </div>
                        `;
                        break;
                        
                    default:
                        content = '<p>Page not found</p>';
                }
                
                contentArea.innerHTML = content;
                
            } catch (error) {
                contentArea.innerHTML = `<div class="text-red-600">Error loading page: ${error.message}</div>`;
            }
        }

        async function logout() {
            try {
                await fetch('/admin-v2/api/logout', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
            } catch (error) {
                console.error('Logout error:', error);
            }
            
            // Reset state
            currentUser = null;
            authToken = null;
            
            // Show login
            document.getElementById('dashboardContainer').classList.remove('active');
            document.getElementById('loginContainer').classList.remove('hidden');
            
            // Clear form
            document.getElementById('password').value = '';
        }
    </script>
</body>
</html>