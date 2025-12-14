<?php
namespace App\Services;

use Carbon\Carbon;

class RetellService
{
    /**
     * Build inbound call response for Retell webhook
     *
     * 🔧 FIX 2025-12-14: CRITICAL - Added temporal context to prevent date hallucination
     * BUG: Retell AI Agent was hallucinating dates (e.g., "Montag" → "17. Juni 2024")
     * CAUSE: Agent had no knowledge of current date, so it guessed from training data
     * FIX: Inject heute_datum, current_date, current_weekday into dynamic_variables
     */
    public static function buildInboundResponse(string $agentId, string $caller): array
    {
        // 🔧 FIX 2025-12-14: Calculate current date/time in Berlin timezone
        $now = Carbon::now('Europe/Berlin');

        return [
            'override_agent_id' => $agentId,
            'dynamic_variables' => [
                'customer_phone' => $caller,

                // 🔧 FIX 2025-12-14: CRITICAL - Temporal context for agent
                // Without this, agent hallucinates dates when user says "Montag", "morgen", etc.
                'heute_datum' => $now->format('d.m.Y'),           // "14.12.2025" - German format
                'current_date' => $now->format('Y-m-d'),          // "2025-12-14" - ISO format
                'current_weekday' => $now->locale('de')->dayName, // "Samstag"
                'current_time' => $now->format('H:i'),            // "12:30"
                'current_year' => $now->format('Y'),              // "2025"
                'current_month' => $now->locale('de')->monthName, // "Dezember"

                // 🔧 Explicit "next Monday" calculation to help agent
                'naechster_montag' => $now->copy()->next(Carbon::MONDAY)->format('d.m.Y'),
            ],
        ];
    }
}
