<?php

namespace App\Overrides;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom StartSession that handles Livewire Redirector objects
 */
class CustomStartSession extends StartSession
{
    /**
     * Handle an incoming request - with Livewire Redirector fix
     */
    public function handle($request, Closure $next)
    {
        if (! $this->sessionConfigured()) {
            return $next($request);
        }

        $session = $this->getSession($request);

        if ($this->manager->shouldBlock() ||
            ($request->route() instanceof \Illuminate\Routing\Route && $request->route()->locksFor())) {
            return $this->handleRequestWhileBlockingFixed($request, $session, $next);
        }

        return $this->handleStatefulRequestFixed($request, $session, $next);
    }

    /**
     * Handle the given request within session state - FIXED version
     */
    protected function handleStatefulRequestFixed(Request $request, $session, Closure $next)
    {
        $request->setLaravelSession(
            $this->startSession($request, $session)
        );

        $this->collectGarbage($session);

        $response = $next($request);

        // FIX: Check if response is a Livewire Redirector and convert it
        if (!($response instanceof Response)) {
            $response = $this->convertToResponse($response, $request);
        }

        $this->storeCurrentUrl($request, $session);

        $this->addCookieToResponse($response, $session);

        $this->saveSession($request);

        return $response;
    }

    /**
     * Handle request while blocking - FIXED version
     */
    protected function handleRequestWhileBlockingFixed(Request $request, $session, Closure $next)
    {
        if (! $request->route() instanceof \Illuminate\Routing\Route) {
            return;
        }

        $lockFor = $request->route() && $request->route()->locksFor()
                        ? $request->route()->locksFor()
                        : $this->manager->defaultRouteBlockLockSeconds();

        $lock = $this->cache($this->manager->blockDriver())
            ->lock('session:'.$session->getId(), $lockFor)
            ->betweenBlockedAttemptsSleepFor(50);

        try {
            $lock->block(
                ! is_null($request->route()->waitsFor())
                        ? $request->route()->waitsFor()
                        : $this->manager->defaultRouteBlockWaitSeconds()
            );

            return $this->handleStatefulRequestFixed($request, $session, $next);
        } finally {
            $lock?->release();
        }
    }

    /**
     * Convert non-Response objects to Response
     */
    protected function convertToResponse($response, Request $request): Response
    {
        // Check if it's a Livewire Redirector
        if (is_object($response)) {
            $className = get_class($response);
            
            if (str_contains($className, 'Livewire') && str_contains($className, 'Redirector')) {
                // Try to get URL
                $url = '/admin/login';
                
                if (method_exists($response, 'getUrl')) {
                    $url = $response->getUrl();
                } elseif (method_exists($response, 'getTargetUrl')) {
                    $url = $response->getTargetUrl();
                } elseif (property_exists($response, 'url')) {
                    $url = $response->url;
                }
                
                // For Livewire requests, return JSON
                if ($request->hasHeader('X-Livewire')) {
                    return new \Illuminate\Http\JsonResponse([
                        'effects' => [
                            'redirect' => $url
                        ]
                    ]);
                }
                
                return new \Illuminate\Http\RedirectResponse($url);
            }
            
            // Try to convert other objects
            if (method_exists($response, 'toResponse')) {
                return $response->toResponse($request);
            }
        }
        
        // Default: wrap in Response
        return new \Illuminate\Http\Response($response);
    }
}