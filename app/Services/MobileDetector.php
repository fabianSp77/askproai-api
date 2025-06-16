<?php

namespace App\Services;

use Illuminate\Http\Request;

class MobileDetector
{
    protected array $mobileHeaders = [
        'HTTP_ACCEPT' => [
            'application/x-obml2d',
            'application/vnd.rim.html',
            'text/vnd.wap.wml',
            'application/vnd.wap.xhtml+xml'
        ],
        'HTTP_X_WAP_PROFILE' => null,
        'HTTP_X_WAP_CLIENTID' => null,
        'HTTP_WAP_CONNECTION' => null,
        'HTTP_PROFILE' => null,
        'HTTP_X_OPERAMINI_PHONE_UA' => null,
        'HTTP_X_NOKIA_GATEWAY_ID' => null,
        'HTTP_X_ORANGE_ID' => null,
        'HTTP_X_VODAFONE_3GPDPCONTEXT' => null,
        'HTTP_X_HUAWEI_USERID' => null,
        'HTTP_UA_OS' => null,
        'HTTP_X_MOBILE_GATEWAY' => null,
        'HTTP_X_ATT_DEVICEID' => null,
        'HTTP_UA_CPU' => ['ARM'],
    ];

    protected array $mobileUserAgents = [
        'android', 'webos', 'iphone', 'ipad', 'ipod', 'blackberry', 'windows phone',
        'opera mini', 'opera mobi', 'iemobile', 'mobile safari', 'kindle', 'silk',
        'fennec', 'nokia', 'samsung', 'lg', 'htc', 'motorola', 'tablet', 'rv:11.0',
        'playstation', 'nintendo', 'googletv', 'appletv', 'smart-tv', 'roku', 'tizen'
    ];

    protected array $tabletUserAgents = [
        'ipad', 'tablet', 'kindle', 'silk', 'playbook', 'nexus 7', 'nexus 10',
        'xoom', 'sm-t', 'gt-p', 'galaxy tab', 'at300', 'a510', 'a511', 'a700',
        'a701', 'w500', 'w700', 'g-tec', 'nook', 'dell streak', 'surface'
    ];

    protected ?Request $request;
    protected ?bool $isMobileCache = null;
    protected ?bool $isTabletCache = null;
    protected ?string $deviceTypeCache = null;

    public function __construct(?Request $request = null)
    {
        $this->request = $request;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;
        $this->clearCache();
        return $this;
    }

    public function isMobile(): bool
    {
        if ($this->isMobileCache !== null) {
            return $this->isMobileCache;
        }

        if (!$this->request) {
            return false;
        }

        // Check mobile headers
        foreach ($this->mobileHeaders as $header => $values) {
            if ($this->request->server($header)) {
                if ($values === null) {
                    $this->isMobileCache = true;
                    return true;
                }
                
                $headerValue = $this->request->server($header);
                foreach ($values as $value) {
                    if (stripos($headerValue, $value) !== false) {
                        $this->isMobileCache = true;
                        return true;
                    }
                }
            }
        }

        // Check user agent
        $userAgent = strtolower($this->request->userAgent() ?? '');
        
        foreach ($this->mobileUserAgents as $mobileAgent) {
            if (stripos($userAgent, $mobileAgent) !== false) {
                $this->isMobileCache = true;
                return true;
            }
        }

        $this->isMobileCache = false;
        return false;
    }

    public function isTablet(): bool
    {
        if ($this->isTabletCache !== null) {
            return $this->isTabletCache;
        }

        if (!$this->request) {
            return false;
        }

        $userAgent = strtolower($this->request->userAgent() ?? '');
        
        foreach ($this->tabletUserAgents as $tabletAgent) {
            if (stripos($userAgent, $tabletAgent) !== false) {
                $this->isTabletCache = true;
                return true;
            }
        }

        // iPad specific check
        if (stripos($userAgent, 'ipad') !== false) {
            $this->isTabletCache = true;
            return true;
        }

        // Android tablet check (no 'mobile' keyword)
        if (stripos($userAgent, 'android') !== false && stripos($userAgent, 'mobile') === false) {
            $this->isTabletCache = true;
            return true;
        }

        $this->isTabletCache = false;
        return false;
    }

    public function isDesktop(): bool
    {
        return !$this->isMobile() && !$this->isTablet();
    }

    public function getDeviceType(): string
    {
        if ($this->deviceTypeCache !== null) {
            return $this->deviceTypeCache;
        }

        if ($this->isTablet()) {
            $this->deviceTypeCache = 'tablet';
        } elseif ($this->isMobile()) {
            $this->deviceTypeCache = 'mobile';
        } else {
            $this->deviceTypeCache = 'desktop';
        }

        return $this->deviceTypeCache;
    }

    public function getViewport(): array
    {
        $type = $this->getDeviceType();
        
        return match($type) {
            'mobile' => ['width' => 375, 'height' => 667],  // iPhone SE size
            'tablet' => ['width' => 768, 'height' => 1024], // iPad size
            default => ['width' => 1920, 'height' => 1080], // Desktop
        };
    }

    public function getUserAgent(): ?string
    {
        return $this->request?->userAgent();
    }

    public function isIOS(): bool
    {
        $userAgent = strtolower($this->getUserAgent() ?? '');
        return stripos($userAgent, 'iphone') !== false || 
               stripos($userAgent, 'ipad') !== false || 
               stripos($userAgent, 'ipod') !== false;
    }

    public function isAndroid(): bool
    {
        $userAgent = strtolower($this->getUserAgent() ?? '');
        return stripos($userAgent, 'android') !== false;
    }

    public function isTouchDevice(): bool
    {
        return $this->isMobile() || $this->isTablet();
    }

    public function getOS(): string
    {
        $userAgent = $this->getUserAgent() ?? '';
        
        if ($this->isIOS()) return 'iOS';
        if ($this->isAndroid()) return 'Android';
        if (stripos($userAgent, 'Windows Phone') !== false) return 'Windows Phone';
        if (stripos($userAgent, 'Windows') !== false) return 'Windows';
        if (stripos($userAgent, 'Mac OS') !== false) return 'macOS';
        if (stripos($userAgent, 'Linux') !== false) return 'Linux';
        
        return 'Unknown';
    }

    public function getBrowser(): string
    {
        $userAgent = $this->getUserAgent() ?? '';
        
        if (stripos($userAgent, 'Chrome') !== false && stripos($userAgent, 'Edge') === false) return 'Chrome';
        if (stripos($userAgent, 'Safari') !== false && stripos($userAgent, 'Chrome') === false) return 'Safari';
        if (stripos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (stripos($userAgent, 'Edge') !== false) return 'Edge';
        if (stripos($userAgent, 'Opera') !== false || stripos($userAgent, 'OPR') !== false) return 'Opera';
        if (stripos($userAgent, 'Trident') !== false || stripos($userAgent, 'MSIE') !== false) return 'Internet Explorer';
        
        return 'Unknown';
    }

    public function supportsWebP(): bool
    {
        return $this->request && str_contains($this->request->header('Accept', ''), 'image/webp');
    }

    public function supportsPWA(): bool
    {
        // Check if browser supports PWA features
        $browser = $this->getBrowser();
        $os = $this->getOS();
        
        // Most modern browsers support PWA
        $supportedBrowsers = ['Chrome', 'Edge', 'Safari', 'Firefox', 'Opera'];
        
        if (!in_array($browser, $supportedBrowsers)) {
            return false;
        }
        
        // iOS Safari has limited PWA support
        if ($os === 'iOS' && $browser === 'Safari') {
            return true; // Limited support
        }
        
        return true;
    }

    public function getDevicePixelRatio(): float
    {
        // Default device pixel ratios
        if ($this->isIOS()) {
            $userAgent = strtolower($this->getUserAgent() ?? '');
            if (stripos($userAgent, 'iphone') !== false) {
                // Modern iPhones have 2x or 3x displays
                return 3.0;
            }
            if (stripos($userAgent, 'ipad') !== false) {
                return 2.0;
            }
        }
        
        if ($this->isAndroid()) {
            // Most modern Android devices have high DPI
            return 2.0;
        }
        
        return 1.0;
    }

    protected function clearCache(): void
    {
        $this->isMobileCache = null;
        $this->isTabletCache = null;
        $this->deviceTypeCache = null;
    }
}