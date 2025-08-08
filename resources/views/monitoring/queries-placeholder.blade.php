<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Queries - AskProAI Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <h1 class="text-2xl font-bold text-gray-900">🗄️ Database Queries</h1>
                    <a href="/telescope" class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Zurück zum Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex space-x-8 py-3">
                    <a href="/telescope" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">Dashboard</a>
                    <a href="/telescope/logs" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">Logs</a>
                    <a href="/telescope/queries" class="text-blue-600 border-b-2 border-blue-600 pb-3 px-1 font-medium text-sm">Queries</a>
                    <a href="/horizon" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">Horizon</a>
                </nav>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                <div class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Query Monitoring</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Erweiterte Query-Analyse ist in Entwicklung.<br>
                        Verwenden Sie das Dashboard für aktuelle Statistiken.
                    </p>
                    <a href="/telescope" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Zum Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>