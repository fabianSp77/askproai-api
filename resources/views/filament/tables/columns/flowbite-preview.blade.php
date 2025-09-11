<div class="flex items-center space-x-2">
    <button 
        type="button"
        class="text-primary-600 hover:text-primary-700 text-sm font-medium"
        wire:click="$emit('openPreview', {{ json_encode($getState()) }})"
    >
        Preview
    </button>
</div>
