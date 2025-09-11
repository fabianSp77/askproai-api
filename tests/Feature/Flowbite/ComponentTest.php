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