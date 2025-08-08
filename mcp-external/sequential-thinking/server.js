#!/usr/bin/env node

const { Server } = require('@modelcontextprotocol/server-sequential-thinking');

// Initialize the server
const server = new Server({
    name: 'sequential-thinking',
    version: '1.0.0',
    description: 'Sequential thinking and problem-solving MCP server'
});

// Handle tool requests
server.on('tools/call', async (request) => {
    const { tool, arguments: args } = request.params;
    
    if (tool === 'sequential_thinking') {
        try {
            const result = await server.processSequentialThinking(args);
            return {
                success: true,
                result
            };
        } catch (error) {
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    throw new Error(`Unknown tool: ${tool}`);
});

// Start the server
server.start().catch(console.error);

console.error('Sequential Thinking MCP Server started');