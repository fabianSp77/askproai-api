<?php

namespace App\Services\Calendar;

use Illuminate\Support\Facades\Log;

abstract class BaseCalendarService implements CalendarInterface
{
    protected $apiKey;
    protected $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->apiKey = $config['api_key'] ?? null;
    }
    
    protected function logError(string $message, array $context = []): void
    {
        Log::error("[{$this->getProviderName()}] $message", $context);
    }
    
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("[{$this->getProviderName()}] $message", $context);
    }
}
