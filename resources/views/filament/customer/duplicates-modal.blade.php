<div class="space-y-4">
    {{-- Original Customer --}}
    <div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-lg border border-primary-200 dark:border-primary-800">
        <h4 class="font-semibold text-primary-900 dark:text-primary-100 mb-2">Aktueller Kunde</h4>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div>
                <span class="text-gray-600 dark:text-gray-400">Name:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $original->name }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">E-Mail:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $original->email ?? '-' }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Telefon:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $original->phone ?? '-' }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Unternehmen:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $original->company?->name ?? '-' }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Termine:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $original->appointments_count ?? 0 }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Erstellt:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $original->created_at->format('d.m.Y H:i') }}</span>
            </div>
        </div>
    </div>

    {{-- Duplicates --}}
    <div>
        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Mögliche Duplikate ({{ $duplicates->count() }})</h4>
        
        @forelse($duplicates as $duplicate)
            <div class="mb-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Name:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100 
                            @if(strtolower($duplicate->name) === strtolower($original->name)) text-red-600 dark:text-red-400 @endif">
                            {{ $duplicate->name }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">E-Mail:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100
                            @if($duplicate->email && strtolower($duplicate->email) === strtolower($original->email)) text-red-600 dark:text-red-400 @endif">
                            {{ $duplicate->email ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Telefon:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100
                            @if($duplicate->phone && $duplicate->phone === $original->phone) text-red-600 dark:text-red-400 @endif">
                            {{ $duplicate->phone ?? '-' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Unternehmen:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $duplicate->company?->name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Termine:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $duplicate->appointments()->count() }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Erstellt:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $duplicate->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
                
                {{-- Match indicators --}}
                <div class="mt-2 flex gap-2">
                    @if(strtolower($duplicate->name) === strtolower($original->name))
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <x-heroicon-m-check class="w-3 h-3 mr-1" />
                            Name identisch
                        </span>
                    @endif
                    @if($duplicate->email && strtolower($duplicate->email) === strtolower($original->email))
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <x-heroicon-m-check class="w-3 h-3 mr-1" />
                            E-Mail identisch
                        </span>
                    @endif
                    @if($duplicate->phone && $duplicate->phone === $original->phone)
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <x-heroicon-m-check class="w-3 h-3 mr-1" />
                            Telefon identisch
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                Keine Duplikate gefunden.
            </p>
        @endforelse
    </div>
    
    {{-- Merge hint --}}
    @if($duplicates->isNotEmpty())
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <p class="text-sm text-blue-700 dark:text-blue-300">
                <strong>Tipp:</strong> Verwenden Sie die "Zusammenführen"-Funktion, um diese Kunden zu einem Eintrag zu kombinieren. 
                Alle Termine und Daten werden dabei übertragen.
            </p>
        </div>
    @endif
</div>