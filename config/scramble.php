<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Your API path. By default, all routes starting with this path will be added to the docs.
     */
    'api_path' => 'api',

    /*
     * Your API domain. By default, app domain is used.
     */
    'api_domain' => null,

    /*
     * The path where your OpenAPI specification will be exported.
     */
    'export_path' => 'api.json',

    'info' => [
        /*
         * API version.
         */
        'version' => env('API_VERSION', '2.0.0'),

        /*
         * Description rendered on the home page of the API documentation.
         */
        'description' => <<<'MD'
# AskPro AI Gateway API

Multi-tenant appointment management and AI voice agent platform.

## Key Integrations

- **Retell.ai** - AI Voice Agent function calls
- **Cal.com** - Scheduling & availability management
- **Service Gateway** - Multi-tenant case management

## Authentication

All API endpoints require authentication via Bearer token or webhook signatures.

## Rate Limits

- Standard endpoints: 60 requests/minute
- Webhook endpoints: 100 requests/minute

## Support

For API support, contact: api-support@askproai.de
MD,
    ],

    /*
     * Customize Stoplight Elements UI
     */
    'ui' => [
        /*
         * Define the title of the documentation's website.
         */
        'title' => 'AskPro API Gateway',

        /*
         * Define the theme of the documentation. Available options are `light`, `dark`, and `system`.
         */
        'theme' => 'system',

        /*
         * Hide the `Try It` feature. Enabled by default.
         */
        'hide_try_it' => false,

        /*
         * Hide the schemas in the Table of Contents.
         */
        'hide_schemas' => false,

        /*
         * URL to an image that displays as a small square logo next to the title.
         */
        'logo' => '/images/askpro-logo.png',

        /*
         * Credential policy for Try It feature.
         */
        'try_it_credentials_policy' => 'include',

        /*
         * Layout: sidebar, responsive, or stacked
         */
        'layout' => 'responsive',
    ],

    /*
     * The list of servers of the API.
     */
    'servers' => [
        'Production' => 'https://api.askproai.de/api',
        'Local' => env('APP_URL', 'http://localhost') . '/api',
    ],

    /**
     * Enum case description strategy.
     */
    'enum_cases_description_strategy' => 'description',

    /**
     * Enum case names strategy.
     */
    'enum_cases_names_strategy' => false,

    /**
     * Flatten deep query parameters for OpenAPI 3.x compatibility.
     */
    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        // Remove RestrictedDocsAccess for public API docs
        // RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
