<?php

namespace App\Livewire;

use Livewire\Component;

class TestLivewire extends Component
{
    public $counter = 0;
    
    public function increment()
    {
        $this->counter++;
    }
    
    public function render()
    {
        return <<<'blade'
            <div>
                <h1>Counter: {{ $counter }}</h1>
                <button wire:click="increment" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Increment
                </button>
            </div>
        blade;
    }
}