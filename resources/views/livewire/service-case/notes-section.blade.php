<div class="space-y-4" role="region" aria-label="Notizen">
    {{-- Add New Note Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <form wire:submit="addNote" class="space-y-3">
            <div>
                <label for="new-note" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Neue Notiz hinzufugen
                </label>
                <textarea
                    id="new-note"
                    wire:model="newNoteContent"
                    rows="3"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 text-sm"
                    placeholder="Notiz eingeben..."
                    aria-describedby="note-help"
                ></textarea>
                @error('newNoteContent')
                    <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror
                <p id="note-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Min. 3 Zeichen, max. 5000 Zeichen
                </p>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input
                        type="checkbox"
                        wire:model="newNoteInternal"
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    >
                    <span>Interne Notiz (nur fur Mitarbeiter sichtbar)</span>
                </label>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-wait"
                >
                    <x-heroicon-o-plus class="w-4 h-4" />
                    <span wire:loading.remove wire:target="addNote">Hinzufugen</span>
                    <span wire:loading wire:target="addNote">Speichern...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Notes List --}}
    <div class="space-y-3" role="list" aria-label="Notizenliste">
        @forelse($notes as $note)
            <div class="note-item" role="listitem">
                @include('livewire.service-case.partials.note-item', ['note' => $note, 'depth' => 0])
            </div>
        @empty
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-chat-bubble-left-ellipsis class="w-12 h-12 mx-auto mb-3 opacity-50" />
                <p class="text-sm">Noch keine Notizen vorhanden</p>
                <p class="text-xs mt-1">Fugen Sie die erste Notiz zu diesem Case hinzu</p>
            </div>
        @endforelse
    </div>

    {{-- Live region for screen reader announcements --}}
    <div aria-live="polite" aria-atomic="true" class="sr-only">
        <span wire:loading wire:target="addNote">Notiz wird gespeichert</span>
        <span wire:loading wire:target="submitReply">Antwort wird gespeichert</span>
        <span wire:loading wire:target="deleteNote">Notiz wird geloscht</span>
    </div>
</div>
