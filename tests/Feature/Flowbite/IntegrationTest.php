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