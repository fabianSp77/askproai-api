<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(Router $router): void
    {
        /* Alias **jedes Mal** beim Booten registrieren  */
        $router->aliasMiddleware(
            'calcom.signature',
            \App\Http\Middleware\VerifyCalcomSignature::class
        );
    }
}
