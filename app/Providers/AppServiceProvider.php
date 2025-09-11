<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;
use App\View\SafeBladeCompiler;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Override the default Blade compiler with our safe version
        $this->app->singleton('blade.compiler', function ($app) {
            return new SafeBladeCompiler(
                $app['files'],
                $app['config']['view.compiled'],
                $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                $app['config']->get('view.cache', true),
                $app['config']->get('view.compiled_extension', 'php'),
            );
        });
    }

    public function boot(Router $router): void
    {
        // Set Carbon locale to German
        \Carbon\Carbon::setLocale('de');
        /* Alias **jedes Mal** beim Booten registrieren  */
        $router->aliasMiddleware(
            'calcom.signature',
            \App\Http\Middleware\VerifyCalcomSignature::class
        );
        
        // Register Livewire components
        Livewire::component('retell-agent-viewer', \App\Livewire\RetellAgentViewer::class);
        Livewire::component('call-viewer', \App\Livewire\CallViewer::class);
        Livewire::component('audio-waveform-player', \App\Livewire\AudioWaveformPlayer::class);
        
        // Register model observers
        \App\Models\Transaction::observe(\App\Observers\TransactionObserver::class);
        
        // Register Flowbite CSS and JS using Filament hooks
        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn (): HtmlString => new HtmlString('<link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" />')
        );
        
        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            fn (): HtmlString => new HtmlString('
                <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
                <script>
                    document.addEventListener("livewire:navigated", () => {
                        if (typeof initFlowbite !== "undefined") {
                            initFlowbite();
                        }
                    });
                    document.addEventListener("DOMContentLoaded", () => {
                        if (typeof initFlowbite !== "undefined") {
                            initFlowbite();
                        }
                    });
                </script>
            ')
        );
    }
}
