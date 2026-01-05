<?php

namespace App\Livewire\ServiceCase;

use App\Models\ServiceCase;
use App\Models\ServiceCaseNote;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * NotesSection Livewire Component
 *
 * Real-time notes management for ServiceCase view.
 * Uses Laravel Gate/Policy for authorization (multi-tenant safe).
 *
 * Features:
 * - Add new notes without page reload
 * - Reply to existing notes (threaded, max 3 levels)
 * - Delete own notes (within 30 minutes)
 * - Internal/external note visibility
 */
class NotesSection extends Component
{
    use AuthorizesRequests;

    public ServiceCase $serviceCase;

    public string $newNoteContent = '';
    public bool $newNoteInternal = false;

    public ?int $replyingToId = null;
    public string $replyContent = '';

    /**
     * Mount the component with the service case
     */
    public function mount(ServiceCase $serviceCase): void
    {
        $this->serviceCase = $serviceCase;
    }

    /**
     * Add a new top-level note
     * Uses Policy: ServiceCaseNotePolicy@create
     */
    public function addNote(): void
    {
        $this->validate([
            'newNoteContent' => 'required|string|min:3|max:5000',
        ]);

        // Use Gate/Policy for authorization (multi-tenant safe)
        if (!Gate::allows('create', [ServiceCaseNote::class, $this->serviceCase])) {
            $this->addError('newNoteContent', 'Sie haben keine Berechtigung, Notizen zu diesem Case hinzuzufügen.');
            return;
        }

        ServiceCaseNote::create([
            'service_case_id' => $this->serviceCase->id,
            'user_id' => Auth::id(),
            'content' => $this->newNoteContent,
            'is_internal' => $this->newNoteInternal,
        ]);

        $this->reset(['newNoteContent', 'newNoteInternal']);

        $this->dispatch('note-added');
    }

    /**
     * Start replying to a note
     */
    public function startReply(int $noteId): void
    {
        $this->replyingToId = $noteId;
        $this->replyContent = '';
    }

    /**
     * Cancel replying
     */
    public function cancelReply(): void
    {
        $this->replyingToId = null;
        $this->replyContent = '';
    }

    /**
     * Submit a reply to a note
     * Uses Policy: ServiceCaseNotePolicy@reply
     */
    public function submitReply(): void
    {
        $this->validate([
            'replyContent' => 'required|string|min:3|max:5000',
        ]);

        $parentNote = ServiceCaseNote::with('serviceCase')->findOrFail($this->replyingToId);

        // Use Gate/Policy for authorization (multi-tenant safe)
        if (!Gate::allows('reply', $parentNote)) {
            $this->addError('replyContent', 'Sie haben keine Berechtigung zu antworten.');
            return;
        }

        ServiceCaseNote::create([
            'service_case_id' => $this->serviceCase->id,
            'user_id' => Auth::id(),
            'parent_id' => $this->replyingToId,
            'content' => $this->replyContent,
            'is_internal' => $parentNote->is_internal, // Inherit from parent
        ]);

        $this->cancelReply();
        $this->dispatch('note-added');
    }

    /**
     * Delete a note
     * Uses Policy: ServiceCaseNotePolicy@delete (multi-tenant safe)
     */
    public function deleteNote(int $noteId): void
    {
        $note = ServiceCaseNote::with('serviceCase')->findOrFail($noteId);

        // Use Gate/Policy for authorization (multi-tenant safe)
        // Policy enforces: same company + (own note within 30min OR admin)
        if (!Gate::allows('delete', $note)) {
            $this->addError('deleteNote', 'Sie können diese Notiz nicht löschen.');
            return;
        }

        $note->delete();

        $this->dispatch('note-deleted');
    }

    /**
     * Get notes with eager loading
     */
    public function getNotes()
    {
        return $this->serviceCase
            ->topLevelNotes()
            ->with(['user', 'replies.user', 'replies.replies.user'])
            ->get();
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.service-case.notes-section', [
            'notes' => $this->getNotes(),
        ]);
    }
}
