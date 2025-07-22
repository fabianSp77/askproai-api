<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerService;
use App\Repositories\CustomerRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    protected CustomerService $customerService;
    protected CustomerRepository $customerRepository;

    public function __construct(
        CustomerService $customerService,
        CustomerRepository $customerRepository
    ) {
        $this->customerService = $customerService;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Display a listing of customers
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|min:2',
            'tag' => 'nullable|string',
            'has_appointments' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $this->customerRepository->query();

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        if ($request->boolean('has_appointments')) {
            $query->has('appointments');
        }

        $customers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CustomerResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string|max:500',
            'birthdate' => 'nullable|date|before:today',
            'notes' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        try {
            $customer = $this->customerService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Kunde erfolgreich erstellt',
                'data' => new CustomerResource($customer),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified customer
     */
    public function show(int $id): JsonResponse
    {
        $customer = $this->customerRepository
            ->with(['appointments'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'birthdate' => 'nullable|date|before:today',
            'notes' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        try {
            $customer = $this->customerService->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Kunde erfolgreich aktualisiert',
                'data' => new CustomerResource($customer),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified customer
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = $this->customerRepository->findOrFail($id);

        // Check if customer has appointments
        if ($customer->appointments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kunde kann nicht gelöscht werden, da Termine vorhanden sind',
            ], 422);
        }

        $this->customerRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich gelöscht',
        ]);
    }

    /**
     * Get customer appointments
     */
    public function appointments(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:scheduled,confirmed,completed,cancelled,no_show',
            'future_only' => 'nullable|boolean',
        ]);

        $customer = $this->customerRepository->findOrFail($id);
        
        $query = $customer->appointments()
            ->with(['staff', 'service', 'branch']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('future_only', true)) {
            $query->where('starts_at', '>=', now());
        }

        $appointments = $query->orderBy('starts_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $appointments,
        ]);
    }

    /**
     * Search customers
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $customers = $this->customerRepository->search($request->q);

        return response()->json([
            'success' => true,
            'data' => CustomerResource::collection($customers),
        ]);
    }

    /**
     * Add tag to customer
     */
    public function addTag(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tag' => 'required|string|max:50',
        ]);

        $this->customerService->addTag($id, $request->tag);

        return response()->json([
            'success' => true,
            'message' => 'Tag erfolgreich hinzugefügt',
        ]);
    }

    /**
     * Remove tag from customer
     */
    public function removeTag(int $id, string $tag): JsonResponse
    {
        $this->customerService->removeTag($id, $tag);

        return response()->json([
            'success' => true,
            'message' => 'Tag erfolgreich entfernt',
        ]);
    }

    /**
     * Get customer history
     */
    public function history(int $id): JsonResponse
    {
        $history = $this->customerService->getHistory($id);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Export customer data
     */
    public function export(int $id): JsonResponse
    {
        $data = $this->customerService->export($id);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Merge duplicate customers
     */
    public function merge(Request $request): JsonResponse
    {
        $request->validate([
            'primary_id' => 'required|integer|exists:customers,id',
            'duplicate_id' => 'required|integer|exists:customers,id|different:primary_id',
        ]);

        try {
            $customer = $this->customerService->mergeDuplicates(
                $request->primary_id,
                $request->duplicate_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Kunden erfolgreich zusammengeführt',
                'data' => new CustomerResource($customer),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Find potential duplicates
     */
    public function duplicates(int $id): JsonResponse
    {
        $duplicates = $this->customerService->findPotentialDuplicates($id);

        return response()->json([
            'success' => true,
            'data' => CustomerResource::collection($duplicates),
        ]);
    }

    /**
     * Block customer
     */
    public function block(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->customerService->block($id, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich blockiert',
        ]);
    }

    /**
     * Unblock customer
     */
    public function unblock(int $id): JsonResponse
    {
        $this->customerService->unblock($id);

        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich entsperrt',
        ]);
    }
}