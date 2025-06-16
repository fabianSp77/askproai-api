<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class TestWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.test-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -1;
    
    public function mount(): void
    {
        $this->testData = [
            'message' => 'Test Widget is Working!',
            'time' => now()->format('Y-m-d H:i:s'),
            'user' => auth()->user()?->email ?? 'Not logged in',
        ];
    }
}