@props([
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'iconColor' => 'blue',
    'actions' => [],
    'footer' => null,
    'padding' => true,
    'shadow' => true,
    'border' => false
])

<div class="{{ $shadow ? 'shadow' : '' }} {{ $border ? 'border border-gray-200 dark:border-gray-700' : '' }} bg-white dark:bg-gray-800 rounded-lg">
    @if($title || !empty($actions))
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    @if($icon)
                        <div class="flex-shrink-0 mr-3">
                            <div class="p-3 bg-{{ $iconColor }}-100 dark:bg-{{ $iconColor }}-900 rounded-lg">
                                {!! $icon !!}
                            </div>
                        </div>
                    @endif
                    <div>
                        @if($title)
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                        @endif
                        @if($subtitle)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
                
                @if(!empty($actions))
                    <div class="flex items-center space-x-2">
                        @foreach($actions as $action)
                            @if(isset($action['dropdown']) && $action['dropdown'])
                                <div class="relative">
                                    <button type="button"
                                            onclick="toggleDropdown('{{ $action['id'] ?? 'dropdown' }}')"
                                            class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>
                                    <div id="{{ $action['id'] ?? 'dropdown' }}"
                                         class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg z-10">
                                        @foreach($action['items'] as $item)
                                            <a href="{{ $item['url'] ?? '#' }}"
                                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                {{ $item['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <a href="{{ $action['url'] ?? '#' }}"
                                   class="{{ $action['class'] ?? 'text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium' }}">
                                    {{ $action['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
    
    <div class="{{ $padding ? 'p-6' : '' }}">
        {{ $slot }}
    </div>
    
    @if($footer)
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 rounded-b-lg">
            {{ $footer }}
        </div>
    @endif
</div>

<script>
function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    dropdown.classList.toggle('hidden');
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('#' + id) && !event.target.closest('[onclick*="' + id + '"]')) {
            dropdown.classList.add('hidden');
        }
    }, { once: true });
}
</script>