<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class MiddlewareStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.middleware-status-widget';

    public function getMiddlewareStatus(): array
    {
        // Prüfe, ob ein Service-File existiert
        $exists = file_exists(app_path('Services/RetellAIService.php'));
        return [
            'active' => $exists,
            'desc' => $exists ? 'Middleware zwischen RetellAI und Cal.com gefunden' : 'Keine Middleware-Integration gefunden!',
        ];
    }
}
