<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Staff::withoutGlobalScopes()
            ->with([
                'company' => function($q) { $q->withoutGlobalScopes(); },
                'branch' => function($q) { $q->withoutGlobalScopes(); },
                'services' => function($q) { $q->withoutGlobalScopes(); }
            ])
            ->withCount(['appointments']);

        // Company filter
        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }

        // Branch filter
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Active filter
        if ($request->has('active')) {
            $query->where('active', $request->get('active') === 'true');
        }

        $staff = $query->paginate($request->get('per_page', 20));

        return response()->json($staff);
    }

    public function show($id): JsonResponse
    {
        $staff = Staff::withoutGlobalScopes()
            ->with([
                'company' => function($q) { $q->withoutGlobalScopes(); },
                'branch' => function($q) { $q->withoutGlobalScopes(); },
                'services' => function($q) { $q->withoutGlobalScopes(); },
                'workingHours'
            ])
            ->withCount(['appointments'])
            ->findOrFail($id);

        return response()->json($staff);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:staff',
            'phone' => 'nullable|string',
            'position' => 'nullable|string',
            'bio' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $staff = Staff::create($validated);

        return response()->json($staff, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $staff = Staff::withoutGlobalScopes()->findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'sometimes|exists:branches,id',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:staff,email,' . $id,
            'phone' => 'nullable|string',
            'position' => 'nullable|string',
            'bio' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        $staff->update($validated);

        return response()->json($staff);
    }

    public function destroy($id): JsonResponse
    {
        $staff = Staff::withoutGlobalScopes()->findOrFail($id);
        
        // Check if staff has appointments
        if ($staff->appointments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete staff member with existing appointments'
            ], 422);
        }

        $staff->delete();

        return response()->json(null, 204);
    }

    public function availability($id): JsonResponse
    {
        $staff = Staff::withoutGlobalScopes()->findOrFail($id);
        
        // Get working hours
        $workingHours = $staff->workingHours;
        
        // Get upcoming appointments
        $appointments = $staff->appointments()
            ->withoutGlobalScopes()
            ->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get(['start_time', 'end_time', 'status']);

        return response()->json([
            'working_hours' => $workingHours,
            'appointments' => $appointments,
        ]);
    }

    public function assignServices(Request $request, $id): JsonResponse
    {
        $staff = Staff::withoutGlobalScopes()->findOrFail($id);

        $validated = $request->validate([
            'service_ids' => 'required|array',
            'service_ids.*' => 'exists:services,id',
        ]);

        $staff->services()->sync($validated['service_ids']);

        return response()->json([
            'message' => 'Services assigned successfully',
            'services' => $staff->fresh()->services
        ]);
    }
}