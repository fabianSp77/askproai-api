<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-end gap-x-3">
            <x-filament::button type="submit">
                Save Test Values
            </x-filament::button>
        </div>
    </form>
    
    <div class="mt-8 p-4 bg-gray-100 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">Current Values:</h3>
        <pre class="text-sm">{{ json_encode($this->data, JSON_PRETTY_PRINT) }}</pre>
    </div>
    
    <div class="mt-4 p-4 bg-blue-50 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">Troubleshooting:</h3>
        <ul class="text-sm space-y-1">
            <li>• If no colors are visible, check browser console for errors</li>
            <li>• Try hard refresh (Ctrl+F5 or Cmd+Shift+R)</li>
            <li>• Ensure Filament assets are compiled: <code>npm run build</code></li>
            <li>• Check if Tailwind CSS classes are being purged</li>
        </ul>
    </div>
</x-filament-panels::page>