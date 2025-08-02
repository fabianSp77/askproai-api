<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\UsesMCPServers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    use UsesMCPServers;

    public function __construct()
    {
        $this->setMCPPreferences([
            'appointment' => true,
            'calcom' => true,
            'customer' => true
        ]);
    }

    /**
     * Display a listing of appointments
     */
    public function index(Request $request)
    {
        return view('portal.appointments.index');
    }
    
    /**
     * Display a specific appointment
     */
    public function show($id)
    {
        $companyId = $this->getCompanyId();
        
        // Get appointment via MCP
        $result = $this->executeMCPTask('getAppointmentDetails', [
            'company_id' => $companyId,
            'appointment_id' => $id
        ]);

        if (!($result['result']['success'] ?? false)) {
            abort(404, $result['result']['message'] ?? 'Appointment not found');
        }

        $appointment = $result['result']['data']['appointment'];
        
        return view('portal.appointments.show', compact('appointment'));
    }
    
    /**
     * API endpoint for appointments list
     */
    public function apiIndex(Request $request)
    {
        $companyId = $this->getCompanyId();
        
        // Get appointments via MCP
        $result = $this->executeMCPTask('searchAppointments', [
            'company_id' => $companyId,
            'filters' => [
                'date' => $request->input('date'),
                'status' => $request->input('status'),
                'branch_id' => $request->input('branch'),
                'search' => $request->input('search')
            ],
            'page' => $request->get('page', 1),
            'per_page' => 20,
            'include' => ['customer', 'staff', 'branch', 'service']
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json(['error' => 'Failed to fetch appointments'], 500);
        }

        return response()->json($result['result']['data']);
    }
    
    /**
     * API endpoint for appointment stats
     */
    public function apiStats(Request $request)
    {
        $companyId = $this->getCompanyId();
        
        // Get stats via MCP
        $result = $this->executeMCPTask('getAppointmentStats', [
            'company_id' => $companyId,
            'period' => $request->get('period', 'current') // current, today, week, month
        ]);

        if (!($result['result']['success'] ?? false)) {
            return response()->json([
                'today' => 0,
                'week' => 0,
                'confirmed' => 0,
                'total' => 0
            ]);
        }

        return response()->json($result['result']['data']);
    }

    /**
     * Get company ID for current context
     */
    protected function getCompanyId(): ?int
    {
        $user = Auth::guard('portal')->user();
        
        if (session('is_admin_viewing')) {
            return session('admin_impersonation.company_id');
        }
        
        return $user ? $user->company_id : null;
    }
}