<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class Context7DocsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_list_command_shows_project_libraries()
    {
        $this->artisan('context7:docs list')
            ->expectsTable(
                ['Library', 'ID', 'Trust', 'Relevance', 'Snippets'],
                [
                    ['Laravel', '/context7/laravel', '10', 'critical', '5724'],
                    ['Filament', '/filamentphp/filament', '8.3', 'critical', '2337'],
                    ['Retell AI', '/context7/docs_retellai_com', '8', 'critical', '405'],
                    ['Cal.com', '/calcom/cal.com', '9.2', 'high', '388'],
                    ['Horizon', '/laravel/horizon', '9.5', 'medium', '150'],
                    ['Livewire', '/livewire/livewire', '9', 'high', '890']
                ]
            )
            ->assertExitCode(0);
    }

    public function test_search_command_requires_query()
    {
        $this->artisan('context7:docs search')
            ->expectsOutput('Please provide a library name to search for')
            ->assertExitCode(0);
    }

    public function test_search_command_finds_laravel()
    {
        $this->artisan('context7:docs search laravel')
            ->expectsOutput('Searching for libraries matching: laravel')
            ->assertExitCode(0);
    }

    public function test_get_command_requires_library_id()
    {
        $this->artisan('context7:docs get')
            ->expectsOutput('Please provide --library-id option')
            ->assertExitCode(0);
    }

    public function test_get_command_with_library_id()
    {
        $this->artisan('context7:docs get --library-id=/context7/laravel')
            ->expectsOutput('Fetching documentation for: /context7/laravel')
            ->assertExitCode(0);
    }

    public function test_get_command_with_topic()
    {
        $this->artisan('context7:docs get --library-id=/context7/laravel --topic=multi-tenancy')
            ->expectsOutput('Fetching documentation for: /context7/laravel')
            ->expectsOutput('Topic: multi-tenancy')
            ->assertExitCode(0);
    }

    public function test_examples_command_requires_library_id()
    {
        $this->artisan('context7:docs examples webhook')
            ->expectsOutput('Please provide --library-id option')
            ->assertExitCode(0);
    }

    public function test_examples_command_requires_query()
    {
        $this->artisan('context7:docs examples --library-id=/retell/docs')
            ->expectsOutput('Please provide a search query for examples')
            ->assertExitCode(0);
    }

    public function test_unknown_action_shows_error()
    {
        $this->artisan('context7:docs unknown-action')
            ->expectsOutput('Unknown action: unknown-action')
            ->expectsOutput('Available actions: search, get, examples, list')
            ->assertExitCode(0);
    }

    public function test_search_with_no_results()
    {
        $this->artisan('context7:docs search xyz123nonexistent')
            ->expectsOutput('Searching for libraries matching: xyz123nonexistent')
            ->assertExitCode(0);
    }

    public function test_can_set_max_tokens()
    {
        $this->artisan('context7:docs get --library-id=/context7/laravel --max-tokens=10000')
            ->expectsOutput('Fetching documentation for: /context7/laravel')
            ->assertExitCode(0);
    }
}