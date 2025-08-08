# Hair Salon MCP Agent Configuration

This document describes the Hair Salon MCP (Model Context Protocol) integration for Retell.ai agent `agent_d7da9e5c49c4ccfff2526df5c1`.

## Overview

The Hair Salon MCP integration adds 4 custom functions to the Retell.ai agent, enabling it to:

1. **List Services** - Display available hair salon services and prices
2. **Check Availability** - Check appointment availability for specific dates
3. **Book Appointment** - Book appointments with customer details
4. **Schedule Callback** - Schedule callback requests from customers

## Configuration Scripts

### Primary Configuration Script
```bash
php configure-hair-salon-mcp-agent.php
```

This script:
- Validates the environment (RETELL_API_KEY must be set)
- Fetches the current agent configuration
- Adds Hair Salon MCP functions without modifying the existing prompt
- Updates the agent via Retell.ai API
- Verifies the configuration

### Test & Validation Script
```bash
php test-hair-salon-mcp-config.php
```

This script:
- Tests agent configuration and function setup
- Tests MCP endpoint connectivity
- Displays detailed configuration information
- Provides comprehensive test results

## MCP Functions Added

### 1. list_services
- **URL**: `https://api.askproai.de/api/v2/hair-salon-mcp/list-services`
- **Purpose**: List all available hair salon services
- **Parameters**: 
  - `category` (optional): Filter by category ('herren', 'damen', 'kinder', 'alle')

### 2. check_availability
- **URL**: `https://api.askproai.de/api/v2/hair-salon-mcp/check-availability`
- **Purpose**: Check available appointment slots
- **Parameters**: 
  - `date` (required): Desired date
  - `service` (required): Desired service
  - `preferred_time` (optional): Preferred time

### 3. book_appointment
- **URL**: `https://api.askproai.de/api/v2/hair-salon-mcp/book-appointment`
- **Purpose**: Book appointments with full customer details
- **Parameters**: 
  - `call_id` (required): Call reference ID
  - `customer_name` (required): Customer's full name
  - `phone_number` (required): Customer's phone number
  - `service` (required): Requested service
  - `date` (required): Appointment date
  - `time` (required): Appointment time
  - `email` (optional): Customer email
  - `notes` (optional): Additional notes

### 4. schedule_callback
- **URL**: `https://api.askproai.de/api/v2/hair-salon-mcp/schedule-callback`
- **Purpose**: Schedule callback requests
- **Parameters**: 
  - `call_id` (required): Call reference ID
  - `phone_number` (required): Customer's phone number
  - `customer_name` (required): Customer's name
  - `callback_date` (required): Callback date
  - `callback_time` (required): Callback time
  - `reason` (required): Reason for callback

## Prerequisites

1. **Environment Variables**:
   ```env
   RETELL_API_KEY=key_6ff998ba48e842092e04a5455d19
   ```

2. **Agent Exists**: 
   - Agent `agent_d7da9e5c49c4ccfff2526df5c1` must exist in your Retell.ai account

3. **MCP Server**: 
   - Hair Salon MCP server should be running at `https://api.askproai.de/api/v2/hair-salon-mcp`

## Usage Instructions

### Step 1: Run Configuration
```bash
cd /var/www/api-gateway
php configure-hair-salon-mcp-agent.php
```

### Step 2: Test Configuration
```bash
php test-hair-salon-mcp-config.php
```

### Step 3: Verify in Dashboard
- Visit Retell.ai dashboard: `https://retell.ai/dashboard/agents/agent_d7da9e5c49c4ccfff2526df5c1`
- Check that the 4 MCP functions are listed under "Custom Functions"

### Step 4: Test with Phone Call
- Call the phone number associated with the agent
- Test the Hair Salon functions by asking about services, availability, and booking

## Troubleshooting

### Common Issues

1. **API Key Invalid**:
   ```
   ERROR: RETELL_API_KEY environment variable is not set
   ```
   - Solution: Set the correct API key in `.env` file

2. **Agent Not Found**:
   ```
   ERROR: Could not fetch agent with ID: agent_d7da9e5c49c4ccfff2526df5c1
   ```
   - Solution: Verify agent exists in Retell.ai dashboard

3. **MCP Endpoint Not Reachable**:
   ```
   MCP endpoint might not be available
   ```
   - Solution: Check if MCP server is running and accessible

### Debug Commands

```bash
# Check logs for Hair Salon MCP activity
tail -f storage/logs/laravel.log | grep -i "hair.*salon"

# Test MCP endpoint directly
curl -X GET https://api.askproai.de/api/v2/hair-salon-mcp/health

# Verify agent configuration
php test-hair-salon-mcp-config.php
```

## Configuration Details

- **Agent ID**: `agent_d7da9e5c49c4ccfff2526df5c1`
- **MCP Base URL**: `https://api.askproai.de/api/v2/hair-salon-mcp`
- **Functions Added**: 4 (list_services, check_availability, book_appointment, schedule_callback)
- **Prompt**: Preserved unchanged (as requested)
- **Authentication**: Uses RETELL_API_KEY environment variable

## Support

For issues with this configuration:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Run the test script: `php test-hair-salon-mcp-config.php`
3. Verify MCP server is running and responding
4. Check Retell.ai dashboard for agent status

---
**Last Updated**: 2025-08-07  
**Created by**: Claude Code  
**Scripts**: `configure-hair-salon-mcp-agent.php`, `test-hair-salon-mcp-config.php`