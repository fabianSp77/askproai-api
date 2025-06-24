@php
    $tags = [];
    
    // Extract tags from analysis
    if (isset($getRecord()->analysis['tags']) && is_array($getRecord()->analysis['tags'])) {
        $tags = array_merge($tags, $getRecord()->analysis['tags']);
    }
    
    // Add automatic tags based on conditions
    if ($getRecord()->appointment_id) {
        $tags[] = ['label' => 'Termin gebucht', 'color' => 'success', 'icon' => 'heroicon-m-calendar-days'];
    }
    
    if ($getRecord()->duration_sec > 300) {
        $tags[] = ['label' => 'Langes Gespräch', 'color' => 'info', 'icon' => 'heroicon-m-clock'];
    }
    
    if (isset($getRecord()->analysis['urgency']) && $getRecord()->analysis['urgency'] === 'high') {
        $tags[] = ['label' => 'Dringend', 'color' => 'danger', 'icon' => 'heroicon-m-exclamation-triangle'];
    }
    
    if ($getRecord()->customer && $getRecord()->customer->tags && in_array('VIP', $getRecord()->customer->tags)) {
        $tags[] = ['label' => 'VIP Kunde', 'color' => 'warning', 'icon' => 'heroicon-m-star'];
    }
    
    // Process string tags into structured format
    $processedTags = [];
    foreach ($tags as $tag) {
        if (is_string($tag)) {
            // Auto-assign colors based on tag content
            $color = 'gray';
            $icon = 'heroicon-m-tag';
            
            if (stripos($tag, 'termin') !== false || stripos($tag, 'appointment') !== false) {
                $color = 'success';
                $icon = 'heroicon-m-calendar-days';
            } elseif (stripos($tag, 'beschwerde') !== false || stripos($tag, 'complaint') !== false) {
                $color = 'danger';
                $icon = 'heroicon-m-exclamation-circle';
            } elseif (stripos($tag, 'verkauf') !== false || stripos($tag, 'sales') !== false) {
                $color = 'primary';
                $icon = 'heroicon-m-currency-euro';
            } elseif (stripos($tag, 'support') !== false || stripos($tag, 'hilfe') !== false) {
                $color = 'info';
                $icon = 'heroicon-m-lifebuoy';
            }
            
            $processedTags[] = [
                'label' => $tag,
                'color' => $color,
                'icon' => $icon
            ];
        } else {
            $processedTags[] = $tag;
        }
    }
    
    // Limit displayed tags
    $displayTags = array_slice($processedTags, 0, 4);
    $remainingCount = count($processedTags) - count($displayTags);
@endphp

<div class="flex flex-wrap items-center gap-1">
    @forelse($displayTags as $tag)
        <div class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium transition-all duration-200 hover:scale-105 cursor-default
                    bg-{{ $tag['color'] }}-100 dark:bg-{{ $tag['color'] }}-900/20 
                    text-{{ $tag['color'] }}-700 dark:text-{{ $tag['color'] }}-300
                    border border-{{ $tag['color'] }}-200 dark:border-{{ $tag['color'] }}-800/50">
            <x-dynamic-component 
                :component="$tag['icon']" 
                class="w-3 h-3"
            />
            <span>{{ Str::limit($tag['label'], 15) }}</span>
        </div>
    @empty
        <div class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs text-gray-400 dark:text-gray-600">
            <x-heroicon-m-tag class="w-3 h-3" />
            <span>Keine Tags</span>
        </div>
    @endforelse
    
    @if($remainingCount > 0)
        <div class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-800 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
            +{{ $remainingCount }}
        </div>
    @endif
</div>