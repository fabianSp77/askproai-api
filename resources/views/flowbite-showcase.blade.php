<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flowbite Pro Components Showcase - AskProAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold text-gray-900 dark:text-white mb-4">
                🎨 Flowbite Pro Components
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-400">
                556+ Premium UI Components für Laravel
            </p>
            <div class="mt-6 flex justify-center gap-4">
                <span class="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full">
                    ✅ 104 Blade Templates
                </span>
                <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                    ⚡ Alpine.js Integration
                </span>
                <span class="bg-purple-100 text-purple-800 text-sm font-medium px-3 py-1 rounded-full">
                    🎯 Production Ready
                </span>
            </div>
        </div>

        <!-- Component Categories Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <!-- Authentication Components -->
            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-blue-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Authentication</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    6 Komponenten für Login, Registrierung und 2FA
                </p>
                <div class="space-y-2">
                    <a href="/flowbite-demo/authentication/sign-in" class="block text-blue-600 hover:underline">→ Sign In</a>
                    <a href="/flowbite-demo/authentication/sign-up" class="block text-blue-600 hover:underline">→ Sign Up</a>
                    <a href="/flowbite-demo/authentication/forgot-password" class="block text-blue-600 hover:underline">→ Password Reset</a>
                    <a href="/flowbite-demo/authentication/two-factor" class="block text-blue-600 hover:underline">→ Two-Factor Auth</a>
                </div>
            </div>

            <!-- E-Commerce Components -->
            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">E-Commerce</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    7 Komponenten für Shop und Zahlungen
                </p>
                <div class="space-y-2">
                    <a href="/flowbite-demo/e-commerce/products" class="block text-blue-600 hover:underline">→ Products</a>
                    <a href="/flowbite-demo/e-commerce/billing" class="block text-blue-600 hover:underline">→ Billing</a>
                    <a href="/flowbite-demo/e-commerce/invoices" class="block text-blue-600 hover:underline">→ Invoices</a>
                    <a href="/flowbite-demo/e-commerce/transactions" class="block text-blue-600 hover:underline">→ Transactions</a>
                </div>
            </div>

            <!-- Dashboard Components -->
            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-purple-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm1 5a1 1 0 00-1 1v6a1 1 0 001 1h12a1 1 0 001-1v-6a1 1 0 00-1-1H4z"/>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboards</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    10 Industry-spezifische Dashboard Templates
                </p>
                <div class="space-y-2">
                    <a href="/flowbite-demo/homepages/saas" class="block text-blue-600 hover:underline">→ SaaS Dashboard</a>
                    <a href="/flowbite-demo/homepages/e-commerce" class="block text-blue-600 hover:underline">→ E-Commerce</a>
                    <a href="/flowbite-demo/homepages/crypto" class="block text-blue-600 hover:underline">→ Crypto Trading</a>
                    <a href="/flowbite-demo/homepages/bank" class="block text-blue-600 hover:underline">→ Banking</a>
                </div>
            </div>

            <!-- Project Management -->
            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-indigo-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H4v10h12V5h-2a1 1 0 100-2 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Project Management</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    7 Komponenten für Projekt-Tools
                </p>
                <div class="space-y-2">
                    <a href="/flowbite-demo/project-management/all-projects" class="block text-blue-600 hover:underline">→ Projects</a>
                    <a href="/flowbite-demo/project-management/to-do" class="block text-blue-600 hover:underline">→ Todo Lists</a>
                    <a href="/flowbite-demo/pages/kanban" class="block text-blue-600 hover:underline">→ Kanban Board</a>
                    <a href="/flowbite-demo/project-management/all-files" class="block text-blue-600 hover:underline">→ File Manager</a>
                </div>
            </div>

            <!-- Tables & Data -->
            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-yellow-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 4a3 3 0 00-3 3v6a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H5zm-1 9v-1h5v2H5a1 1 0 01-1-1zm7 1h4a1 1 0 001-1v-1h-5v2z" clip-rule="evenodd"/>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Tables & Data</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Advanced Data Tables mit Sortierung
                </p>
                <div class="space-y-2">
                    <a href="/flowbite-demo/pages/datatables" class="block text-blue-600 hover:underline">→ Data Tables</a>
                    <a href="/flowbite-demo/users/list" class="block text-blue-600 hover:underline">→ User List</a>
                    <a href="/flowbite-demo/customers/list" class="block text-blue-600 hover:underline">→ Customer Management</a>
                </div>
            </div>

            <!-- Communication -->
            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                        <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.585 1.585A1 1 0 019 16h2a6 6 0 006-6V7h-2z"/>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Communication</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Chat, Video & Mailing Komponenten
                </p>
                <div class="space-y-2">
                    <a href="/flowbite-demo/pages/chat-room" class="block text-blue-600 hover:underline">→ Chat Room</a>
                    <a href="/flowbite-demo/mailing/inbox" class="block text-blue-600 hover:underline">→ Email Inbox</a>
                    <a href="/flowbite-demo/video/meeting-room" class="block text-blue-600 hover:underline">→ Video Meeting</a>
                    <a href="/flowbite-demo/pages/ai-chat" class="block text-blue-600 hover:underline">→ AI Chat</a>
                </div>
            </div>
        </div>

        <!-- Interactive Components Demo -->
        <div class="mt-16">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                ⚡ Interaktive Komponenten Demo
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Modal Demo -->
                <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
                    <h3 class="text-xl font-bold mb-4">Modal Dialog</h3>
                    <div x-data="{ open: false }">
                        <button @click="open = true" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                            Open Modal
                        </button>
                        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                            <div @click.away="open = false" class="bg-white rounded-lg shadow-lg p-6 max-w-md">
                                <h3 class="text-lg font-bold mb-4">Flowbite Modal</h3>
                                <p class="text-gray-600 mb-4">Dies ist ein Alpine.js-powered Modal!</p>
                                <button @click="open = false" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">
                                    Schließen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Demo -->
                <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
                    <h3 class="text-xl font-bold mb-4">Dropdown Menu</h3>
                    <div x-data="{ dropdown: false }" class="relative">
                        <button @click="dropdown = !dropdown" class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5">
                            Dropdown Button
                            <svg class="w-4 h-4 ml-2 inline" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="dropdown" @click.away="dropdown = false" x-cloak class="absolute z-10 mt-2 w-44 bg-white rounded-lg shadow dark:bg-gray-700">
                            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                                <li><a href="#" class="block px-4 py-2 hover:bg-gray-100">Dashboard</a></li>
                                <li><a href="#" class="block px-4 py-2 hover:bg-gray-100">Settings</a></li>
                                <li><a href="#" class="block px-4 py-2 hover:bg-gray-100">Earnings</a></li>
                                <li><a href="#" class="block px-4 py-2 hover:bg-gray-100">Sign out</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tabs Demo -->
                <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
                    <h3 class="text-xl font-bold mb-4">Tab Navigation</h3>
                    <div x-data="{ tab: 'profile' }">
                        <div class="border-b border-gray-200">
                            <ul class="flex flex-wrap -mb-px">
                                <li class="mr-2">
                                    <button @click="tab = 'profile'" :class="tab === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="inline-block p-4 border-b-2 rounded-t-lg">
                                        Profile
                                    </button>
                                </li>
                                <li class="mr-2">
                                    <button @click="tab = 'dashboard'" :class="tab === 'dashboard' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="inline-block p-4 border-b-2 rounded-t-lg">
                                        Dashboard
                                    </button>
                                </li>
                                <li class="mr-2">
                                    <button @click="tab = 'settings'" :class="tab === 'settings' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="inline-block p-4 border-b-2 rounded-t-lg">
                                        Settings
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="p-4">
                            <div x-show="tab === 'profile'">Profile Content</div>
                            <div x-show="tab === 'dashboard'">Dashboard Content</div>
                            <div x-show="tab === 'settings'">Settings Content</div>
                        </div>
                    </div>
                </div>

                <!-- Progress Demo -->
                <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
                    <h3 class="text-xl font-bold mb-4">Progress Bar</h3>
                    <div x-data="{ progress: 0 }" x-init="setInterval(() => progress = progress < 100 ? progress + 10 : 0, 1000)">
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" :style="`width: ${progress}%`"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">Progress: <span x-text="progress"></span>%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="mt-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-white text-center">
                <div>
                    <div class="text-4xl font-bold">556+</div>
                    <div class="text-sm opacity-90">Total Components</div>
                </div>
                <div>
                    <div class="text-4xl font-bold">104</div>
                    <div class="text-sm opacity-90">Blade Templates</div>
                </div>
                <div>
                    <div class="text-4xl font-bold">452</div>
                    <div class="text-sm opacity-90">React Patterns</div>
                </div>
                <div>
                    <div class="text-4xl font-bold">100%</div>
                    <div class="text-sm opacity-90">Laravel Native</div>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>