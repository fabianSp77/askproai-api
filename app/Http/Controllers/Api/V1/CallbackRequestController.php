<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CallbackRequestResource;
use App\Models\CallbackRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * CallbackRequestController
 *
 * RESTful API for managing callback requests.
 * Requires authentication via Sanctum.
 * All operations are scoped to the authenticated user's company (multi-tenancy).
 *
 * @group Callback Requests
 */
class CallbackRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of callback requests.
     *
     * GET /api/v1/callbacks
     *
     * @queryParam status string Filter by status (pending, assigned, contacted, completed, etc.)
     * @queryParam priority string Filter by priority (normal, high, urgent)
     * @queryParam assigned_to string Filter by assigned staff UUID
     * @queryParam overdue boolean Filter overdue callbacks (true/false)
     * @queryParam per_page integer Items per page (default 15, max 100)
     * @queryParam include string Relationships to include (customer,branch,service,staff,assignedTo,escalations)
     *
     * @response 200 {
     *   "data": [
     *     {"id": 1, "customer_name": "Max Mustermann", ...}
     *   ],
     *   "links": {...},
     *   "meta": {...}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CallbackRequest::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by assigned staff
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter overdue
        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        // Eager load relationships if requested
        $includes = explode(',', $request->get('include', ''));
        $allowedIncludes = ['customer', 'branch', 'service', 'staff', 'assignedTo', 'escalations'];
        $validIncludes = array_intersect($includes, $allowedIncludes);

        if (!empty($validIncludes)) {
            $query->with($validIncludes);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);

        return CallbackRequestResource::collection(
            $query->orderBy('created_at', 'desc')->paginate($perPage)
        );
    }

    /**
     * Store a newly created callback request.
     *
     * POST /api/v1/callbacks
     *
     * @bodyParam customer_id integer optional Customer ID
     * @bodyParam customer_name string required Customer name
     * @bodyParam phone_number string required Phone number
     * @bodyParam branch_id integer required Branch ID
     * @bodyParam service_id integer optional Service ID
     * @bodyParam staff_id integer optional Preferred staff ID
     * @bodyParam preferred_time_window array optional Time preferences
     * @bodyParam priority string optional Priority (normal|high|urgent)
     * @bodyParam notes string optional Additional notes
     * @bodyParam expires_at datetime optional Expiration timestamp
     *
     * @response 201 {
     *   "data": {"id": 1, "customer_name": "Max Mustermann", ...}
     * }
     */
    public function store(Request $request): CallbackRequestResource|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'service_id' => 'nullable|exists:services,id',
            'staff_id' => 'nullable|exists:staff,id',
            'preferred_time_window' => 'nullable|array',
            'priority' => ['nullable', Rule::in(CallbackRequest::PRIORITIES)],
            'notes' => 'nullable|string',
            'expires_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $callback = CallbackRequest::create(array_merge(
            $validator->validated(),
            [
                'status' => CallbackRequest::STATUS_PENDING,
                'priority' => $request->get('priority', CallbackRequest::PRIORITY_NORMAL),
            ]
        ));

        return new CallbackRequestResource($callback);
    }

    /**
     * Display the specified callback request.
     *
     * GET /api/v1/callbacks/{id}
     *
     * @urlParam id integer required Callback Request ID
     * @queryParam include string Relationships to include (customer,branch,service,staff,assignedTo,escalations)
     *
     * @response 200 {
     *   "data": {"id": 1, "customer_name": "Max Mustermann", ...}
     * }
     * @response 404 {
     *   "message": "Callback request not found"
     * }
     */
    public function show(Request $request, int $id): CallbackRequestResource|JsonResponse
    {
        $callback = CallbackRequest::find($id);

        if (!$callback) {
            return response()->json(['message' => 'Callback request not found'], 404);
        }

        // Eager load relationships if requested
        $includes = explode(',', $request->get('include', ''));
        $allowedIncludes = ['customer', 'branch', 'service', 'staff', 'assignedTo', 'escalations'];
        $validIncludes = array_intersect($includes, $allowedIncludes);

        if (!empty($validIncludes)) {
            $callback->load($validIncludes);
        }

        return new CallbackRequestResource($callback);
    }

    /**
     * Update the specified callback request.
     *
     * PUT/PATCH /api/v1/callbacks/{id}
     *
     * @urlParam id integer required Callback Request ID
     * @bodyParam status string optional Status (pending|assigned|contacted|completed|cancelled|expired)
     * @bodyParam priority string optional Priority (normal|high|urgent)
     * @bodyParam assigned_to string optional Assigned staff UUID
     * @bodyParam notes string optional Update notes
     *
     * @response 200 {
     *   "data": {"id": 1, "status": "assigned", ...}
     * }
     * @response 404 {
     *   "message": "Callback request not found"
     * }
     */
    public function update(Request $request, int $id): CallbackRequestResource|JsonResponse
    {
        $callback = CallbackRequest::find($id);

        if (!$callback) {
            return response()->json(['message' => 'Callback request not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['nullable', Rule::in(CallbackRequest::STATUSES)],
            'priority' => ['nullable', Rule::in(CallbackRequest::PRIORITIES)],
            'assigned_to' => 'nullable|exists:staff,id',
            'notes' => 'nullable|string',
            'preferred_time_window' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $callback->update($validator->validated());

        return new CallbackRequestResource($callback->fresh());
    }

    /**
     * Remove the specified callback request.
     *
     * DELETE /api/v1/callbacks/{id}
     *
     * @urlParam id integer required Callback Request ID
     *
     * @response 204 No Content
     * @response 404 {
     *   "message": "Callback request not found"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $callback = CallbackRequest::find($id);

        if (!$callback) {
            return response()->json(['message' => 'Callback request not found'], 404);
        }

        $callback->delete();

        return response()->json(null, 204);
    }

    /**
     * Assign callback to staff member.
     *
     * POST /api/v1/callbacks/{id}/assign
     *
     * @urlParam id integer required Callback Request ID
     * @bodyParam staff_id string required Staff UUID to assign
     *
     * @response 200 {
     *   "data": {"id": 1, "assigned_to": "uuid", "status": "assigned", ...}
     * }
     */
    public function assign(Request $request, int $id): CallbackRequestResource|JsonResponse
    {
        $callback = CallbackRequest::find($id);

        if (!$callback) {
            return response()->json(['message' => 'Callback request not found'], 404);
        }

        $request->validate([
            'staff_id' => 'required|exists:staff,id',
        ]);

        $callback->assigned_to = $request->staff_id;
        $callback->status = CallbackRequest::STATUS_ASSIGNED;
        $callback->assigned_at = now();
        $callback->save();

        return new CallbackRequestResource($callback->fresh());
    }

    /**
     * Mark callback as contacted.
     *
     * POST /api/v1/callbacks/{id}/contact
     *
     * @urlParam id integer required Callback Request ID
     *
     * @response 200 {
     *   "data": {"id": 1, "status": "contacted", "contacted_at": "...", ...}
     * }
     */
    public function contact(int $id): CallbackRequestResource|JsonResponse
    {
        $callback = CallbackRequest::find($id);

        if (!$callback) {
            return response()->json(['message' => 'Callback request not found'], 404);
        }

        $callback->status = CallbackRequest::STATUS_CONTACTED;
        $callback->contacted_at = now();
        $callback->save();

        return new CallbackRequestResource($callback->fresh());
    }

    /**
     * Mark callback as completed.
     *
     * POST /api/v1/callbacks/{id}/complete
     *
     * @urlParam id integer required Callback Request ID
     *
     * @response 200 {
     *   "data": {"id": 1, "status": "completed", "completed_at": "...", ...}
     * }
     */
    public function complete(int $id): CallbackRequestResource|JsonResponse
    {
        $callback = CallbackRequest::find($id);

        if (!$callback) {
            return response()->json(['message' => 'Callback request not found'], 404);
        }

        $callback->status = CallbackRequest::STATUS_COMPLETED;
        $callback->completed_at = now();
        $callback->save();

        return new CallbackRequestResource($callback->fresh());
    }
}
