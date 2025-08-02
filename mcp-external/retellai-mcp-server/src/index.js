require('dotenv').config();
const express = require('express');
const axios = require('axios');
const crypto = require('crypto');
const { RetellAIMCPServer } = require('@abhaybabbar/retellai-mcp-server');

const app = express();
app.use(express.json());

// Generate webhook signature for security
function generateWebhookSignature(payload) {
  const secret = process.env.MCP_WEBHOOK_SECRET || process.env.LARAVEL_API_TOKEN;
  const timestamp = Math.floor(Date.now() / 1000);
  const payloadString = JSON.stringify(payload);
  
  // Create signature
  const signaturePayload = `${timestamp}.${payloadString}`;
  const signature = crypto.createHmac('sha256', secret).update(signaturePayload).digest('hex');
  
  return {
    'X-MCP-Signature': signature,
    'X-MCP-Timestamp': timestamp.toString()
  };
}

// Initialize Retell.ai MCP Server
const retellMCPServer = new RetellAIMCPServer({
  apiKey: process.env.RETELLAI_API_KEY
});

// Bridge endpoint to handle MCP requests from Laravel
app.post('/mcp/execute', async (req, res) => {
  try {
    const { tool, params, metadata } = req.body;
    
    // Validate request
    if (!tool || !params) {
      return res.status(400).json({
        error: 'Missing required parameters: tool and params'
      });
    }

    // Execute the MCP tool
    const result = await retellMCPServer.executeTool(tool, params);
    
    // Send result back to Laravel
    res.json({
      success: true,
      result,
      metadata: {
        ...metadata,
        executedAt: new Date().toISOString()
      }
    });
    
  } catch (error) {
    console.error('MCP execution error:', error);
    res.status(500).json({
      success: false,
      error: error.message,
      stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
    });
  }
});

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    service: 'retellai-mcp-server',
    timestamp: new Date().toISOString(),
    config: {
      hasApiKey: !!process.env.RETELLAI_API_KEY,
      port: process.env.MCP_SERVER_PORT || 3001
    }
  });
});

// List available tools
app.get('/mcp/tools', async (req, res) => {
  try {
    const tools = retellMCPServer.getAvailableTools();
    res.json({
      success: true,
      tools,
      count: tools.length
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Tool-specific endpoints for better integration
app.post('/mcp/call/create', async (req, res) => {
  try {
    const result = await retellMCPServer.executeTool('create_call', req.body);
    
    // Notify Laravel about the new call
    if (process.env.LARAVEL_API_URL) {
      const webhookData = {
        callId: result.call_id,
        params: req.body,
        timestamp: new Date().toISOString()
      };
      
      // Generate webhook signature
      const webhookHeaders = generateWebhookSignature(webhookData);
      
      await axios.post(
        `${process.env.LARAVEL_API_URL}/api/mcp/retell/call-created`,
        webhookData,
        {
          headers: {
            'Authorization': `Bearer ${process.env.LARAVEL_API_TOKEN}`,
            'Content-Type': 'application/json',
            ...webhookHeaders
          }
        }
      );
    }
    
    res.json({ success: true, result });
  } catch (error) {
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Start server
const PORT = process.env.MCP_SERVER_PORT || 3001;
app.listen(PORT, () => {
  console.log(`Retell.ai MCP Server running on port ${PORT}`);
  console.log(`Health check: http://localhost:${PORT}/health`);
});