<x-filament-widgets::widget>
    <x-filament::card>
        <div class="text-xl font-bold">Test Widget</div>
        <p>If you can see this, widgets are rendering correctly!</p>
        <p>Current time: {{ now()->format('H:i:s') }}</p>
    </x-filament::card>
</x-filament-widgets::widget>