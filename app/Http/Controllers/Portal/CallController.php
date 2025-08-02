<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\UsesMCPServers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallController extends Controller
{
    use UsesMCPServers;

    public function __construct()
    {
        $this->setMCPPreferences([
            'call' => true,
            'customer' => true,
            'appointment' => true
        ]);
    }

    /**
     * Display a listing of calls
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Check if user is authenticated
        if (!$user && !session('is_admin_viewing')) {
            return redirect()->route('business.login');
        }
        
        // Redirect to React view
        return view('portal.calls.index');
    }
    
    /**
     * Display a specific call
     */
    public function show($id)
    {
        $companyId = $this->getCompanyId();
        
        if (!$companyId) {
            abort(403, 'No company context');
        }
        
        // Get call details via MCP
        $result = $this->executeMCPTask('getCall', [
            'call_id' => $id,
            'company_id' => $companyId
        ]);

        if (!($result['result']['success'] ?? false)) {
            abort(404, $result['result']['error'] ?? 'Call not found');
        }

        $call = $result['result']['data'];
        
        return view('portal.calls.show', compact('call'));
    }
    
    /**
     * Update call status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:new,in_progress,completed,requires_action,callback_scheduled',
            'notes' => 'nullable|string'
        ]);
        
        // Update via MCP
        $result = $this->executeMCPTask('updateCallStatus', [
            'call_id' => $id,
            'status' => $request->input('status'),
            'notes' => $request->input('notes'),
            'user_id' => $this->getCurrentUserId()
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['result']['error'] ?? 'Failed to update status'
            ], 400);
        }

        return response()->json($result['result']);
    }
    
    /**
     * Assign call to user
     */
    public function assign(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'notes' => 'nullable|string'
        ]);
        
        // Assign via MCP
        $result = $this->executeMCPTask('assignCall', [
            'call_id' => $id,
            'user_id' => $request->input('user_id'),
            'notes' => $request->input('notes'),
            'assigned_by' => $this->getCurrentUserId()
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['result']['error'] ?? 'Failed to assign call'
            ], 400);
        }

        return response()->json($result['result']);
    }
    
    /**
     * Add note to call
     */
    public function addNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string',
            'is_internal' => 'boolean'
        ]);
        
        // Add note via MCP
        $result = $this->executeMCPTask('addCallNote', [
            'call_id' => $id,
            'note' => $request->input('note'),
            'is_internal' => $request->input('is_internal', true),
            'user_id' => $this->getCurrentUserId()
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['result']['error'] ?? 'Failed to add note'
            ], 400);
        }

        return response()->json($result['result']);
    }
    
    /**
     * Schedule callback
     */
    public function scheduleCallback(Request $request, $id)
    {
        $request->validate([
            'callback_at' => 'required|date|after:now',
            'notes' => 'nullable|string'
        ]);
        
        // Schedule callback via MCP
        $result = $this->executeMCPTask('scheduleCallback', [
            'call_id' => $id,
            'callback_at' => $request->input('callback_at'),
            'notes' => $request->input('notes'),
            'scheduled_by' => $this->getCurrentUserId()
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['result']['error'] ?? 'Failed to schedule callback'
            ], 400);
        }

        return response()->json($result['result']);
    }
    
    /**
     * API endpoint for calls list
     */
    public function apiIndex(Request $request)
    {
        $companyId = $this->getCompanyId();
        
        if (!$companyId) {
            return response()->json(['error' => 'No company context'], 403);
        }
        
        // Get calls via MCP
        $result = $this->executeMCPTask('listCalls', [
            'company_id' => $companyId,
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 20),
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
            'branch_id' => $request->input('branch_id'),
            'assigned_to' => $request->input('assigned_to')
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => 'Failed to fetch calls'], 500);
        }

        return response()->json($result['result']['data']);
    }
    
    /**
     * API endpoint for call stats
     */
    public function apiStats(Request $request)
    {
        $companyId = $this->getCompanyId();
        
        if (!$companyId) {
            return response()->json(['error' => 'No company context'], 403);
        }
        
        // Get stats via MCP
        $result = $this->executeMCPTask('getCallStats', [
            'company_id' => $companyId,
            'period' => $request->get('period', 'today'),
            'branch_id' => $request->input('branch_id')
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => 'Failed to fetch stats'], 500);
        }

        return response()->json($result['result']['data']);
    }
    
    /**
     * Export calls
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'in:csv,excel,pdf',
            'call_ids' => 'array',
            'call_ids.*' => 'string'
        ]);
        
        $companyId = $this->getCompanyId();
        
        if (!$companyId) {
            return response()->json(['error' => 'No company context'], 403);
        }
        
        // Export via MCP
        $result = $this->executeMCPTask('exportCalls', [
            'company_id' => $companyId,
            'format' => $request->get('format', 'csv'),
            'call_ids' => $request->input('call_ids'),
            'filters' => $request->except(['format', 'call_ids'])
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => 'Export failed'], 500);
        }

        $export = $result['result']['export'];
        
        return response()->download(
            $export['path'],
            $export['filename'],
            $export['headers'] ?? []
        );
    }
    
    /**
     * Get company ID for current context
     */
    protected function getCompanyId(): ?int
    {
        if (session('is_admin_viewing')) {
            return session('admin_impersonation.company_id');
        }
        
        $user = Auth::guard('portal')->user();
        return $user ? $user->company_id : null;
    }
    
    /**
     * Get current user ID
     */
    protected function getCurrentUserId(): ?int
    {
        $user = Auth::guard('portal')->user();
        return $user ? $user->id : null;
    }
}