<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Branch::where("company_id", auth()->user()->company_id)
            ->with([
                'company' => function($q) { $q->where("company_id", auth()->user()->company_id); },
                'phoneNumbers'
            ])
            ->withCount(['staff', 'appointments']);

        // Company filter
        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $branches = $query->paginate($request->get('per_page', 20));

        return response()->json($branches);
    }

    public function show($id): JsonResponse
    {
        $branch = Branch::where("company_id", auth()->user()->company_id)
            ->with([
                'company' => function($q) { $q->where("company_id", auth()->user()->company_id); },
                'phoneNumbers',
                'staff' => function($q) { $q->where("company_id", auth()->user()->company_id); },
                'workingHours'
            ])
            ->withCount(['staff', 'appointments'])
            ->findOrFail($id);

        return response()->json($branch);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'timezone' => 'required|string',
            'active' => 'boolean',
        ]);

        $branch = Branch::create($validated);

        return response()->json($branch, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $branch = Branch::where('company_id', auth()->user()->company_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'timezone' => 'sometimes|string',
            'active' => 'sometimes|boolean',
        ]);

        $branch->update($validated);

        return response()->json($branch);
    }

    public function destroy($id): JsonResponse
    {
        $branch = Branch::where('company_id', auth()->user()->company_id)->findOrFail($id);
        
        // Check if branch has data
        if ($branch->appointments()->exists() || $branch->staff()->exists()) {
            return response()->json([
                'message' => 'Cannot delete branch with existing data'
            ], 422);
        }

        $branch->delete();

        return response()->json(null, 204);
    }

    public function workingHours($id): JsonResponse
    {
        $branch = Branch::where('company_id', auth()->user()->company_id)->findOrFail($id);
        $workingHours = $branch->workingHours;

        return response()->json($workingHours);
    }

    public function updateWorkingHours(Request $request, $id): JsonResponse
    {
        $branch = Branch::where('company_id', auth()->user()->company_id)->findOrFail($id);

        $validated = $request->validate([
            'working_hours' => 'required|array',
            'working_hours.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'working_hours.*.open' => 'required|boolean',
            'working_hours.*.start_time' => 'required_if:working_hours.*.open,true|date_format:H:i',
            'working_hours.*.end_time' => 'required_if:working_hours.*.open,true|date_format:H:i|after:working_hours.*.start_time',
        ]);

        // Update or create working hours
        foreach ($validated['working_hours'] as $hours) {
            $branch->workingHours()->updateOrCreate(
                ['day' => $hours['day']],
                [
                    'open' => $hours['open'],
                    'start_time' => $hours['open'] ? $hours['start_time'] : null,
                    'end_time' => $hours['open'] ? $hours['end_time'] : null,
                ]
            );
        }

        return response()->json(['message' => 'Working hours updated successfully']);
    }
}