<?php

namespace App\Http\Controllers\Admin\Api;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AppointmentController extends BaseAdminApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::withoutGlobalScopes()
            ->with([
                'company' => function($q) { $q->withoutGlobalScopes(); },
                'branch' => function($q) { $q->withoutGlobalScopes(); },
                'customer' => function($q) { $q->withoutGlobalScopes(); },
                'staff' => function($q) { $q->withoutGlobalScopes(); },
                'service' => function($q) { $q->withoutGlobalScopes(); }
            ]);

        // Date filter
        if ($request->has('date')) {
            $query->whereDate('starts_at', $request->get('date'));
        } elseif ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('starts_at', [$request->get('date_from'), $request->get('date_to')]);
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Company filter
        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'starts_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $appointments = $query->paginate($request->get('per_page', 20));

        // Transform for frontend
        $appointments->getCollection()->transform(function ($appointment) {
            return [
                'id' => $appointment->id,
                'starts_at' => $appointment->starts_at ? $appointment->starts_at->format('d.m.Y H:i') : null,
                'ends_at' => $appointment->ends_at ? $appointment->ends_at->format('H:i') : null,
                'status' => $appointment->status,
                'price' => $appointment->price,
                'notes' => $appointment->notes,
                'company' => $appointment->company ? [
                    'id' => $appointment->company->id,
                    'name' => $appointment->company->name
                ] : null,
                'branch' => $appointment->branch ? [
                    'id' => $appointment->branch->id,
                    'name' => $appointment->branch->name
                ] : null,
                'customer' => $appointment->customer ? [
                    'id' => $appointment->customer->id,
                    'name' => $appointment->customer->name,
                    'phone' => $appointment->customer->phone
                ] : null,
                'staff' => $appointment->staff ? [
                    'id' => $appointment->staff->id,
                    'name' => $appointment->staff->name
                ] : null,
                'service' => $appointment->service ? [
                    'id' => $appointment->service->id,
                    'name' => $appointment->service->name,
                    'duration' => $appointment->service->duration
                ] : null,
                'reminder_sent' => $appointment->reminder_sent,
                'cal_event_id' => $appointment->cal_event_id,
                'payment_status' => $appointment->payment_status,
                'no_show_count' => $appointment->customer->appointments()
                    ->where('status', 'no_show')
                    ->count(),
            ];
        });

        return response()->json($appointments);
    }

    public function show($id): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScopes()
            ->with([
                'company' => function($q) { $q->withoutGlobalScopes(); },
                'branch' => function($q) { $q->withoutGlobalScopes(); },
                'customer' => function($q) { $q->withoutGlobalScopes(); },
                'staff' => function($q) { $q->withoutGlobalScopes(); },
                'service' => function($q) { $q->withoutGlobalScopes(); },
                'call' => function($q) { $q->withoutGlobalScopes(); }
            ])
            ->findOrFail($id);

        return response()->json($appointment);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'required|exists:customers,id',
            'staff_id' => 'required|exists:staff,id',
            'service_id' => 'required|exists:services,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'status' => 'required|in:scheduled,confirmed,completed,cancelled,no_show',
            'price' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $appointment = Appointment::create($validated);

        return response()->json($appointment, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScopes()->findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'staff_id' => 'sometimes|exists:staff,id',
            'service_id' => 'sometimes|exists:services,id',
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date|after:starts_at',
            'status' => 'sometimes|in:scheduled,confirmed,completed,cancelled,no_show',
            'price' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $appointment->update($validated);

        return response()->json($appointment);
    }

    public function destroy($id): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScopes()->findOrFail($id);
        $appointment->delete();

        return response()->json(null, 204);
    }

    public function cancel($id): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScopes()->findOrFail($id);
        $appointment->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Appointment cancelled successfully']);
    }

    public function confirm($id): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScopes()->findOrFail($id);
        $appointment->update(['status' => 'confirmed']);

        return response()->json(['message' => 'Appointment confirmed successfully']);
    }

    public function complete($id): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScopes()->findOrFail($id);
        $appointment->update(['status' => 'completed']);

        return response()->json(['message' => 'Appointment completed successfully']);
    }

    public function noShow($id): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScopes()->findOrFail($id);
        $appointment->update(['status' => 'no_show']);

        // Track no-show count for customer
        $customer = $appointment->customer;
        $noShowCount = $customer->appointments()->where('status', 'no_show')->count();

        return response()->json([
            'message' => 'Appointment marked as no-show',
            'customer_no_show_count' => $noShowCount
        ]);
    }

    public function sendReminder($id): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScopes()->findOrFail($id);

        try {
            // Dispatch reminder job
            \App\Jobs\SendAppointmentReminderJob::dispatch($appointment);
            
            $appointment->update(['reminder_sent' => true]);

            return response()->json(['message' => 'Reminder sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send reminder'], 500);
        }
    }

    public function reschedule(Request $request, $id): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScopes()->findOrFail($id);

        $validated = $request->validate([
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'staff_id' => 'sometimes|exists:staff,id',
        ]);

        DB::beginTransaction();
        try {
            $appointment->update($validated);
            
            // Update Cal.com event if exists
            if ($appointment->cal_event_id) {
                // Trigger Cal.com update job
                \App\Jobs\UpdateCalcomEventJob::dispatch($appointment);
            }

            DB::commit();

            return response()->json([
                'message' => 'Appointment rescheduled successfully',
                'appointment' => $appointment
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to reschedule appointment'], 500);
        }
    }

    public function checkIn($id): JsonResponse
    {
        $appointment = Appointment::withoutGlobalScopes()->findOrFail($id);
        
        $appointment->update([
            'checked_in_at' => now(),
            'status' => 'confirmed'
        ]);

        return response()->json(['message' => 'Customer checked in successfully']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'appointment_ids' => 'required|array',
            'appointment_ids.*' => 'exists:appointments,id',
            'action' => 'required|in:update_status,send_reminders,export',
            'status' => 'required_if:action,update_status|in:scheduled,confirmed,completed,cancelled,no_show',
        ]);

        $appointments = Appointment::withoutGlobalScopes()
            ->whereIn('id', $validated['appointment_ids'])
            ->get();

        switch ($validated['action']) {
            case 'update_status':
                DB::beginTransaction();
                try {
                    foreach ($appointments as $appointment) {
                        $appointment->update(['status' => $validated['status']]);
                    }
                    DB::commit();
                    return response()->json(['message' => count($appointments) . ' appointments updated']);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['error' => 'Failed to update appointments'], 500);
                }
                
            case 'send_reminders':
                $sent = 0;
                foreach ($appointments as $appointment) {
                    if (!$appointment->reminder_sent && $appointment->status === 'scheduled') {
                        \App\Jobs\SendAppointmentReminderJob::dispatch($appointment);
                        $appointment->update(['reminder_sent' => true]);
                        $sent++;
                    }
                }
                return response()->json(['message' => $sent . ' reminders sent']);
                
            case 'export':
                // Generate CSV export
                $csvData = $this->generateCsvExport($appointments);
                return response()->json(['csv_data' => $csvData]);
        }
    }

    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $query = Appointment::withoutGlobalScopes()
            ->whereBetween('starts_at', [$dateFrom, $dateTo]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $stats = [
            'total_appointments' => $query->count(),
            'by_status' => $query->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status'),
            'revenue' => $query->whereIn('status', ['completed', 'confirmed'])->sum('price'),
            'average_price' => $query->whereIn('status', ['completed', 'confirmed'])->avg('price'),
            'most_booked_service' => $query->groupBy('service_id')
                ->selectRaw('service_id, count(*) as count')
                ->orderBy('count', 'desc')
                ->with('service')
                ->first(),
            'busiest_days' => $query->selectRaw('DAYOFWEEK(starts_at) as day, count(*) as count')
                ->groupBy('day')
                ->orderBy('count', 'desc')
                ->get(),
            'peak_hours' => $query->selectRaw('HOUR(starts_at) as hour, count(*) as count')
                ->groupBy('hour')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }

    public function quickFilters(Request $request): JsonResponse
    {
        $baseQuery = Appointment::withoutGlobalScopes();

        if ($request->has('company_id')) {
            $baseQuery->where('company_id', $request->get('company_id'));
        }

        $filters = [
            'today' => $baseQuery->clone()->whereDate('starts_at', today())->count(),
            'tomorrow' => $baseQuery->clone()->whereDate('starts_at', today()->addDay())->count(),
            'this_week' => $baseQuery->clone()->whereBetween('starts_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'past_due' => $baseQuery->clone()
                ->where('starts_at', '<', now())
                ->where('status', 'scheduled')
                ->count(),
            'created_today' => $baseQuery->clone()->whereDate('created_at', today())->count(),
        ];

        return response()->json($filters);
    }

    private function generateCsvExport($appointments)
    {
        $headers = ['ID', 'Date', 'Time', 'Customer', 'Service', 'Staff', 'Status', 'Price'];
        $rows = [];

        foreach ($appointments as $appointment) {
            $rows[] = [
                $appointment->id,
                $appointment->starts_at ? $appointment->starts_at->format('d.m.Y') : '',
                $appointment->starts_at ? $appointment->starts_at->format('H:i') : '',
                $appointment->customer->name ?? '',
                $appointment->service->name ?? '',
                $appointment->staff->name ?? '',
                $appointment->status,
                $appointment->price ?? 0
            ];
        }

        return [
            'headers' => $headers,
            'rows' => $rows
        ];
    }

}