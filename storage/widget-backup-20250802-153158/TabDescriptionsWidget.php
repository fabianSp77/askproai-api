<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class TabDescriptionsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.tab-descriptions';
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $activeTab = null;
    
    public array $tabs = [];
    
    public function mount(?string $activeTab = null, array $tabs = []): void
    {
        $this->activeTab = $activeTab;
        $this->tabs = $tabs;
    }
    
    public function getViewData(): array
    {
        return [
            'activeTab' => $this->activeTab,
            'tabs' => $this->tabs,
        ];
    }
}