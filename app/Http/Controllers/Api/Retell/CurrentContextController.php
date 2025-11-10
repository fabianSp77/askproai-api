<?php

namespace App\Http\Controllers\Api\Retell;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * CurrentContextController
 *
 * Provides current date/time context for Retell AI agent
 * State-of-the-art 2025: Dynamic temporal context
 */
class CurrentContextController extends Controller
{
    /**
     * Handle get_current_context function call
     *
     * Returns current date, time, and contextual information
     * for the AI agent to use in conversations
     */
    public function handle(Request $request): JsonResponse
    {
        $now = Carbon::now('Europe/Berlin');

        return response()->json([
            'date' => $now->format('Y-m-d'),
            'time' => $now->format('H:i'),
            'day_of_week' => $now->locale('de')->dayName,
            'day_number' => $now->day,
            'month_name' => $now->locale('de')->monthName,
            'month_number' => $now->month,
            'year' => $now->year,
            'week_number' => $now->weekOfYear,
            'is_weekend' => $now->isWeekend(),
            'tomorrow' => [
                'date' => $now->copy()->addDay()->format('Y-m-d'),
                'day' => $now->copy()->addDay()->locale('de')->dayName,
            ],
            'yesterday' => [
                'date' => $now->copy()->subDay()->format('Y-m-d'),
                'day' => $now->copy()->subDay()->locale('de')->dayName,
            ],
            'formatted' => $now->locale('de')->isoFormat('dddd, D. MMMM YYYY'),
            'timezone' => 'Europe/Berlin',
            'timestamp' => $now->timestamp,
        ]);
    }
}
