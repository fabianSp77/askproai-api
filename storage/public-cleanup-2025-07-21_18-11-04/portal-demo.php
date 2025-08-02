<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AskProAI Business Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div id="app" class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">AskProAI Business Portal</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">demo@askproai.de</span>
                        <button class="text-sm text-gray-500 hover:text-gray-700">Logout</button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex space-x-8">
                    <a href="#dashboard" class="nav-item border-b-2 border-blue-500 text-blue-600 py-4 px-1 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="#calls" class="nav-item text-gray-500 hover:text-gray-700 py-4 px-1 text-sm font-medium">
                        Anrufe
                    </a>
                    <a href="#appointments" class="nav-item text-gray-500 hover:text-gray-700 py-4 px-1 text-sm font-medium">
                        Termine
                    </a>
                    <a href="#customers" class="nav-item text-gray-500 hover:text-gray-700 py-4 px-1 text-sm font-medium">
                        Kunden
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Anrufe Heute</h3>
                    <p class="text-3xl font-bold text-gray-900 mt-2">24</p>
                    <p class="text-sm text-green-600 mt-1">+12% vs gestern</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Termine Heute</h3>
                    <p class="text-3xl font-bold text-gray-900 mt-2">8</p>
                    <p class="text-sm text-blue-600 mt-1">3 ausstehend</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Neue Kunden</h3>
                    <p class="text-3xl font-bold text-gray-900 mt-2">5</p>
                    <p class="text-sm text-gray-600 mt-1">Diese Woche</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500">Erfolgsquote</h3>
                    <p class="text-3xl font-bold text-gray-900 mt-2">87%</p>
                    <p class="text-sm text-green-600 mt-1">+5% diese Woche</p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white shadow rounded-lg p-6 fade-in">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Letzte Aktivitäten</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-3 border-b">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Neuer Anruf von +49 123 456789</p>
                            <p class="text-sm text-gray-500">vor 5 Minuten</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            Termin gebucht
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Termin mit Max Mustermann</p>
                            <p class="text-sm text-gray-500">Heute, 14:00 Uhr</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            Bestätigt
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Verpasster Anruf</p>
                            <p class="text-sm text-gray-500">vor 1 Stunde</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            Rückruf ausstehend
                        </span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Simple navigation handling
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                // Remove active classes
                document.querySelectorAll('.nav-item').forEach(nav => {
                    nav.classList.remove('border-b-2', 'border-blue-500', 'text-blue-600');
                    nav.classList.add('text-gray-500');
                });
                // Add active class
                e.target.classList.remove('text-gray-500');
                e.target.classList.add('border-b-2', 'border-blue-500', 'text-blue-600');
            });
        });

        console.log('Portal Demo loaded successfully!');
    </script>
</body>
</html>