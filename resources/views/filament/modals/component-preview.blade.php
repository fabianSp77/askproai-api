<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <strong>Name:</strong> {{ $component->name }}
            </div>
            <div>
                <strong>Type:</strong> 
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                    @if($component->type === 'blade') bg-blue-100 text-blue-800 
                    @elseif($component->type === 'alpine') bg-green-100 text-green-800
                    @else bg-yellow-100 text-yellow-800 @endif">
                    {{ ucfirst($component->type) }}
                </span>
            </div>
            <div>
                <strong>Category:</strong> {{ $component->category }}
            </div>
            <div>
                <strong>Size:</strong> {{ $component->file_size > 1024 ? round($component->file_size / 1024, 2) . ' KB' : $component->file_size . ' B' }}
            </div>
            <div>
                <strong>Interactive:</strong> {{ $component->interactive ? 'Yes' : 'No' }}
            </div>
            <div>
                <strong>Path:</strong> <code class="text-xs">{{ $component->path }}</code>
            </div>
        </div>
    </div>
    
    <div class="border rounded-lg p-4 bg-white dark:bg-gray-900">
        <div class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            Component Preview:
        </div>
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded p-4 min-h-32">
            @try
                <x-dynamic-component :component="$component->path" />
            @catch(Exception $e)
                <div class="text-red-500 text-sm">
                    <strong>Preview Error:</strong> {{ $e->getMessage() }}
                </div>
            @endtry
        </div>
    </div>
    
    <div class="text-xs text-gray-500 dark:text-gray-400">
        <strong>File Path:</strong> {{ $component->file_path }}
    </div>
</div>