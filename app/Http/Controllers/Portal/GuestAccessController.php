<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Company;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\GuestAccessRequestNotification;

class GuestAccessController extends Controller
{
    /**
     * Show guest access login page
     */
    public function showGuestLogin(Request $request, $callId)
    {
        // Find the call
        $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->with(['company', 'branch'])
            ->findOrFail($callId);
        
        // If user is already authenticated with access to this company, redirect
        if (Auth::guard('portal')->check()) {
            $user = Auth::guard('portal')->user();
            if ($user->company_id === $call->company_id) {
                return redirect()->route('business.calls.show.v2', $callId);
            }
        }
        
        return view('portal.auth.guest-login', [
            'call' => $call,
            'company' => $call->company,
            'returnUrl' => $request->get('return', route('business.calls.show.v2', $callId))
        ]);
    }
    
    /**
     * Request guest access
     */
    public function requestAccess(Request $request)
    {
        $request->validate([
            'call_id' => 'required|exists:calls,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'reason' => 'required|string|max:1000',
        ]);
        
        $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->with(['company'])
            ->findOrFail($request->call_id);
        
        // Check if user already exists
        $existingUser = PortalUser::where('email', $request->email)
            ->where('company_id', $call->company_id)
            ->first();
        
        if ($existingUser) {
            if ($existingUser->is_active) {
                return back()->with('info', 'Sie haben bereits Zugang. Bitte melden Sie sich mit Ihren Zugangsdaten an.');
            } else {
                return back()->with('info', 'Ihr Zugang wartet auf Aktivierung. Bitte kontaktieren Sie den Administrator.');
            }
        }
        
        // Find company admins
        $admins = PortalUser::where('company_id', $call->company_id)
            ->whereHas('permissions', function ($query) {
                $query->where('name', 'admin')
                    ->orWhere('name', 'company.manage');
            })
            ->where('is_active', true)
            ->get();
        
        // Create guest access request
        $accessRequest = \App\Models\GuestAccessRequest::create([
            'company_id' => $call->company_id,
            'call_id' => $call->id,
            'name' => $request->name,
            'email' => $request->email,
            'reason' => $request->reason,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);
        
        // Notify admins
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new GuestAccessRequestNotification(
                $accessRequest,
                $call,
                $admin
            ));
        }
        
        return back()->with('success', 'Ihre Zugriffsanfrage wurde gesendet. Sie erhalten eine E-Mail, sobald Ihr Zugang genehmigt wurde.');
    }
    
    /**
     * Approve guest access (for admins)
     */
    public function approveAccess(Request $request, $token)
    {
        $accessRequest = \App\Models\GuestAccessRequest::where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('approved_at')
            ->whereNull('rejected_at')
            ->firstOrFail();
        
        // Check if admin has permission
        $user = Auth::guard('portal')->user();
        if (!$user || $user->company_id !== $accessRequest->company_id || !$user->hasPermission('company.manage')) {
            abort(403);
        }
        
        // Create portal user
        $password = Str::random(12);
        $portalUser = PortalUser::create([
            'company_id' => $accessRequest->company_id,
            'branch_id' => $accessRequest->call->branch_id,
            'name' => $accessRequest->name,
            'email' => $accessRequest->email,
            'password' => bcrypt($password),
            'is_active' => true,
            'phone' => null,
            'role' => 'guest',
        ]);
        
        // Assign guest permissions
        $portalUser->permissions()->attach([
            'calls.view_own',
            'calls.export',
        ]);
        
        // Mark request as approved
        $accessRequest->update([
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);
        
        // Send credentials email
        Mail::to($portalUser->email)->send(new \App\Mail\GuestAccessApproved(
            $portalUser,
            $password,
            $accessRequest->call
        ));
        
        return redirect()->route('business.team.index')
            ->with('success', 'Gastzugang wurde erstellt und Zugangsdaten wurden per E-Mail versendet.');
    }
    
    /**
     * Reject guest access (for admins)
     */
    public function rejectAccess(Request $request, $token)
    {
        $accessRequest = \App\Models\GuestAccessRequest::where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('approved_at')
            ->whereNull('rejected_at')
            ->firstOrFail();
        
        // Check if admin has permission
        $user = Auth::guard('portal')->user();
        if (!$user || $user->company_id !== $accessRequest->company_id || !$user->hasPermission('company.manage')) {
            abort(403);
        }
        
        // Mark request as rejected
        $accessRequest->update([
            'rejected_at' => now(),
            'rejected_by' => $user->id,
            'rejection_reason' => $request->get('reason'),
        ]);
        
        // Send rejection email
        Mail::to($accessRequest->email)->send(new \App\Mail\GuestAccessRejected(
            $accessRequest,
            $request->get('reason')
        ));
        
        return redirect()->route('business.team.index')
            ->with('success', 'Gastzugang wurde abgelehnt.');
    }
}