<x-filament-panels::page>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-medium mb-4">Wählen Sie eine Filiale</h3>
                
                <div class="space-y-2">
                    @if(count($branches) > 1)
                        <button
                            wire:click="switchBranch('')"
                            @class([
                                'w-full text-left p-4 rounded-lg transition',
                                'bg-primary-50 dark:bg-primary-900/20 border-2 border-primary-500' => $currentBranchId === '',
                                'bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600' => $currentBranchId !== '',
                            ])
                        >
                            <div class="font-semibold">🏢 Alle Filialen</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Zeige Daten aller Filialen</div>
                        </button>
                    @endif
                    
                    @foreach($branches as $branch)
                        <button
                            wire:click="switchBranch('{{ $branch['id'] }}')"
                            @class([
                                'w-full text-left p-4 rounded-lg transition',
                                'bg-primary-50 dark:bg-primary-900/20 border-2 border-primary-500' => $currentBranchId === $branch['id'],
                                'bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600' => $currentBranchId !== $branch['id'],
                                'opacity-50 cursor-not-allowed' => !$branch['is_active'],
                            ])
                            @disabled(!$branch['is_active'])
                        >
                            <div class="font-medium">{{ $branch['name'] }}</div>
                            @if($branch['company_name'])
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $branch['company_name'] }}</div>
                            @endif
                        </button>
                    @endforeach
                </div>
                
                <div class="mt-6 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        <strong>Aktuelle Filiale:</strong> 
                        @if($currentBranchId === '')
                            Alle Filialen
                        @else
                            {{ collect($branches)->firstWhere('id', $currentBranchId)['name'] ?? 'Unbekannt' }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>