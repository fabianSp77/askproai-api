<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MobileDetector as MobileDetectorService;

class MobileDetector
{
    protected MobileDetectorService $detector;

    public function __construct(MobileDetectorService $detector)
    {
        $this->detector = $detector;
    }

    public function handle(Request $request, Closure $next)
    {
        // Set the request in the detector
        $this->detector->setRequest($request);
        
        // Add device information to request attributes
        $request->attributes->set('device_type', $this->detector->getDeviceType());
        $request->attributes->set('is_mobile', $this->detector->isMobile());
        $request->attributes->set('is_tablet', $this->detector->isTablet());
        $request->attributes->set('is_touch', $this->detector->isTouchDevice());
        $request->attributes->set('device_os', $this->detector->getOS());
        $request->attributes->set('device_browser', $this->detector->getBrowser());
        
        // Add viewport configuration
        $request->attributes->set('viewport', $this->detector->getViewport());
        
        // Share device info with all views
        view()->share([
            'deviceType' => $this->detector->getDeviceType(),
            'isMobile' => $this->detector->isMobile(),
            'isTablet' => $this->detector->isTablet(),
            'isTouch' => $this->detector->isTouchDevice(),
            'deviceOS' => $this->detector->getOS(),
            'deviceBrowser' => $this->detector->getBrowser(),
            'supportsPWA' => $this->detector->supportsPWA(),
            'supportsWebP' => $this->detector->supportsWebP(),
        ]);
        
        $response = $next($request);
        
        // Add device-specific headers
        if ($this->detector->isMobile()) {
            $response->headers->set('X-Device-Type', 'mobile');
            
            // Add viewport meta tag for mobile if response is HTML
            if ($response->headers->get('Content-Type', 'text/html') === 'text/html') {
                $content = $response->getContent();
                if (stripos($content, '<head>') !== false && stripos($content, 'viewport') === false) {
                    $viewportMeta = '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">';
                    $content = str_replace('<head>', '<head>' . $viewportMeta, $content);
                    $response->setContent($content);
                }
            }
        }
        
        return $response;
    }
}