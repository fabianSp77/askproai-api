<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalSessionIsolation
{
    /**
     * Portal-spezifische Session Isolation
     * Stellt sicher, dass jedes Portal seine eigene Session hat
     */
    public function handle(Request $request, Closure $next)
    {
        // Erkenne welches Portal
        $portal = $this->detectPortal($request);
        
        // Setze portal-spezifische Session-Konfiguration
        $this->configurePortalSession($portal);
        
        // Stelle sicher, dass Company Context erhalten bleibt
        $this->preserveCompanyContext($portal);
        
        return $next($request);
    }
    
    /**
     * Erkennt welches Portal basierend auf URL
     */
    private function detectPortal(Request $request): string
    {
        if ($request->is('admin/*') || $request->is('livewire/*')) {
            return 'admin';
        }
        
        if ($request->is('portal/*') || $request->is('business/*')) {
            return 'business';
        }
        
        if ($request->is('api/admin/*')) {
            return 'admin_api';
        }
        
        if ($request->is('api/portal/*')) {
            return 'portal_api';
        }
        
        // Default
        return 'web';
    }
    
    /**
     * Konfiguriert Session für spezifisches Portal
     */
    private function configurePortalSession(string $portal): void
    {
        // Für Admin Portal, nutze admin-spezifische Config
        if ($portal === 'admin') {
            $adminConfig = config('session_admin');
            if ($adminConfig) {
                foreach ($adminConfig as $key => $value) {
                    config(['session.' . $key => $value]);
                }
            }
        }
        // Für Business Portal, nutze portal-spezifische Config
        elseif ($portal === 'business') {
            $portalConfig = config('session_portal');
            if ($portalConfig) {
                foreach ($portalConfig as $key => $value) {
                    config(['session.' . $key => $value]);
                }
            }
        }
        
        // Speichere aktuelles Portal in Session (nur wenn Session bereits gestartet)
        if (app()->bound('session') && session()->isStarted()) {
            session(['current_portal' => $portal]);
        }
    }
    
    /**
     * Erhält Company Context über Portal-Wechsel
     */
    private function preserveCompanyContext(string $portal): void
    {
        // Wenn kein Company Context, versuche zu ermitteln
        if (!session('active_company_id')) {
            $companyId = null;
            
            // Admin Portal
            if ($portal === 'admin' && Auth::check()) {
                $user = Auth::user();
                if ($user && method_exists($user, 'company')) {
                    $companyId = $user->company_id ?? $user->company->id ?? null;
                }
            }
            
            // Business Portal
            if ($portal === 'business' && Auth::guard('portal')->check()) {
                $portalUser = Auth::guard('portal')->user();
                if ($portalUser && $portalUser->company_id) {
                    $companyId = $portalUser->company_id;
                }
            }
            
            // Speichere Company ID explizit
            if ($companyId) {
                session(['active_company_id' => $companyId]);
                session(['portal_company_id' => $companyId]);
            }
        }
    }
}