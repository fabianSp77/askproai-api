<?php

namespace App\Providers;

use App\Mail\Transport\ResendTransport;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;

class ResendMailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Mail::extend('resend', function (array $config = []) {
            $apiKey = $config['key'] ?? config('services.resend.key');
            
            if (!$apiKey) {
                throw new \InvalidArgumentException('Resend API key is not configured.');
            }
            
            return new ResendTransport($apiKey);
        });
    }
}