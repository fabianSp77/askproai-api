<x-filament-panels::page>
    <div wire:init="loadData">
        <div style="background: white; padding: 2rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Livewire Test Page</h2>
            
            <p style="margin-bottom: 0.5rem;">Message: {{ $message }}</p>
            <p style="margin-bottom: 0.5rem;">Counter: {{ $counter }}</p>
            <p style="margin-bottom: 1rem;">Data Loaded: {{ $dataLoaded ? 'Yes' : 'No' }}</p>
            
            <button 
                wire:click="increment"
                style="background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; cursor: pointer;">
                Increment Counter
            </button>
            
            <button 
                wire:click="loadData"
                style="background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; cursor: pointer; margin-left: 0.5rem;">
                Load Data Manually
            </button>
        </div>
        
        <div style="background: #f3f4f6; padding: 1rem; border-radius: 0.5rem;">
            <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Debug Info:</h3>
            <ul style="list-style: disc; margin-left: 1.5rem;">
                <li>wire:init should trigger loadData() automatically</li>
                <li>Check browser console for any JavaScript errors</li>
                <li>Check network tab for Livewire requests</li>
                <li>Look at Laravel logs for method calls</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>