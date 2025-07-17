<?php

namespace App\Http\Controllers\Portal\Api;
use Illuminate\Http\Request;
use App\Models\Feedback;
use App\Models\PortalUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FeedbackApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $company = $this->getCompany();
        $user = $this->getCurrentUser();
        
        if (!$company || !$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission
        if ($user instanceof PortalUser && !$user->hasPermissionTo('feedback.view_team')) {
            // Show only own feedback
            $query = Feedback::where('user_id', $user->id);
        } else {
            // Show all company feedback (admin or user with permission)
            $query = Feedback::where('company_id', $company->id);
        }

        // Apply filters
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Get feedback with user info
        $feedback = $query->with(['user', 'responses.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get statistics
        $stats = [
            'total' => Feedback::where('company_id', $company->id)->count(),
            'open' => Feedback::where('company_id', $company->id)->where('status', 'open')->count(),
            'in_progress' => Feedback::where('company_id', $company->id)->where('status', 'in_progress')->count(),
            'resolved' => Feedback::where('company_id', $company->id)->where('status', 'resolved')->count(),
            'avg_response_time' => $this->calculateAvgResponseTime($company->id),
        ];

        return response()->json([
            'feedback' => $feedback,
            'stats' => $stats
        ]);
    }

    public function store(Request $request)
    {
        $company = $this->getCompany();
        $user = $this->getCurrentUser();
        
        if (!$company || !$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission (skip for admin users)
        if ($user instanceof PortalUser && !$user->hasPermissionTo('feedback.create')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'type' => 'required|in:bug,feature,improvement,question,complaint',
            'priority' => 'required|in:low,medium,high,urgent',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240' // 10MB max
        ]);

        $feedback = new Feedback();
        $feedback->company_id = $company->id;
        $feedback->user_id = $user->id;
        $feedback->type = $request->type;
        $feedback->priority = $request->priority;
        $feedback->subject = $request->subject;
        $feedback->message = $request->message;
        $feedback->status = 'open';
        $feedback->save();

        // Handle attachments
        if ($request->hasFile('attachments')) {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('feedback-attachments/' . $feedback->id, 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType()
                ];
            }
            $feedback->attachments = $attachments;
            $feedback->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Feedback erfolgreich übermittelt',
            'feedback' => $feedback
        ]);
    }

    public function show($id)
    {
        $company = $this->getCompany();
        $user = $this->getCurrentUser();
        
        if (!$company || !$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $feedback = Feedback::with(['user', 'responses.user'])
            ->where('company_id', $company->id)
            ->findOrFail($id);

        // Check if user can view this feedback (skip for admin users)
        if ($user instanceof PortalUser && !$user->hasPermissionTo('feedback.view_team')) {
            if ($feedback->user_id !== $user->id) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        return response()->json([
            'feedback' => $feedback
        ]);
    }

    public function respond(Request $request, $id)
    {
        $company = $this->getCompany();
        $user = $this->getCurrentUser();
        
        if (!$company || !$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission (skip for admin users)
        if ($user instanceof PortalUser && !$user->hasPermissionTo('feedback.respond')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'message' => 'required|string',
            'status' => 'nullable|in:open,in_progress,resolved,closed',
            'internal_note' => 'nullable|boolean'
        ]);

        $feedback = Feedback::where('company_id', $company->id)->findOrFail($id);

        // Create response
        DB::table('feedback_responses')->insert([
            'feedback_id' => $feedback->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_internal' => $request->internal_note ?? false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Update status if provided
        if ($request->has('status')) {
            $feedback->status = $request->status;
            $feedback->save();
        }

        // Update first response time if this is the first response
        if (!$feedback->first_response_at) {
            $feedback->first_response_at = now();
            $feedback->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Antwort erfolgreich hinzugefügt'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $company = $this->getCompany();
        $user = $this->getCurrentUser();
        
        if (!$company || !$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check permission (skip for admin users)
        if ($user instanceof PortalUser && !$user->hasPermissionTo('feedback.respond')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed'
        ]);

        $feedback = Feedback::where('company_id', $company->id)->findOrFail($id);
        $feedback->status = $request->status;
        
        if ($request->status === 'resolved' || $request->status === 'closed') {
            $feedback->resolved_at = now();
        }
        
        $feedback->save();

        return response()->json([
            'success' => true,
            'message' => 'Status erfolgreich aktualisiert'
        ]);
    }

    public function getFilters()
    {
        return response()->json([
            'types' => [
                ['value' => 'bug', 'label' => 'Fehler'],
                ['value' => 'feature', 'label' => 'Feature-Anfrage'],
                ['value' => 'improvement', 'label' => 'Verbesserung'],
                ['value' => 'question', 'label' => 'Frage'],
                ['value' => 'complaint', 'label' => 'Beschwerde']
            ],
            'priorities' => [
                ['value' => 'low', 'label' => 'Niedrig'],
                ['value' => 'medium', 'label' => 'Mittel'],
                ['value' => 'high', 'label' => 'Hoch'],
                ['value' => 'urgent', 'label' => 'Dringend']
            ],
            'statuses' => [
                ['value' => 'open', 'label' => 'Offen'],
                ['value' => 'in_progress', 'label' => 'In Bearbeitung'],
                ['value' => 'resolved', 'label' => 'Gelöst'],
                ['value' => 'closed', 'label' => 'Geschlossen']
            ]
        ]);
    }

    private function calculateAvgResponseTime($companyId)
    {
        $avgMinutes = Feedback::where('company_id', $companyId)
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_minutes')
            ->first()
            ->avg_minutes;

        if (!$avgMinutes) {
            return null;
        }

        if ($avgMinutes < 60) {
            return round($avgMinutes) . ' Min.';
        } elseif ($avgMinutes < 1440) {
            return round($avgMinutes / 60) . ' Std.';
        } else {
            return round($avgMinutes / 1440) . ' Tage';
        }
    }
}