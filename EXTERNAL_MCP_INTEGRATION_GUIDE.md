# External MCP Integration Guide for AskProAI

## Overview
This guide explains how to integrate external Model Context Protocol (MCP) servers into the AskProAI platform. The requested MCP servers provide enhanced capabilities for Claude:

1. **sequential-thinking** - Step-by-step reasoning and problem-solving
2. **postgres** - Direct PostgreSQL database access and management
3. **effect-docs** - Documentation and effect handling
4. **taskmaster-ai** - Advanced task management and automation

## Prerequisites

- Node.js 18+ installed
- Docker (optional, for containerized MCPs)
- Access to the AskProAI server
- MCP client configuration in Claude

## Installation Guide

### 1. Sequential-Thinking MCP Server

Sequential Thinking facilitates detailed, step-by-step thinking processes for problem-solving and analysis.

#### Installation Options:

**Option A: NPM Installation**
```bash
# Install globally
npm install -g @modelcontextprotocol/server-sequential-thinking

# Or run directly with npx
npx -y @modelcontextprotocol/server-sequential-thinking
```

**Option B: Docker Installation**
```bash
docker run --rm -i mcp/sequentialthinking
```

#### Configuration:
Add to your Claude Desktop `config.json`:
```json
{
  "mcpServers": {
    "sequential-thinking": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-sequential-thinking"]
    }
  }
}
```

### 2. PostgreSQL MCP Server

The PostgreSQL MCP server connects AI models directly to PostgreSQL databases.

#### Installation:
```bash
# Install the PostgreSQL MCP server
npm install -g @modelcontextprotocol/server-postgres
```

#### Configuration:
Add to your Claude Desktop `config.json`:
```json
{
  "mcpServers": {
    "postgres": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-postgres"],
      "env": {
        "POSTGRES_HOST": "127.0.0.1",
        "POSTGRES_PORT": "3306",
        "POSTGRES_USER": "askproai_user",
        "POSTGRES_PASSWORD": "lkZ57Dju9EDjrMxn",
        "POSTGRES_DATABASE": "askproai_db"
      }
    }
  }
}
```

**Note**: For AskProAI, you're using MariaDB/MySQL, not PostgreSQL. You might need a MySQL MCP server instead.

### 3. Effect-Docs MCP Server

This server handles documentation and effect management.

#### Installation:
```bash
# Check if available in the MCP registry
npm search @modelcontextprotocol/server-effect-docs

# If available, install:
npm install -g @modelcontextprotocol/server-effect-docs
```

### 4. Taskmaster-AI MCP Server

Advanced task management and automation capabilities.

#### Installation:
```bash
# Check availability
npm search @modelcontextprotocol/server-taskmaster-ai

# If available, install:
npm install -g @modelcontextprotocol/server-taskmaster-ai
```

## Integration with AskProAI

### Step 1: Create External MCP Configuration

Create a new configuration file for external MCPs:

```php
// config/mcp-external.php
<?php

return [
    'external_servers' => [
        'sequential_thinking' => [
            'enabled' => env('MCP_SEQUENTIAL_THINKING_ENABLED', true),
            'command' => 'npx',
            'args' => ['-y', '@modelcontextprotocol/server-sequential-thinking'],
            'timeout' => 30,
        ],
        
        'postgres' => [
            'enabled' => env('MCP_POSTGRES_ENABLED', true),
            'command' => 'npx',
            'args' => ['-y', '@modelcontextprotocol/server-postgres'],
            'env' => [
                'POSTGRES_HOST' => env('DB_HOST', '127.0.0.1'),
                'POSTGRES_PORT' => env('DB_PORT', '3306'),
                'POSTGRES_USER' => env('DB_USERNAME'),
                'POSTGRES_PASSWORD' => env('DB_PASSWORD'),
                'POSTGRES_DATABASE' => env('DB_DATABASE'),
            ],
            'timeout' => 15,
        ],
        
        'effect_docs' => [
            'enabled' => env('MCP_EFFECT_DOCS_ENABLED', false),
            'command' => 'npx',
            'args' => ['-y', '@modelcontextprotocol/server-effect-docs'],
            'timeout' => 20,
        ],
        
        'taskmaster_ai' => [
            'enabled' => env('MCP_TASKMASTER_ENABLED', false),
            'command' => 'npx',
            'args' => ['-y', '@modelcontextprotocol/server-taskmaster-ai'],
            'timeout' => 30,
        ],
    ],
];
```

### Step 2: Create External MCP Manager

```php
// app/Services/MCP/ExternalMCPManager.php
<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class ExternalMCPManager
{
    protected array $config;
    protected array $runningServers = [];

    public function __construct()
    {
        $this->config = config('mcp-external.external_servers', []);
    }

    public function startServer(string $name): bool
    {
        if (!isset($this->config[$name]) || !$this->config[$name]['enabled']) {
            return false;
        }

        $serverConfig = $this->config[$name];
        
        try {
            $process = Process::command(
                array_merge([$serverConfig['command']], $serverConfig['args'])
            );

            if (isset($serverConfig['env'])) {
                $process->env($serverConfig['env']);
            }

            $process->timeout($serverConfig['timeout'] ?? 30);
            $process->start();

            $this->runningServers[$name] = $process;
            
            Log::info("Started external MCP server: {$name}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to start external MCP server {$name}: " . $e->getMessage());
            return false;
        }
    }

    public function stopServer(string $name): bool
    {
        if (!isset($this->runningServers[$name])) {
            return false;
        }

        try {
            $this->runningServers[$name]->stop();
            unset($this->runningServers[$name]);
            
            Log::info("Stopped external MCP server: {$name}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to stop external MCP server {$name}: " . $e->getMessage());
            return false;
        }
    }

    public function getStatus(): array
    {
        $status = [];
        
        foreach ($this->config as $name => $config) {
            $status[$name] = [
                'enabled' => $config['enabled'],
                'running' => isset($this->runningServers[$name]) && 
                           $this->runningServers[$name]->running(),
            ];
        }
        
        return $status;
    }
}
```

### Step 3: Create Artisan Command

```php
// app/Console/Commands/MCPExternalServers.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\ExternalMCPManager;

class MCPExternalServers extends Command
{
    protected $signature = 'mcp:external {action : start|stop|status} {server? : Server name}';
    protected $description = 'Manage external MCP servers';

    public function handle(ExternalMCPManager $manager)
    {
        $action = $this->argument('action');
        $server = $this->argument('server');

        switch ($action) {
            case 'start':
                if ($server) {
                    $success = $manager->startServer($server);
                    $this->info($success ? "Started {$server}" : "Failed to start {$server}");
                } else {
                    $this->info("Starting all enabled external MCP servers...");
                    foreach (config('mcp-external.external_servers', []) as $name => $config) {
                        if ($config['enabled']) {
                            $manager->startServer($name);
                        }
                    }
                }
                break;

            case 'stop':
                if ($server) {
                    $success = $manager->stopServer($server);
                    $this->info($success ? "Stopped {$server}" : "Failed to stop {$server}");
                } else {
                    $this->info("Stopping all external MCP servers...");
                    foreach (array_keys($manager->getStatus()) as $name) {
                        $manager->stopServer($name);
                    }
                }
                break;

            case 'status':
                $status = $manager->getStatus();
                $this->table(
                    ['Server', 'Enabled', 'Running'],
                    collect($status)->map(function ($info, $name) {
                        return [
                            $name,
                            $info['enabled'] ? '✓' : '✗',
                            $info['running'] ? '✓' : '✗',
                        ];
                    })->toArray()
                );
                break;

            default:
                $this->error("Invalid action. Use: start, stop, or status");
        }
    }
}
```

### Step 4: Update .env Configuration

Add to your `.env` file:
```bash
# External MCP Configuration
MCP_SEQUENTIAL_THINKING_ENABLED=true
MCP_POSTGRES_ENABLED=true
MCP_EFFECT_DOCS_ENABLED=false  # Enable when available
MCP_TASKMASTER_ENABLED=false   # Enable when available
```

## Usage

### Starting External MCP Servers
```bash
# Start all enabled external MCP servers
php artisan mcp:external start

# Start specific server
php artisan mcp:external start sequential_thinking

# Check status
php artisan mcp:external status

# Stop servers
php artisan mcp:external stop
```

### Claude Desktop Configuration

Add to your Claude Desktop `config.json` (typically in `~/.config/claude/config.json`):

```json
{
  "mcpServers": {
    "askproai-webhook": {
      "command": "php",
      "args": ["/var/www/api-gateway/artisan", "mcp:webhook"]
    },
    "askproai-calcom": {
      "command": "php",
      "args": ["/var/www/api-gateway/artisan", "mcp:calcom"]
    },
    "askproai-database": {
      "command": "php",
      "args": ["/var/www/api-gateway/artisan", "mcp:database"]
    },
    "sequential-thinking": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-sequential-thinking"]
    },
    "postgres": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-postgres"],
      "env": {
        "POSTGRES_HOST": "127.0.0.1",
        "POSTGRES_PORT": "3306",
        "POSTGRES_USER": "askproai_user",
        "POSTGRES_PASSWORD": "lkZ57Dju9EDjrMxn",
        "POSTGRES_DATABASE": "askproai_db"
      }
    }
  }
}
```

## Benefits of Each MCP Server

### Sequential-Thinking
- **Enhanced Reasoning**: Step-by-step problem decomposition
- **Better Planning**: Structured approach to complex tasks
- **Improved Accuracy**: Reduces errors through systematic thinking

### PostgreSQL/Database MCP
- **Direct SQL Access**: Execute queries without going through the application
- **Schema Analysis**: Understand database structure
- **Performance Tuning**: Analyze query performance directly

### Effect-Docs
- **Documentation Generation**: Auto-generate API docs
- **Effect Tracking**: Monitor side effects of operations
- **Knowledge Management**: Better documentation handling

### Taskmaster-AI
- **Advanced Task Management**: Complex task orchestration
- **Automation**: Streamline repetitive tasks
- **Progress Tracking**: Better visibility into task completion

## Troubleshooting

1. **MCP Server Not Starting**
   - Check Node.js version: `node --version` (should be 18+)
   - Verify npm packages are installed
   - Check permissions and paths

2. **Connection Issues**
   - Verify server is running: `php artisan mcp:external status`
   - Check logs: `tail -f storage/logs/laravel.log`
   - Ensure firewall allows connections

3. **Database MCP Issues**
   - Verify database credentials in `.env`
   - Note: AskProAI uses MySQL/MariaDB, not PostgreSQL
   - Consider using a MySQL MCP server instead

## Next Steps

1. Install the sequential-thinking MCP server first (most useful)
2. Test the integration with simple commands
3. Consider creating a MySQL-specific MCP server for AskProAI
4. Monitor performance and adjust timeouts as needed