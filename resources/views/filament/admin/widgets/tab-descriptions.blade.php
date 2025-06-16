<x-filament-widgets::widget>
    <div class="tab-descriptions-widget">
        <div class="tab-descriptions-container">
            @foreach($tabs as $key => $tab)
                <div class="tab-description {{ $activeTab === $key ? 'active' : '' }}">
                    <h3>{{ $tab['label'] }}</h3>
                    <p>{{ $tab['description'] }}</p>
                    @if($activeTab === $key)
                        <div class="tab-description-connector"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>