<div class="p-6 bg-white rounded-lg shadow-md max-w-md mx-auto">
    <h2 class="text-2xl font-bold mb-4">Livewire Test Counter</h2>
    
    <div class="text-center mb-4">
        <span class="text-4xl font-bold">{{ $count }}</span>
    </div>
    
    <div class="flex justify-center space-x-4">
        <button wire:click="decrement" 
                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
            - Decrement
        </button>
        
        <button wire:click="increment" 
                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition">
            + Increment
        </button>
    </div>
    
    <div class="mt-4 text-sm text-gray-600 text-center">
        <p>This is a test component to verify Livewire is working correctly.</p>
        <p class="mt-2">Component loaded at: {{ now()->format('H:i:s') }}</p>
    </div>
</div>