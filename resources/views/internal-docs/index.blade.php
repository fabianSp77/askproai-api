<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Documentation - AskPro AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'askpro-bg': '#0f172a',
                        'askpro-card': '#1e293b',
                        'askpro-border': '#334155',
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-askpro-bg text-gray-100 min-h-screen">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-askpro-bg/95 backdrop-blur border-b border-askpro-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <a href="/admin" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="font-bold text-xl gradient-text">Internal Docs</span>
                    </a>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-400">{{ auth()->user()->name ?? 'Admin' }}</span>
                    <a href="/admin" class="px-4 py-2 rounded-lg bg-askpro-card hover:bg-gray-700 transition-colors text-sm">
                        Zurueck zum Admin
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">Internal Documentation</h1>
            <p class="text-gray-400">{{ $totalFiles }} Dokumente verfuegbar - Nur fuer Super Admins</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            @foreach($categories as $categoryName => $items)
            <div class="bg-askpro-card rounded-lg p-4 border border-askpro-border">
                <div class="text-2xl font-bold text-white">{{ count($items) }}</div>
                <div class="text-sm text-gray-400">{{ $categoryName }}</div>
            </div>
            @endforeach
        </div>

        <!-- Categories -->
        @foreach($categories as $categoryName => $items)
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                @switch($categoryName)
                    @case('Load Testing')
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        @break
                    @case('Agent Documentation')
                        <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                        @break
                    @case('API Tester')
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        @break
                    @case('Email Templates')
                        <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                        @break
                    @case('System Documentation')
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        @break
                    @default
                        <span class="w-3 h-3 rounded-full bg-gray-500"></span>
                @endswitch
                {{ $categoryName }}
                <span class="text-sm text-gray-500 font-normal">({{ count($items) }})</span>
            </h2>

            <div class="bg-askpro-card rounded-xl border border-askpro-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-askpro-bg/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Dokument</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider hidden md:table-cell">Groesse</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider hidden sm:table-cell">Geaendert</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Aktion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-askpro-border">
                            @foreach($items as $file)
                            <tr class="hover:bg-askpro-bg/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <div>
                                            <a href="{{ route('internal.show', $file['name']) }}" class="font-medium text-white hover:text-blue-400 transition-colors">
                                                {{ $file['title'] }}
                                            </a>
                                            <p class="text-xs text-gray-500 font-mono">{{ $file['name'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400 hidden md:table-cell">{{ $file['size'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-400 hidden sm:table-cell">{{ $file['modified'] }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('internal.show', $file['name']) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 transition-colors text-sm"
                                       target="_blank">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Oeffnen
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        @endforeach

        @if(empty($categories))
        <div class="bg-askpro-card rounded-xl p-12 border border-askpro-border text-center">
            <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-400 mb-2">Keine Dokumente gefunden</h3>
            <p class="text-gray-500">Es sind keine internen Dokumente verfuegbar.</p>
        </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="border-t border-askpro-border py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-500">
            <p>AskPro AI Gateway - Internal Documentation</p>
            <p class="mt-1">Zugriff nur fuer autorisierte Administratoren</p>
        </div>
    </footer>
</body>
</html>
