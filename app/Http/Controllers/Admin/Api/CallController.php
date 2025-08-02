<?php

namespace App\Http\Controllers\Admin\Api;

use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CallController extends BaseAdminApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Call::where("company_id", auth()->user()->company_id)
            ->with([
                'company' => function($q) { $q->where("company_id", auth()->user()->company_id); },
                'branch' => function($q) { $q->where("company_id", auth()->user()->company_id); },
                'customer' => function($q) { $q->where("company_id", auth()->user()->company_id); }
            ])
            ->latest();

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('from_phone_number', 'like', "%{$search}%")
                  ->orWhere('to_phone_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }

        $calls = $query->paginate($request->get('per_page', 20));

        // Transform data for frontend
        $calls->getCollection()->transform(function ($call) {
            return [
                'id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'from_phone_number' => $call->from_phone_number,
                'to_phone_number' => $call->to_phone_number,
                'direction' => $call->direction,
                'status' => $call->status,
                'duration_seconds' => $call->duration_seconds,
                'duration_formatted' => gmdate('i:s', $call->duration_seconds),
                'created_at' => $call->created_at->format('d.m.Y H:i'),
                'company' => $call->company ? [
                    'id' => $call->company->id,
                    'name' => $call->company->name
                ] : null,
                'branch' => $call->branch ? [
                    'id' => $call->branch->id,
                    'name' => $call->branch->name
                ] : null,
                'customer' => $call->customer ? [
                    'id' => $call->customer->id,
                    'name' => $call->customer->name,
                    'phone' => $call->customer->phone
                ] : null,
                'has_recording' => !empty($call->recording_url),
                'has_transcript' => !empty($call->transcript),
                'answered' => $call->answered,
                'sentiment' => $call->sentiment,
                'cost' => floatval($call->cost ?? 0),
                'is_refunded' => $call->is_refunded ?? false,
                'is_non_billable' => $call->is_non_billable ?? false,
                'appointment_created' => $call->appointment_id ? true : false,
                'metadata' => $call->metadata,
            ];
        });

        return response()->json($calls);
    }

    public function show($id): JsonResponse
    {
        $call = Call::where("company_id", auth()->user()->company_id)
            ->with([
                'company' => function($q) { 
                    $q->withoutGlobalScopes()->with('billingRate'); 
                },
                'branch' => function($q) { $q->where("company_id", auth()->user()->company_id); },
                'customer' => function($q) { $q->where("company_id", auth()->user()->company_id); },
                'appointment' => function($q) { $q->where("company_id", auth()->user()->company_id); }
            ])
            ->findOrFail($id);

        // Get billing rate
        $billingRate = $call->company->billingRate()->active()->first();
        
        $data = [
            'id' => $call->id,
            'retell_call_id' => $call->retell_call_id,
            'from_phone_number' => $call->from_phone_number,
            'to_phone_number' => $call->to_phone_number,
            'direction' => $call->direction,
            'status' => $call->status,
            'duration_seconds' => $call->duration_seconds,
            'duration_sec' => $call->duration_sec ?? $call->duration_seconds,
            'duration_formatted' => gmdate('i:s', $call->duration_seconds ?? $call->duration_sec ?? 0),
            'created_at' => $call->created_at->format('d.m.Y H:i:s'),
            'ended_at' => $call->ended_at ? $call->ended_at->format('d.m.Y H:i:s') : null,
            'company' => array_merge($call->company->toArray(), [
                'billing_rate_per_min' => $billingRate ? $billingRate->rate_per_minute : 1.50
            ]),
            'branch' => $call->branch,
            'customer' => $call->customer,
            'appointment' => $call->appointment,
            'recording_url' => $call->recording_url,
            'transcript' => $call->transcript,
            'summary' => $call->summary,
            'sentiment' => $call->sentiment,
            'answered' => $call->answered,
            'metadata' => $call->metadata,
            'cost' => floatval($call->cost ?? 0),
        ];

        return response()->json($data);
    }

    public function transcript($id): JsonResponse
    {
        $call = Call::where("company_id", auth()->user()->company_id)->findOrFail($id);

        if (empty($call->transcript)) {
            return response()->json([
                'message' => 'No transcript available for this call'
            ], 404);
        }

        return response()->json([
            'transcript' => $call->transcript,
            'summary' => $call->summary,
            'sentiment' => $call->sentiment
        ]);
    }

    public function stats(): JsonResponse
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $totalCalls = Call::where("company_id", auth()->user()->company_id)->count();
        $answeredCalls = Call::where("company_id", auth()->user()->company_id)->where('status', 'completed')->count();

        $stats = [
            'total_calls' => $totalCalls,
            'calls_today' => Call::where("company_id", auth()->user()->company_id)->whereDate('created_at', $today)->count(),
            'calls_yesterday' => Call::where("company_id", auth()->user()->company_id)->whereDate('created_at', $yesterday)->count(),
            'answered_rate' => $totalCalls > 0 ? ($answeredCalls / $totalCalls * 100) : 0,
            'average_duration' => Call::where("company_id", auth()->user()->company_id)->where('status', 'completed')->avg('duration_sec') ?? 0,
            'by_status' => Call::where("company_id", auth()->user()->company_id)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_sentiment' => Call::where("company_id", auth()->user()->company_id)
                ->whereNotNull('sentiment')
                ->selectRaw('sentiment, count(*) as count')
                ->groupBy('sentiment')
                ->pluck('count', 'sentiment'),
        ];

        return response()->json($stats);
    }

    public function markNonBillable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_ids' => 'required|array',
            'call_ids.*' => 'exists:calls,id'
        ]);

        DB::beginTransaction();
        try {
            Call::where("company_id", auth()->user()->company_id)
                ->whereIn('id', $validated['call_ids'])
                ->update(['is_non_billable' => true]);

            DB::commit();

            return response()->json([
                'message' => count($validated['call_ids']) . ' calls marked as non-billable'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update calls'], 500);
        }
    }

    public function createRefund(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_ids' => 'required|array',
            'call_ids.*' => 'exists:calls,id',
            'refund_reason' => 'required|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            Call::where("company_id", auth()->user()->company_id)
                ->whereIn('id', $validated['call_ids'])
                ->update([
                    'is_refunded' => true,
                    'refund_reason' => $validated['refund_reason'],
                    'refunded_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'message' => count($validated['call_ids']) . ' calls refunded'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create refunds'], 500);
        }
    }

    public function share($id): JsonResponse
    {
        $call = Call::where("company_id", auth()->user()->company_id)->findOrFail($id);
        
        // Generate a temporary sharing link (valid for 7 days)
        $shareToken = \Str::random(32);
        
        DB::table('call_shares')->insert([
            'call_id' => $call->id,
            'token' => $shareToken,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $shareUrl = config('app.url') . '/shared-call/' . $shareToken;

        return response()->json([
            'share_url' => $shareUrl,
            'expires_at' => now()->addDays(7)->format('Y-m-d H:i:s')
        ]);
    }

    public function getRecording($id): JsonResponse
    {
        $call = Call::where("company_id", auth()->user()->company_id)->findOrFail($id);

        if (empty($call->recording_url)) {
            return response()->json([
                'message' => 'No recording available for this call'
            ], 404);
        }

        return response()->json([
            'recording_url' => $call->recording_url,
            'duration' => $call->duration_seconds,
            'format' => 'mp3'
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_ids' => 'required|array',
            'call_ids.*' => 'exists:calls,id'
        ]);

        DB::beginTransaction();
        try {
            Call::where("company_id", auth()->user()->company_id)
                ->whereIn('id', $validated['call_ids'])
                ->delete();

            DB::commit();

            return response()->json([
                'message' => count($validated['call_ids']) . ' calls deleted'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete calls'], 500);
        }
    }

}