<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\PortalUser;

class CallsHtmlExportController extends Controller
{
    /**
     * Export call as HTML with print-optimized styles
     */
    public function exportHtml(Request $request, $id)
    {
        $user = auth()->guard('portal')->user() ?: auth()->guard('web')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $call = Call::where('company_id', $user->company_id)
            ->with([
                'company', 
                'branch', 
                'customer', 
                'callPortalData.assignedTo',
                'callNotes.user'
            ])
            ->findOrFail($id);
        
        // Check if user has permission (for portal users)
        if ($user instanceof PortalUser && !$user->hasPermission('calls.view_own')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Check if user should see costs
        $showCosts = $user instanceof PortalUser ? $user->hasPermission('billing.view') : true;

        // Get selected fields
        $selectedFields = [];
        if ($request->has('fields')) {
            $selectedFields = explode(',', $request->get('fields'));
        }
        
        // Default fields if none selected
        if (empty($selectedFields)) {
            $selectedFields = ['date', 'time', 'phone_number', 'customer_name', 'summary', 'duration', 'status', 'branch'];
        }

        // Create filename with company name and date
        $companyName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $call->company->name ?? 'Firma');
        $filename = $companyName . '_Anruf_' . now()->format('Y-m-d') . '_' . $call->id;

        // Render the HTML view with selective fields
        return view('portal.calls.export-html-print-selective', [
            'call' => $call,
            'showCosts' => $showCosts,
            'selectedFields' => $selectedFields,
            'filename' => $filename,
            'printMode' => true
        ]);
    }
}