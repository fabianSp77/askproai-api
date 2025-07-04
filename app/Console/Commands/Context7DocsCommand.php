<?php

namespace App\Console\Commands;

use App\Services\Context7Service;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;

class Context7DocsCommand extends Command
{
    protected $signature = 'context7:docs 
                            {action : search|get|examples|list}
                            {query? : Library name or ID to search for}
                            {--topic= : Specific topic to focus on}
                            {--max-tokens=5000 : Maximum tokens to retrieve}
                            {--library-id= : Library ID for get/examples actions}';

    protected $description = 'Access Context7 documentation for project libraries';

    protected Context7Service $context7Service;

    public function __construct(Context7Service $context7Service)
    {
        parent::__construct();
        $this->context7Service = $context7Service;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $query = $this->argument('query');

        switch ($action) {
            case 'search':
                $this->searchLibrary($query);
                break;
            
            case 'get':
                $this->getDocumentation();
                break;
            
            case 'examples':
                $this->getCodeExamples();
                break;
            
            case 'list':
                $this->listProjectLibraries();
                break;
            
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: search, get, examples, list");
        }
    }

    /**
     * Search for a library
     */
    protected function searchLibrary(?string $query)
    {
        if (!$query) {
            $this->error('Please provide a library name to search for');
            return;
        }

        $this->info("Searching for libraries matching: {$query}");
        
        $results = $this->context7Service->searchLibrary($query);
        
        if (empty($results)) {
            $this->warn("No libraries found matching '{$query}'");
            return;
        }

        $table = new Table($this->output);
        $table->setHeaders(['Library', 'ID', 'Trust Score', 'Description']);
        
        foreach ($results as $library) {
            $table->addRow([
                $library['name'],
                $library['library_id'],
                $library['trust_score'],
                $this->truncate($library['description'], 50)
            ]);
        }
        
        $table->render();
    }

    /**
     * Get documentation for a library
     */
    protected function getDocumentation()
    {
        $libraryId = $this->option('library-id');
        if (!$libraryId) {
            $this->error('Please provide --library-id option');
            return;
        }

        $topic = $this->option('topic');
        $maxTokens = (int) $this->option('max-tokens');

        $this->info("Fetching documentation for: {$libraryId}");
        if ($topic) {
            $this->info("Topic: {$topic}");
        }

        $docs = $this->context7Service->getLibraryDocs($libraryId, $topic, $maxTokens);
        
        $this->line('');
        $this->line($docs['content']);
        $this->line('');
        
        $this->info("Snippets available: {$docs['snippets_count']}");
    }

    /**
     * Get code examples
     */
    protected function getCodeExamples()
    {
        $libraryId = $this->option('library-id');
        $query = $this->argument('query');
        
        if (!$libraryId) {
            $this->error('Please provide --library-id option');
            return;
        }
        
        if (!$query) {
            $this->error('Please provide a search query for examples');
            return;
        }

        $this->info("Searching for code examples in {$libraryId} for: {$query}");
        
        $examples = $this->context7Service->searchCodeExamples($libraryId, $query);
        
        if (empty($examples)) {
            $this->warn("No code examples found");
            return;
        }

        foreach ($examples as $example) {
            $this->line('');
            $this->comment("=== {$example['title']} ===");
            $this->line($example['code']);
        }
    }

    /**
     * List project libraries
     */
    protected function listProjectLibraries()
    {
        $this->info("Libraries used in AskProAI project:");
        
        $libraries = [
            ['Laravel', '/context7/laravel', '10', 'critical', '5724'],
            ['Filament', '/filamentphp/filament', '8.3', 'critical', '2337'],
            ['Retell AI', '/context7/docs_retellai_com', '8', 'critical', '405'],
            ['Cal.com', '/calcom/cal.com', '9.2', 'high', '388'],
            ['Horizon', '/laravel/horizon', '9.5', 'medium', '150'],
            ['Livewire', '/livewire/livewire', '9', 'high', '890']
        ];

        $table = new Table($this->output);
        $table->setHeaders(['Library', 'ID', 'Trust', 'Relevance', 'Snippets']);
        
        foreach ($libraries as $lib) {
            $table->addRow($lib);
        }
        
        $table->render();
        
        $this->line('');
        $this->info('Use "php artisan context7:docs get --library-id=<id>" to get documentation');
        $this->info('Use "php artisan context7:docs examples <query> --library-id=<id>" to search code examples');
    }

    /**
     * Truncate string to specified length
     */
    protected function truncate(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - 3) . '...';
    }
}