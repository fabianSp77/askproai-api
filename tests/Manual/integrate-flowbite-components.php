#!/usr/bin/env php
<?php

/**
 * Flowbite Pro Components Integration Script
 * Processes and integrates all Flowbite Pro components into Laravel
 */

class FlowbiteIntegrator {
    private $basePath = '/var/www/api-gateway/resources/flowbite-pro';
    private $targetPath = '/var/www/api-gateway/resources/views/components/flowbite-pro';
    private $stats = [
        'total_files' => 0,
        'processed' => 0,
        'converted' => 0,
        'copied' => 0,
        'errors' => 0
    ];
    
    private $componentMap = [];
    
    public function run() {
        echo "🚀 Starting Flowbite Pro Integration\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        // Create target directory
        if (!is_dir($this->targetPath)) {
            mkdir($this->targetPath, 0755, true);
            echo "✅ Created target directory: {$this->targetPath}\n";
        }
        
        // Process each package
        $this->processAdminDashboard();
        $this->processReactBlocks();
        $this->generateComponentIndex();
        
        $this->printStats();
    }
    
    private function processAdminDashboard() {
        echo "📦 Processing Admin Dashboard\n";
        $dashboardPath = "{$this->basePath}/flowbite-pro/flowbite-admin-dashboard-v2.2.0";
        
        if (is_dir($dashboardPath)) {
            $contentPath = "$dashboardPath/content";
            if (is_dir($contentPath)) {
                $this->processDirectory($contentPath, 'admin-dashboard');
            }
            
            // Process layouts
            $layoutsPath = "$dashboardPath/layouts";
            if (is_dir($layoutsPath)) {
                $this->processLayouts($layoutsPath);
            }
        }
    }
    
    private function processReactBlocks() {
        echo "\n📦 Processing React Blocks\n";
        $blocksPath = "{$this->basePath}/flowbite-react-blocks-1.8.0-beta";
        
        if (is_dir($blocksPath)) {
            $componentsPath = "$blocksPath/components";
            if (is_dir($componentsPath)) {
                $this->processReactComponents($componentsPath);
            }
        }
    }
    
    private function processDirectory($dir, $category) {
        $files = glob("$dir/*");
        foreach ($files as $file) {
            if (is_dir($file)) {
                $dirname = basename($file);
                $this->processDirectory($file, "$category/$dirname");
            } elseif (is_file($file)) {
                $this->processFile($file, $category);
            }
        }
    }
    
    private function processFile($file, $category) {
        $this->stats['total_files']++;
        $filename = basename($file);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Create category directory
        $categoryPath = "{$this->targetPath}/$category";
        if (!is_dir($categoryPath)) {
            mkdir($categoryPath, 0755, true);
        }
        
        if ($ext === 'html') {
            // Convert HTML to Blade
            $content = file_get_contents($file);
            $bladeContent = $this->htmlToBlade($content);
            
            $bladeName = str_replace('.html', '.blade.php', $filename);
            $targetFile = "$categoryPath/$bladeName";
            
            file_put_contents($targetFile, $bladeContent);
            $this->stats['converted']++;
            
            // Add to component map
            $componentName = str_replace('.blade.php', '', $bladeName);
            $this->componentMap[$category][] = $componentName;
            
            echo "   ✅ Converted: $category/$bladeName\n";
        } elseif (in_array($ext, ['css', 'js'])) {
            // Copy static assets
            copy($file, "$categoryPath/$filename");
            $this->stats['copied']++;
        }
        
        $this->stats['processed']++;
    }
    
    private function processLayouts($layoutsPath) {
        echo "\n📐 Processing Layouts\n";
        $layoutFiles = glob("$layoutsPath/**/*.html");
        
        foreach ($layoutFiles as $file) {
            $this->processFile($file, 'layouts');
        }
    }
    
    private function processReactComponents($componentsPath) {
        $components = glob("$componentsPath/*", GLOB_ONLYDIR);
        
        foreach ($components as $componentDir) {
            $componentName = basename($componentDir);
            $indexFile = "$componentDir/index.tsx";
            
            if (file_exists($indexFile)) {
                $this->convertReactToBlade($indexFile, $componentName);
            }
        }
    }
    
    private function convertReactToBlade($file, $componentName) {
        $this->stats['total_files']++;
        
        $content = file_get_contents($file);
        $bladeContent = $this->reactToBlade($content);
        
        $categoryPath = "{$this->targetPath}/react-blocks";
        if (!is_dir($categoryPath)) {
            mkdir($categoryPath, 0755, true);
        }
        
        $bladeName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $componentName)) . '.blade.php';
        $targetFile = "$categoryPath/$bladeName";
        
        file_put_contents($targetFile, $bladeContent);
        $this->stats['converted']++;
        $this->stats['processed']++;
        
        $this->componentMap['react-blocks'][] = str_replace('.blade.php', '', $bladeName);
        
        echo "   ✅ Converted React: react-blocks/$bladeName\n";
    }
    
    private function htmlToBlade($html) {
        // Add Blade directive
        $blade = "@php\n\$attributes = \$attributes ?? new \\Illuminate\\View\\ComponentAttributeBag();\n@endphp\n\n";
        $blade .= $html;
        
        // Convert common patterns
        $blade = preg_replace('/\{\{([^}]+)\}\}/', '@{{ $1 }}', $blade);
        $blade = str_replace('class="', 'class="{{ $attributes->get(\'class\', \'\') }} ', $blade);
        
        return $blade;
    }
    
    private function reactToBlade($react) {
        $blade = "@php\n\$attributes = \$attributes ?? new \\Illuminate\\View\\ComponentAttributeBag();\n@endphp\n\n";
        
        // Extract JSX from React component
        if (preg_match('/return\s*\(([\s\S]*?)\);/m', $react, $matches)) {
            $jsx = $matches[1];
        } elseif (preg_match('/return\s+([\s\S]*?)^}/m', $react, $matches)) {
            $jsx = $matches[1];
        } else {
            $jsx = $react;
        }
        
        // Convert JSX to Blade
        $blade .= $this->jsxToBlade($jsx);
        
        return $blade;
    }
    
    private function jsxToBlade($jsx) {
        // Remove TypeScript/JavaScript code
        $blade = preg_replace('/^import.*$/m', '', $jsx);
        $blade = preg_replace('/^export.*$/m', '', $blade);
        $blade = preg_replace('/^const.*$/m', '', $blade);
        
        // Convert className to class
        $blade = preg_replace('/className=/i', 'class=', $blade);
        
        // Convert JSX expressions to Blade
        $blade = preg_replace('/\{([^}]+)\}/', '{{ $1 }}', $blade);
        
        // Convert onClick to Alpine.js
        $blade = preg_replace('/onClick=\{([^}]+)\}/', '@click="$1"', $blade);
        
        // Clean up
        $blade = trim($blade);
        
        return $blade;
    }
    
    private function generateComponentIndex() {
        echo "\n📚 Generating Component Index\n";
        
        $indexContent = "# Flowbite Pro Components Index\n\n";
        $indexContent .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($this->componentMap as $category => $components) {
            $indexContent .= "## " . ucfirst(str_replace('-', ' ', $category)) . "\n\n";
            foreach ($components as $component) {
                $indexContent .= "- `$component`\n";
            }
            $indexContent .= "\n";
        }
        
        file_put_contents("{$this->targetPath}/INDEX.md", $indexContent);
        echo "   ✅ Created component index\n";
    }
    
    private function printStats() {
        echo "\n" . str_repeat("=", 52) . "\n";
        echo "📊 Integration Statistics:\n";
        echo "   Total Files: {$this->stats['total_files']}\n";
        echo "   Processed: {$this->stats['processed']}\n";
        echo "   Converted: {$this->stats['converted']}\n";
        echo "   Copied: {$this->stats['copied']}\n";
        echo "   Errors: {$this->stats['errors']}\n";
        echo "\n✅ Integration complete!\n";
        echo "📁 Components available at: {$this->targetPath}\n";
    }
}

// Run the integrator
$integrator = new FlowbiteIntegrator();
$integrator->run();