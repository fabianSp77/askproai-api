<?php

namespace App\Livewire;

use App\Models\Call;
use App\Models\CallNote;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CallNotesComponent extends Component
{
    public Call $call;
    public $notes = [];
    public $newNoteType = 'general';
    public $newNoteContent = '';
    public $loading = false;

    protected $rules = [
        'newNoteType' => 'required|in:general,customer_feedback,internal,action_required,status_change,assignment,callback_scheduled',
        'newNoteContent' => 'required|string|min:1|max:2000',
    ];

    public function mount(Call $call)
    {
        $this->call = $call;
        $this->loadNotes();
    }

    public function loadNotes()
    {
        $this->notes = $this->call->callNotes()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function addNote()
    {
        $this->loading = true;
        
        $this->validate();

        try {
            $note = CallNote::create([
                'call_id' => $this->call->id,
                'user_id' => Auth::id(),
                'type' => $this->newNoteType,
                'content' => trim($this->newNoteContent),
            ]);

            // Load the user relationship
            $note->load('user');

            // Add to the beginning of the notes array
            array_unshift($this->notes, $note->toArray());

            // Reset form
            $this->resetForm();

            // Show success message
            session()->flash('success', 'Notiz erfolgreich hinzugefügt');

        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Hinzufügen der Notiz');
        } finally {
            $this->loading = false;
        }
    }

    public function deleteNote($noteId)
    {
        try {
            $note = CallNote::where('call_id', $this->call->id)->find($noteId);
            
            if (!$note) {
                session()->flash('error', 'Notiz nicht gefunden');
                return;
            }

            // Check permissions
            $user = Auth::user();
            if ($note->user_id !== $user->id && !$this->isAdmin($user)) {
                session()->flash('error', 'Keine Berechtigung zum Löschen dieser Notiz');
                return;
            }

            $note->delete();

            // Remove from notes array
            $this->notes = array_filter($this->notes, function($n) use ($noteId) {
                return $n['id'] != $noteId;
            });
            $this->notes = array_values($this->notes); // Re-index array

            session()->flash('success', 'Notiz erfolgreich gelöscht');

        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Löschen der Notiz');
        }
    }

    public function resetForm()
    {
        $this->newNoteType = 'general';
        $this->newNoteContent = '';
        $this->resetValidation();
    }

    public function getTypeLabels()
    {
        return CallNote::TYPES;
    }

    public function getTypeColor($type)
    {
        return match($type) {
            'general' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'customer_feedback' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'internal' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
            'action_required' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'status_change' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'assignment' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'callback_scheduled' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
        };
    }

    private function isAdmin($user): bool
    {
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['admin', 'super-admin']);
        }

        if ($user instanceof \App\Models\PortalUser) {
            return $user->is_admin ?? false;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.call-notes-component');
    }
}