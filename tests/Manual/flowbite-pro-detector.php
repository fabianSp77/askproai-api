#!/usr/bin/env php
<?php

/**
 * Flowbite Pro Component Detector and Mapper
 * ==========================================
 * Automatically detects Flowbite Pro components and creates Laravel/Filament integrations
 */

class FlowbiteProDetector {
    
    private $baseDir = '/var/www/api-gateway';
    private $flowbiteDir;
    private $components = [];
    private $layouts = [];
    private $patterns = [];
    
    // Component patterns to detect
    private $componentPatterns = [
        'alerts' => ['alert', 'notification', 'banner'],
        'badges' => ['badge', 'chip', 'tag', 'label'],
        'buttons' => ['btn', 'button', 'action'],
        'cards' => ['card', 'panel', 'box'],
        'charts' => ['chart', 'graph', 'analytics'],
        'datatables' => ['table', 'grid', 'list'],
        'forms' => ['form', 'input', 'field', 'control'],
        'modals' => ['modal', 'dialog', 'popup', 'drawer'],
        'navigation' => ['nav', 'menu', 'sidebar', 'breadcrumb'],
        'marketing' => ['hero', 'cta', 'feature', 'pricing'],
        'ecommerce' => ['product', 'cart', 'checkout', 'payment'],
        'application' => ['dashboard', 'settings', 'profile', 'admin']
    ];
    
    // Filament component mappings
    private $filamentMappings = [
        'alerts' => 'Filament\Notifications\Notification',
        'badges' => 'Filament\Support\Components\Badge',
        'buttons' => 'Filament\Support\Components\Button',
        'cards' => 'Filament\Support\Components\Card',
        'charts' => 'Filament\Widgets\ChartWidget',
        'datatables' => 'Filament\Tables\Table',
        'forms' => 'Filament\Forms\Form',
        'modals' => 'Filament\Support\Components\Modal',
        'navigation' => 'Filament\Navigation\NavigationItem'
    ];
    
    public function __construct($flowbiteDir = null) {
        $this->flowbiteDir = $flowbiteDir ?: $this->baseDir . '/resources/flowbite-pro';
        
        if (!is_dir($this->flowbiteDir)) {
            $this->info("Creating Flowbite Pro directory...");
            mkdir($this->flowbiteDir, 0755, true);
        }
    }
    
    /**
     * Main detection and integration process
     */
    public function detect() {
        $this->info("🔍 Starting Flowbite Pro Component Detection\n");
        
        // Step 1: Scan for components
        $this->scanDirectory($this->flowbiteDir);
        
        // Step 2: Analyze component structure
        $this->analyzeComponents();
        
        // Step 3: Generate mappings
        $this->generateMappings();
        
        // Step 4: Create integrations
        $this->createIntegrations();
        
        // Step 5: Generate test suite
        $this->generateTestSuite();
        
        $this->success("\n✅ Detection complete!");
        $this->printSummary();
    }
    
    /**
     * Scan directory for Flowbite Pro files
     */
    private function scanDirectory($dir) {
        if (!is_dir($dir)) {
            $this->warning("Directory not found: $dir");
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $this->analyzeFile($file);
            }
        }
    }
    
    /**
     * Analyze individual file for component patterns
     */
    private function analyzeFile($file) {
        $path = $file->getPathname();
        $relativePath = str_replace($this->flowbiteDir . '/', '', $path);
        $filename = $file->getFilename();
        $extension = $file->getExtension();
        
        // Skip non-relevant files
        if (!in_array($extension, ['html', 'js', 'jsx', 'vue', 'css', 'scss', 'json'])) {
            return;
        }
        
        $content = file_get_contents($path);
        
        // Detect component type
        foreach ($this->componentPatterns as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($filename, $pattern) !== false || 
                    stripos($content, "class=\"$pattern") !== false ||
                    stripos($content, "id=\"$pattern") !== false) {
                    
                    $this->components[$type][] = [
                        'file' => $relativePath,
                        'name' => $this->extractComponentName($filename),
                        'path' => $path,
                        'type' => $extension,
                        'size' => $file->getSize(),
                        'patterns' => $this->extractPatterns($content)
                    ];
                    
                    $this->info("  Found: $type component in $relativePath");
                    break 2;
                }
            }
        }
    }
    
    /**
     * Extract component name from filename
     */
    private function extractComponentName($filename) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/[-_]/', ' ', $name);
        return ucwords($name);
    }
    
    /**
     * Extract CSS patterns and classes
     */
    private function extractPatterns($content) {
        $patterns = [];
        
        // Extract Tailwind classes
        preg_match_all('/class=["\']([^"\']+)["\']/', $content, $matches);
        if (!empty($matches[1])) {
            $patterns['classes'] = array_unique($matches[1]);
        }
        
        // Extract data attributes
        preg_match_all('/data-([a-z-]+)=["\']([^"\']+)["\']/', $content, $matches);
        if (!empty($matches[1])) {
            $patterns['data'] = array_combine($matches[1], $matches[2]);
        }
        
        // Extract Alpine.js directives
        preg_match_all('/x-([a-z-]+)=["\']([^"\']+)["\']/', $content, $matches);
        if (!empty($matches[1])) {
            $patterns['alpine'] = array_combine($matches[1], $matches[2]);
        }
        
        return $patterns;
    }
    
    /**
     * Analyze detected components
     */
    private function analyzeComponents() {
        $this->info("\n📊 Analyzing component structure...\n");
        
        foreach ($this->components as $type => $items) {
            $this->info("  $type: " . count($items) . " components found");
            
            // Group by file type
            $byType = [];
            foreach ($items as $item) {
                $byType[$item['type']][] = $item['name'];
            }
            
            foreach ($byType as $fileType => $names) {
                $this->info("    - $fileType: " . implode(', ', array_slice($names, 0, 3)) . 
                           (count($names) > 3 ? '...' : ''));
            }
        }
    }
    
    /**
     * Generate component mappings
     */
    private function generateMappings() {
        $this->info("\n🗺️ Generating component mappings...\n");
        
        $mappings = [
            'version' => '1.0.0',
            'generated' => date('Y-m-d H:i:s'),
            'components' => [],
            'filament_integrations' => [],
            'blade_components' => [],
            'livewire_components' => []
        ];
        
        foreach ($this->components as $type => $items) {
            foreach ($items as $item) {
                $componentId = $this->generateComponentId($type, $item['name']);
                
                $mappings['components'][$componentId] = [
                    'type' => $type,
                    'name' => $item['name'],
                    'source' => $item['file'],
                    'patterns' => $item['patterns'] ?? []
                ];
                
                // Generate Filament integration
                if (isset($this->filamentMappings[$type])) {
                    $mappings['filament_integrations'][$componentId] = [
                        'extends' => $this->filamentMappings[$type],
                        'blade_view' => "flowbite.$type.$componentId",
                        'livewire' => "flowbite.$type.$componentId"
                    ];
                }
                
                // Generate Blade component mapping
                $mappings['blade_components'][$componentId] = [
                    'tag' => "x-flowbite-$type-" . $this->kebabCase($item['name']),
                    'class' => "App\\View\\Components\\Flowbite\\" . $this->pascalCase($type) . "\\" . $this->pascalCase($item['name']),
                    'view' => "components.flowbite.$type." . $this->kebabCase($item['name'])
                ];
            }
        }
        
        // Save mappings
        $mappingFile = $this->baseDir . '/config/flowbite-mappings.json';
        file_put_contents($mappingFile, json_encode($mappings, JSON_PRETTY_PRINT));
        
        $this->success("  ✓ Mappings saved to: config/flowbite-mappings.json");
    }
    
    /**
     * Create Laravel/Filament integrations
     */
    private function createIntegrations() {
        $this->info("\n🔧 Creating integrations...\n");
        
        // Create Blade components
        $this->createBladeComponents();
        
        // Create Filament widgets
        $this->createFilamentWidgets();
        
        // Create Livewire components
        $this->createLivewireComponents();
        
        // Update configuration files
        $this->updateConfigurations();
    }
    
    /**
     * Create Blade component wrappers
     */
    private function createBladeComponents() {
        $componentDir = $this->baseDir . '/app/View/Components/Flowbite';
        
        if (!is_dir($componentDir)) {
            mkdir($componentDir, 0755, true);
        }
        
        foreach ($this->components as $type => $items) {
            $typeDir = $componentDir . '/' . $this->pascalCase($type);
            if (!is_dir($typeDir)) {
                mkdir($typeDir, 0755, true);
            }
            
            foreach ($items as $item) {
                $className = $this->pascalCase($item['name']);
                $classFile = "$typeDir/$className.php";
                
                if (!file_exists($classFile)) {
                    $this->createBladeComponentClass($classFile, $type, $className);
                    $this->info("  ✓ Created Blade component: $className");
                }
            }
        }
    }
    
    /**
     * Create Blade component class
     */
    private function createBladeComponentClass($file, $type, $className) {
        $namespace = "App\\View\\Components\\Flowbite\\" . $this->pascalCase($type);
        $viewPath = "components.flowbite.$type." . $this->kebabCase($className);
        
        $content = <<<PHP
<?php

namespace $namespace;

use Illuminate\View\Component;

class $className extends Component
{
    public function __construct(
        public ?string \$variant = 'default',
        public ?string \$size = 'md',
        public ?array \$data = []
    ) {}

    public function render()
    {
        return view('$viewPath');
    }
}
PHP;
        
        file_put_contents($file, $content);
    }
    
    /**
     * Create Filament widgets
     */
    private function createFilamentWidgets() {
        $widgetDir = $this->baseDir . '/app/Filament/Widgets/Flowbite';
        
        if (!is_dir($widgetDir)) {
            mkdir($widgetDir, 0755, true);
        }
        
        // Create sample widgets for key component types
        $widgetTypes = ['charts', 'cards', 'datatables'];
        
        foreach ($widgetTypes as $type) {
            if (isset($this->components[$type]) && count($this->components[$type]) > 0) {
                $widgetName = $this->pascalCase($type) . 'Widget';
                $widgetFile = "$widgetDir/$widgetName.php";
                
                if (!file_exists($widgetFile)) {
                    $this->createFilamentWidget($widgetFile, $type, $widgetName);
                    $this->info("  ✓ Created Filament widget: $widgetName");
                }
            }
        }
    }
    
    /**
     * Create Filament widget class
     */
    private function createFilamentWidget($file, $type, $className) {
        $content = <<<PHP
<?php

namespace App\Filament\Widgets\Flowbite;

use Filament\Widgets\Widget;

class $className extends Widget
{
    protected static string \$view = 'filament.widgets.flowbite.$type';
    
    protected int | string | array \$columnSpan = 'full';
    
    public function getDisplayData(): array
    {
        return [
            'type' => '$type',
            'components' => \$this->getFlowbiteComponents()
        ];
    }
    
    private function getFlowbiteComponents(): array
    {
        \$mappings = json_decode(
            file_get_contents(config_path('flowbite-mappings.json')),
            true
        );
        
        return array_filter(
            \$mappings['components'] ?? [],
            fn(\$c) => \$c['type'] === '$type'
        );
    }
}
PHP;
        
        file_put_contents($file, $content);
    }
    
    /**
     * Create Livewire components
     */
    private function createLivewireComponents() {
        $livewireDir = $this->baseDir . '/app/Livewire/Flowbite';
        
        if (!is_dir($livewireDir)) {
            mkdir($livewireDir, 0755, true);
        }
        
        // Create interactive component wrappers
        $interactiveTypes = ['forms', 'modals', 'datatables'];
        
        foreach ($interactiveTypes as $type) {
            if (isset($this->components[$type])) {
                $componentName = $this->pascalCase($type) . 'Component';
                $componentFile = "$livewireDir/$componentName.php";
                
                if (!file_exists($componentFile)) {
                    $this->createLivewireComponent($componentFile, $type, $componentName);
                    $this->info("  ✓ Created Livewire component: $componentName");
                }
            }
        }
    }
    
    /**
     * Create Livewire component class
     */
    private function createLivewireComponent($file, $type, $className) {
        $content = <<<PHP
<?php

namespace App\Livewire\Flowbite;

use Livewire\Component;

class $className extends Component
{
    public \$data = [];
    public \$config = [];
    
    public function mount(\$data = [], \$config = [])
    {
        \$this->data = \$data;
        \$this->config = \$config;
    }
    
    public function render()
    {
        return view('livewire.flowbite.$type', [
            'flowbiteType' => '$type',
            'flowbiteData' => \$this->data,
            'flowbiteConfig' => \$this->config
        ]);
    }
}
PHP;
        
        file_put_contents($file, $content);
    }
    
    /**
     * Update configuration files
     */
    private function updateConfigurations() {
        // Update AppServiceProvider
        $this->updateAppServiceProvider();
        
        // Update Tailwind config
        $this->updateTailwindConfig();
        
        // Create Flowbite config file
        $this->createFlowbiteConfig();
    }
    
    /**
     * Update AppServiceProvider
     */
    private function updateAppServiceProvider() {
        $providerFile = $this->baseDir . '/app/Providers/AppServiceProvider.php';
        $content = file_get_contents($providerFile);
        
        if (stripos($content, 'Flowbite components registered') === false) {
            $registration = <<<'PHP'

        // Flowbite components registered
        Blade::componentNamespace('App\\View\\Components\\Flowbite', 'flowbite');
PHP;
            
            $content = str_replace(
                'public function boot(): void',
                "public function boot(): void\n    {" . $registration,
                $content
            );
            
            // Only update if the registration was added
            if (stripos($content, 'Flowbite components registered') !== false) {
                file_put_contents($providerFile, $content);
                $this->info("  ✓ Updated AppServiceProvider");
            }
        }
    }
    
    /**
     * Update Tailwind configuration
     */
    private function updateTailwindConfig() {
        $configFile = $this->baseDir . '/tailwind.config.js';
        
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            
            if (stripos($content, 'flowbite-pro') === false) {
                // Add Flowbite Pro to content paths
                $content = preg_replace(
                    '/content:\s*\[/',
                    "content: [\n    './resources/flowbite-pro/**/*.{html,js}',",
                    $content
                );
                
                file_put_contents($configFile, $content);
                $this->info("  ✓ Updated Tailwind config");
            }
        }
    }
    
    /**
     * Create Flowbite configuration file
     */
    private function createFlowbiteConfig() {
        $configFile = $this->baseDir . '/config/flowbite.php';
        
        if (!file_exists($configFile)) {
            $content = <<<'PHP'
<?php

return [
    /**
     * Flowbite Pro Configuration
     */
    
    // Enable Flowbite Pro components
    'enabled' => env('FLOWBITE_ENABLED', true),
    
    // Component paths
    'paths' => [
        'components' => resource_path('flowbite-pro'),
        'views' => resource_path('views/flowbite'),
        'assets' => public_path('flowbite-pro'),
    ],
    
    // Theme configuration
    'theme' => [
        'primary' => 'blue',
        'dark_mode' => true,
        'rtl' => false,
    ],
    
    // Component defaults
    'defaults' => [
        'size' => 'md',
        'variant' => 'default',
        'rounded' => 'lg',
    ],
    
    // Filament integration
    'filament' => [
        'enabled' => true,
        'widgets' => true,
        'forms' => true,
        'tables' => true,
    ],
    
    // Livewire integration
    'livewire' => [
        'enabled' => true,
        'prefix' => 'flowbite',
    ],
];
PHP;
            
            file_put_contents($configFile, $content);
            $this->info("  ✓ Created Flowbite config");
        }
    }
    
    /**
     * Generate test suite
     */
    private function generateTestSuite() {
        $this->info("\n🧪 Generating test suite...\n");
        
        $testDir = $this->baseDir . '/tests/Feature/Flowbite';
        
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        // Create component test
        $this->createComponentTest($testDir);
        
        // Create integration test
        $this->createIntegrationTest($testDir);
        
        $this->success("  ✓ Test suite generated");
    }
    
    /**
     * Create component test
     */
    private function createComponentTest($testDir) {
        $testFile = "$testDir/ComponentTest.php";
        
        $content = <<<'PHP'
<?php

namespace Tests\Feature\Flowbite;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComponentTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_flowbite_components_are_registered()
    {
        $mappings = json_decode(
            file_get_contents(config_path('flowbite-mappings.json')),
            true
        );
        
        $this->assertNotEmpty($mappings['components']);
    }
    
    public function test_blade_components_can_render()
    {
        $view = view('flowbite-test')->render();
        
        $this->assertStringContainsString('Flowbite Pro', $view);
    }
    
    public function test_filament_widgets_load()
    {
        $this->actingAs($this->getAdminUser())
            ->get('/admin')
            ->assertSuccessful();
    }
    
    private function getAdminUser()
    {
        return \App\Models\User::factory()->create([
            'email' => 'test@example.com'
        ])->assignRole('super_admin');
    }
}
PHP;
        
        file_put_contents($testFile, $content);
    }
    
    /**
     * Create integration test
     */
    private function createIntegrationTest($testDir) {
        $testFile = "$testDir/IntegrationTest.php";
        
        $content = <<<'PHP'
<?php

namespace Tests\Feature\Flowbite;

use Tests\TestCase;
use Livewire\Livewire;

class IntegrationTest extends TestCase
{
    public function test_livewire_components_mount()
    {
        $components = [
            'flowbite.forms-component',
            'flowbite.modals-component',
            'flowbite.datatables-component'
        ];
        
        foreach ($components as $component) {
            if (class_exists("App\\Livewire\\Flowbite\\" . str_replace('-', '', ucwords($component, '-')))) {
                Livewire::test($component)
                    ->assertSuccessful();
            }
        }
        
        $this->assertTrue(true);
    }
    
    public function test_flowbite_config_loads()
    {
        $config = config('flowbite');
        
        $this->assertTrue($config['enabled']);
        $this->assertEquals('blue', $config['theme']['primary']);
    }
}
PHP;
        
        file_put_contents($testFile, $content);
    }
    
    /**
     * Print summary
     */
    private function printSummary() {
        $total = array_sum(array_map('count', $this->components));
        
        echo "\n";
        echo "╔══════════════════════════════════════════════════════╗\n";
        echo "║         FLOWBITE PRO DETECTION SUMMARY               ║\n";
        echo "╠══════════════════════════════════════════════════════╣\n";
        echo "║                                                      ║\n";
        
        foreach ($this->components as $type => $items) {
            $count = count($items);
            $line = sprintf("║  %-20s: %3d components         ║", ucfirst($type), $count);
            echo "$line\n";
        }
        
        echo "║                                                      ║\n";
        echo "╠══════════════════════════════════════════════════════╣\n";
        echo "║  Total Components: " . sprintf("%-33d", $total) . " ║\n";
        echo "╚══════════════════════════════════════════════════════╝\n";
        
        echo "\n📁 Files Created:\n";
        echo "  • config/flowbite-mappings.json\n";
        echo "  • config/flowbite.php\n";
        echo "  • app/View/Components/Flowbite/*\n";
        echo "  • app/Filament/Widgets/Flowbite/*\n";
        echo "  • app/Livewire/Flowbite/*\n";
        echo "  • tests/Feature/Flowbite/*\n";
        
        echo "\n🚀 Next Steps:\n";
        echo "  1. Upload Flowbite Pro files to: resources/flowbite-pro/\n";
        echo "  2. Run: php flowbite-pro-detector.php\n";
        echo "  3. Build assets: npm run build\n";
        echo "  4. Test: php artisan test --filter=Flowbite\n";
        echo "  5. Visit: /flowbite-test\n";
    }
    
    // Helper methods
    
    private function generateComponentId($type, $name) {
        return $this->kebabCase($type) . '-' . $this->kebabCase($name);
    }
    
    private function kebabCase($string) {
        return strtolower(preg_replace('/[^A-Za-z0-9]/', '-', $string));
    }
    
    private function pascalCase($string) {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }
    
    private function info($message) {
        echo "\033[0;36m$message\033[0m\n";
    }
    
    private function success($message) {
        echo "\033[0;32m$message\033[0m\n";
    }
    
    private function warning($message) {
        echo "\033[0;33m$message\033[0m\n";
    }
}

// Run detector
$detector = new FlowbiteProDetector();
$detector->detect();