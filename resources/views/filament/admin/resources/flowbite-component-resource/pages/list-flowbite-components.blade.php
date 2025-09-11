<x-filament-panels::page>
    <div class="space-y-6" x-data="{ 
        showPreviewModal: false, 
        showCodeModal: false, 
        currentComponent: null, 
        currentCode: ''
    }">
        
        <!-- Stats Header -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::card>
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary-600">{{ count($components) }}</div>
                    <div class="text-sm text-gray-500">Total Components</div>
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-center">
                    <div class="text-2xl font-bold text-success-600">
                        {{ count(array_filter($components, fn($c) => $c['type'] === 'alpine')) }}
                    </div>
                    <div class="text-sm text-gray-500">Alpine.js</div>
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-center">
                    <div class="text-2xl font-bold text-info-600">
                        {{ count(array_filter($components, fn($c) => $c['type'] === 'blade')) }}
                    </div>
                    <div class="text-sm text-gray-500">Blade</div>
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-center">
                    <div class="text-2xl font-bold text-warning-600">
                        {{ count(array_filter($components, fn($c) => $c['interactive'])) }}
                    </div>
                    <div class="text-sm text-gray-500">Interactive</div>
                </div>
            </x-filament::card>
        </div>
        
        <!-- Filters -->
        <x-filament::card>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Search Components
                    </label>
                    <x-filament::input 
                        wire:model.live="searchTerm" 
                        placeholder="Search by name or category..."
                        class="w-full"
                    />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Category
                    </label>
                    <select wire:model.live="selectedCategory" class="w-full">
                        @foreach($this->getCategories() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Type
                    </label>
                    <select wire:model.live="selectedType" class="w-full">
                        @foreach($this->getTypes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::card>
        
        <!-- Components Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($components as $component)
                <x-filament::card class="hover:shadow-lg transition-shadow duration-200">
                    <div class="space-y-3">
                        <!-- Component Header -->
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $component['name'] }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                    {{ $component['category'] }}
                                </p>
                            </div>
                            
                            @if($component['interactive'])
                                <x-filament::badge color="success" size="sm">
                                    Interactive
                                </x-filament::badge>
                            @endif
                        </div>
                        
                        <!-- Component Type & Size -->
                        <div class="flex items-center justify-between text-xs">
                            <x-filament::badge 
                                :color="match($component['type']) {
                                    'alpine' => 'success',
                                    'react-converted' => 'warning', 
                                    default => 'primary'
                                }"
                                size="sm"
                            >
                                {{ ucfirst($component['type']) }}
                            </x-filament::badge>
                            
                            <span class="text-gray-400">{{ $component['formatted_size'] }}</span>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex space-x-2">
                            <x-filament::button 
                                wire:click="previewComponent({{ $component['id'] }})"
                                size="sm" 
                                color="info"
                                class="flex-1"
                                icon="heroicon-m-eye"
                            >
                                Preview
                            </x-filament::button>
                            
                            <x-filament::button 
                                wire:click="viewCode({{ $component['id'] }})"
                                size="sm" 
                                color="gray"
                                class="flex-1"
                                icon="heroicon-m-code-bracket"
                            >
                                Code
                            </x-filament::button>
                        </div>
                        
                        <!-- Component Path -->
                        <div class="text-xs text-gray-400 dark:text-gray-500 truncate" title="{{ $component['path'] }}">
                            {{ $component['path'] }}
                        </div>
                    </div>
                </x-filament::card>
            @empty
                <div class="col-span-full">
                    <x-filament::card>
                        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <x-filament::icon 
                                icon="heroicon-o-squares-2x2" 
                                class="mx-auto h-12 w-12 text-gray-400 mb-4"
                            />
                            <h3 class="text-lg font-medium">No Components Found</h3>
                            <p class="text-sm mt-1">
                                @if($searchTerm || $selectedCategory || $selectedType)
                                    Try adjusting your filters or search term.
                                @else
                                    No Flowbite components were found in the filesystem.
                                @endif
                            </p>
                        </div>
                    </x-filament::card>
                </div>
            @endforelse
        </div>
        
        @if(count($components) > 0)
            <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                Showing {{ count($components) }} component{{ count($components) !== 1 ? 's' : '' }}
                @if($searchTerm || $selectedCategory || $selectedType)
                    (filtered)
                @endif
            </div>
        @endif
    </div>
    
    <!-- Preview Modal -->
    <x-filament::modal 
        id="component-preview-modal"
        wire:model="showPreviewModal"
        width="7xl"
        :close-by-clicking-away="false"
    >
        <x-slot name="header">
            <div class="flex items-center space-x-2">
                <x-filament::icon icon="heroicon-o-eye" class="h-5 w-5" />
                <span>Component Preview</span>
            </div>
        </x-slot>
        
        <div id="preview-content" class="space-y-4">
            <!-- Content will be loaded via Livewire -->
        </div>
        
        <x-slot name="footer">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'component-preview-modal' })">
                Close
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
    
    <!-- Code Modal -->
    <x-filament::modal 
        id="component-code-modal"
        wire:model="showCodeModal"
        width="7xl"
        :close-by-clicking-away="false"
    >
        <x-slot name="header">
            <div class="flex items-center space-x-2">
                <x-filament::icon icon="heroicon-o-code-bracket" class="h-5 w-5" />
                <span>Component Code</span>
            </div>
        </x-slot>
        
        <div id="code-content" class="space-y-4">
            <!-- Content will be loaded via Livewire -->
        </div>
        
        <x-slot name="footer">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'component-code-modal' })">
                Close
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
    
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-component-modal', (event) => {
                const data = event[0];
                
                if (data.type === 'preview') {
                    document.getElementById('preview-content').innerHTML = `
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><strong>Name:</strong> ${data.component.name}</div>
                                <div><strong>Type:</strong> <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${data.component.type}</span></div>
                                <div><strong>Category:</strong> ${data.component.category}</div>
                                <div><strong>Size:</strong> ${data.component.formatted_size}</div>
                                <div><strong>Interactive:</strong> ${data.component.interactive ? 'Yes' : 'No'}</div>
                                <div><strong>Path:</strong> <code class="text-xs">${data.component.path}</code></div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500">
                            <strong>File Path:</strong> ${data.component.file_path}
                        </div>
                    `;
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'component-preview-modal' }));
                    
                } else if (data.type === 'code') {
                    document.getElementById('code-content').innerHTML = `
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><strong>Component:</strong> ${data.component.name}</div>
                                <div><strong>Type:</strong> ${data.component.type}</div>
                                <div><strong>Category:</strong> ${data.component.category}</div>
                                <div><strong>Size:</strong> ${data.component.formatted_size}</div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Source Code:</h3>
                                <button onclick="copyCodeToClipboard()" class="text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 px-2 py-1 rounded">
                                    Copy Code
                                </button>
                            </div>
                            <div class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto">
                                <pre id="codeBlock" class="text-xs"><code>${data.code.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500">
                            <strong>File Path:</strong> ${data.component.file_path}
                        </div>
                    `;
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'component-code-modal' }));
                }
            });
        });
        
        function copyCodeToClipboard() {
            const codeBlock = document.getElementById('codeBlock');
            const textArea = document.createElement('textarea');
            textArea.value = codeBlock.innerText;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            // Show feedback
            event.target.innerText = 'Copied!';
            event.target.classList.add('bg-green-100', 'text-green-800');
            setTimeout(() => {
                event.target.innerText = 'Copy Code';
                event.target.classList.remove('bg-green-100', 'text-green-800');
            }, 2000);
        }
    </script>
</x-filament-panels::page>