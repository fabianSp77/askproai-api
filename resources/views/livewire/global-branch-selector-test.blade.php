<div class="relative">
    {{-- Simple Test Dropdown --}}
    <div x-data="{ open: false }">
        <button 
            @click="open = !open"
            class="px-4 py-2 bg-blue-500 text-white rounded"
        >
            Toggle Dropdown (open: <span x-text="open"></span>)
        </button>
        
        <div 
            x-show="open"
            @click.away="open = false"
            class="absolute mt-2 w-48 bg-white border rounded shadow-lg"
        >
            <div class="p-4">
                <p>Dropdown Content</p>
                <button @click="open = false" class="mt-2 px-2 py-1 bg-gray-200 rounded">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>