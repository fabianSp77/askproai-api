<div class="w-full space-y-4">
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-200 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-200 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    <!-- Add Note Form -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-4">
        <form wire:submit.prevent="addNote">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Neue Notiz hinzufügen
                    </label>
                    <div class="flex space-x-2">
                        <select 
                            wire:model="newNoteType"
                            class="flex-shrink-0 block w-40 text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            @foreach($this->getTypeLabels() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea 
                            wire:model="newNoteContent"
                            placeholder="Ihre Notiz hier eingeben..."
                            rows="2"
                            class="flex-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                        ></textarea>
                    </div>
                    @error('newNoteContent') 
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
                    @enderror
                </div>
                <div class="flex justify-end space-x-2">
                    <button 
                        type="button"
                        wire:click="resetForm"
                        class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                    >
                        Zurücksetzen
                    </button>
                    <button 
                        type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-1.5 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="addNote">Notiz hinzufügen</span>
                        <span wire:loading wire:target="addNote">Wird gespeichert...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Existing Notes -->
    <div class="space-y-3">
        @forelse($notes as $note)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $this->getTypeColor($note['type']) }}">
                                {{ $this->getTypeLabels()[$note['type']] ?? $note['type'] }}
                            </span>
                            
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($note['created_at'])->format('d.m.Y H:i') }}
                                @if(isset($note['user']['name']))
                                    von {{ $note['user']['name'] }}
                                @endif
                            </span>
                        </div>
                        
                        <div class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $note['content'] }}</div>
                    </div>
                    
                    <div class="flex items-center space-x-1 ml-4">
                        <button 
                            type="button"
                            wire:click="deleteNote({{ $note['id'] }})"
                            wire:confirm="Sind Sie sicher, dass Sie diese Notiz löschen möchten?"
                            class="p-1 text-gray-400 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 rounded"
                            title="Notiz löschen"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">Noch keine Notizen vorhanden</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Fügen Sie die erste Notiz zu diesem Anruf hinzu.
                </p>
            </div>
        @endforelse
    </div>
</div>