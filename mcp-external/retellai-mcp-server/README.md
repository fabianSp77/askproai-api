# Retell.ai MCP Server for AskProAI

This is the external MCP (Model Context Protocol) server that bridges AskProAI with Retell.ai's voice capabilities.

## Quick Start

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your API keys
   ```

3. **Start the server:**
   ```bash
   # Development
   npm run dev
   
   # Production
   npm start
   ```

## Configuration

Required environment variables in `.env`:

```env
RETELLAI_API_KEY=your_retell_api_key
MCP_SERVER_PORT=3001
MCP_SERVER_HOST=localhost
LARAVEL_API_URL=http://localhost:8000
LARAVEL_API_TOKEN=your_internal_token
```

## API Endpoints

- `GET /health` - Health check
- `GET /mcp/tools` - List available MCP tools
- `POST /mcp/execute` - Execute MCP tool
- `POST /mcp/call/create` - Create outbound call

## Integration with Laravel

This server communicates with Laravel through:
1. **Incoming requests**: Laravel's `RetellAIBridgeMCPServer` calls this server
2. **Webhook notifications**: This server notifies Laravel about call events

## PM2 Production Setup

```bash
# Install PM2 globally
npm install -g pm2

# Start the server
pm2 start src/index.js --name retell-mcp-server

# Save PM2 configuration
pm2 save
pm2 startup

# View logs
pm2 logs retell-mcp-server
```

## Troubleshooting

1. **Port already in use**: Change `MCP_SERVER_PORT` in `.env`
2. **Connection refused**: Ensure server is running and accessible
3. **Authentication errors**: Verify `RETELLAI_API_KEY` is correct

## Development

To add new MCP tools:
1. Extend the RetellAIMCPServer class
2. Add tool methods following MCP protocol
3. Update Laravel's bridge server accordingly

For more details, see the main documentation at `/RETELL_AI_MCP_INTEGRATION_GUIDE.md`