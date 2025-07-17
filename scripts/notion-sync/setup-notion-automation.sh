#!/bin/bash

# Setup script for Notion documentation automation

echo "🚀 Setting up Notion documentation automation..."

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p docs/search
mkdir -p docs/templates
mkdir -p docs/onboarding
mkdir -p storage/documentation-backups
mkdir -p storage/notion-cache
mkdir -p public/docs

# Make scripts executable
echo "🔧 Making scripts executable..."
chmod +x scripts/notion-sync/notion-sync.php
chmod +x scripts/notion-sync/monitoring-dashboard.php
chmod +x scripts/notion-sync/search-optimizer.php

# Install cron jobs
echo "⏰ Installing cron jobs..."

# Add to crontab (runs every hour)
(crontab -l 2>/dev/null; echo "0 * * * * /usr/bin/php /var/www/api-gateway/scripts/notion-sync/notion-sync.php >> /var/www/api-gateway/storage/logs/notion-sync.log 2>&1") | crontab -

# Documentation monitoring (runs daily at 9 AM)
(crontab -l 2>/dev/null; echo "0 9 * * * /usr/bin/php /var/www/api-gateway/scripts/notion-sync/monitoring-dashboard.php >> /var/www/api-gateway/storage/logs/doc-monitoring.log 2>&1") | crontab -

# Search optimization (runs daily at 2 AM)
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/bin/php /var/www/api-gateway/scripts/notion-sync/search-optimizer.php >> /var/www/api-gateway/storage/logs/search-optimizer.log 2>&1") | crontab -

# Create initial documentation mapping
echo "🗺️ Creating initial documentation mapping..."
cat > scripts/notion-sync/doc-mapping.json << 'EOL'
{
    "app/Services/RetellV2Service.php": {
        "notion_page_id": "retell-service-docs",
        "type": "service",
        "category": "integration"
    },
    "app/Services/CalcomV2Service.php": {
        "notion_page_id": "calcom-service-docs",
        "type": "service",
        "category": "integration"
    }
}
EOL

# Create environment variables template
echo "🔐 Creating environment template..."
cat >> .env.example << 'EOL'

# Notion Integration
NOTION_API_KEY=
NOTION_DATABASE_ID=
NOTION_STATUS_PAGE_ID=
EOL

# Create Git hooks for documentation updates
echo "🪝 Setting up Git hooks..."
cat > .githooks/pre-commit << 'EOL'
#!/bin/bash
# Check if documentation needs updating

# Get list of changed files
changed_files=$(git diff --cached --name-only)

# Check if any service files changed
if echo "$changed_files" | grep -q "app/Services/"; then
    echo "📚 Service files changed. Remember to update documentation!"
    echo "Run: php artisan docs:check-updates"
fi

# Check if any API routes changed
if echo "$changed_files" | grep -q "routes/"; then
    echo "📚 Routes changed. Remember to update API documentation!"
fi

exit 0
EOL

chmod +x .githooks/pre-commit

# Create Laravel commands
echo "📝 Creating Laravel commands..."
cat > app/Console/Commands/DocsCheckUpdates.php << 'EOL'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DocsCheckUpdates extends Command
{
    protected $signature = 'docs:check-updates {--auto-fix}';
    protected $description = 'Check if documentation needs updating';

    public function handle()
    {
        $this->info('Checking documentation status...');
        
        // Run the monitoring dashboard
        $output = shell_exec('php scripts/notion-sync/monitoring-dashboard.php');
        $this->line($output);
        
        if ($this->option('auto-fix')) {
            $this->info('Running auto-fix...');
            shell_exec('php scripts/notion-sync/notion-sync.php');
        }
        
        return 0;
    }
}
EOL

cat > app/Console/Commands/DocsSearch.php << 'EOL'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DocsSearch extends Command
{
    protected $signature = 'docs:search {query}';
    protected $description = 'Search documentation';

    public function handle()
    {
        $query = $this->argument('query');
        
        // Load search index
        $indexFile = base_path('docs/search/index.json');
        if (!file_exists($indexFile)) {
            $this->error('Search index not found. Run: php scripts/notion-sync/search-optimizer.php');
            return 1;
        }
        
        $index = json_decode(file_get_contents($indexFile), true);
        $results = [];
        
        // Simple search implementation
        foreach ($index as $doc) {
            $searchText = strtolower($doc['title'] . ' ' . $doc['description']);
            if (str_contains($searchText, strtolower($query))) {
                $results[] = $doc;
            }
        }
        
        if (empty($results)) {
            $this->info("No results found for: $query");
        } else {
            $this->info("Found " . count($results) . " results:");
            foreach ($results as $result) {
                $this->line("");
                $this->line("<comment>{$result['title']}</comment>");
                $this->line("{$result['description']}");
                $this->line("<info>File:</info> {$result['file']}");
            }
        }
        
        return 0;
    }
}
EOL

# Create monitoring widget for Filament
echo "📊 Creating Filament widget..."
cat > app/Filament/Widgets/DocumentationHealthWidget.php << 'EOL'
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DocumentationHealthWidget extends Widget
{
    protected static string $view = 'filament.widgets.documentation-health-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getHealthData(): array
    {
        $dashboardFile = storage_path('documentation-dashboard.json');
        
        if (!file_exists($dashboardFile)) {
            return [
                'health_score' => 0,
                'coverage' => ['percentage' => 0],
                'alerts' => [],
            ];
        }
        
        return json_decode(file_get_contents($dashboardFile), true);
    }
}
EOL

# Create widget view
echo "🎨 Creating widget view..."
mkdir -p resources/views/filament/widgets
cat > resources/views/filament/widgets/documentation-health-widget.blade.php << 'EOL'
<x-filament::widget>
    <x-filament::card>
        @php
            $data = $this->getHealthData();
            $healthScore = $data['health_score'] ?? 0;
            $coverage = $data['coverage']['percentage'] ?? 0;
            $alerts = $data['alerts'] ?? [];
            
            $healthColor = $healthScore > 80 ? 'success' : ($healthScore > 60 ? 'warning' : 'danger');
        @endphp
        
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium">Documentation Health</h2>
            <div class="flex items-center space-x-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-{{ $healthColor }}-600">{{ $healthScore }}%</div>
                    <div class="text-xs text-gray-500">Health Score</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold">{{ $coverage }}%</div>
                    <div class="text-xs text-gray-500">Coverage</div>
                </div>
            </div>
        </div>
        
        @if(count($alerts) > 0)
            <div class="space-y-2">
                @foreach($alerts as $alert)
                    <div class="p-3 rounded-lg bg-{{ $alert['type'] === 'critical' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-50">
                        <div class="font-medium text-{{ $alert['type'] === 'critical' ? 'red' : ($alert['type'] === 'warning' ? 'yellow' : 'blue') }}-800">
                            {{ $alert['title'] }}
                        </div>
                        <div class="text-sm text-gray-600">{{ $alert['message'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
        
        <div class="mt-4 flex space-x-2">
            <x-filament::button size="sm" tag="a" href="/docs/dashboard.html" target="_blank">
                View Full Dashboard
            </x-filament::button>
            <x-filament::button size="sm" color="secondary" wire:click="$refresh">
                Refresh
            </x-filament::button>
        </div>
    </x-filament::card>
</x-filament::widget>
EOL

# Run initial sync
echo "🔄 Running initial sync..."
php scripts/notion-sync/search-optimizer.php
php scripts/notion-sync/monitoring-dashboard.php

echo "✅ Setup complete!"
echo ""
echo "📋 Next steps:"
echo "1. Add your Notion API credentials to .env:"
echo "   NOTION_API_KEY=your-api-key"
echo "   NOTION_DATABASE_ID=your-database-id"
echo "   NOTION_STATUS_PAGE_ID=your-status-page-id"
echo ""
echo "2. Register the Laravel commands in app/Console/Kernel.php"
echo ""
echo "3. Add the widget to your Filament panel provider"
echo ""
echo "4. Access the search interface at: /docs/search.html"
echo "5. View the monitoring dashboard at: /docs/dashboard.html"
echo ""
echo "📚 Documentation has been created in:"
echo "   - docs/templates/ (Documentation templates)"
echo "   - docs/onboarding/ (Team onboarding guides)"
echo "   - docs/search/ (Search indexes)"
echo ""
echo "🎉 Happy documenting!"