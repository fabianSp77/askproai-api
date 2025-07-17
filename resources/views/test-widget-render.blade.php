<!DOCTYPE html>
<html>
<head>
    <title>Widget Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
    @filamentStyles
    @vite('resources/css/app.css')
</head>
<body>
    <div style="padding: 20px;">
        <h1>Widget Render Test</h1>
        
        <div style="border: 2px solid red; padding: 20px; margin: 20px 0;">
            <h2>CallKpiWidget</h2>
            @livewire(\App\Filament\Admin\Widgets\CallKpiWidget::class)
        </div>
        
        <div style="border: 2px solid blue; padding: 20px; margin: 20px 0;">
            <h2>CallLiveStatusWidget</h2>
            @livewire(\App\Filament\Admin\Widgets\CallLiveStatusWidget::class)
        </div>
        
        <div style="border: 2px solid green; padding: 20px; margin: 20px 0;">
            <h2>GlobalFilterWidget</h2>
            @livewire(\App\Filament\Admin\Widgets\GlobalFilterWidget::class)
        </div>
        
        <div style="border: 2px solid orange; padding: 20px; margin: 20px 0;">
            <h2>CallAnalyticsWidget</h2>
            @livewire(\App\Filament\Admin\Resources\CallResource\Widgets\CallAnalyticsWidget::class)
        </div>
    </div>
    
    @livewireScripts
    @filamentScripts
    @vite('resources/js/app.js')
</body>
</html>