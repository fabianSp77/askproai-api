<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm {{ $isCompact ? 'p-4' : 'p-6' }}">
    @if(!$isCompact)
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-phone-arrow-up-right class="w-5 h-5 text-primary-600" />
                Quick AI Call
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Initiate an AI-powered outbound call
            </p>
        </div>
    @endif

    <form wire:submit="initiateCall" class="space-y-4">
        {{ $this->form }}

        <div class="flex items-center gap-3 {{ $isCompact ? 'mt-3' : 'mt-6' }}">
            <x-filament::button 
                type="submit" 
                icon="heroicon-o-phone-arrow-up-right"
                wire:loading.attr="disabled"
                :size="$isCompact ? 'sm' : 'md'"
            >
                <span wire:loading.remove>
                    {{ $isCompact ? 'Call' : 'Initiate Call' }}
                </span>
                <span wire:loading>
                    <x-filament::loading-indicator class="w-4 h-4" />
                    Initiating...
                </span>
            </x-filament::button>

            @if($customerId && !$isCompact)
                <span class="text-sm text-gray-500">
                    Calling: {{ $phoneNumber }}
                </span>
            @endif
        </div>
    </form>

    @if(!$isCompact)
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                Common Scenarios
            </h4>
            <div class="grid grid-cols-2 gap-2">
                <button
                    wire:click="$set('purpose', 'appointment_reminder')"
                    class="text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    :class="{ 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400': purpose === 'appointment_reminder' }"
                >
                    <x-heroicon-m-calendar class="w-4 h-4 inline mr-1" />
                    Appointment Reminder
                </button>
                
                <button
                    wire:click="$set('purpose', 'follow_up')"
                    class="text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    :class="{ 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400': purpose === 'follow_up' }"
                >
                    <x-heroicon-m-arrow-path class="w-4 h-4 inline mr-1" />
                    Follow-up Call
                </button>
                
                <button
                    wire:click="$set('purpose', 'feedback_collection')"
                    class="text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    :class="{ 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400': purpose === 'feedback_collection' }"
                >
                    <x-heroicon-m-chat-bubble-left-right class="w-4 h-4 inline mr-1" />
                    Collect Feedback
                </button>
                
                <button
                    wire:click="$set('purpose', 'no_show_follow_up')"
                    class="text-left px-3 py-2 text-sm rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    :class="{ 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400': purpose === 'no_show_follow_up' }"
                >
                    <x-heroicon-m-x-circle class="w-4 h-4 inline mr-1" />
                    No-Show Follow-up
                </button>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            // Listen for call initiated events
            window.addEventListener('call-initiated', event => {
                console.log('Call initiated:', event.detail);
                
                // Optional: Show additional UI feedback
                if (event.detail.callId) {
                    // Could open a call monitoring modal or update UI
                }
            });
        </script>
    @endpush
</div>