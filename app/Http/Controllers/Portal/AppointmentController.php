<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // If admin viewing, get company from session
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            $companyId = $user->company_id;
        }
        
        $appointments = Appointment::where('company_id', $companyId)
            ->with(['customer', 'staff', 'branch'])
            ->orderBy('starts_at', 'desc')
            ->paginate(20);
        
        return view('portal.appointments.index', compact('appointments'));
    }
    
    /**
     * Display a specific appointment
     */
    public function show($id)
    {
        $user = Auth::guard('portal')->user();
        
        // If admin viewing, get company from session
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            $companyId = $user->company_id;
        }
        
        $appointment = Appointment::where('company_id', $companyId)
            ->with(['customer', 'staff', 'branch', 'service'])
            ->findOrFail($id);
        
        return view('portal.appointments.show', compact('appointment'));
    }
}