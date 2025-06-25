<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class TestLivewirePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Test Livewire';
    protected static ?string $navigationGroup = 'Control Center';
    protected static ?int $navigationSort = 99;
    
    protected static string $view = 'filament.admin.pages.test-livewire-page';
    
    public string $message = 'Initial message';
    public int $counter = 0;
    public bool $dataLoaded = false;
    
    public function mount(): void
    {
        Log::info('TestLivewirePage - mount() called');
        $this->message = 'Component mounted';
    }
    
    public function loadData(): void
    {
        Log::info('TestLivewirePage - loadData() called');
        $this->message = 'Data loaded via wire:init!';
        $this->dataLoaded = true;
        $this->counter = 42;
    }
    
    public function increment(): void
    {
        $this->counter++;
        $this->message = "Counter incremented to {$this->counter}";
        Log::info('TestLivewirePage - increment() called', ['counter' => $this->counter]);
    }
}