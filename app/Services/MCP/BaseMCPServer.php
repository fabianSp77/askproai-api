<?php

namespace App\Services\MCP;

abstract class BaseMCPServer
{
    protected string $name;
    protected string $version = '1.0.0';
    protected array $tools = [];
    
    abstract public function getName(): string;
    abstract public function getVersion(): string;
    abstract public function getTools(): array;
    
    public function __construct()
    {
        $this->name = $this->getName();
        $this->tools = $this->getTools();
    }
    
    public function handleRequest(array $request): array
    {
        $method = $request['method'] ?? '';
        
        return match ($method) {
            'initialize' => $this->handleInitialize(),
            'tools/list' => $this->handleToolsList(),
            'tools/call' => $this->handleToolCall($request),
            default => [
                'error' => [
                    'code' => -32601,
                    'message' => 'Method not found'
                ]
            ]
        };
    }
    
    protected function handleInitialize(): array
    {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => []
            ],
            'serverInfo' => [
                'name' => $this->name,
                'version' => $this->version
            ]
        ];
    }
    
    protected function handleToolsList(): array
    {
        return [
            'tools' => $this->tools
        ];
    }
    
    protected function handleToolCall(array $request): array
    {
        $toolName = $request['params']['name'] ?? '';
        $arguments = $request['params']['arguments'] ?? [];
        
        $methodName = 'handle' . str_replace('-', '', ucwords($toolName, '-'));
        
        if (!method_exists($this, $methodName)) {
            return [
                'error' => [
                    'code' => -32602,
                    'message' => "Tool '$toolName' not found"
                ]
            ];
        }
        
        try {
            $result = $this->$methodName($arguments);
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT)
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'error' => [
                    'code' => -32603,
                    'message' => $e->getMessage()
                ]
            ];
        }
    }
}