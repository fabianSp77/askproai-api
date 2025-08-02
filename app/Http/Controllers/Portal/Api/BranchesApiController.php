<?php

namespace App\Http\Controllers\Portal\Api;

use App\Models\Branch;
use App\Models\StaffMember;
use App\Services\MCP\BranchMCPServer;
use Illuminate\Http\Request;

class BranchesApiController extends BaseApiController
{
    protected BranchMCPServer $branchMCP;
    
    public function __construct(BranchMCPServer $branchMCP)
    {
        $this->branchMCP = $branchMCP;
    }
    
    /**
     * Get all branches for the authenticated company
     */
    public function index(Request $request)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $result = $this->branchMCP->getBranchesByCompany($company->id);
        
        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 500);
        }
        
        // Add statistics for each branch
        $branches = collect($result['data'])->map(function ($branch) {
            // Get additional stats from database
            $branchModel = Branch::find($branch['id']);
            
            return array_merge($branch, [
                'appointments_today' => $branchModel->appointments()
                    ->whereDate('start_time', today())
                    ->count(),
                'calls_today' => $branchModel->calls()
                    ->whereDate('created_at', today())
                    ->count(),
                'revenue_this_month' => $branchModel->transactions()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount')
            ]);
        });
        
        return response()->json([
            'branches' => $branches,
            'total' => $result['count']
        ]);
    }
    
    /**
     * Get a specific branch by ID
     */
    public function show($id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Verify branch belongs to company
        $branch = Branch::where('company_id', $company->id)->find($id);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }
        
        $result = $this->branchMCP->getBranch($id);
        
        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 500);
        }
        
        return response()->json(['branch' => $result['data']]);
    }
    
    /**
     * Create a new branch
     */
    public function store(Request $request)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'phone_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'timezone' => 'nullable|string|max:50',
            'create_default_hours' => 'nullable|boolean'
        ]);
        
        $data = array_merge($request->all(), [
            'company_id' => $company->id
        ]);
        
        $result = $this->branchMCP->createBranch($data);
        
        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 500);
        }
        
        return response()->json([
            'success' => true,
            'branch' => $result['data']
        ], 201);
    }
    
    /**
     * Update a branch
     */
    public function update(Request $request, $id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Verify branch belongs to company
        $branch = Branch::where('company_id', $company->id)->find($id);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }
        
        $request->validate([
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'phone_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'timezone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean'
        ]);
        
        $result = $this->branchMCP->updateBranch($id, $request->all());
        
        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 500);
        }
        
        return response()->json([
            'success' => true,
            'branch' => $result['data']
        ]);
    }
    
    /**
     * Delete a branch
     */
    public function destroy($id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Verify branch belongs to company
        $branch = Branch::where('company_id', $company->id)->find($id);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }
        
        // Check if this is the last branch
        $branchCount = Branch::where('company_id', $company->id)->count();
        if ($branchCount <= 1) {
            return response()->json(['error' => 'Cannot delete the last branch'], 400);
        }
        
        // Check if branch has active appointments or staff
        if ($branch->appointments()->future()->count() > 0) {
            return response()->json(['error' => 'Cannot delete branch with future appointments'], 400);
        }
        
        if ($branch->staff()->where('is_active', true)->count() > 0) {
            return response()->json(['error' => 'Cannot delete branch with active staff members'], 400);
        }
        
        $branch->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully'
        ]);
    }
    
    /**
     * Get branch staff
     */
    public function staff($id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Verify branch belongs to company
        $branch = Branch::where('company_id', $company->id)->find($id);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }
        
        $result = $this->branchMCP->getBranchStaff($id);
        
        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 500);
        }
        
        return response()->json([
            'staff' => $result['data'],
            'total' => $result['count']
        ]);
    }
    
    /**
     * Get branch services
     */
    public function services($id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Verify branch belongs to company
        $branch = Branch::where('company_id', $company->id)->find($id);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }
        
        $result = $this->branchMCP->getBranchServices($id);
        
        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 500);
        }
        
        return response()->json([
            'services' => $result['data'],
            'total' => $result['count']
        ]);
    }
    
    /**
     * Get branch working hours
     */
    public function workingHours($id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Verify branch belongs to company
        $branch = Branch::where('company_id', $company->id)->find($id);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }
        
        $result = $this->branchMCP->getBranchWorkingHours($id);
        
        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 500);
        }
        
        return response()->json([
            'working_hours' => $result['data'],
            'formatted' => $result['formatted_hours']
        ]);
    }
    
    /**
     * Update branch working hours
     */
    public function updateWorkingHours(Request $request, $id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Verify branch belongs to company
        $branch = Branch::where('company_id', $company->id)->find($id);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }
        
        $request->validate([
            'hours' => 'required|array',
            'hours.*.day_of_week' => 'required|integer|between:0,6',
            'hours.*.start_time' => 'nullable|date_format:H:i',
            'hours.*.end_time' => 'nullable|date_format:H:i|after:hours.*.start_time',
            'hours.*.is_closed' => 'nullable|boolean'
        ]);
        
        // Delete existing hours and create new ones
        $branch->workingHours()->delete();
        
        foreach ($request->hours as $hours) {
            $branch->workingHours()->create([
                'day_of_week' => $hours['day_of_week'],
                'start_time' => $hours['is_closed'] ?? false ? null : $hours['start_time'] . ':00',
                'end_time' => $hours['is_closed'] ?? false ? null : $hours['end_time'] . ':00',
                'is_closed' => $hours['is_closed'] ?? false
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Working hours updated successfully'
        ]);
    }
    
    /**
     * Check if branch is open
     */
    public function checkOpen($id)
    {
        $company = $this->getCompany();
        
        if (!$company) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Verify branch belongs to company
        $branch = Branch::where('company_id', $company->id)->find($id);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }
        
        $result = $this->branchMCP->isBranchOpen($id);
        
        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 500);
        }
        
        return response()->json($result);
    }
}