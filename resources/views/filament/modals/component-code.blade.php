<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <strong>Component:</strong> {{ $component->name }}
            </div>
            <div>
                <strong>Type:</strong> {{ ucfirst($component->type) }}
            </div>
            <div>
                <strong>Category:</strong> {{ $component->category }}
            </div>
            <div>
                <strong>File Size:</strong> {{ $component->file_size > 1024 ? round($component->file_size / 1024, 2) . ' KB' : $component->file_size . ' B' }}
            </div>
        </div>
    </div>
    
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Source Code:
            </h3>
            <button onclick="copyToClipboard()" class="text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 px-2 py-1 rounded">
                Copy Code
            </button>
        </div>
        
        <div class="bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto">
            <pre id="codeBlock" class="text-xs"><code>{{ $code }}</code></pre>
        </div>
    </div>
    
    <div class="text-xs text-gray-500 dark:text-gray-400">
        <strong>File Path:</strong> {{ $component->file_path }}
    </div>
</div>

<script>
function copyToClipboard() {
    const codeBlock = document.getElementById('codeBlock');
    const textArea = document.createElement('textarea');
    textArea.value = codeBlock.innerText;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    
    // Show feedback
    const button = event.target;
    const originalText = button.innerText;
    button.innerText = 'Copied!';
    button.classList.add('bg-green-100', 'text-green-800');
    setTimeout(() => {
        button.innerText = originalText;
        button.classList.remove('bg-green-100', 'text-green-800');
    }, 2000);
}
</script>