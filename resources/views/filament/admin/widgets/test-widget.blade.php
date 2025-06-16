<x-filament-widgets::widget>
    <x-filament::card>
        <div class="text-center p-4">
            <h2 class="text-2xl font-bold text-green-600">{{ $testData['message'] }}</h2>
            <p class="mt-2">Current Time: {{ $testData['time'] }}</p>
            <p class="mt-1">Logged in as: {{ $testData['user'] }}</p>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>