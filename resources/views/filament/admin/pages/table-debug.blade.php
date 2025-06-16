<x-filament-panels::page>
    <div>
        <h3>Debug Information</h3>
        <div class="mb-4 p-4 bg-gray-100 rounded">
            <p>Session ID: {{ session()->getId() }}</p>
            <p>CSRF Token: {{ csrf_token() }}</p>
            <p>User ID: {{ auth()->id() }}</p>
            <p>Request Method: {{ request()->method() }}</p>
            <p>Is Livewire: {{ request()->hasHeader('X-Livewire') ? 'Yes' : 'No' }}</p>
        </div>
        
        {{ $this->table }}
    </div>
</x-filament-panels::page>