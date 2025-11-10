<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates that Retell function calls contain a valid call_id.
 *
 * Defense-in-Depth layer that provides early validation before reaching
 * the controller's getCanonicalCallId() method.
 *
 * FIX 2025-11-03: P1 Incident (call_bdcc364c) - Empty call_id Resolution
 * Part of 7-point optimization strategy (Anpassung #1)
 */
class ValidateRetellCallId
{
    /**
     * Handle an incoming Retell function call request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract call_id from both possible sources
        $callIdWebhook = $request->input('call.call_id');
        $callIdArgs = $request->input('args.call_id');

        // Normalize empty strings and "None" to null for consistent validation
        if ($callIdWebhook === '' || $callIdWebhook === 'None') {
            $callIdWebhook = null;
        }
        if ($callIdArgs === '' || $callIdArgs === 'None') {
            $callIdArgs = null;
        }

        // If BOTH sources are missing → 400 Bad Request (defense-in-depth)
        if (!$callIdWebhook && !$callIdArgs) {
            Log::warning('🚨 ValidateRetellCallId: Missing call_id from ALL sources', [
                'metric' => 'validation_missing_call_id',
                'url' => $request->url(),
                'method' => $request->method(),
                'webhook_raw_value' => $request->input('call.call_id'),
                'args_raw_value' => $request->input('args.call_id'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'error' => 'Missing call_id',
                'message' => 'Required call_id not found in webhook context or function arguments',
                'hint' => 'Ensure Retell agent configuration includes call_id parameter with {{call.call_id}} dynamic variable',
                'sources_checked' => [
                    'call.call_id' => 'empty or missing',
                    'args.call_id' => 'empty or missing'
                ]
            ], 400);
        }

        // Log validation success for monitoring
        Log::debug('✅ ValidateRetellCallId: Validation passed', [
            'canonical_source' => $callIdWebhook ? 'webhook' : 'args',
            'call_id_present' => true,
            'url' => $request->url()
        ]);

        return $next($request);
    }
}
