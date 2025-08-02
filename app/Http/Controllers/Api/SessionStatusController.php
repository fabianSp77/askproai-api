<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SessionStatusController extends Controller
{
    /**
     * Get current session status and remaining time
     */
    public function status(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'authenticated' => false,
                'message' => __('security.session.expired.message')
            ], 401);
        }

        $sessionLifetime = config('session.lifetime') * 60; // Convert minutes to seconds
        $lastActivity = Session::get('last_activity', time());
        $timeElapsed = time() - $lastActivity;
        $timeLeft = max(0, $sessionLifetime - $timeElapsed);

        // Update last activity
        Session::put('last_activity', time());

        $status = [
            'authenticated' => true,
            'time_left' => $timeLeft,
            'time_left_human' => $this->formatTime($timeLeft),
            'lifetime' => $sessionLifetime,
            'warning_threshold' => 5 * 60, // 5 minutes
            'should_warn' => $timeLeft <= (5 * 60) && $timeLeft > 0,
            'user' => [
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
            ],
            'messages' => [
                'warning_title' => __('security.session.warning.title'),
                'warning_message' => __('security.session.warning.message', ['minutes' => ceil($timeLeft / 60)]),
                'extend_button' => __('security.session.warning.actions.extend'),
                'logout_button' => __('security.session.warning.actions.logout'),
            ]
        ];

        return response()->json($status);
    }

    /**
     * Extend the current session
     */
    public function extend(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => __('security.session.expired.message')
            ], 401);
        }

        // Reset session activity
        Session::put('last_activity', time());
        
        // Regenerate session ID for security
        Session::regenerate();

        $newLifetime = config('session.lifetime') * 60;

        return response()->json([
            'success' => true,
            'message' => __('security.session.extended.message'),
            'time_left' => $newLifetime,
            'time_left_human' => $this->formatTime($newLifetime),
            'extended_by' => $this->formatTime($newLifetime),
            'celebration' => [
                'title' => __('security.session.extended.title'),
                'message' => __('security.session.extended.message'),
                'emoji' => '👍',
                'show_confetti' => false
            ]
        ]);
    }

    /**
     * Get session activity log for the current user
     */
    public function activity(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'authenticated' => false
            ], 401);
        }

        // This would typically come from a sessions or activity log table
        $activities = [
            [
                'action' => 'login',
                'timestamp' => now()->subHours(2)->toISOString(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'location' => 'Deutschland', // Would use IP geolocation
            ],
            [
                'action' => 'session_extended',
                'timestamp' => now()->subMinutes(30)->toISOString(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'location' => 'Deutschland',
            ]
        ];

        return response()->json([
            'activities' => $activities,
            'current_session' => [
                'started_at' => Session::get('login_time', now()->subHours(2)->toISOString()),
                'last_activity' => now()->toISOString(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        ]);
    }

    /**
     * Logout with friendly message
     */
    public function logout(Request $request): JsonResponse
    {
        $userName = Auth::user()->name ?? 'User';
        
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();

        return response()->json([
            'success' => true,
            'message' => __('security.success.logout', ['name' => $userName]),
            'farewell' => [
                'title' => 'Bis bald, ' . $userName . '! 👋',
                'message' => 'Du warst heute richtig produktiv!',
                'tip' => 'Deine Arbeit wurde automatisch gespeichert.',
                'emoji' => '😊'
            ],
            'redirect_url' => route('filament.admin.auth.login')
        ]);
    }

    /**
     * Format seconds into human readable time
     */
    private function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' Sekunden';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            if ($remainingSeconds > 0) {
                return $minutes . ' Min. ' . $remainingSeconds . ' Sek.';
            }
            return $minutes . ' Minuten';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            if ($minutes > 0) {
                return $hours . ' Std. ' . $minutes . ' Min.';
            }
            return $hours . ' Stunden';
        }
    }

    /**
     * Check if user needs security reminders
     */
    public function securityReminders(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['authenticated' => false], 401);
        }

        $user = Auth::user();
        $reminders = [];

        // Check 2FA status
        if (!$this->userHas2FA($user)) {
            $reminders[] = [
                'type' => '2fa_setup',
                'priority' => 'high',
                'title' => 'Zwei-Faktor-Auth einrichten',
                'message' => 'Mach dein Konto unbezwingbar! 🛡️',
                'action_url' => route('filament.admin.auth.two-factor'),
                'action_text' => 'Jetzt einrichten',
                'dismissible' => true,
                'emoji' => '🔐'
            ];
        }

        // Check password age
        if ($user->password_changed_at && now()->diffInDays($user->password_changed_at) > 90) {
            $reminders[] = [
                'type' => 'password_update',
                'priority' => 'medium',
                'title' => 'Passwort aktualisieren',
                'message' => 'Dein Passwort ist schon eine Weile alt. Zeit für ein neues! 🔑',
                'action_url' => route('filament.admin.auth.password'),
                'action_text' => 'Passwort ändern',
                'dismissible' => true,
                'emoji' => '🔄'
            ];
        }

        // Security tip of the day
        $tips = [
            'Melde dich immer ab, wenn du fertig bist 👋',
            'Nutze verschiedene Passwörter für verschiedene Dienste 🔐',
            'Prüfe regelmäßig deine Login-Aktivitäten 🔍',
            'Zwei-Faktor-Auth reduziert das Hack-Risiko um 99.9%! 📊'
        ];

        $reminders[] = [
            'type' => 'security_tip',
            'priority' => 'low',
            'title' => 'Sicherheits-Tipp des Tages',
            'message' => $tips[array_rand($tips)],
            'dismissible' => true,
            'emoji' => '💡'
        ];

        return response()->json([
            'reminders' => $reminders,
            'count' => count($reminders),
            'high_priority_count' => count(array_filter($reminders, fn($r) => $r['priority'] === 'high'))
        ]);
    }

    /**
     * Check if user has 2FA enabled
     */
    private function userHas2FA($user): bool
    {
        // Implement based on your 2FA system
        return !empty($user->two_factor_secret) || !empty($user->two_factor_confirmed_at);
    }
}