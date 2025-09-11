@props([
    'title' => 'Form',
    'description' => '',
    'action' => '',
    'method' => 'POST',
    'fields' => [],
    'submitLabel' => 'Submit',
    'cancelUrl' => null,
    'layout' => 'single', // 'single', 'two-column', 'tabs'
    'sections' => []
])

<div class="p-4">
    {{-- Header Section --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title }}</h1>
        @if($description)
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
        @endif
    </div>

    {{-- Form Card --}}
    <form action="{{ $action }}" method="{{ $method === 'GET' ? 'GET' : 'POST' }}" class="space-y-6">
        @if($method !== 'GET' && $method !== 'POST')
            @method($method)
        @endif
        @csrf

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            @if($layout === 'tabs' && !empty($sections))
                {{-- Tabbed Layout --}}
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        @foreach($sections as $index => $section)
                            <button type="button"
                                    onclick="switchTab('{{ Str::slug($section['title']) }}')"
                                    data-tab="{{ Str::slug($section['title']) }}"
                                    class="tab-button {{ $index === 0 ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                {{ $section['title'] }}
                            </button>
                        @endforeach
                    </nav>
                </div>
                
                @foreach($sections as $index => $section)
                    <div id="tab-{{ Str::slug($section['title']) }}" 
                         class="tab-content p-6 {{ $index !== 0 ? 'hidden' : '' }}">
                        <div class="{{ $layout === 'two-column' ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : 'space-y-6' }}">
                            @foreach($section['fields'] as $field)
                                <x-admin.flowbite-field :field="$field" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                {{-- Regular Layout --}}
                <div class="p-6">
                    @if(!empty($sections))
                        @foreach($sections as $section)
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $section['title'] }}</h3>
                                @if(isset($section['description']))
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $section['description'] }}</p>
                                @endif
                                <div class="{{ $layout === 'two-column' ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : 'space-y-6' }}">
                                    @foreach($section['fields'] as $field)
                                        <x-admin.flowbite-field :field="$field" />
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="{{ $layout === 'two-column' ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : 'space-y-6' }}">
                            @foreach($fields as $field)
                                <x-admin.flowbite-field :field="$field" />
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
            
            {{-- Form Actions --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 rounded-b-lg">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="text-red-500">*</span> Required fields
                    </div>
                    <div class="flex items-center space-x-3">
                        @if($cancelUrl)
                            <a href="{{ $cancelUrl }}"
                               class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancel
                            </a>
                        @endif
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ $submitLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@if($layout === 'tabs')
<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active state from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    
    // Add active state to clicked button
    const activeButton = document.querySelector('[data-tab="' + tabName + '"]');
    activeButton.classList.remove('border-transparent', 'text-gray-500');
    activeButton.classList.add('border-blue-500', 'text-blue-600');
}
</script>
@endif