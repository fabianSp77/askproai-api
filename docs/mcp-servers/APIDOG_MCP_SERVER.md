# Apidog MCP Server Documentation

## Overview

The Apidog MCP Server provides seamless integration with API specifications, enabling automatic code generation, documentation management, and API validation directly within the AskProAI system. It supports Apidog projects, OpenAPI/Swagger specifications, and provides powerful tools for API-driven development.

## Installation Status

✅ **INSTALLED** - The Apidog MCP Server has been successfully installed and configured.

## Features

- **API Specification Management**: Fetch and cache API specs from Apidog or OpenAPI sources
- **Code Generation**: Generate PHP clients, models, controllers, and Filament resources
- **Endpoint Discovery**: Browse and search API endpoints with filtering
- **Request Validation**: Validate requests against API specifications
- **Documentation Access**: Get detailed endpoint information including parameters and schemas
- **Filament Integration**: Generate complete Filament resources from API specs

## Configuration

### 1. Environment Variables

Add the following to your `.env` file:

```bash
# Apidog MCP Server
MCP_APIDOG_ENABLED=true
APIDOG_API_KEY=your_apidog_api_key_here
APIDOG_PROJECT_ID=your_default_project_id
```

### 2. Apidog API Key (Optional)

If you're using Apidog cloud projects:
1. Log in to your Apidog account
2. Go to Settings → API Keys
3. Generate a new API key
4. Add it to your `.env` file

Note: You can also use public OpenAPI specifications without an API key.

## Available Tools

### 1. API Specification Management

#### fetch_api_spec
Fetch and cache an API specification.
```php
$result = $apidog->executeTool('fetch_api_spec', [
    'source' => 'https://api.example.com/openapi.json',
    'format' => 'openapi' // openapi, swagger, or apidog
]);
```

#### list_cached_specs
List all cached API specifications.
```php
$result = $apidog->executeTool('list_cached_specs', []);
```

### 2. Endpoint Discovery

#### list_endpoints
List all endpoints from a cached specification.
```php
$result = $apidog->executeTool('list_endpoints', [
    'spec_id' => 'your-spec-id',
    'tag' => 'users' // optional filter by tag
]);
```

#### get_endpoint_details
Get detailed information about a specific endpoint.
```php
$result = $apidog->executeTool('get_endpoint_details', [
    'spec_id' => 'your-spec-id',
    'path' => '/users/{id}',
    'method' => 'GET'
]);
```

### 3. Code Generation

#### generate_code
Generate code based on API specification.
```php
$result = $apidog->executeTool('generate_code', [
    'spec_id' => 'your-spec-id',
    'language' => 'php',
    'type' => 'client', // client, models, controllers, tests
    'endpoints' => ['/users', '/posts'] // optional specific endpoints
]);
```

#### import_to_filament
Generate a complete Filament resource from API specification.
```php
$result = $apidog->executeTool('import_to_filament', [
    'spec_id' => 'your-spec-id',
    'resource_name' => 'ApiUser',
    'endpoints' => ['/users', '/users/{id}']
]);
```

### 4. Validation

#### validate_request
Validate a request against the API specification.
```php
$result = $apidog->executeTool('validate_request', [
    'spec_id' => 'your-spec-id',
    'path' => '/users',
    'method' => 'POST',
    'request' => [
        'body' => ['name' => 'John', 'email' => 'john@example.com'],
        'headers' => ['Content-Type' => 'application/json']
    ]
]);
```

## Usage Examples

### Example 1: Import External API and Generate Client

```php
use App\Services\MCP\ApidogMCPServer;

$apidog = app(ApidogMCPServer::class);

// 1. Fetch the API specification
$result = $apidog->executeTool('fetch_api_spec', [
    'source' => 'https://petstore.swagger.io/v2/swagger.json',
    'format' => 'swagger'
]);

if ($result['success']) {
    $specId = $result['data']['spec_id'];
    echo "API Spec imported: {$result['data']['title']}\n";
    
    // 2. Generate PHP client
    $codeResult = $apidog->executeTool('generate_code', [
        'spec_id' => $specId,
        'language' => 'php',
        'type' => 'client'
    ]);
    
    if ($codeResult['success']) {
        // Save the generated client
        file_put_contents(
            app_path('Services/ApiClients/PetStoreClient.php'),
            $codeResult['data']['code']
        );
        echo "Client generated successfully!\n";
    }
}
```

### Example 2: Generate Filament Resource from API

```php
// Import your company's API spec
$result = $apidog->executeTool('fetch_api_spec', [
    'source' => 'https://api.yourcompany.com/v1/openapi.json'
]);

if ($result['success']) {
    $specId = $result['data']['spec_id'];
    
    // Generate Filament resource for user management
    $filamentResult = $apidog->executeTool('import_to_filament', [
        'spec_id' => $specId,
        'resource_name' => 'ExternalUser',
        'endpoints' => [
            '/users',
            '/users/{id}',
            '/users/{id}/roles'
        ]
    ]);
    
    if ($filamentResult['success']) {
        file_put_contents(
            app_path($filamentResult['data']['file_path']),
            $filamentResult['data']['code']
        );
        echo "Filament resource created at: {$filamentResult['data']['file_path']}\n";
    }
}
```

### Example 3: Validate API Requests

```php
// Validate before sending request to external API
$validation = $apidog->executeTool('validate_request', [
    'spec_id' => $specId,
    'path' => '/users',
    'method' => 'POST',
    'request' => [
        'body' => [
            'name' => 'John Doe',
            'email' => 'invalid-email' // Missing @ symbol
        ]
    ]
]);

if (!$validation['data']['valid']) {
    echo "Request validation failed:\n";
    foreach ($validation['data']['errors'] as $error) {
        echo "- {$error}\n";
    }
}
```

### Example 4: Browse and Document APIs

```php
// List all available endpoints
$endpoints = $apidog->executeTool('list_endpoints', [
    'spec_id' => $specId,
    'tag' => 'authentication'
]);

foreach ($endpoints['data'] as $endpoint) {
    echo "{$endpoint['method']} {$endpoint['path']}\n";
    
    // Get detailed documentation
    $details = $apidog->executeTool('get_endpoint_details', [
        'spec_id' => $specId,
        'path' => $endpoint['path'],
        'method' => $endpoint['method']
    ]);
    
    if ($details['success']) {
        $op = $details['data']['operation'];
        echo "  Summary: {$op['summary']}\n";
        echo "  Parameters: " . count($op['parameters'] ?? []) . "\n";
        echo "  Security: " . json_encode($details['data']['security']) . "\n\n";
    }
}
```

## Integration with AskProAI

### Use Case 1: External Service Integration

When integrating with external services (payment providers, shipping APIs, etc.):

```php
// 1. Import their API specification
$apidog->executeTool('fetch_api_spec', [
    'source' => 'https://api.stripe.com/openapi/spec3.json'
]);

// 2. Generate type-safe client
$apidog->executeTool('generate_code', [
    'spec_id' => $specId,
    'language' => 'php',
    'type' => 'client'
]);

// 3. Generate models for data handling
$apidog->executeTool('generate_code', [
    'spec_id' => $specId,
    'language' => 'php',
    'type' => 'models'
]);
```

### Use Case 2: API Documentation

Automatically generate documentation for your APIs:

```php
// Generate markdown documentation
$endpoints = $apidog->executeTool('list_endpoints', ['spec_id' => $specId]);
$markdown = "# API Documentation\n\n";

foreach ($endpoints['data'] as $endpoint) {
    $details = $apidog->executeTool('get_endpoint_details', [
        'spec_id' => $specId,
        'path' => $endpoint['path'],
        'method' => $endpoint['method']
    ]);
    
    $markdown .= "## {$endpoint['method']} {$endpoint['path']}\n";
    $markdown .= "{$endpoint['summary']}\n\n";
    // ... add parameters, responses, etc.
}
```

### Use Case 3: Rapid Prototyping

Quickly scaffold admin interfaces for external APIs:

```php
// Import API and generate complete Filament resource
$apidog->executeTool('import_to_filament', [
    'spec_id' => $specId,
    'resource_name' => 'RemoteData',
    'endpoints' => ['/data', '/data/{id}']
]);

// The generated resource includes:
// - Table with columns from API response
// - Create/Edit forms based on request schemas
// - View pages with all available data
// - Proper validation rules
```

## Testing

Run the test script to verify the Apidog MCP Server is working:

```bash
php test-apidog-mcp.php
```

## Troubleshooting

### API Spec Not Loading
- Check the URL is accessible
- Verify the format (OpenAPI 3.0, Swagger 2.0)
- Check for CORS issues if loading from browser

### Code Generation Issues
- Ensure the specification has proper schemas defined
- Check that endpoints have operationId for better method names
- Verify request/response schemas are properly structured

### Cache Issues
If specs are outdated:
```php
// Clear specific spec cache
Cache::forget('apidog_specs_' . $specId);

// Clear all Apidog cache
Cache::flush(); // Use with caution
```

## Performance Considerations

- API specifications are cached for 7 days by default
- Large specs (>10MB) may take time to process
- Generated code is not cached - regenerate as needed
- Consider storing generated code in version control

## Security Notes

1. **API Keys**: Store securely in `.env`, never commit
2. **External APIs**: Validate SSL certificates
3. **Generated Code**: Review before using in production
4. **Request Validation**: Always validate before sending to external APIs

## Future Enhancements

- GraphQL support
- Postman collection import
- Automatic test generation
- API mocking for development
- Webhook endpoint generation
- API versioning support
- Automatic API change detection