#!/usr/bin/env node
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import fetch from 'node-fetch';
import yaml from 'js-yaml';
import { config } from 'dotenv';
import fs from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';

config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Cache directory for API specifications
const CACHE_DIR = path.join(__dirname, '.cache');

// Create MCP server
const server = new Server(
  {
    name: 'apidog-mcp',
    version: '1.0.0',
  },
  {
    capabilities: {
      tools: {},
      resources: {},
    },
  }
);

// Initialize cache directory
async function initCache() {
  try {
    await fs.mkdir(CACHE_DIR, { recursive: true });
  } catch (error) {
    console.error('Failed to create cache directory:', error);
  }
}

// Tool definitions
server.setRequestHandler('tools/list', async () => ({
  tools: [
    {
      name: 'fetch_api_spec',
      description: 'Fetch API specification from Apidog project or OpenAPI URL',
      inputSchema: {
        type: 'object',
        properties: {
          source: {
            type: 'string',
            description: 'Source URL (Apidog project URL or OpenAPI spec URL)',
          },
          project_id: {
            type: 'string',
            description: 'Apidog project ID (optional, extracted from URL if not provided)',
          },
          format: {
            type: 'string',
            description: 'Expected format: openapi, swagger, apidog',
            enum: ['openapi', 'swagger', 'apidog'],
            default: 'openapi',
          },
        },
        required: ['source'],
      },
    },
    {
      name: 'list_endpoints',
      description: 'List all endpoints from cached API specification',
      inputSchema: {
        type: 'object',
        properties: {
          spec_id: {
            type: 'string',
            description: 'ID of cached specification',
          },
          tag: {
            type: 'string',
            description: 'Filter by tag/category',
          },
        },
        required: ['spec_id'],
      },
    },
    {
      name: 'get_endpoint_details',
      description: 'Get detailed information about a specific endpoint',
      inputSchema: {
        type: 'object',
        properties: {
          spec_id: {
            type: 'string',
            description: 'ID of cached specification',
          },
          path: {
            type: 'string',
            description: 'API endpoint path',
          },
          method: {
            type: 'string',
            description: 'HTTP method',
            enum: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'],
          },
        },
        required: ['spec_id', 'path', 'method'],
      },
    },
    {
      name: 'generate_code',
      description: 'Generate code based on API specification',
      inputSchema: {
        type: 'object',
        properties: {
          spec_id: {
            type: 'string',
            description: 'ID of cached specification',
          },
          language: {
            type: 'string',
            description: 'Target programming language',
            enum: ['javascript', 'typescript', 'php', 'python', 'java', 'go'],
          },
          type: {
            type: 'string',
            description: 'Type of code to generate',
            enum: ['client', 'server', 'models', 'controllers', 'tests'],
          },
          endpoints: {
            type: 'array',
            items: { type: 'string' },
            description: 'Specific endpoints to generate code for',
          },
        },
        required: ['spec_id', 'language', 'type'],
      },
    },
    {
      name: 'validate_request',
      description: 'Validate a request against API specification',
      inputSchema: {
        type: 'object',
        properties: {
          spec_id: {
            type: 'string',
            description: 'ID of cached specification',
          },
          path: {
            type: 'string',
            description: 'API endpoint path',
          },
          method: {
            type: 'string',
            description: 'HTTP method',
          },
          request: {
            type: 'object',
            description: 'Request to validate (headers, body, query params)',
          },
        },
        required: ['spec_id', 'path', 'method', 'request'],
      },
    },
    {
      name: 'list_cached_specs',
      description: 'List all cached API specifications',
      inputSchema: {
        type: 'object',
        properties: {},
      },
    },
  ],
}));

// Resource handlers for cached specifications
server.setRequestHandler('resources/list', async () => {
  try {
    const files = await fs.readdir(CACHE_DIR);
    const resources = [];
    
    for (const file of files) {
      if (file.endsWith('.json')) {
        const spec = JSON.parse(await fs.readFile(path.join(CACHE_DIR, file), 'utf-8'));
        resources.push({
          uri: `apidog://spec/${file.replace('.json', '')}`,
          name: spec.info?.title || file,
          description: spec.info?.description || 'Cached API specification',
          mimeType: 'application/json',
        });
      }
    }
    
    return { resources };
  } catch (error) {
    return { resources: [] };
  }
});

// Tool execution handler
server.setRequestHandler('tools/call', async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case 'fetch_api_spec': {
        const { source, project_id, format } = args;
        
        // Fetch the specification
        const response = await fetch(source, {
          headers: {
            'Accept': 'application/json, application/yaml, text/yaml',
            'User-Agent': 'Apidog-MCP-Server/1.0',
          },
        });
        
        if (!response.ok) {
          throw new Error(`Failed to fetch API spec: ${response.status} ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        let spec;
        
        if (contentType?.includes('yaml') || source.endsWith('.yaml') || source.endsWith('.yml')) {
          const text = await response.text();
          spec = yaml.load(text);
        } else {
          spec = await response.json();
        }
        
        // Generate spec ID
        const specId = project_id || Buffer.from(source).toString('base64').slice(0, 16);
        
        // Cache the specification
        await fs.writeFile(
          path.join(CACHE_DIR, `${specId}.json`),
          JSON.stringify(spec, null, 2)
        );
        
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify({
                spec_id: specId,
                title: spec.info?.title,
                version: spec.info?.version,
                description: spec.info?.description,
                servers: spec.servers,
                paths: Object.keys(spec.paths || {}),
              }, null, 2),
            },
          ],
        };
      }

      case 'list_endpoints': {
        const { spec_id, tag } = args;
        const specPath = path.join(CACHE_DIR, `${spec_id}.json`);
        const spec = JSON.parse(await fs.readFile(specPath, 'utf-8'));
        
        const endpoints = [];
        
        for (const [path, pathItem] of Object.entries(spec.paths || {})) {
          for (const [method, operation] of Object.entries(pathItem)) {
            if (method === 'parameters') continue;
            
            if (!tag || (operation.tags && operation.tags.includes(tag))) {
              endpoints.push({
                path,
                method: method.toUpperCase(),
                summary: operation.summary,
                description: operation.description,
                tags: operation.tags || [],
                operationId: operation.operationId,
              });
            }
          }
        }
        
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(endpoints, null, 2),
            },
          ],
        };
      }

      case 'get_endpoint_details': {
        const { spec_id, path, method } = args;
        const specPath = path.join(CACHE_DIR, `${spec_id}.json`);
        const spec = JSON.parse(await fs.readFile(specPath, 'utf-8'));
        
        const pathItem = spec.paths?.[path];
        const operation = pathItem?.[method.toLowerCase()];
        
        if (!operation) {
          throw new Error(`Endpoint ${method} ${path} not found`);
        }
        
        // Include global parameters if any
        const parameters = [
          ...(pathItem.parameters || []),
          ...(operation.parameters || []),
        ];
        
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify({
                path,
                method,
                operation: {
                  ...operation,
                  parameters,
                },
                security: operation.security || spec.security,
                servers: operation.servers || spec.servers,
              }, null, 2),
            },
          ],
        };
      }

      case 'generate_code': {
        const { spec_id, language, type, endpoints } = args;
        const specPath = path.join(CACHE_DIR, `${spec_id}.json`);
        const spec = JSON.parse(await fs.readFile(specPath, 'utf-8'));
        
        // This is a simplified code generation - in real implementation,
        // you would use proper code generation libraries
        let code = '';
        
        if (language === 'php' && type === 'client') {
          code = generatePHPClient(spec, endpoints);
        } else if (language === 'typescript' && type === 'models') {
          code = generateTypeScriptModels(spec);
        } else {
          code = `// Code generation for ${language} ${type} not implemented yet\n`;
          code += `// Available endpoints:\n`;
          for (const [path, pathItem] of Object.entries(spec.paths || {})) {
            for (const method of Object.keys(pathItem)) {
              if (method !== 'parameters') {
                code += `// ${method.toUpperCase()} ${path}\n`;
              }
            }
          }
        }
        
        return {
          content: [
            {
              type: 'text',
              text: code,
            },
          ],
        };
      }

      case 'validate_request': {
        const { spec_id, path, method, request } = args;
        const specPath = path.join(CACHE_DIR, `${spec_id}.json`);
        const spec = JSON.parse(await fs.readFile(specPath, 'utf-8'));
        
        const operation = spec.paths?.[path]?.[method.toLowerCase()];
        if (!operation) {
          throw new Error(`Endpoint ${method} ${path} not found`);
        }
        
        const errors = [];
        
        // Validate parameters
        const parameters = operation.parameters || [];
        for (const param of parameters) {
          if (param.required) {
            const value = request[param.in]?.[param.name];
            if (value === undefined) {
              errors.push(`Missing required parameter: ${param.name} in ${param.in}`);
            }
          }
        }
        
        // Validate request body
        if (operation.requestBody && operation.requestBody.required && !request.body) {
          errors.push('Missing required request body');
        }
        
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify({
                valid: errors.length === 0,
                errors,
                warnings: [],
              }, null, 2),
            },
          ],
        };
      }

      case 'list_cached_specs': {
        const files = await fs.readdir(CACHE_DIR);
        const specs = [];
        
        for (const file of files) {
          if (file.endsWith('.json')) {
            const spec = JSON.parse(await fs.readFile(path.join(CACHE_DIR, file), 'utf-8'));
            specs.push({
              id: file.replace('.json', ''),
              title: spec.info?.title,
              version: spec.info?.version,
              description: spec.info?.description,
            });
          }
        }
        
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(specs, null, 2),
            },
          ],
        };
      }

      default:
        throw new Error(`Unknown tool: ${name}`);
    }
  } catch (error) {
    return {
      content: [
        {
          type: 'text',
          text: `Error: ${error.message}`,
        },
      ],
      isError: true,
    };
  }
});

// Simple PHP client generator
function generatePHPClient(spec, endpoints) {
  let code = '<?php\n\n';
  code += `namespace App\\Services\\ApiClients;\n\n`;
  code += `use Illuminate\\Support\\Facades\\Http;\n\n`;
  code += `class ${spec.info?.title?.replace(/[^a-zA-Z0-9]/g, '')}Client\n{\n`;
  code += `    protected string $baseUrl;\n\n`;
  code += `    public function __construct()\n    {\n`;
  code += `        $this->baseUrl = '${spec.servers?.[0]?.url || 'https://api.example.com'}';\n`;
  code += `    }\n\n`;
  
  for (const [path, pathItem] of Object.entries(spec.paths || {})) {
    for (const [method, operation] of Object.entries(pathItem)) {
      if (method === 'parameters') continue;
      if (endpoints && !endpoints.includes(path)) continue;
      
      const methodName = operation.operationId || 
        `${method}${path.replace(/[^a-zA-Z0-9]/g, '')}`;
      
      code += `    public function ${methodName}(array $params = [])\n    {\n`;
      code += `        return Http::${method}($this->baseUrl . '${path}', $params);\n`;
      code += `    }\n\n`;
    }
  }
  
  code += '}\n';
  return code;
}

// Simple TypeScript model generator
function generateTypeScriptModels(spec) {
  let code = '// Generated TypeScript models\n\n';
  
  const schemas = spec.components?.schemas || {};
  
  for (const [name, schema] of Object.entries(schemas)) {
    code += `export interface ${name} {\n`;
    
    if (schema.properties) {
      for (const [propName, propSchema] of Object.entries(schema.properties)) {
        const required = schema.required?.includes(propName) ? '' : '?';
        const type = getTypeScriptType(propSchema);
        code += `  ${propName}${required}: ${type};\n`;
      }
    }
    
    code += '}\n\n';
  }
  
  return code;
}

function getTypeScriptType(schema) {
  if (schema.type === 'string') return 'string';
  if (schema.type === 'number' || schema.type === 'integer') return 'number';
  if (schema.type === 'boolean') return 'boolean';
  if (schema.type === 'array') return `${getTypeScriptType(schema.items)}[]`;
  if (schema.type === 'object') return 'any';
  if (schema.$ref) return schema.$ref.split('/').pop();
  return 'any';
}

// Start the server
async function main() {
  await initCache();
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error('Apidog MCP Server running on stdio');
}

main().catch((error) => {
  console.error('Fatal error:', error);
  process.exit(1);
});