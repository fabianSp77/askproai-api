<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MatthiasMullie\Minify;

class AssetOptimizationService
{
    protected $publicPath;
    protected $storagePath;
    protected $cachePath;
    protected $manifestPath;
    protected $cdnEnabled;
    protected $cdnUrl;

    public function __construct()
    {
        $this->publicPath = public_path();
        $this->storagePath = storage_path('app/public');
        $this->cachePath = storage_path('framework/cache/assets');
        $this->manifestPath = public_path('build/manifest.json');
        $this->cdnEnabled = config('cdn.enabled', false);
        $this->cdnUrl = config('cdn.url', '');

        // Ensure cache directory exists
        if (!File::exists($this->cachePath)) {
            File::makeDirectory($this->cachePath, 0755, true);
        }
    }

    /**
     * Optimize all assets in the public directory.
     */
    public function optimizeAll(): array
    {
        $results = [
            'css' => $this->optimizeCssFiles(),
            'js' => $this->optimizeJsFiles(),
            'images' => $this->optimizeImages(),
            'total_saved' => 0,
            'errors' => [],
        ];

        // Calculate total savings
        foreach (['css', 'js', 'images'] as $type) {
            if (isset($results[$type]['total_saved'])) {
                $results['total_saved'] += $results[$type]['total_saved'];
            }
        }

        // Update asset manifest
        $this->updateAssetManifest($results);

        // Clear view cache to use new assets
        \Artisan::call('view:clear');

        return $results;
    }

    /**
     * Optimize CSS files.
     */
    public function optimizeCssFiles(): array
    {
        $cssPath = public_path('css');
        $results = [
            'files' => [],
            'total_saved' => 0,
            'errors' => [],
        ];

        if (!File::exists($cssPath)) {
            return $results;
        }

        $files = File::glob($cssPath . '/**/*.css');

        foreach ($files as $file) {
            // Skip already minified files
            if (str_contains($file, '.min.css')) {
                continue;
            }

            try {
                $result = $this->optimizeCssFile($file);
                $results['files'][] = $result;
                $results['total_saved'] += $result['saved'];
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'file' => $file,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Optimize a single CSS file.
     */
    public function optimizeCssFile(string $path): array
    {
        $originalSize = filesize($path);
        $content = File::get($path);

        // Create minifier instance
        $minifier = new Minify\CSS($content);

        // Add optimizations
        $optimized = $minifier->minify();

        // Remove unused CSS (basic implementation)
        $optimized = $this->removeUnusedCss($optimized);

        // Optimize URL references
        $optimized = $this->optimizeCssUrls($optimized, $path);

        // Generate unique filename
        $hash = substr(md5($optimized), 0, 8);
        $info = pathinfo($path);
        $optimizedPath = $info['dirname'] . '/' . $info['filename'] . '.' . $hash . '.min.css';

        // Save optimized file
        File::put($optimizedPath, $optimized);

        $optimizedSize = strlen($optimized);
        $saved = $originalSize - $optimizedSize;
        $reduction = round(($saved / $originalSize) * 100, 2);

        // Generate source map
        $this->generateSourceMap($path, $optimizedPath, 'css');

        return [
            'original' => $path,
            'optimized' => $optimizedPath,
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'saved' => $saved,
            'reduction' => $reduction,
            'hash' => $hash,
        ];
    }

    /**
     * Optimize JavaScript files.
     */
    public function optimizeJsFiles(): array
    {
        $jsPath = public_path('js');
        $results = [
            'files' => [],
            'total_saved' => 0,
            'errors' => [],
        ];

        if (!File::exists($jsPath)) {
            return $results;
        }

        $files = File::glob($jsPath . '/**/*.js');

        foreach ($files as $file) {
            // Skip already minified files
            if (str_contains($file, '.min.js')) {
                continue;
            }

            try {
                $result = $this->optimizeJsFile($file);
                $results['files'][] = $result;
                $results['total_saved'] += $result['saved'];
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'file' => $file,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Optimize a single JavaScript file.
     */
    public function optimizeJsFile(string $path): array
    {
        $originalSize = filesize($path);
        $content = File::get($path);

        // Create minifier instance
        $minifier = new Minify\JS($content);

        // Minify
        $optimized = $minifier->minify();

        // Additional optimizations
        $optimized = $this->optimizeJsCode($optimized);

        // Generate unique filename
        $hash = substr(md5($optimized), 0, 8);
        $info = pathinfo($path);
        $optimizedPath = $info['dirname'] . '/' . $info['filename'] . '.' . $hash . '.min.js';

        // Save optimized file
        File::put($optimizedPath, $optimized);

        $optimizedSize = strlen($optimized);
        $saved = $originalSize - $optimizedSize;
        $reduction = round(($saved / $originalSize) * 100, 2);

        // Generate source map
        $this->generateSourceMap($path, $optimizedPath, 'js');

        // Check if file should be split
        if ($optimizedSize > 100 * 1024) { // 100KB
            $this->suggestCodeSplitting($path, $optimizedSize);
        }

        return [
            'original' => $path,
            'optimized' => $optimizedPath,
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'saved' => $saved,
            'reduction' => $reduction,
            'hash' => $hash,
        ];
    }

    /**
     * Optimize images.
     */
    public function optimizeImages(): array
    {
        $results = [
            'files' => [],
            'total_saved' => 0,
            'errors' => [],
        ];

        $imagePaths = [
            public_path('images'),
            public_path('img'),
            $this->storagePath,
        ];

        foreach ($imagePaths as $path) {
            if (!File::exists($path)) {
                continue;
            }

            $images = File::glob($path . '/**/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

            foreach ($images as $image) {
                try {
                    $result = $this->optimizeImage($image);
                    if ($result['saved'] > 0) {
                        $results['files'][] = $result;
                        $results['total_saved'] += $result['saved'];
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'file' => $image,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Optimize a single image.
     */
    public function optimizeImage(string $path): array
    {
        $originalSize = filesize($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Skip if already optimized
        if (str_contains($path, '.optimized.')) {
            return [
                'original' => $path,
                'optimized' => $path,
                'original_size' => $originalSize,
                'optimized_size' => $originalSize,
                'saved' => 0,
                'reduction' => 0,
            ];
        }

        // Load image
        $image = null;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($path);
                break;
            case 'png':
                $image = imagecreatefrompng($path);
                break;
            case 'gif':
                $image = imagecreatefromgif($path);
                break;
            case 'webp':
                $image = imagecreatefromwebp($path);
                break;
        }

        if (!$image) {
            throw new \Exception('Failed to load image');
        }

        // Get dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Resize if too large
        $maxWidth = 2048;
        $maxHeight = 2048;
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($extension === 'png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // Generate optimized filename
        $info = pathinfo($path);
        $optimizedPath = $info['dirname'] . '/' . $info['filename'] . '.optimized.' . $extension;

        // Save optimized image
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($image, $optimizedPath, 85); // 85% quality
                break;
            case 'png':
                imagepng($image, $optimizedPath, 7); // Compression level 7
                break;
            case 'gif':
                imagegif($image, $optimizedPath);
                break;
            case 'webp':
                imagewebp($image, $optimizedPath, 85);
                break;
        }

        imagedestroy($image);

        // Generate WebP version
        if ($extension !== 'webp') {
            $this->generateWebpVersion($optimizedPath);
        }

        $optimizedSize = filesize($optimizedPath);
        $saved = $originalSize - $optimizedSize;
        $reduction = round(($saved / $originalSize) * 100, 2);

        // Generate responsive images
        $this->generateResponsiveImages($optimizedPath);

        return [
            'original' => $path,
            'optimized' => $optimizedPath,
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'saved' => $saved > 0 ? $saved : 0,
            'reduction' => $reduction > 0 ? $reduction : 0,
        ];
    }

    /**
     * Bundle multiple CSS files.
     */
    public function bundleCss(array $files, string $outputName): string
    {
        $bundled = '';
        $sourceMaps = [];

        foreach ($files as $file) {
            if (File::exists($file)) {
                $content = File::get($file);
                $bundled .= "\n/* Source: {$file} */\n" . $content;
                $sourceMaps[] = $file;
            }
        }

        // Minify bundled content
        $minifier = new Minify\CSS($bundled);
        $optimized = $minifier->minify();

        // Generate hash for cache busting
        $hash = substr(md5($optimized), 0, 8);
        $outputPath = public_path("css/{$outputName}.{$hash}.min.css");

        // Save bundled file
        File::put($outputPath, $optimized);

        // Update manifest
        $this->updateManifestEntry("css/{$outputName}.css", "css/{$outputName}.{$hash}.min.css");

        return $outputPath;
    }

    /**
     * Bundle multiple JavaScript files.
     */
    public function bundleJs(array $files, string $outputName): string
    {
        $bundled = '';
        $sourceMaps = [];

        // Wrap in IIFE to avoid global scope pollution
        $bundled .= "(function() {\n'use strict';\n";

        foreach ($files as $file) {
            if (File::exists($file)) {
                $content = File::get($file);
                $bundled .= "\n/* Source: {$file} */\n" . $content;
                $sourceMaps[] = $file;
            }
        }

        $bundled .= "\n})();";

        // Minify bundled content
        $minifier = new Minify\JS($bundled);
        $optimized = $minifier->minify();

        // Generate hash for cache busting
        $hash = substr(md5($optimized), 0, 8);
        $outputPath = public_path("js/{$outputName}.{$hash}.min.js");

        // Save bundled file
        File::put($outputPath, $optimized);

        // Update manifest
        $this->updateManifestEntry("js/{$outputName}.js", "js/{$outputName}.{$hash}.min.js");

        return $outputPath;
    }

    /**
     * Generate critical CSS for above-the-fold content.
     */
    public function generateCriticalCss(string $url): string
    {
        // This would typically use a headless browser to determine critical CSS
        // For now, we'll extract basic critical styles
        
        $criticalCss = '';
        
        // Add reset and base styles
        $criticalCss .= $this->getBaseCriticalCss();
        
        // Add layout styles
        $criticalCss .= $this->getLayoutCriticalCss();
        
        // Add typography styles
        $criticalCss .= $this->getTypographyCriticalCss();
        
        // Minify
        $minifier = new Minify\CSS($criticalCss);
        $optimized = $minifier->minify();
        
        // Cache the critical CSS
        $cacheKey = 'critical_css_' . md5($url);
        Cache::put($cacheKey, $optimized, 3600); // Cache for 1 hour
        
        return $optimized;
    }

    /**
     * Preload important assets.
     */
    public function generatePreloadTags(): array
    {
        $preloads = [];
        
        // Get manifest
        $manifest = $this->getAssetManifest();
        
        // Preload critical CSS
        if (isset($manifest['css/app.css'])) {
            $preloads[] = [
                'href' => $this->assetUrl($manifest['css/app.css']),
                'as' => 'style',
                'type' => 'text/css',
            ];
        }
        
        // Preload critical JS
        if (isset($manifest['js/app.js'])) {
            $preloads[] = [
                'href' => $this->assetUrl($manifest['js/app.js']),
                'as' => 'script',
                'type' => 'text/javascript',
            ];
        }
        
        // Preload fonts
        $fonts = File::glob(public_path('fonts/*.{woff,woff2}'), GLOB_BRACE);
        foreach ($fonts as $font) {
            $preloads[] = [
                'href' => $this->assetUrl(str_replace(public_path(), '', $font)),
                'as' => 'font',
                'type' => 'font/' . pathinfo($font, PATHINFO_EXTENSION),
                'crossorigin' => 'anonymous',
            ];
        }
        
        return $preloads;
    }

    /**
     * Helper methods
     */
    protected function removeUnusedCss(string $css): string
    {
        // Basic implementation - remove common unused patterns
        // In production, would use PurgeCSS or similar
        
        $patterns = [
            // Remove empty rules
            '/[^{}]+\{\s*\}/' => '',
            // Remove comments
            '/\/\*[^*]*\*+(?:[^/*][^*]*\*+)*\//' => '',
            // Remove extra whitespace
            '/\s+/' => ' ',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $css = preg_replace($pattern, $replacement, $css);
        }
        
        return trim($css);
    }

    protected function optimizeCssUrls(string $css, string $filePath): string
    {
        // Convert relative URLs to absolute or CDN URLs
        return preg_replace_callback('/url\([\'"]?([^\'")]+)[\'"]?\)/', function($matches) use ($filePath) {
            $url = $matches[1];
            
            // Skip data URLs and absolute URLs
            if (strpos($url, 'data:') === 0 || strpos($url, 'http') === 0) {
                return $matches[0];
            }
            
            // Convert to absolute path
            $absolutePath = dirname($filePath) . '/' . $url;
            $publicPath = str_replace(public_path(), '', $absolutePath);
            
            // Use CDN if enabled
            $finalUrl = $this->assetUrl($publicPath);
            
            return "url('{$finalUrl}')";
        }, $css);
    }

    protected function optimizeJsCode(string $js): string
    {
        // Additional JS optimizations beyond minification
        
        // Remove console.log in production
        if (app()->environment('production')) {
            $js = preg_replace('/console\.(log|debug|info|warn|error)\([^)]*\);?/', '', $js);
        }
        
        // Remove debugger statements
        $js = preg_replace('/debugger;?/', '', $js);
        
        return $js;
    }

    protected function generateSourceMap(string $original, string $optimized, string $type): void
    {
        // Basic source map generation
        $map = [
            'version' => 3,
            'file' => basename($optimized),
            'sources' => [basename($original)],
            'mappings' => 'AAAA', // Simplified mapping
        ];
        
        $mapPath = $optimized . '.map';
        File::put($mapPath, json_encode($map));
        
        // Add source map reference to optimized file
        $content = File::get($optimized);
        if ($type === 'css') {
            $content .= "\n/*# sourceMappingURL=" . basename($mapPath) . " */";
        } else {
            $content .= "\n//# sourceMappingURL=" . basename($mapPath);
        }
        File::put($optimized, $content);
    }

    protected function generateWebpVersion(string $imagePath): void
    {
        $info = pathinfo($imagePath);
        $webpPath = $info['dirname'] . '/' . $info['filename'] . '.webp';
        
        // Skip if WebP already exists
        if (File::exists($webpPath)) {
            return;
        }
        
        $extension = strtolower($info['extension']);
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'png':
                $image = imagecreatefrompng($imagePath);
                break;
            default:
                return;
        }
        
        if ($image) {
            imagewebp($image, $webpPath, 85);
            imagedestroy($image);
        }
    }

    protected function generateResponsiveImages(string $imagePath): void
    {
        $sizes = [
            'sm' => 640,
            'md' => 768,
            'lg' => 1024,
            'xl' => 1280,
        ];
        
        $info = pathinfo($imagePath);
        
        foreach ($sizes as $name => $width) {
            $responsivePath = $info['dirname'] . '/' . $info['filename'] . '-' . $name . '.' . $info['extension'];
            
            // Skip if already exists
            if (File::exists($responsivePath)) {
                continue;
            }
            
            // Create responsive version
            $this->resizeImage($imagePath, $responsivePath, $width);
        }
    }

    protected function resizeImage(string $source, string $destination, int $maxWidth): void
    {
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        
        // Load image
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'png':
                $image = imagecreatefrompng($source);
                break;
            case 'webp':
                $image = imagecreatefromwebp($source);
                break;
            default:
                return;
        }
        
        if (!$image) {
            return;
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Skip if already smaller
        if ($width <= $maxWidth) {
            imagedestroy($image);
            return;
        }
        
        // Calculate new dimensions
        $ratio = $maxWidth / $width;
        $newWidth = $maxWidth;
        $newHeight = round($height * $ratio);
        
        // Create resized image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($extension === 'png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save resized image
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($resized, $destination, 90);
                break;
            case 'png':
                imagepng($resized, $destination, 7);
                break;
            case 'webp':
                imagewebp($resized, $destination, 90);
                break;
        }
        
        imagedestroy($image);
        imagedestroy($resized);
    }

    protected function suggestCodeSplitting(string $file, int $size): void
    {
        Log::info('Large JavaScript file detected', [
            'file' => $file,
            'size' => round($size / 1024, 2) . 'KB',
            'recommendation' => 'Consider splitting this file into smaller chunks for better loading performance',
        ]);
    }

    protected function updateAssetManifest(array $results): void
    {
        $manifest = $this->getAssetManifest();
        
        // Update CSS entries
        foreach ($results['css']['files'] ?? [] as $file) {
            $original = str_replace(public_path() . '/', '', $file['original']);
            $optimized = str_replace(public_path() . '/', '', $file['optimized']);
            $manifest[$original] = $optimized;
        }
        
        // Update JS entries
        foreach ($results['js']['files'] ?? [] as $file) {
            $original = str_replace(public_path() . '/', '', $file['original']);
            $optimized = str_replace(public_path() . '/', '', $file['optimized']);
            $manifest[$original] = $optimized;
        }
        
        // Save manifest
        File::put($this->manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    protected function getAssetManifest(): array
    {
        if (File::exists($this->manifestPath)) {
            return json_decode(File::get($this->manifestPath), true) ?? [];
        }
        return [];
    }

    protected function updateManifestEntry(string $original, string $optimized): void
    {
        $manifest = $this->getAssetManifest();
        $manifest[$original] = $optimized;
        File::put($this->manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    protected function assetUrl(string $path): string
    {
        // Remove leading slash
        $path = ltrim($path, '/');
        
        // Use CDN if enabled
        if ($this->cdnEnabled && $this->cdnUrl) {
            return rtrim($this->cdnUrl, '/') . '/' . $path;
        }
        
        return asset($path);
    }

    protected function getBaseCriticalCss(): string
    {
        return '
            * { box-sizing: border-box; }
            body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            img { max-width: 100%; height: auto; }
        ';
    }

    protected function getLayoutCriticalCss(): string
    {
        return '
            .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
            .row { display: flex; flex-wrap: wrap; margin: 0 -15px; }
            .col { flex: 1; padding: 0 15px; }
        ';
    }

    protected function getTypographyCriticalCss(): string
    {
        return '
            h1, h2, h3, h4, h5, h6 { margin-top: 0; line-height: 1.2; }
            p { line-height: 1.6; }
            a { color: #3B82F6; text-decoration: none; }
            a:hover { text-decoration: underline; }
        ';
    }
}