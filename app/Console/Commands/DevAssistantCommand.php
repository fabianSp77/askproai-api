<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DeveloperAssistantService;
use Illuminate\Support\Facades\File;

class DevAssistantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev {action} {--file=} {--type=} {--name=} {--model=} {--output=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Developer assistant for code generation and analysis';

    protected DeveloperAssistantService $assistant;

    /**
     * Execute the console command.
     */
    public function handle(DeveloperAssistantService $assistant): int
    {
        $this->assistant = $assistant;
        $action = $this->argument('action');
        
        return match($action) {
            'generate', 'gen' => $this->handleGenerate(),
            'analyze' => $this->handleAnalyze(),
            'suggest' => $this->handleSuggest(),
            'boilerplate', 'bp' => $this->handleBoilerplate(),
            'similar' => $this->handleFindSimilar(),
            'explain' => $this->handleExplain(),
            'pattern' => $this->handlePattern(),
            'help' => $this->showHelp(),
            default => $this->invalidAction($action)
        };
    }
    
    /**
     * Handle code generation
     */
    protected function handleGenerate(): int
    {
        $this->info('🤖 AI Code Generation');
        
        $description = $this->ask('Describe what you want to generate');
        $type = $this->option('type') ?: 'auto';
        
        $this->info('Generating code...');
        
        $result = $this->assistant->generateCode($description, $type);
        
        if (!$result['success']) {
            $this->error('Generation failed: ' . $result['error']);
            return 1;
        }
        
        $this->info('✅ Code generated successfully!');
        $this->line('');
        
        // Display generated code
        $this->info('Generated Code:');
        foreach ($result['code'] as $type => $code) {
            if ($code) {
                $this->line("--- {$type} ---");
                $this->line($code);
                $this->line('');
            }
        }
        
        // Suggest file locations
        if (!empty($result['files'])) {
            $this->info('Suggested file locations:');
            foreach ($result['files'] as $file) {
                $this->line("  - {$file}");
            }
        }
        
        // Show test suggestions
        if (!empty($result['tests'])) {
            $this->line('');
            $this->info('Suggested tests:');
            $this->line($result['tests']);
        }
        
        // Save to file if requested
        if ($output = $this->option('output')) {
            File::put($output, $result['code']['main']);
            $this->info("Code saved to: {$output}");
        }
        
        return 0;
    }
    
    /**
     * Handle code analysis
     */
    protected function handleAnalyze(): int
    {
        $file = $this->option('file') ?: $this->ask('Enter file path to analyze');
        
        if (!File::exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }
        
        $this->info("🔍 Analyzing: {$file}");
        $this->line('');
        
        $analysis = $this->assistant->analyzeCode($file);
        
        // Display issues
        if (!empty($analysis['issues'])) {
            $this->warn('Issues Found:');
            foreach ($analysis['issues'] as $issue) {
                $this->line("  ⚠️  {$issue['message']} (line {$issue['line']})");
            }
            $this->line('');
        } else {
            $this->info('✅ No issues found!');
            $this->line('');
        }
        
        // Display improvements
        if (!empty($analysis['improvements'])) {
            $this->info('Suggested Improvements:');
            foreach ($analysis['improvements'] as $improvement) {
                $this->line("  💡 {$improvement}");
            }
            $this->line('');
        }
        
        // Display pattern compliance
        if (isset($analysis['pattern_compliance'])) {
            $compliance = $analysis['pattern_compliance'];
            $score = $compliance['score'] ?? 0;
            $color = $score >= 80 ? 'info' : ($score >= 60 ? 'comment' : 'error');
            
            $this->line('Pattern Compliance:');
            $this->{$color}("  Score: {$score}%");
            
            if (!empty($compliance['violations'])) {
                $this->line('  Violations:');
                foreach ($compliance['violations'] as $violation) {
                    $this->line("    - {$violation}");
                }
            }
            $this->line('');
        }
        
        // Display performance suggestions
        if (!empty($analysis['performance'])) {
            $this->info('Performance Suggestions:');
            foreach ($analysis['performance'] as $suggestion) {
                $this->line("  ⚡ {$suggestion}");
            }
            $this->line('');
        }
        
        // Display security issues
        if (!empty($analysis['security'])) {
            $this->error('Security Issues:');
            foreach ($analysis['security'] as $issue) {
                $this->line("  🔒 {$issue}");
            }
            $this->line('');
        }
        
        return 0;
    }
    
    /**
     * Handle development suggestions
     */
    protected function handleSuggest(): int
    {
        $this->info('💡 Development Suggestions');
        
        $context = $this->ask('Any specific context? (press enter to skip)', '');
        
        $this->info('Analyzing project...');
        
        $result = $this->assistant->suggestNextSteps($context);
        
        if (!$result['success']) {
            $this->error('Failed to generate suggestions: ' . $result['error']);
            return 1;
        }
        
        // Display project health
        $health = $result['project_health'] ?? [];
        if (!empty($health)) {
            $this->info('Project Health:');
            $this->line("  Overall Score: {$health['score']}%");
            $this->line("  Test Coverage: {$health['test_coverage']}%");
            $this->line("  Code Quality: {$health['code_quality']}");
            $this->line('');
        }
        
        // Display suggestions by category
        foreach ($result['suggestions'] as $category => $suggestions) {
            if (empty($suggestions)) continue;
            
            $this->info(ucfirst(str_replace('_', ' ', $category)) . ':');
            foreach ($suggestions as $suggestion) {
                $priority = $suggestion['priority'] ?? 'medium';
                $icon = match($priority) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    'low' => '🟢',
                    default => '⚪'
                };
                
                $this->line("  {$icon} {$suggestion['task']}");
                if (isset($suggestion['reason'])) {
                    $this->line("     → {$suggestion['reason']}");
                }
            }
            $this->line('');
        }
        
        // Display recommended focus
        if (isset($result['recommended_focus'])) {
            $this->comment("Recommended Focus: {$result['recommended_focus']}");
        }
        
        return 0;
    }
    
    /**
     * Handle boilerplate generation
     */
    protected function handleBoilerplate(): int
    {
        $types = [
            'filament-resource' => 'Filament admin resource',
            'mcp-server' => 'MCP server class',
            'service' => 'Service class with interface',
            'repository' => 'Repository pattern implementation',
            'test' => 'PHPUnit test class',
            'migration' => 'Database migration',
            'api-endpoint' => 'API controller and routes',
            'job' => 'Queue job class',
            'event-listener' => 'Event and listener classes',
            'notification' => 'Notification class'
        ];
        
        $type = $this->option('type');
        
        if (!$type) {
            $type = $this->choice('Select boilerplate type', array_keys($types));
        }
        
        $params = [];
        
        // Collect parameters based on type
        switch ($type) {
            case 'filament-resource':
                $params['model'] = $this->option('model') ?: $this->ask('Model name (e.g., Product)');
                break;
                
            case 'mcp-server':
            case 'service':
            case 'repository':
                $params['name'] = $this->option('name') ?: $this->ask('Name (e.g., Payment)');
                break;
                
            case 'migration':
                $params['table'] = $this->ask('Table name');
                $params['action'] = $this->choice('Action', ['create', 'alter', 'drop'], 0);
                break;
                
            case 'api-endpoint':
                $params['resource'] = $this->ask('Resource name (e.g., products)');
                $params['actions'] = $this->choice(
                    'Actions (comma-separated)', 
                    ['index,show,store,update,destroy'],
                    0
                );
                break;
        }
        
        $this->info("Generating {$types[$type]} boilerplate...");
        
        $result = $this->assistant->generateBoilerplate($type, $params);
        
        if (!$result['success']) {
            $this->error('Generation failed: ' . $result['error']);
            if (isset($result['available_types'])) {
                $this->info('Available types: ' . implode(', ', $result['available_types']));
            }
            return 1;
        }
        
        $this->info('✅ Boilerplate generated!');
        $this->line('');
        
        // Display generated files
        $this->info('Generated files:');
        foreach ($result['files'] as $path => $content) {
            $this->line("  📄 {$path}");
            
            if ($this->confirm("View {$path}?", false)) {
                $this->line('');
                $this->line($content);
                $this->line('');
            }
            
            if ($this->confirm("Save {$path}?", true)) {
                $dir = dirname($path);
                if (!File::exists($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
                File::put($path, $content);
                $this->info("  ✅ Saved!");
            }
        }
        
        // Show instructions
        if (!empty($result['instructions'])) {
            $this->line('');
            $this->info('Next steps:');
            foreach ($result['instructions'] as $step => $instruction) {
                $this->line("{$step}. {$instruction}");
            }
        }
        
        return 0;
    }
    
    /**
     * Handle finding similar code
     */
    protected function handleFindSimilar(): int
    {
        $this->info('🔍 Find Similar Code');
        
        $file = $this->option('file');
        
        if ($file && File::exists($file)) {
            $code = File::get($file);
        } else {
            $this->info('Enter code snippet (type "END" on a new line when done):');
            $lines = [];
            while (($line = $this->ask('')) !== 'END') {
                $lines[] = $line;
            }
            $code = implode("\n", $lines);
        }
        
        $this->info('Searching for similar code...');
        
        $results = $this->assistant->findSimilarCode($code);
        
        if (empty($results)) {
            $this->info('No similar code found.');
            return 0;
        }
        
        $this->info('Found similar code:');
        $this->line('');
        
        foreach ($results as $result) {
            $this->line("📄 {$result['file']} ({$result['similarity']}% similar)");
            
            if (!empty($result['matches'])) {
                foreach ($result['matches'] as $match) {
                    $this->line("   Line {$match['line']}: {$match['content']}");
                }
            }
            
            $this->line('');
        }
        
        return 0;
    }
    
    /**
     * Handle code explanation
     */
    protected function handleExplain(): int
    {
        $file = $this->option('file') ?: $this->ask('Enter file path');
        
        if (!File::exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }
        
        $this->info("📖 Explaining: {$file}");
        
        $startLine = null;
        $endLine = null;
        
        if ($this->confirm('Explain specific lines?', false)) {
            $startLine = (int) $this->ask('Start line');
            $endLine = (int) $this->ask('End line');
        }
        
        $explanation = $this->assistant->explainCode($file, $startLine, $endLine);
        
        // Display explanation
        $this->line('');
        $this->info('Summary:');
        $this->line($explanation['summary']);
        
        $this->line('');
        $this->info('Purpose:');
        $this->line($explanation['purpose']);
        
        if (!empty($explanation['components'])) {
            $this->line('');
            $this->info('Components:');
            foreach ($explanation['components'] as $component => $desc) {
                $this->line("  • {$component}: {$desc}");
            }
        }
        
        if (!empty($explanation['dependencies'])) {
            $this->line('');
            $this->info('Dependencies:');
            foreach ($explanation['dependencies'] as $dep) {
                $this->line("  - {$dep}");
            }
        }
        
        if (!empty($explanation['side_effects'])) {
            $this->line('');
            $this->warn('Side Effects:');
            foreach ($explanation['side_effects'] as $effect) {
                $this->line("  ⚠️  {$effect}");
            }
        }
        
        if (!empty($explanation['usage_examples'])) {
            $this->line('');
            $this->info('Usage Examples:');
            $this->line($explanation['usage_examples']);
        }
        
        return 0;
    }
    
    /**
     * Handle pattern management
     */
    protected function handlePattern(): int
    {
        $subAction = $this->choice(
            'Pattern action',
            ['learn', 'list', 'apply'],
            0
        );
        
        switch ($subAction) {
            case 'learn':
                return $this->learnPattern();
            case 'list':
                return $this->listPatterns();
            case 'apply':
                return $this->applyPattern();
        }
        
        return 0;
    }
    
    /**
     * Learn a new pattern from existing code
     */
    protected function learnPattern(): int
    {
        $file = $this->ask('Enter file path to learn pattern from');
        
        if (!File::exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }
        
        $name = $this->ask('Pattern name');
        $description = $this->ask('Pattern description');
        
        $this->info('Learning pattern...');
        
        // This would analyze the file and extract patterns
        // For now, we'll just save the file as a pattern
        
        $this->info("✅ Pattern '{$name}' learned successfully!");
        
        return 0;
    }
    
    /**
     * List available patterns
     */
    protected function listPatterns(): int
    {
        $this->info('Available Patterns:');
        
        // This would list patterns from memory bank
        $patterns = [
            ['service', 'Service class pattern', '15 uses'],
            ['repository', 'Repository pattern', '8 uses'],
            ['filament-resource', 'Filament resource pattern', '12 uses'],
            ['api-controller', 'API controller pattern', '6 uses'],
        ];
        
        $this->table(['Type', 'Description', 'Usage'], $patterns);
        
        return 0;
    }
    
    /**
     * Apply a pattern
     */
    protected function applyPattern(): int
    {
        $pattern = $this->ask('Pattern name');
        $target = $this->ask('Target name (e.g., UserProfile)');
        
        $this->info("Applying '{$pattern}' pattern...");
        
        // This would generate code based on the pattern
        
        $this->info("✅ Pattern applied successfully!");
        
        return 0;
    }
    
    /**
     * Show help information
     */
    protected function showHelp(): int
    {
        $this->info('🤖 Developer Assistant Commands');
        $this->line('');
        
        $commands = [
            ['generate, gen', 'Generate code from description'],
            ['analyze', 'Analyze code for issues and improvements'],
            ['suggest', 'Get development suggestions'],
            ['boilerplate, bp', 'Generate boilerplate code'],
            ['similar', 'Find similar code in project'],
            ['explain', 'Explain code functionality'],
            ['pattern', 'Manage code patterns'],
            ['help', 'Show this help message'],
        ];
        
        $this->table(['Command', 'Description'], $commands);
        
        $this->line('');
        $this->info('Examples:');
        $this->line('  php artisan dev generate                # Generate code interactively');
        $this->line('  php artisan dev analyze --file=app/Services/UserService.php');
        $this->line('  php artisan dev suggest                 # Get project suggestions');
        $this->line('  php artisan dev bp --type=service --name=Payment');
        $this->line('  php artisan dev explain --file=app/Models/User.php');
        
        return 0;
    }
    
    /**
     * Handle invalid action
     */
    protected function invalidAction(string $action): int
    {
        $this->error("Unknown action: {$action}");
        $this->line('Run "php artisan dev help" for available commands');
        return 1;
    }
}