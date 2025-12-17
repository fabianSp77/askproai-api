<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pilot Company Middleware
 *
 * PURPOSE: Gradual rollout of Customer Portal
 *
 * MECHANISM:
 * - Check if user's company is flagged as pilot
 * - Allow access if pilot OR if full rollout enabled
 * - Return 403 with informative message otherwise
 *
 * CONFIGURATION:
 * - Database flag: companies.is_pilot
 * - Global override: config('features.customer_portal_enabled')
 *
 * DEPLOYMENT STRATEGY:
 * 1. Phase 1: Pilot companies only (is_pilot = true)
 * 2. Phase 2: Gradual rollout (50% of companies)
 * 3. Phase 3: Full rollout (config override)
 */
class PilotCompanyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Global feature flag check (for full rollout)
        if (config('features.customer_portal_enabled', false)) {
            return $next($request);
        }

        // Get authenticated user
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required',
                'code' => 'UNAUTHORIZED',
            ], 401);
        }

        // Check if user's company is pilot
        if (!$user->company) {
            return response()->json([
                'success' => false,
                'error' => 'No company assigned to user',
                'code' => 'NO_COMPANY',
            ], 403);
        }

        // Pilot company check
        if ($user->company->is_pilot) {
            // Log pilot company access for metrics
            \Log::info('Pilot company accessed customer portal', [
                'company_id' => $user->company->id,
                'company_name' => $user->company->name,
                'user_id' => $user->id,
                'user_role' => $user->roles->pluck('name'),
            ]);

            return $next($request);
        }

        // Not pilot company - deny access
        return response()->json([
            'success' => false,
            'error' => 'Customer portal is not available for your company yet. Please contact support for early access.',
            'code' => 'NOT_PILOT_COMPANY',
            'meta' => [
                'feature' => 'customer_portal',
                'status' => 'pilot_only',
                'contact_email' => config('app.support_email', 'support@askpro.ai'),
            ],
        ], 403);
    }
}
