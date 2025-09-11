<div class="flowbite-component-preview">
    @if(isset($component['path']))
        <div class="preview-header mb-4 pb-4 border-b dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ $component['name'] ?? 'Component Preview' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $component['category'] ?? '' }} 
                @if(isset($component['type']))
                    <span class="ml-2 px-2 py-0.5 text-xs rounded
                        @if($component['type'] == 'alpine') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($component['type'] == 'livewire') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                        @elseif($component['type'] == 'react-converted') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                        @endif">
                        {{ ucfirst($component['type']) }}
                    </span>
                @endif
            </p>
        </div>
        
        <div class="preview-tabs mb-4">
            <ul class="flex flex-wrap text-sm font-medium text-center text-gray-500 border-b border-gray-200 dark:text-gray-400 dark:border-gray-700">
                <li class="mr-2">
                    <button 
                        type="button"
                        onclick="switchPreviewTab('preview')"
                        class="preview-tab-btn inline-block p-4 rounded-t-lg hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:hover:text-gray-300 active"
                        data-tab="preview">
                        Preview
                    </button>
                </li>
                <li class="mr-2">
                    <button 
                        type="button"
                        onclick="switchPreviewTab('code')"
                        class="preview-tab-btn inline-block p-4 rounded-t-lg hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                        data-tab="code">
                        Code
                    </button>
                </li>
                <li class="mr-2">
                    <button 
                        type="button"
                        onclick="switchPreviewTab('usage')"
                        class="preview-tab-btn inline-block p-4 rounded-t-lg hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                        data-tab="usage">
                        Usage
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="preview-content">
            <!-- Preview Tab -->
            <div id="preview-tab" class="preview-tab-content">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-8 min-h-[200px]">
                    @php
                        $componentPath = str_replace(resource_path('views/components/'), '', $component['path']);
                        $componentPath = str_replace('.blade.php', '', $componentPath);
                        $componentPath = str_replace('/', '.', $componentPath);
                    @endphp
                    
                    @if(View::exists('components.' . $componentPath))
                        <x-dynamic-component :component="$componentPath" />
                    @else
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            Component preview not available
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Code Tab -->
            <div id="code-tab" class="preview-tab-content hidden">
                <div class="relative">
                    <button 
                        type="button"
                        onclick="copyComponentCode()"
                        class="absolute top-2 right-2 px-3 py-1 text-xs bg-gray-700 text-white rounded hover:bg-gray-600">
                        Copy
                    </button>
                    <pre class="bg-gray-900 text-gray-300 rounded-lg p-4 overflow-x-auto max-h-[400px]"><code id="component-code">{{ htmlspecialchars(file_get_contents($component['path'])) }}</code></pre>
                </div>
            </div>
            
            <!-- Usage Tab -->
            <div id="usage-tab" class="preview-tab-content hidden">
                <div class="prose dark:prose-invert max-w-none">
                    <h4>Usage Instructions</h4>
                    <p>To use this component in your Blade templates:</p>
                    <pre class="bg-gray-900 text-gray-300 rounded-lg p-4">
&lt;x-{{ $componentPath }} /&gt;</pre>
                    
                    @if($component['type'] == 'alpine')
                        <h5>Alpine.js Requirements</h5>
                        <p>This component uses Alpine.js. Make sure Alpine is loaded in your layout.</p>
                    @elseif($component['type'] == 'livewire')
                        <h5>Livewire Requirements</h5>
                        <p>This component uses Livewire. Make sure Livewire is installed and configured.</p>
                    @endif
                    
                    <h5>Props and Slots</h5>
                    <p>Check the code tab to see available props and slots for this component.</p>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
            No component data available
        </div>
    @endif
</div>

<script>
function switchPreviewTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.preview-tab-content').forEach(el => {
        el.classList.add('hidden');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.preview-tab-btn').forEach(el => {
        el.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
    });
    
    // Show selected tab
    document.getElementById(tab + '-tab').classList.remove('hidden');
    
    // Add active class to clicked button
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
}

function copyComponentCode() {
    const codeElement = document.getElementById('component-code');
    const text = codeElement.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        // Show success message
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        btn.classList.add('bg-green-600');
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.classList.remove('bg-green-600');
        }, 2000);
    });
}
</script>

<style>
.preview-tab-btn.active {
    @apply text-blue-600 border-b-2 border-blue-600;
}
</style>