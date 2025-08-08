<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CallNote;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CallNoteController extends Controller
{
    /**
     * Store a new note for a call
     */
    public function store(Request $request, Call $call): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(CallNote::TYPES))],
            'content' => 'required|string|min:1|max:2000',
        ]);

        $note = CallNote::create([
            'call_id' => $call->id,
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'content' => trim($validated['content']),
        ]);

        // Load the user relationship for the response
        $note->load('user');

        return response()->json([
            'id' => $note->id,
            'call_id' => $note->call_id,
            'user_id' => $note->user_id,
            'type' => $note->type,
            'content' => $note->content,
            'created_at' => $note->created_at->toISOString(),
            'updated_at' => $note->updated_at->toISOString(),
            'user' => $note->user ? [
                'id' => $note->user->id,
                'name' => $note->user->name,
                'email' => $note->user->email,
            ] : null,
            'type_label' => $note->type_label,
        ], 201);
    }

    /**
     * Delete a note
     */
    public function destroy(Call $call, CallNote $note): JsonResponse
    {
        // Ensure the note belongs to the specified call
        if ($note->call_id !== $call->id) {
            return response()->json([
                'error' => 'Note does not belong to the specified call'
            ], 404);
        }

        // Check if user can delete this note
        // Users can delete their own notes, or admins can delete any note
        $user = Auth::user();
        if ($note->user_id !== $user->id && !$this->isAdmin($user)) {
            return response()->json([
                'error' => 'Unauthorized to delete this note'
            ], 403);
        }

        $note->delete();

        return response()->json([
            'message' => 'Note deleted successfully'
        ]);
    }

    /**
     * Check if user is admin (has admin privileges)
     */
    private function isAdmin($user): bool
    {
        // Check if user has admin role or permissions
        // This depends on your role/permission system
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['admin', 'super-admin']);
        }

        // Fallback: check if user is PortalUser with admin permissions
        if ($user instanceof \App\Models\PortalUser) {
            return $user->is_admin ?? false;
        }

        // For regular User model, you might check other conditions
        return false;
    }
}