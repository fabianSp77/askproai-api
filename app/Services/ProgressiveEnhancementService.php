<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Jenssegers\Agent\Agent;

class ProgressiveEnhancementService
{
    protected Agent $agent;
    
    public function __construct()
    {
        $this->agent = new Agent();
    }
    
    /**
     * Determine the enhancement level for the current request
     * Level 0: No JavaScript (server-only)
     * Level 1: Basic Alpine.js
     * Level 2: Full Alpine.js
     * Level 3: Alpine + React hybrid
     * Level 4: Full React SPA
     */
    public function getEnhancementLevel(Request $request): int
    {
        // Check if user has forced a specific level
        if ($request->has('enhancement_level')) {
            $level = (int) $request->get('enhancement_level');
            Cookie::queue('enhancement_level', $level, 60 * 24 * 30); // 30 days
            return $level;
        }
        
        // Check cookie preference
        if ($request->cookie('enhancement_level') !== null) {
            return (int) $request->cookie('enhancement_level');
        }
        
        // Auto-detect based on device and connection
        return $this->autoDetectLevel($request);
    }
    
    protected function autoDetectLevel(Request $request): int
    {
        // No JavaScript support detected
        if ($request->header('X-No-JavaScript') === 'true') {
            return 0;
        }
        
        // Check device capabilities
        if ($this->agent->isMobile()) {
            // Mobile devices get lighter experience by default
            if ($this->isSlowConnection($request)) {
                return 1; // Basic Alpine.js only
            }
            return 2; // Full Alpine.js
        }
        
        // Desktop/tablet devices
        if ($this->agent->isTablet()) {
            return 3; // Hybrid mode
        }
        
        // Desktop gets full experience
        return 4; // Full React SPA
    }
    
    protected function isSlowConnection(Request $request): bool
    {
        // Check Save-Data header
        if ($request->header('Save-Data') === 'on') {
            return true;
        }
        
        // Check Network Information API hints
        $ect = $request->header('ECT'); // Effective Connection Type
        if (in_array($ect, ['slow-2g', '2g'])) {
            return true;
        }
        
        // Check RTT (Round Trip Time)
        $rtt = $request->header('RTT');
        if ($rtt && (int) $rtt > 300) { // 300ms threshold
            return true;
        }
        
        return false;
    }
    
    public function getAssets(int $level): array
    {
        $assets = [
            'css' => [
                'css/app.css', // Always included
            ],
            'js' => [],
            'preload' => [],
        ];
        
        switch ($level) {
            case 0: // No JavaScript
                // Only CSS, no JS
                break;
                
            case 1: // Basic Alpine.js
                $assets['js'][] = 'js/alpine-core.js';
                $assets['js'][] = 'js/alpine-basic-components.js';
                break;
                
            case 2: // Full Alpine.js
                $assets['js'][] = 'js/alpine-core.js';
                $assets['js'][] = 'js/alpine-plugins.js';
                $assets['js'][] = 'js/alpine-portal.js';
                $assets['preload'][] = 'js/alpine-components.js';
                break;
                
            case 3: // Hybrid
                $assets['js'][] = 'js/alpine-core.js';
                $assets['js'][] = 'js/alpine-portal.js';
                $assets['js'][] = 'js/react-lite.js';
                $assets['js'][] = 'js/hybrid-bridge.js';
                $assets['preload'][] = 'js/react-components.js';
                break;
                
            case 4: // Full React SPA
                $assets['js'][] = 'js/react.js';
                $assets['js'][] = 'js/app.js';
                $assets['preload'][] = 'js/vendor.js';
                $assets['preload'][] = 'fonts/inter-var.woff2';
                break;
        }
        
        return $assets;
    }
    
    public function getLayoutView(int $level): string
    {
        $layouts = [
            0 => 'portal.layouts.no-js',
            1 => 'portal.layouts.alpine-basic',
            2 => 'portal.layouts.alpine-app',
            3 => 'portal.layouts.hybrid',
            4 => 'portal.layouts.spa',
        ];
        
        return $layouts[$level] ?? $layouts[2];
    }
    
    public function shouldUseServerSideRendering(int $level): bool
    {
        return $level <= 2; // SSR for levels 0, 1, 2
    }
    
    public function shouldPreloadAssets(int $level): bool
    {
        return $level >= 2; // Preload for levels 2, 3, 4
    }
    
    public function getComponentStrategy(int $level, string $component): string
    {
        $strategies = [
            0 => [ // No JS
                'form' => 'server-form',
                'table' => 'server-table',
                'modal' => 'page-redirect',
                'dropdown' => 'select-element',
            ],
            1 => [ // Basic Alpine
                'form' => 'alpine-form-basic',
                'table' => 'alpine-table-basic',
                'modal' => 'alpine-modal-basic',
                'dropdown' => 'alpine-dropdown',
            ],
            2 => [ // Full Alpine
                'form' => 'alpine-form',
                'table' => 'alpine-table',
                'modal' => 'alpine-modal',
                'dropdown' => 'alpine-dropdown',
            ],
            3 => [ // Hybrid
                'form' => 'alpine-form',
                'table' => 'react-table',
                'modal' => 'alpine-modal',
                'dropdown' => 'alpine-dropdown',
            ],
            4 => [ // Full React
                'form' => 'react-form',
                'table' => 'react-table',
                'modal' => 'react-modal',
                'dropdown' => 'react-dropdown',
            ],
        ];
        
        return $strategies[$level][$component] ?? $component;
    }
    
    public function getCacheStrategy(int $level): array
    {
        return [
            'ttl' => match($level) {
                0 => 3600,      // 1 hour for no-JS
                1, 2 => 1800,   // 30 min for Alpine
                3, 4 => 300,    // 5 min for React
                default => 600,
            },
            'tags' => match($level) {
                0 => ['static', 'no-js'],
                1, 2 => ['alpine', 'enhanced'],
                3 => ['hybrid', 'dynamic'],
                4 => ['spa', 'dynamic'],
                default => ['default'],
            },
        ];
    }
    
    public function getPerformanceHints(int $level): array
    {
        return [
            'lazy_load_images' => $level <= 2,
            'defer_non_critical_css' => $level >= 1,
            'use_web_workers' => $level >= 3,
            'prefetch_routes' => $level >= 4,
            'use_service_worker' => $level >= 2,
            'inline_critical_css' => $level <= 1,
        ];
    }
}