<?php

namespace App\Livewire;

use Livewire\Component;

class TestDebug extends Component
{
    public $message = 'Initial message';
    
    public function mount()
    {
        $this->message = 'Component mounted successfully';
    }
    
    public function testUpdate()
    {
        $this->message = 'Update successful at ' . now();
    }
    
    public function render()
    {
        return <<<'HTML'
        <div>
            <h1>Livewire Test Component</h1>
            <p>{{ $message }}</p>
            <button wire:click="testUpdate" class="bg-blue-500 text-white px-4 py-2 rounded">
                Test Update
            </button>
        </div>
        HTML;
    }
}