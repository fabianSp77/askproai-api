<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\GDPRService;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GDPRController extends Controller
{
    protected GDPRService $gdprService;
    
    public function __construct(GDPRService $gdprService)
    {
        $this->gdprService = $gdprService;
    }
    
    /**
     * Request data export
     */
    public function requestDataExport(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'required'
        ]);
        
        // Find customer by email and phone
        $customer = Customer::where('email', $request->email)
            ->where('phone', 'LIKE', '%' . substr(preg_replace('/[^0-9]/', '', $request->phone), -10))
            ->first();
            
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => __('gdpr.customer_not_found')
            ], 404);
        }
        
        // Check rate limiting (max 1 request per day)
        $recentRequest = DB::table('gdpr_requests')
            ->where('customer_id', $customer->id)
            ->where('type', 'export')
            ->where('created_at', '>', now()->subDay())
            ->exists();
            
        if ($recentRequest) {
            return response()->json([
                'success' => false,
                'message' => __('gdpr.rate_limit_exceeded')
            ], 429);
        }
        
        $result = $this->gdprService->processDataExportRequest($customer);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => __('gdpr.export_email_sent'),
                'expires_at' => $result['expires_at']
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => __('gdpr.export_failed')
        ], 500);
    }
    
    /**
     * Request data deletion
     */
    public function requestDataDeletion(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'required'
        ]);
        
        // Find customer by email and phone
        $customer = Customer::where('email', $request->email)
            ->where('phone', 'LIKE', '%' . substr(preg_replace('/[^0-9]/', '', $request->phone), -10))
            ->first();
            
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => __('gdpr.customer_not_found')
            ], 404);
        }
        
        // Check for pending deletion request
        $pendingRequest = DB::table('gdpr_requests')
            ->where('customer_id', $customer->id)
            ->where('type', 'deletion')
            ->where('status', 'pending_confirmation')
            ->where('expires_at', '>', now())
            ->exists();
            
        if ($pendingRequest) {
            return response()->json([
                'success' => false,
                'message' => __('gdpr.deletion_pending')
            ], 409);
        }
        
        $result = $this->gdprService->processDataDeletionRequest($customer);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => __('gdpr.deletion_email_sent'),
                'expires_at' => $result['expires_at']
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => __('gdpr.deletion_failed')
        ], 500);
    }
    
    /**
     * Download exported data
     */
    public function downloadData(string $token)
    {
        $request = DB::table('gdpr_requests')
            ->where('token', $token)
            ->where('type', 'export')
            ->where('status', 'completed')
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$request || !$request->file_path) {
            abort(404, 'Download link is invalid or expired');
        }
        
        if (!Storage::exists($request->file_path)) {
            Log::error('GDPR export file not found', ['path' => $request->file_path]);
            abort(404, 'File not found');
        }
        
        // Log download
        DB::table('gdpr_requests')
            ->where('id', $request->id)
            ->update([
                'downloaded_at' => now(),
                'download_count' => DB::raw('download_count + 1')
            ]);
        
        return Storage::download($request->file_path, 'personal-data-export.zip');
    }
    
    /**
     * Confirm data deletion
     */
    public function confirmDeletion(string $token)
    {
        $result = $this->gdprService->confirmDataDeletion($token);
        
        if ($result['success']) {
            return view('gdpr.deletion-confirmed');
        }
        
        return view('gdpr.deletion-error', ['error' => $result['error']]);
    }
    
    /**
     * GDPR information page
     */
    public function privacyTools()
    {
        return view('gdpr.privacy-tools');
    }
}