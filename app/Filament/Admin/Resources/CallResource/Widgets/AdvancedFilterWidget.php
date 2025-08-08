<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class AdvancedFilterWidget extends Widget
{
    protected static string $view = 'filament.admin.resources.call-resource.widgets.advanced-filter-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    public $filters = [];
    
    #[On('applyCallFilters')]
    public function handleFilterUpdate($filters)
    {
        $this->filters = $filters;
        
        // Emit to the table to apply filters
        $this->dispatch('filterCallTable', filters: $filters);
    }
}