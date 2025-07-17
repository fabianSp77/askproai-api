<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A simple test to verify the test infrastructure works.
     */
    public function test_basic_application_boot(): void
    {
        $response = $this->get('/');

        // Root redirects to /admin in this app
        $response->assertStatus(302);
        $response->assertRedirect('/admin');
    }

    /**
     * Test database connection works.
     */
    public function test_database_connection(): void
    {
        // Check that migrations ran successfully
        $migrationCount = \DB::table('migrations')->count();
        $this->assertGreaterThan(100, $migrationCount, 'Migrations should have run');
        
        // Test we can create a simple model
        $company = \App\Models\Company::factory()->create();
        $this->assertDatabaseHas('companies', [
            'id' => $company->id
        ]);
        
        // Clean up
        $company->delete();
    }
}