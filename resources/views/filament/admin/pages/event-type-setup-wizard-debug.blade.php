<x-filament-panels::page>
    <div class="space-y-4">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
            <h3 class="font-semibold mb-2">Debug Information</h3>
            <p>Selected Company: {{ $selectedCompany ?? 'none' }}</p>
            <p>Selected Branch: {{ $selectedBranch ?? 'none' }}</p>
            <p>Branches Count: {{ count($branches) }}</p>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-1">Company</label>
            <select wire:model.live="selectedCompany" 
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @if(auth()->user()->company_id)
                    <option value="{{ auth()->user()->company_id }}">
                        {{ \App\Models\Company::find(auth()->user()->company_id)?->name }}
                    </option>
                @else
                    <option value="">Select Company...</option>
                    @foreach(\App\Models\Company::all() as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        
        @if($selectedCompany)
            <div>
                <label class="block text-sm font-medium mb-1">Branch ({{ count($branches) }} available)</label>
                <select wire:model="selectedBranch" 
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">All Branches</option>
                    @foreach($branches as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        
        <div class="mt-6 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <h4 class="font-semibold mb-2">Debug Log:</h4>
            <pre class="text-xs">
Company ID: {{ json_encode($selectedCompany) }}
Branch ID: {{ json_encode($selectedBranch) }}
Branches: {{ json_encode($branches, JSON_PRETTY_PRINT) }}
            </pre>
        </div>
    </div>
</x-filament-panels::page>