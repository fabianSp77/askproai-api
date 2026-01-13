<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SessionController
 *
 * Verwaltet Session-Verlängerung und -Status für das Session-Timeout-Warning-System.
 * Wird vom Frontend Session Manager aufgerufen um die Session aktiv zu halten.
 *
 * @see resources/js/session-manager.js
 */
class SessionController extends Controller
{
    /**
     * Ping-Endpoint zur Session-Verlängerung.
     *
     * Wird vom Frontend bei Benutzeraktivität aufgerufen (debounced).
     * Der HTTP-Request selbst verlängert die Session automatisch (Laravel Session-Middleware).
     *
     * WICHTIG: Wir verwenden NICHT session()->regenerate() hier!
     * regenerate() ändert die Session-ID UND das CSRF-Token, was dazu führt,
     * dass nachfolgende Livewire-Requests mit 419 (CSRF Mismatch) fehlschlagen.
     * regenerate() ist nur nach Login zur Session-Fixation-Prevention gedacht.
     *
     * @see https://laravel.com/docs/session#regenerating-the-session-id
     * @param Request $request
     * @return JsonResponse
     */
    public function ping(Request $request): JsonResponse
    {
        // Prüfe ob Benutzer authentifiziert ist
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'unauthenticated',
                'message' => 'Sitzung abgelaufen',
            ], 401);
        }

        // Session wird automatisch durch diesen Request verlängert (StartSession Middleware)
        // KEIN regenerate() - das würde CSRF-Token invalidieren!

        // Berechne verbleibende Zeit
        $lifetime = (int) config('session.lifetime', 120);
        $remainingSeconds = $lifetime * 60;

        return response()->json([
            'success' => true,
            'remaining' => $remainingSeconds,
            'expires_at' => now()->addMinutes($lifetime)->toIso8601String(),
            'lifetime_minutes' => $lifetime,
            'warning_minutes' => 5,
        ]);
    }

    /**
     * Status-Endpoint für initiale Session-Konfiguration.
     *
     * Wird beim Seitenlade aufgerufen um die Session-Konfiguration zu erhalten.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'authenticated' => false,
                'remaining' => 0,
            ]);
        }

        $lifetime = (int) config('session.lifetime', 120);

        return response()->json([
            'authenticated' => true,
            'remaining' => $lifetime * 60,
            'lifetime_minutes' => $lifetime,
            'warning_minutes' => 5,
            'user_id' => Auth::id(),
        ]);
    }
}
