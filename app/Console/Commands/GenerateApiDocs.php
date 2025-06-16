<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class GenerateApiDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:docs 
                            {--format=openapi : Documentation format (openapi, markdown)}
                            {--output=public/api-docs.json : Output file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API documentation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = $this->option('format');
        $output = $this->option('output');

        $this->info('🔍 Generating API documentation...');

        if ($format === 'openapi') {
            $docs = $this->generateOpenApiDocs();
            File::put($output, json_encode($docs, JSON_PRETTY_PRINT));
            $this->info("✅ OpenAPI documentation saved to: {$output}");
        } else {
            $docs = $this->generateMarkdownDocs();
            File::put($output, $docs);
            $this->info("✅ Markdown documentation saved to: {$output}");
        }

        return 0;
    }

    /**
     * Generate OpenAPI documentation
     */
    private function generateOpenApiDocs(): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'AskProAI API',
                'description' => 'AI-powered appointment booking system API',
                'version' => '2.0.0',
                'contact' => [
                    'email' => 'api@askproai.de',
                ],
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api/v2',
                    'description' => 'Production API',
                ],
            ],
            'security' => [
                ['bearerAuth' => []],
            ],
            'paths' => $this->generatePaths(),
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum',
                    ],
                ],
                'schemas' => $this->generateSchemas(),
            ],
        ];
    }

    /**
     * Generate API paths
     */
    private function generatePaths(): array
    {
        return [
            '/appointments' => [
                'get' => [
                    'tags' => ['Appointments'],
                    'summary' => 'List appointments',
                    'parameters' => [
                        [
                            'name' => 'date',
                            'in' => 'query',
                            'schema' => ['type' => 'string', 'format' => 'date'],
                        ],
                        [
                            'name' => 'staff_id',
                            'in' => 'query',
                            'schema' => ['type' => 'integer'],
                        ],
                        [
                            'name' => 'status',
                            'in' => 'query',
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Successful response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/Appointment'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Appointments'],
                    'summary' => 'Create appointment',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/CreateAppointment'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Appointment created',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'message' => ['type' => 'string'],
                                            'data' => ['$ref' => '#/components/schemas/Appointment'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/appointments/{id}' => [
                'get' => [
                    'tags' => ['Appointments'],
                    'summary' => 'Get appointment details',
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Successful response',
                        ],
                    ],
                ],
                'put' => [
                    'tags' => ['Appointments'],
                    'summary' => 'Update appointment',
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Appointment updated',
                        ],
                    ],
                ],
            ],
            '/appointments/{id}/cancel' => [
                'post' => [
                    'tags' => ['Appointments'],
                    'summary' => 'Cancel appointment',
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer'],
                        ],
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'reason' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Appointment cancelled',
                        ],
                    ],
                ],
            ],
            '/customers' => [
                'get' => [
                    'tags' => ['Customers'],
                    'summary' => 'List customers',
                    'responses' => [
                        '200' => [
                            'description' => 'Successful response',
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Customers'],
                    'summary' => 'Create customer',
                    'responses' => [
                        '201' => [
                            'description' => 'Customer created',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate schemas
     */
    private function generateSchemas(): array
    {
        return [
            'Appointment' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'status' => ['type' => 'string'],
                    'starts_at' => ['type' => 'string', 'format' => 'date-time'],
                    'ends_at' => ['type' => 'string', 'format' => 'date-time'],
                    'price' => ['type' => 'number'],
                    'customer' => ['$ref' => '#/components/schemas/Customer'],
                    'staff' => ['$ref' => '#/components/schemas/Staff'],
                    'service' => ['$ref' => '#/components/schemas/Service'],
                    'branch' => ['$ref' => '#/components/schemas/Branch'],
                ],
            ],
            'CreateAppointment' => [
                'type' => 'object',
                'required' => ['customer_name', 'customer_phone', 'staff_id', 'branch_id', 'starts_at', 'ends_at'],
                'properties' => [
                    'customer_name' => ['type' => 'string'],
                    'customer_email' => ['type' => 'string', 'format' => 'email'],
                    'customer_phone' => ['type' => 'string'],
                    'staff_id' => ['type' => 'integer'],
                    'service_id' => ['type' => 'integer'],
                    'branch_id' => ['type' => 'integer'],
                    'starts_at' => ['type' => 'string', 'format' => 'date-time'],
                    'ends_at' => ['type' => 'string', 'format' => 'date-time'],
                    'notes' => ['type' => 'string'],
                ],
            ],
            'Customer' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'tags' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                ],
            ],
            'Staff' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                ],
            ],
            'Service' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'duration' => ['type' => 'integer'],
                    'price' => ['type' => 'number'],
                ],
            ],
            'Branch' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'address' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                ],
            ],
        ];
    }

    /**
     * Generate Markdown documentation
     */
    private function generateMarkdownDocs(): string
    {
        $markdown = "# AskProAI API Documentation\n\n";
        $markdown .= "## Base URL\n\n";
        $markdown .= "```\n" . config('app.url') . "/api/v2\n```\n\n";
        $markdown .= "## Authentication\n\n";
        $markdown .= "All API requests require authentication using Bearer tokens.\n\n";
        $markdown .= "```\nAuthorization: Bearer YOUR_API_TOKEN\n```\n\n";
        
        // Add endpoints documentation
        $markdown .= "## Endpoints\n\n";
        $markdown .= $this->generateMarkdownEndpoints();
        
        return $markdown;
    }

    /**
     * Generate Markdown endpoints
     */
    private function generateMarkdownEndpoints(): string
    {
        $endpoints = [
            '### Appointments' => [
                'GET /appointments' => 'List all appointments',
                'POST /appointments' => 'Create new appointment',
                'GET /appointments/{id}' => 'Get appointment details',
                'PUT /appointments/{id}' => 'Update appointment',
                'POST /appointments/{id}/cancel' => 'Cancel appointment',
            ],
            '### Customers' => [
                'GET /customers' => 'List all customers',
                'POST /customers' => 'Create new customer',
                'GET /customers/{id}' => 'Get customer details',
                'PUT /customers/{id}' => 'Update customer',
                'DELETE /customers/{id}' => 'Delete customer',
            ],
        ];

        $markdown = '';
        foreach ($endpoints as $section => $routes) {
            $markdown .= "\n{$section}\n\n";
            foreach ($routes as $route => $description) {
                $markdown .= "- `{$route}` - {$description}\n";
            }
        }

        return $markdown;
    }
}