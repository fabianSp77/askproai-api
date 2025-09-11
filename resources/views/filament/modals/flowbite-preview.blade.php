<div class="p-4">
    <h3 class="text-lg font-semibold mb-4">{{ $component['name'] ?? 'Component Preview' }}</h3>
    
    <div class="border rounded-lg p-4 bg-gray-50">
        <p class="text-sm text-gray-600 mb-2">Category: {{ $component['category'] ?? 'Unknown' }}</p>
        <p class="text-sm text-gray-600 mb-2">Type: {{ $component['type'] ?? 'Unknown' }}</p>
        
        @if(isset($component['interactive']) && $component['interactive'])
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded">
                Interactive
            </span>
        @endif
    </div>
    
    <div class="mt-4">
        <button 
            type="button"
            class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700"
            onclick="navigator.clipboard.writeText('Component code here')"
        >
            Copy Code
        </button>
    </div>
</div>
