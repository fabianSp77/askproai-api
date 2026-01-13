@php
    $isOwn = $note->user_id === auth()->id();
    $canDelete = $isOwn && $note->created_at->diffInMinutes(now()) <= 30;
    $canReply = $depth < 3;
    $isAdmin = auth()->user()->hasRole('admin') || auth()->user()->hasRole('super_admin');
@endphp

<div
    class="bg-white dark:bg-gray-800 rounded-lg border {{ $note->is_internal ? 'border-warning-300 dark:border-warning-700 bg-warning-50 dark:bg-warning-950' : 'border-gray-200 dark:border-gray-700' }} p-4 {{ $depth > 0 ? 'ml-' . ($depth * 6) : '' }}"
    style="{{ $depth > 0 ? 'margin-left: ' . ($depth * 1.5) . 'rem;' : '' }}"
>
    {{-- Note Header --}}
    <div class="flex items-start justify-between gap-3 mb-2">
        <div class="flex items-center gap-2">
            {{-- Author Avatar --}}
            <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">
                    {{ strtoupper(substr($note->user?->name ?? 'U', 0, 2)) }}
                </span>
            </div>

            <div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $note->user?->name ?? 'Unbekannt' }}
                    </span>
                    @if($note->is_internal)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-warning-100 dark:bg-warning-900 text-warning-800 dark:text-warning-200 rounded-full">
                            <x-heroicon-o-lock-closed class="w-3 h-3" />
                            Intern
                        </span>
                    @endif
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $note->created_at->diffForHumans() }}
                    @if($note->created_at->ne($note->updated_at))
                        <span class="ml-1">(bearbeitet)</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-1">
            @if($canReply)
                <button
                    wire:click="startReply({{ $note->id }})"
                    class="p-1.5 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    title="Antworten"
                    aria-label="Auf diese Notiz antworten"
                >
                    <x-heroicon-o-chat-bubble-left class="w-4 h-4" />
                </button>
            @endif

            @if($canDelete || $isAdmin)
                <button
                    wire:click="deleteNote({{ $note->id }})"
                    wire:confirm="Sind Sie sicher, dass Sie diese Notiz loschen mochten?"
                    class="p-1.5 text-gray-400 hover:text-danger-600 dark:hover:text-danger-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    title="Loschen"
                    aria-label="Diese Notiz loschen"
                >
                    <x-heroicon-o-trash class="w-4 h-4" />
                </button>
            @endif
        </div>
    </div>

    {{-- Note Content --}}
    <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words">
        {{ $note->content }}
    </div>

    {{-- Reply Form (inline) --}}
    @if($this->replyingToId === $note->id)
        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
            <form wire:submit="submitReply" class="space-y-2">
                <textarea
                    wire:model="replyContent"
                    rows="2"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 text-sm"
                    placeholder="Antwort eingeben..."
                    autofocus
                ></textarea>
                @error('replyContent')
                    <p class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror

                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        wire:click="cancelReply"
                        class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        Abbrechen
                    </button>
                    <button
                        type="submit"
                        class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="submitReply">Antworten</span>
                        <span wire:loading wire:target="submitReply">...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Nested Replies --}}
    @if($note->replies->count() > 0)
        <div class="mt-3 space-y-3">
            @foreach($note->replies as $reply)
                @include('livewire.service-case.partials.note-item', ['note' => $reply, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
