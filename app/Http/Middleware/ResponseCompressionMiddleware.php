<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResponseCompressionMiddleware
{
    /**
     * Minimum size for compression (in bytes)
     */
    protected int $minSize = 1024; // 1KB
    
    /**
     * Compression level (1-9)
     */
    protected int $compressionLevel = 6;
    
    /**
     * Content types that should be compressed
     */
    protected array $compressibleTypes = [
        'text/html',
        'text/css',
        'text/xml',
        'text/plain',
        'application/json',
        'application/javascript',
        'application/xml',
        'application/rss+xml',
        'application/atom+xml',
        'application/font-woff',
        'image/svg+xml'
    ];
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Skip if already encoded
        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }
        
        // Check if client accepts gzip
        if (!$this->acceptsGzip($request)) {
            return $response;
        }
        
        // Check if content type is compressible
        if (!$this->isCompressible($response)) {
            return $response;
        }
        
        // Check content size
        $content = $response->getContent();
        if (strlen($content) < $this->minSize) {
            return $response;
        }
        
        // Compress content
        $compressed = gzencode($content, $this->compressionLevel);
        
        if ($compressed === false) {
            return $response;
        }
        
        // Update response
        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', $this->getVaryHeader($response));
        $response->headers->set('Content-Length', strlen($compressed));
        
        // Add cache headers for static content
        $this->addCacheHeaders($request, $response);
        
        // Add ETag for conditional requests
        $this->addETag($response);
        
        return $response;
    }
    
    /**
     * Check if client accepts gzip encoding
     */
    protected function acceptsGzip(Request $request): bool
    {
        $acceptEncoding = $request->header('Accept-Encoding', '');
        return str_contains($acceptEncoding, 'gzip');
    }
    
    /**
     * Check if response content type is compressible
     */
    protected function isCompressible($response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        
        foreach ($this->compressibleTypes as $type) {
            if (str_starts_with($contentType, $type)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get appropriate Vary header value
     */
    protected function getVaryHeader($response): string
    {
        $vary = $response->headers->get('Vary', '');
        $varyHeaders = array_filter(array_map('trim', explode(',', $vary)));
        
        if (!in_array('Accept-Encoding', $varyHeaders)) {
            $varyHeaders[] = 'Accept-Encoding';
        }
        
        return implode(', ', $varyHeaders);
    }
    
    /**
     * Add cache headers for cacheable content
     */
    protected function addCacheHeaders(Request $request, $response): void
    {
        // Skip if already has cache headers
        if ($response->headers->has('Cache-Control')) {
            return;
        }
        
        // API responses - short cache
        if ($request->is('api/*')) {
            if ($request->isMethod('GET')) {
                $response->headers->set('Cache-Control', 'public, max-age=60');
            } else {
                $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            }
            return;
        }
        
        // Static assets - long cache
        if ($this->isStaticAsset($request)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            return;
        }
        
        // HTML pages - no cache by default
        if ($this->isHtmlResponse($response)) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
    }
    
    /**
     * Add ETag header for conditional requests
     */
    protected function addETag($response): void
    {
        if ($response->headers->has('ETag')) {
            return;
        }
        
        $content = $response->getContent();
        $etag = '"' . md5($content) . '"';
        
        $response->headers->set('ETag', $etag);
    }
    
    /**
     * Check if request is for static asset
     */
    protected function isStaticAsset(Request $request): bool
    {
        $path = $request->path();
        $staticExtensions = ['js', 'css', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
        
        foreach ($staticExtensions as $ext) {
            if (str_ends_with($path, '.' . $ext)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if response is HTML
     */
    protected function isHtmlResponse($response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_starts_with($contentType, 'text/html');
    }
}