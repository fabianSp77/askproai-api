<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Security\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AdaptiveRateLimitMiddleware
{
    private RateLimiter $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestKey($request);
        $endpoint = $request->path();

        if ($this->rateLimiter->tooManyAttempts($key, $endpoint)) {
            return $this->buildResponse($key, $endpoint);
        }

        $this->rateLimiter->hit($key, $endpoint);

        $response = $next($request);

        return $this->addHeaders($response, $key, $endpoint);
    }

    /**
     * Resolve the request key
     */
    protected function resolveRequestKey(Request $request): string
    {
        if ($user = $request->user()) {
            return 'user:' . $user->id;
        }

        return 'ip:' . $request->ip();
    }

    /**
     * Build rate limit exceeded response with delightful UX
     */
    protected function buildResponse(string $key, string $endpoint): Response
    {
        $retryAfter = $this->rateLimiter->availableIn($key);
        $limit = $this->rateLimiter->getLimit($endpoint)['requests'] ?? 60;
        
        // Get friendly message based on user and context
        $friendlyMessage = $this->getFriendlyRateLimitMessage($retryAfter, $endpoint);
        
        $response = [
            'error' => 'rate_limit_exceeded',
            'title' => __('security.rate_limit.title'),
            'message' => $friendlyMessage['message'],
            'motivation' => $friendlyMessage['motivation'],
            'retry_after' => $retryAfter,
            'retry_after_human' => $this->formatRetryTime($retryAfter),
            'tips' => $friendlyMessage['tips'],
            'actions' => [
                'wait' => __('security.rate_limit.actions.wait'),
                'learn_more' => __('security.rate_limit.actions.learn_more'),
                'contact_support' => __('security.rate_limit.actions.contact_support'),
            ],
            'ux_data' => [
                'show_countdown' => true,
                'show_progress' => true,
                'celebration_on_unlock' => true,
                'limit' => $limit,
                'endpoint' => $endpoint
            ]
        ];

        return response()->json($response, 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => 0,
            'X-UX-Friendly' => 'true',
        ]);
    }

    /**
     * Add rate limit headers to response with UX enhancements
     */
    protected function addHeaders(Response $response, string $key, string $endpoint): Response
    {
        if ($response instanceof \Illuminate\Http\Response || $response instanceof \Symfony\Component\HttpFoundation\Response) {
            $limit = $this->rateLimiter->getLimit($endpoint)['requests'] ?? 60;
            $remaining = $this->rateLimiter->remaining($key, $endpoint);
            $usedPercentage = (($limit - $remaining) / $limit) * 100;
            
            $response->headers->add([
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => $remaining,
                'X-RateLimit-Used-Percentage' => round($usedPercentage, 1),
                'X-RateLimit-Warning-Threshold' => 80, // Show warning at 80%
                'X-RateLimit-Status' => $this->getRateLimitStatus($usedPercentage),
            ]);
        }

        return $response;
    }
    
    /**
     * Get friendly rate limit message based on context
     */
    protected function getFriendlyRateLimitMessage(int $retryAfter, string $endpoint): array
    {
        $messages = [
            'api' => [
                'message' => 'Du warst richtig fleißig mit der API! 🚀 Kurze Verschnaufpause?',
                'motivation' => 'Das schützt alle vor Überlastung und hält das System flott.',
                'tips' => ['Nutze die Zeit für einen Kaffee ☕', 'Schau dir die API-Docs an 📚']
            ],
            'admin' => [
                'message' => 'Wow, du bist heute sehr aktiv im Admin-Bereich! 💪',
                'motivation' => 'Eine kurze Pause tut gut - du machst großartige Arbeit!',
                'tips' => ['Perfekte Zeit für ein Status-Update 📊', 'Vielleicht die Hilfe-Sektion durchstöbern? 🤔']
            ],
            'portal' => [
                'message' => 'Du nutzt das Portal sehr intensiv! 🎯',
                'motivation' => 'Das zeigt, wie wertvoll unser System für dich ist!',
                'tips' => ['Zeit für einen kurzen Stretch 🧘‍♀️', 'Oder schnell die Notifications checken 🔔']
            ],
            'default' => [
                'message' => 'Du warst gerade sehr aktiv! 🚀 Wir brauchen nur eine kurze Verschnaufpause.',
                'motivation' => 'Das ist völlig normal und schützt die Performance für alle.',
                'tips' => ['Perfekte Zeit für einen kleinen Break 😊', 'Vielleicht ein kurzer Blick auf deine Erfolge? 📈']
            ]
        ];
        
        // Determine context from endpoint
        if (str_contains($endpoint, 'api/')) {
            $context = 'api';
        } elseif (str_contains($endpoint, 'admin/')) {
            $context = 'admin';
        } elseif (str_contains($endpoint, 'portal/')) {
            $context = 'portal';
        } else {
            $context = 'default';
        }
        
        return $messages[$context];
    }
    
    /**
     * Format retry time in human-readable format
     */
    protected function formatRetryTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' Sekunden';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . 'm ' . $remainingSeconds . 's';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }
    
    /**
     * Get rate limit status for UX indicators
     */
    protected function getRateLimitStatus(float $usedPercentage): string
    {
        if ($usedPercentage >= 95) return 'critical';
        if ($usedPercentage >= 80) return 'warning';
        if ($usedPercentage >= 60) return 'moderate';
        return 'ok';
    }
}