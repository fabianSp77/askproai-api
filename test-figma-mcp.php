#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\FigmaMCPServer;

try {
    echo "Testing Figma MCP Server...\n";
    echo str_repeat("=", 60) . "\n\n";

    // Initialize Figma MCP Server
    $figma = app(FigmaMCPServer::class);
    
    // Get server info
    echo "Server Information:\n";
    echo "- Name: " . $figma->getName() . "\n";
    echo "- Version: " . $figma->getVersion() . "\n";
    echo "- Capabilities: " . implode(', ', $figma->getCapabilities()) . "\n\n";

    // List available tools
    echo "Available Figma Tools:\n";
    $tools = $figma->getTools();
    foreach ($tools as $tool) {
        echo "  - {$tool['name']}: {$tool['description']}\n";
    }
    echo "\n";

    // Check API token configuration
    $apiToken = config('services.figma.api_token');
    if (!$apiToken || $apiToken === 'your_figma_api_token_here') {
        echo "⚠️  WARNING: Figma API token not configured!\n";
        echo "Please set FIGMA_API_TOKEN in your .env file\n\n";
        echo "To get your Figma API token:\n";
        echo "1. Go to https://www.figma.com/settings\n";
        echo "2. Navigate to 'Personal Access Tokens'\n";
        echo "3. Create a new token\n";
        echo "4. Add it to your .env file\n\n";
        
        echo "Once configured, you can:\n";
        echo "- Generate HTML/React/Blade components from designs\n";
        echo "- Extract color palettes and typography\n";
        echo "- Export assets and images\n";
        echo "- Convert UI designs to code\n\n";
    } else {
        echo "✅ Figma API token is configured\n\n";
    }

    echo "💡 Usage Examples:\n\n";
    
    echo "1. Get Figma file structure:\n";
    echo "```php\n";
    echo "\$result = \$figma->executeTool('get_file', [\n";
    echo "    'file_key' => 'your-figma-file-key'\n";
    echo "]);\n```\n\n";
    
    echo "2. Generate Tailwind HTML:\n";
    echo "```php\n";
    echo "\$result = \$figma->executeTool('generate_html', [\n";
    echo "    'file_key' => 'your-figma-file-key',\n";
    echo "    'node_id' => 'component-node-id',\n";
    echo "    'framework' => 'tailwind'\n";
    echo "]);\n```\n\n";
    
    echo "3. Generate Laravel Blade component:\n";
    echo "```php\n";
    echo "\$result = \$figma->executeTool('generate_blade', [\n";
    echo "    'file_key' => 'your-figma-file-key',\n";
    echo "    'node_id' => 'component-node-id',\n";
    echo "    'component_name' => 'user-card',\n";
    echo "    'use_alpine' => true\n";
    echo "]);\n```\n\n";
    
    echo "4. Extract design tokens:\n";
    echo "```php\n";
    echo "// Get colors\n";
    echo "\$colors = \$figma->executeTool('extract_colors', [\n";
    echo "    'file_key' => 'your-figma-file-key',\n";
    echo "    'format' => 'tailwind'\n";
    echo "]);\n\n";
    echo "// Get typography\n";
    echo "\$typography = \$figma->executeTool('extract_typography', [\n";
    echo "    'file_key' => 'your-figma-file-key',\n";
    echo "    'format' => 'css'\n";
    echo "]);\n```\n\n";

    echo "📚 Integration with Laravel:\n";
    echo "The Figma MCP Server integrates seamlessly with your Laravel application.\n\n";
    
    echo "Example: Auto-generate Blade components from Figma:\n";
    echo "```php\n";
    echo "class ComponentGeneratorCommand extends Command\n";
    echo "{\n";
    echo "    protected \$signature = 'figma:generate-component {file_key} {node_id} {name}';\n";
    echo "    \n";
    echo "    public function handle(FigmaMCPServer \$figma)\n";
    echo "    {\n";
    echo "        \$result = \$figma->executeTool('generate_blade', [\n";
    echo "            'file_key' => \$this->argument('file_key'),\n";
    echo "            'node_id' => \$this->argument('node_id'),\n";
    echo "            'component_name' => \$this->argument('name')\n";
    echo "        ]);\n";
    echo "        \n";
    echo "        if (\$result['success']) {\n";
    echo "            file_put_contents(\n";
    echo "                base_path(\$result['data']['blade_path']),\n";
    echo "                \$result['data']['blade']\n";
    echo "            );\n";
    echo "            \n";
    echo "            file_put_contents(\n";
    echo "                base_path(\$result['data']['class_path']),\n";
    echo "                \$result['data']['component_class']\n";
    echo "            );\n";
    echo "            \n";
    echo "            \$this->info('Component generated successfully!');\n";
    echo "        }\n";
    echo "    }\n";
    echo "}\n";
    echo "```\n\n";

    echo "🎨 Design-to-Code Workflow:\n";
    echo "1. Design UI components in Figma\n";
    echo "2. Get file key from Figma URL (e.g., figma.com/file/ABC123/...)\n";
    echo "3. Get node IDs using 'get_file' tool\n";
    echo "4. Generate code using appropriate generator\n";
    echo "5. Customize generated code as needed\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}