<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Services\AppointmentService;
use App\Repositories\AppointmentRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    protected AppointmentService $appointmentService;
    protected AppointmentRepository $appointmentRepository;

    public function __construct(
        AppointmentService $appointmentService,
        AppointmentRepository $appointmentRepository
    ) {
        $this->appointmentService = $appointmentService;
        $this->appointmentRepository = $appointmentRepository;
    }

    /**
     * Display a listing of appointments
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date',
            'staff_id' => 'nullable|integer|exists:staff,id',
            'status' => 'nullable|string|in:scheduled,confirmed,completed,cancelled,no_show',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $this->appointmentRepository->with(['customer', 'staff', 'service', 'branch']);

        // Apply filters
        if ($request->has('date')) {
            $date = Carbon::parse($request->date);
            $query->pushCriteria(function ($q) use ($date) {
                $q->whereDate('starts_at', $date);
            });
        }

        if ($request->has('staff_id')) {
            $query->pushCriteria(function ($q) use ($request) {
                $q->where('staff_id', $request->staff_id);
            });
        }

        if ($request->has('status')) {
            $query->pushCriteria(function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        $appointments = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments),
            'meta' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
            ],
        ]);
    }

    /**
     * Store a newly created appointment
     */
    public function store(CreateAppointmentRequest $request): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Termin erfolgreich erstellt',
                'data' => new AppointmentResource($appointment),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified appointment
     */
    public function show(int $id): JsonResponse
    {
        $appointment = $this->appointmentRepository
            ->with(['customer', 'staff', 'service', 'branch', 'calls'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment),
        ]);
    }

    /**
     * Update the specified appointment
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'starts_at' => 'nullable|date|after:now',
            'ends_at' => 'nullable|date|after:starts_at',
            'staff_id' => 'nullable|integer|exists:staff,id',
            'service_id' => 'nullable|integer|exists:services,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $appointment = $this->appointmentService->update($id, $request->only([
                'starts_at', 'ends_at', 'staff_id', 'service_id', 'notes'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Termin erfolgreich aktualisiert',
                'data' => new AppointmentResource($appointment),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel the specified appointment
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->appointmentService->cancel($id, $request->get('reason'));

            return response()->json([
                'success' => true,
                'message' => 'Termin erfolgreich storniert',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get available time slots
     */
    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'staff_id' => 'required|integer|exists:staff,id',
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:15|max:480',
        ]);

        $slots = $this->appointmentService->getAvailableSlots(
            $request->staff_id,
            Carbon::parse($request->date),
            $request->get('duration', 30)
        );

        return response()->json([
            'success' => true,
            'data' => $slots,
        ]);
    }

    /**
     * Search appointments
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $appointments = $this->appointmentRepository->search($request->q);

        return response()->json([
            'success' => true,
            'data' => AppointmentResource::collection($appointments),
        ]);
    }

    /**
     * Get appointment statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date') 
            ? Carbon::parse($request->start_date) 
            : now()->startOfMonth();
            
        $endDate = $request->has('end_date') 
            ? Carbon::parse($request->end_date) 
            : now()->endOfMonth();

        $stats = $this->appointmentService->getStatistics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Complete appointment
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
            'actual_price' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->appointmentService->complete($id, $request->only(['notes', 'actual_price']));

            return response()->json([
                'success' => true,
                'message' => 'Termin als abgeschlossen markiert',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark appointment as no-show
     */
    public function noShow(int $id): JsonResponse
    {
        try {
            $this->appointmentService->markAsNoShow($id);

            return response()->json([
                'success' => true,
                'message' => 'Termin als nicht erschienen markiert',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}