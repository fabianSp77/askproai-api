<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::where("company_id", auth()->user()->company_id)
            ->with([
                'company' => function($q) { $q->where("company_id", auth()->user()->company_id); },
                'staff' => function($q) { $q->where("company_id", auth()->user()->company_id); }
            ])
            ->withCount(['appointments']);

        // Company filter
        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Active filter
        if ($request->has('active')) {
            $query->where('active', $request->get('active') === 'true');
        }

        $services = $query->paginate($request->get('per_page', 20));

        return response()->json($services);
    }

    public function show($id): JsonResponse
    {
        $service = Service::where("company_id", auth()->user()->company_id)
            ->with([
                'company' => function($q) { $q->where("company_id", auth()->user()->company_id); },
                'staff' => function($q) { $q->where("company_id", auth()->user()->company_id); }
            ])
            ->withCount(['appointments'])
            ->findOrFail($id);

        return response()->json($service);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'required|integer|min:5|max:480',
            'price' => 'required|numeric|min:0',
            'buffer_time' => 'nullable|integer|min:0|max:120',
            'active' => 'boolean',
        ]);

        $service = Service::create($validated);

        return response()->json($service, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $service = Service::where("company_id", auth()->user()->company_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'sometimes|integer|min:5|max:480',
            'price' => 'sometimes|numeric|min:0',
            'buffer_time' => 'nullable|integer|min:0|max:120',
            'active' => 'sometimes|boolean',
        ]);

        $service->update($validated);

        return response()->json($service);
    }

    public function destroy($id): JsonResponse
    {
        $service = Service::where("company_id", auth()->user()->company_id)->findOrFail($id);
        
        // Check if service has appointments
        if ($service->appointments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete service with existing appointments'
            ], 422);
        }

        $service->delete();

        return response()->json(null, 204);
    }

    public function assignStaff(Request $request, $id): JsonResponse
    {
        $service = Service::where("company_id", auth()->user()->company_id)->findOrFail($id);

        $validated = $request->validate([
            'staff_ids' => 'required|array',
            'staff_ids.*' => 'exists:staff,id',
        ]);

        $service->staff()->sync($validated['staff_ids']);

        return response()->json([
            'message' => 'Staff assigned successfully',
            'staff' => $service->fresh()->staff
        ]);
    }
}