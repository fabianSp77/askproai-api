#!/usr/bin/env node

const { Server } = require('@modelcontextprotocol/server-memory');
const { createServer } = require('@modelcontextprotocol/sdk');

async function main() {
  // Initialize the Memory Bank server
  const memoryServer = new Server({
    name: 'memory-bank',
    version: '1.0.0',
    description: 'Persistent memory for Claude Code sessions',
    capabilities: ['context_retention', 'memory_storage', 'session_management']
  });

  // Create MCP server
  const server = createServer(memoryServer);
  
  // Add tools
  server.setRequestHandler('tools/list', async () => ({
    tools: [
      {
        name: 'store_memory',
        description: 'Store information in persistent memory',
        inputSchema: {
          type: 'object',
          properties: {
            key: {
              type: 'string',
              description: 'Unique key for the memory'
            },
            value: {
              type: 'object',
              description: 'Information to store'
            },
            context: {
              type: 'string',
              description: 'Context or category for the memory'
            }
          },
          required: ['key', 'value']
        }
      },
      {
        name: 'retrieve_memory',
        description: 'Retrieve information from memory',
        inputSchema: {
          type: 'object',
          properties: {
            key: {
              type: 'string',
              description: 'Key of the memory to retrieve'
            },
            context: {
              type: 'string',
              description: 'Optional context filter'
            }
          },
          required: ['key']
        }
      },
      {
        name: 'search_memories',
        description: 'Search memories by pattern or context',
        inputSchema: {
          type: 'object',
          properties: {
            query: {
              type: 'string',
              description: 'Search query'
            },
            context: {
              type: 'string',
              description: 'Optional context filter'
            },
            limit: {
              type: 'integer',
              default: 10
            }
          },
          required: ['query']
        }
      },
      {
        name: 'list_contexts',
        description: 'List all available memory contexts',
        inputSchema: {
          type: 'object',
          properties: {}
        }
      },
      {
        name: 'clear_context',
        description: 'Clear all memories in a specific context',
        inputSchema: {
          type: 'object',
          properties: {
            context: {
              type: 'string',
              description: 'Context to clear'
            }
          },
          required: ['context']
        }
      },
      {
        name: 'get_session_summary',
        description: 'Get a summary of the current session memories',
        inputSchema: {
          type: 'object',
          properties: {
            include_contexts: {
              type: 'array',
              items: { type: 'string' },
              description: 'Contexts to include in summary'
            }
          }
        }
      }
    ]
  }));

  // Handle tool calls
  server.setRequestHandler('tools/call', async (request) => {
    const { name, arguments: args } = request.params;
    
    try {
      switch (name) {
        case 'store_memory':
          return memoryServer.storeMemory(args);
        case 'retrieve_memory':
          return memoryServer.retrieveMemory(args);
        case 'search_memories':
          return memoryServer.searchMemories(args);
        case 'list_contexts':
          return memoryServer.listContexts();
        case 'clear_context':
          return memoryServer.clearContext(args);
        case 'get_session_summary':
          return memoryServer.getSessionSummary(args);
        default:
          throw new Error(`Unknown tool: ${name}`);
      }
    } catch (error) {
      return {
        success: false,
        error: error.message
      };
    }
  });

  // Start the server
  const transport = server.createStdioTransport();
  await server.connect(transport);
  
  console.error('Memory Bank MCP Server running...');
}

main().catch(console.error);