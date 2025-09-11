#!/usr/bin/env php
<?php

/**
 * Flowbite React to Laravel Blade Converter
 * ==========================================
 * Converts React/TypeScript components to Laravel Blade templates
 */

class ReactToBladeConverter {
    
    private $sourceDir;
    private $targetDir;
    private $components = [];
    private $converted = 0;
    
    public function __construct() {
        $this->sourceDir = '/var/www/api-gateway/resources/flowbite-pro/flowbite-react-blocks-1.8.0-beta';
        $this->targetDir = '/var/www/api-gateway/resources/views/flowbite';
        
        if (!is_dir($this->targetDir)) {
            mkdir($this->targetDir, 0755, true);
        }
    }
    
    public function convert() {
        echo "🔄 Starting React to Blade conversion...\n\n";
        
        // Convert application UI components
        $this->convertCategory('application-ui', [
            'advanced-tables' => 'tables',
            'dashboard-navbars' => 'navigation',
            'side-navigation' => 'navigation',
            'create-forms' => 'forms',
            'update-forms' => 'forms',
            'create-modals' => 'modals',
            'update-modals' => 'modals',
            'delete-confirm' => 'modals',
            'crud-layouts' => 'layouts'
        ]);
        
        // Convert marketing UI components
        $this->convertCategory('marketing-ui', [
            'heroes' => 'sections',
            'features' => 'sections',
            'pricing' => 'sections'
        ]);
        
        // Convert e-commerce components
        $this->convertCategory('ecommerce-ui', [
            'product-cards' => 'cards',
            'shopping-carts' => 'carts'
        ]);
        
        // Create index file
        $this->createIndexFile();
        
        // Create Filament integration
        $this->createFilamentIntegration();
        
        echo "\n✅ Conversion complete!\n";
        echo "📊 Converted {$this->converted} components\n";
        echo "📁 Output directory: {$this->targetDir}\n";
    }
    
    private function convertCategory($category, $mappings) {
        $categoryPath = "{$this->sourceDir}/app/{$category}";
        
        if (!is_dir($categoryPath)) {
            echo "⚠️  Category not found: $category\n";
            return;
        }
        
        foreach ($mappings as $source => $target) {
            $sourcePath = "$categoryPath/$source";
            
            if (!is_dir($sourcePath)) {
                continue;
            }
            
            $this->processDirectory($sourcePath, $target, $source);
        }
    }
    
    private function processDirectory($path, $targetCategory, $componentName) {
        $files = glob("$path/*.tsx");
        
        foreach ($files as $file) {
            $this->convertFile($file, $targetCategory, $componentName);
        }
    }
    
    private function convertFile($file, $targetCategory, $componentName) {
        $content = file_get_contents($file);
        $filename = basename($file, '.tsx');
        
        // Extract the component JSX
        $jsx = $this->extractJSX($content);
        
        if (!$jsx) {
            return;
        }
        
        // Convert JSX to Blade
        $blade = $this->jsxToBlade($jsx);
        
        // Create target directory
        $targetDir = "{$this->targetDir}/{$targetCategory}";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Save Blade file
        $bladePath = "$targetDir/{$filename}.blade.php";
        file_put_contents($bladePath, $blade);
        
        // Track component
        $this->components[$targetCategory][] = $filename;
        $this->converted++;
        
        echo "✓ Converted: $componentName/$filename → $targetCategory/$filename.blade.php\n";
    }
    
    private function extractJSX($content) {
        // Find the return statement with JSX
        if (preg_match('/return\s*\(([\s\S]*?)\);/m', $content, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/return\s*<([\s\S]*?)>;/m', $content, $matches)) {
            return '<' . $matches[1] . '>';
        }
        
        // Try to find JSX in export default
        if (preg_match('/export default.*?{\s*return\s*\(([\s\S]*?)\);\s*}/m', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function jsxToBlade($jsx) {
        // Remove TypeScript/React specific syntax
        $blade = $jsx;
        
        // Convert className to class
        $blade = preg_replace('/className=/i', 'class=', $blade);
        
        // Convert JSX expressions to Blade
        $blade = preg_replace('/\{([^}]+)\}/', '{{ $1 }}', $blade);
        
        // Convert self-closing tags
        $blade = preg_replace('/<(\w+)([^>]*?)\/>/i', '<$1$2></$1>', $blade);
        
        // Remove React imports and fragments
        $blade = preg_replace('/<\/?>/i', '', $blade);
        $blade = preg_replace('/<React\.Fragment>|<\/React\.Fragment>/i', '', $blade);
        
        // Convert onClick to Alpine.js
        $blade = preg_replace('/onClick=\{([^}]+)\}/i', '@click="$1"', $blade);
        
        // Convert conditional rendering
        $blade = preg_replace('/\{([^}]+)\s*&&\s*\(([\s\S]*?)\)\}/m', '@if($1)$2@endif', $blade);
        
        // Clean up JavaScript functions
        $blade = preg_replace('/\(\)\s*=>\s*/', '', $blade);
        
        // Convert map functions to Blade foreach
        $blade = preg_replace(
            '/\{(\w+)\.map\(\((\w+)\)\s*=>\s*\(([\s\S]*?)\)\)\}/m',
            '@foreach($$1 as $$2)$3@endforeach',
            $blade
        );
        
        // Add Blade layout
        $blade = "@extends('layouts.app')\n\n@section('content')\n" . $blade . "\n@endsection";
        
        // Add component props
        $blade = $this->addBladeProps($blade);
        
        return $blade;
    }
    
    private function addBladeProps($blade) {
        // Add common props
        $props = "@props([\n";
        $props .= "    'title' => '',\n";
        $props .= "    'description' => '',\n";
        $props .= "    'items' => [],\n";
        $props .= "    'class' => ''\n";
        $props .= "])\n\n";
        
        return $props . $blade;
    }
    
    private function createIndexFile() {
        $content = "<!DOCTYPE html>\n";
        $content .= "<html lang=\"en\">\n";
        $content .= "<head>\n";
        $content .= "    <meta charset=\"UTF-8\">\n";
        $content .= "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        $content .= "    <title>Flowbite Pro Components - Laravel</title>\n";
        $content .= "    <script src=\"https://cdn.tailwindcss.com\"></script>\n";
        $content .= "    <link href=\"https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css\" rel=\"stylesheet\" />\n";
        $content .= "</head>\n";
        $content .= "<body class=\"bg-gray-50\">\n";
        $content .= "    <div class=\"container mx-auto px-4 py-8\">\n";
        $content .= "        <h1 class=\"text-3xl font-bold mb-8\">Flowbite Pro Components</h1>\n";
        $content .= "        <div class=\"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6\">\n";
        
        foreach ($this->components as $category => $files) {
            $content .= "            <div class=\"bg-white rounded-lg shadow p-6\">\n";
            $content .= "                <h2 class=\"text-xl font-semibold mb-4\">" . ucfirst($category) . "</h2>\n";
            $content .= "                <ul class=\"space-y-2\">\n";
            
            foreach ($files as $file) {
                $url = "/flowbite-demo/$category/$file";
                $content .= "                    <li><a href=\"$url\" class=\"text-blue-600 hover:underline\">$file</a></li>\n";
            }
            
            $content .= "                </ul>\n";
            $content .= "            </div>\n";
        }
        
        $content .= "        </div>\n";
        $content .= "    </div>\n";
        $content .= "    <script src=\"https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js\"></script>\n";
        $content .= "</body>\n";
        $content .= "</html>\n";
        
        file_put_contents("{$this->targetDir}/index.blade.php", $content);
    }
    
    private function createFilamentIntegration() {
        // Create Filament resource with Flowbite components
        $resourceDir = '/var/www/api-gateway/app/Filament/Resources/FlowbiteDemo';
        
        if (!is_dir($resourceDir)) {
            mkdir($resourceDir, 0755, true);
        }
        
        $content = "<?php\n\n";
        $content .= "namespace App\Filament\Resources\FlowbiteDemo;\n\n";
        $content .= "use Filament\Resources\Resource;\n";
        $content .= "use Filament\Resources\Pages\Page;\n\n";
        $content .= "class FlowbiteDemoResource extends Resource\n";
        $content .= "{\n";
        $content .= "    protected static ?string \$model = null;\n";
        $content .= "    protected static ?string \$navigationIcon = 'heroicon-o-sparkles';\n";
        $content .= "    protected static ?string \$navigationLabel = 'Flowbite Pro';\n\n";
        $content .= "    public static function getPages(): array\n";
        $content .= "    {\n";
        $content .= "        return [\n";
        $content .= "            'index' => Pages\ListFlowbiteComponents::route('/'),\n";
        $content .= "        ];\n";
        $content .= "    }\n";
        $content .= "}\n";
        
        file_put_contents("$resourceDir/FlowbiteDemoResource.php", $content);
        
        echo "✓ Created Filament integration\n";
    }
}

// Run converter
$converter = new ReactToBladeConverter();
$converter->convert();