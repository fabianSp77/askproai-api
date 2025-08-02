# Retell.ai MCP Server Integration Guide

> 🚀 **Status**: Implementation Complete (2025-07-22)
> 
> This guide documents the integration of the @abhaybabbar/retellai-mcp-server with AskProAI's admin portal to enable AI-initiated outbound calls.

## 📋 Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Setup Instructions](#setup-instructions)
- [Features](#features)
- [Usage Guide](#usage-guide)
- [API Reference](#api-reference)
- [Troubleshooting](#troubleshooting)
- [Future Enhancements](#future-enhancements)

## Overview

The Retell.ai MCP (Model Context Protocol) Server integration enables AI assistants to:
- Initiate outbound phone calls programmatically
- Manage call campaigns for bulk outreach
- Test voice configurations in real-time
- Monitor call performance and analytics

### Key Benefits

- **Proactive Customer Engagement**: AI can call customers for follow-ups, reminders, and surveys
- **Automated Campaigns**: Run bulk calling campaigns with dynamic variables
- **Voice Testing**: Test and optimize AI agent configurations before deployment
- **Seamless Integration**: Works with existing multi-tenant architecture

## Architecture

```
┌─────────────────────┐     ┌─────────────────────┐     ┌──────────────────┐
│   Admin Portal      │────▶│  RetellAIBridgeMCP  │────▶│ External MCP     │
│  (Filament/React)   │     │    (Laravel)        │     │  Server (Node)   │
└─────────────────────┘     └─────────────────────┘     └──────────────────┘
                                       │                          │
                                       ▼                          ▼
                            ┌─────────────────────┐     ┌──────────────────┐
                            │  Database (MySQL)   │     │  Retell.ai API   │
                            └─────────────────────┘     └──────────────────┘
```

### Components

1. **External MCP Server** (`/mcp-external/retellai-mcp-server/`)
   - Node.js server implementing MCP protocol
   - Interfaces with @abhaybabbar/retellai-mcp-server
   - Handles communication with Retell.ai API

2. **RetellAIBridgeMCPServer** (`app/Services/MCP/RetellAIBridgeMCPServer.php`)
   - Laravel service bridging internal system with external MCP
   - Handles authentication, multi-tenancy, and data persistence
   - Provides high-level methods for call and campaign management

3. **Admin Portal Components**
   - **AICallCenter Page**: Central dashboard for outbound calls
   - **CallInitiatorWidget**: Reusable widget for quick calls
   - **VoiceTestConsole**: Testing interface for voice configurations

## Setup Instructions

### 1. Install External MCP Server

```bash
# Navigate to MCP server directory
cd /var/www/api-gateway/mcp-external/retellai-mcp-server

# Install dependencies
npm install

# Copy environment file
cp .env.example .env

# Edit .env with your Retell.ai API key
RETELLAI_API_KEY=your_actual_api_key_here
MCP_SERVER_PORT=3001
LARAVEL_API_URL=http://localhost:8000
LARAVEL_API_TOKEN=your_internal_token_here
```

### 2. Start the MCP Server

```bash
# Development mode
npm run dev

# Production mode with PM2
pm2 start src/index.js --name retell-mcp-server
pm2 save
pm2 startup
```

### 3. Configure Laravel Environment

Add to your `.env` file:

```env
# Retell MCP Configuration
RETELL_MCP_SERVER_URL=http://localhost:3001
RETELL_MCP_SERVER_TOKEN=your_internal_token_here
RETELL_MCP_ENABLED=true
RETELL_MCP_TIMEOUT=30
RETELL_DEFAULT_FROM_NUMBER=+49123456789
```

### 4. Run Database Migrations

```bash
php artisan migrate
```

### 5. Access the AI Call Center

Navigate to `/admin` and look for "AI Call Center" in the sidebar under "AI Tools".

## Features

### 1. Quick Call Initiation

Make single outbound calls with specific purposes:
- Follow-up calls
- Appointment reminders
- Feedback collection
- Custom messages

```php
// Using CallInitiatorWidget in any Filament page
@livewire('call-initiator-widget', [
    'customerId' => $customer->id,
    'defaultPurpose' => 'appointment_reminder'
])
```

### 2. Call Campaigns

Create and manage bulk calling campaigns:
- Target all customers, inactive customers, or custom lists
- Schedule campaigns for immediate or future execution
- Track progress and success rates in real-time
- Pause and resume campaigns as needed

### 3. Voice Testing Console

Test AI agent configurations before deployment:
- Multiple test scenarios (greeting, booking, objections, etc.)
- Real-time call monitoring
- Transcript analysis
- Performance recommendations

```php
// Add to any Filament page
@livewire('voice-test-console')
```

### 4. API Integration

Programmatic access for external systems:

```bash
# Initiate a call
curl -X POST https://api.askproai.de/api/mcp/retell/initiate-call \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "to_number": "+49123456789",
    "agent_id": "agent_123",
    "purpose": "follow_up",
    "dynamic_variables": {
      "customer_name": "John Doe"
    }
  }'

# Create a campaign
curl -X POST https://api.askproai.de/api/mcp/retell/campaign/create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Inactive Customer Reactivation",
    "agent_id": "agent_123",
    "target_type": "inactive_customers",
    "target_criteria": {
      "inactive_days": 90
    }
  }'
```

## Usage Guide

### Making a Quick Call

1. Navigate to AI Call Center
2. Enter phone number and select agent
3. Choose call purpose or custom message
4. Add any dynamic variables
5. Click "Initiate Call"

### Creating a Campaign

1. Click "New Campaign" in AI Call Center
2. Configure:
   - Campaign name and description
   - Target audience (all, inactive, or custom)
   - AI agent to use
   - Schedule (immediate or future)
   - Dynamic variables
3. Review target count
4. Create and start campaign

### Testing Voice Configuration

1. Open Voice Test Console
2. Select test scenario:
   - Basic Greeting Test
   - Appointment Booking Flow
   - Objection Handling
   - Custom Scenario
3. Enter test phone number
4. Monitor call in real-time
5. Review analysis and recommendations

## API Reference

### Endpoints

#### `POST /api/mcp/retell/initiate-call`
Initiate a single outbound call.

**Request:**
```json
{
  "to_number": "+49123456789",
  "agent_id": "agent_123",
  "from_number": "+49987654321", // optional
  "purpose": "follow_up",
  "customer_id": 123, // optional
  "dynamic_variables": {
    "key": "value"
  }
}
```

**Response:**
```json
{
  "success": true,
  "call_id": "456",
  "retell_call_id": "retell_789",
  "status": "initiated",
  "message": "Outbound call initiated successfully"
}
```

#### `POST /api/mcp/retell/campaign/create`
Create a new call campaign.

**Request:**
```json
{
  "name": "Campaign Name",
  "description": "Description",
  "agent_id": "agent_123",
  "target_type": "inactive_customers",
  "target_criteria": {
    "inactive_days": 90
  },
  "schedule_type": "immediate",
  "dynamic_variables": {}
}
```

#### `GET /api/mcp/retell/call/{callId}/status`
Get call status and details.

#### `POST /api/mcp/retell/campaign/{campaignId}/start`
Start a campaign.

#### `POST /api/mcp/retell/campaign/{campaignId}/pause`
Pause a running campaign.

#### `GET /api/mcp/retell/campaigns`
List all campaigns with pagination.

## Troubleshooting

### MCP Server Not Responding

1. Check if the Node.js server is running:
   ```bash
   pm2 status retell-mcp-server
   ```

2. Check logs:
   ```bash
   pm2 logs retell-mcp-server
   ```

3. Verify connectivity:
   ```bash
   curl http://localhost:3001/health
   ```

### Calls Not Initiating

1. Verify Retell.ai API key in MCP server `.env`
2. Check company has `retell_agent_id` configured
3. Ensure phone numbers are in E.164 format (+491234567890)
4. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Campaign Processing Issues

1. Ensure Horizon is running: `php artisan horizon`
2. Check campaign queue: `php artisan queue:work campaigns`
3. Verify customer phone numbers exist
4. Check job failures: `php artisan queue:failed`

## Future Enhancements

### Planned Features

1. **Multi-Agent Support**
   - Fetch and manage multiple agents per company
   - A/B testing different agent configurations

2. **Advanced Analytics**
   - Call sentiment analysis
   - Conversion tracking
   - Cost per acquisition metrics

3. **Integration Improvements**
   - WhatsApp integration for follow-ups
   - SMS fallback for failed calls
   - CRM synchronization

4. **Campaign Templates**
   - Pre-built campaign templates
   - Industry-specific scenarios
   - Multi-language templates

5. **Voice Cloning**
   - Custom voice creation
   - Brand-specific voice profiles

### Contributing

To add new features:

1. Extend `RetellAIBridgeMCPServer` for new functionality
2. Add corresponding API endpoints in `RetellMCPController`
3. Create UI components using Filament/Livewire
4. Update this documentation

## Security Considerations

- API tokens are encrypted in database
- Multi-tenant isolation enforced
- Rate limiting on API endpoints
- Webhook authentication for external MCP
- Phone number validation and sanitization

## Performance Notes

- Campaigns process in batches of 10 calls
- 2-second delay between batches to avoid overwhelming
- Redis caching for agent configurations
- Async processing via Laravel Horizon

## Support

For issues or questions:
1. Check the [Troubleshooting](#troubleshooting) section
2. Review Laravel logs: `tail -f storage/logs/laravel.log`
3. Check MCP server logs: `pm2 logs retell-mcp-server`
4. Create an issue in the project repository

---

**Last Updated**: 2025-07-22
**Version**: 1.0.0
**Author**: AskProAI Development Team