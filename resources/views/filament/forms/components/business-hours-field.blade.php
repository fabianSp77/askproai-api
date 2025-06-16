<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }">
        @livewire('business-hours-manager', [
            'businessHours' => $getState() ?? [],
            'filialName' => $getRecord()?->name ?? ''
        ])
        
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('business-hours-updated', (hours) => {
                    @this.set('{{ $getStatePath() }}', hours[0]);
                });
            });
        </script>
    </div>
</x-dynamic-component>
