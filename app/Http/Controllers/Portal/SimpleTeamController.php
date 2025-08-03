<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SimpleTeamController extends Controller
{
    /**
     * Display team members
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        $query = PortalUser::where('company_id', $user->company_id)
            ->with('branch');
            
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $teamMembers = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Use unified layout for consistency
        return view('portal.team.index-unified', compact('teamMembers'));
    }
    
    /**
     * Show create form
     */
    public function create()
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        
        return view('portal.team.create-unified', compact('branches'));
    }
    
    /**
     * Store new team member
     */
    public function store(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('portal_users')->where('company_id', $user->company_id)],
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,user',
            'branch_id' => 'nullable|exists:branches,id'
        ]);
        
        $teamMember = new PortalUser();
        $teamMember->name = $validated['name'];
        $teamMember->email = $validated['email'];
        $teamMember->password = Hash::make($validated['password']);
        $teamMember->role = $validated['role'];
        $teamMember->branch_id = $validated['branch_id'];
        $teamMember->company_id = $user->company_id;
        $teamMember->is_active = true;
        $teamMember->save();
        
        return redirect()->route('business.team.index')
            ->with('success', 'Teammitglied wurde erfolgreich hinzugefügt!');
    }
    
    /**
     * Show edit form
     */
    public function edit($id)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        $member = PortalUser::where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();
            
        $branches = Branch::where('company_id', $user->company_id)->get();
        
        return view('portal.team.edit-unified', compact('member', 'branches'));
    }
    
    /**
     * Update team member
     */
    public function update(Request $request, $id)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        $member = PortalUser::where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('portal_users')->where('company_id', $user->company_id)->ignore($member->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,user',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean'
        ]);
        
        $member->name = $validated['name'];
        $member->email = $validated['email'];
        if (!empty($validated['password'])) {
            $member->password = Hash::make($validated['password']);
        }
        $member->role = $validated['role'];
        $member->branch_id = $validated['branch_id'];
        $member->is_active = $validated['is_active'] ?? true;
        $member->save();
        
        return redirect()->route('business.team.index')
            ->with('success', 'Teammitglied wurde erfolgreich aktualisiert!');
    }
    
    /**
     * Delete team member
     */
    public function destroy($id)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id || $user->id == $id) {
            abort(401, 'Unauthorized');
        }
        
        $member = PortalUser::where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();
            
        $member->delete();
        
        return redirect()->route('business.team.index')
            ->with('success', 'Teammitglied wurde erfolgreich entfernt!');
    }
}