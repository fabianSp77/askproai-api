<?php

namespace App\Services\MCP;

use Illuminate\Support\Collection;

class MCPServiceRegistry
{
    protected array $services = [];
    
    public function __construct()
    {
        $this->registerDefaultServices();
    }
    
    protected function registerDefaultServices(): void
    {
        $this->services = [
            'webhook' => app(WebhookMCPServer::class),
            'calcom' => app(CalcomMCPServer::class),
            'database' => app(DatabaseMCPServer::class),
            'queue' => app(QueueMCPServer::class),
            'retell' => app(RetellMCPServer::class),
            'stripe' => app(StripeMCPServer::class),
            // 'knowledge' => app(KnowledgeMCPServer::class), // Commented out temporarily
        ];
    }
    
    public function register(string $name, $service): void
    {
        $this->services[$name] = $service;
    }
    
    public function get(string $name)
    {
        return $this->services[$name] ?? null;
    }
    
    public function getService(string $name)
    {
        return $this->get($name);
    }
    
    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }
    
    public function getAllServices(): array
    {
        return $this->services;
    }
    
    public function getServiceNames(): array
    {
        return array_keys($this->services);
    }
    
    public function getHealthStatus(): array
    {
        $status = [];
        
        foreach ($this->services as $name => $service) {
            try {
                if (method_exists($service, 'healthCheck')) {
                    $health = $service->healthCheck();
                    $status[$name] = [
                        'status' => $health['status'] ?? false ? 'healthy' : 'unhealthy',
                        'message' => $health['message'] ?? 'No message',
                        'checked_at' => now()->toIso8601String(),
                    ];
                } else {
                    $status[$name] = [
                        'status' => 'unknown',
                        'message' => 'Health check not implemented',
                        'checked_at' => now()->toIso8601String(),
                    ];
                }
            } catch (\Exception $e) {
                $status[$name] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'checked_at' => now()->toIso8601String(),
                ];
            }
        }
        
        return $status;
    }
}