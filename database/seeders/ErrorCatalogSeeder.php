<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ErrorCatalog;
use App\Models\ErrorSolution;
use App\Models\ErrorPreventionTip;
use App\Models\ErrorTag;

class ErrorCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create common tags
        $tags = [
            'webhook' => ErrorTag::firstOrCreate(['name' => 'Webhook', 'color' => '#3B82F6']),
            'api' => ErrorTag::firstOrCreate(['name' => 'API', 'color' => '#10B981']),
            'configuration' => ErrorTag::firstOrCreate(['name' => 'Configuration', 'color' => '#F59E0B']),
            'authentication' => ErrorTag::firstOrCreate(['name' => 'Authentication', 'color' => '#EF4444']),
            'database' => ErrorTag::firstOrCreate(['name' => 'Database', 'color' => '#8B5CF6']),
            'queue' => ErrorTag::firstOrCreate(['name' => 'Queue', 'color' => '#6366F1']),
        ];

        // RETELL_001: No calls imported
        $error1 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'RETELL_001'],
            [
                'category' => 'INTEGRATION',
                'service' => 'retell',
                'title' => 'No calls imported / Es werden keine Anrufe eingespielt',
                'description' => 'Retell.ai calls are not being imported into the system. This typically happens when the webhook is not properly configured or the queue worker is not running.',
                'symptoms' => 'No new calls appear in the admin panel despite calls being made through Retell.ai',
                'stack_pattern' => 'RetellService::fetchCalls\(\) returns empty',
                'root_causes' => [
                    'Queue Worker' => 'Horizon queue worker is not running',
                    'API Key' => 'Company retell_api_key is null or incorrect',
                    'Webhook' => 'Webhook URL not registered in Retell.ai dashboard',
                    'API Version' => 'Using outdated API endpoint',
                ],
                'severity' => 'critical',
                'is_active' => true,
                'auto_detectable' => true,
            ]
        );

        $error1->tags()->sync([$tags['webhook']->id, $tags['api']->id, $tags['queue']->id]);

        // Add solutions for RETELL_001
        $error1->solutions()->createMany([
            [
                'order' => 1,
                'type' => 'command',
                'title' => 'Start Horizon Queue Worker',
                'description' => 'Ensure the Laravel Horizon queue worker is running to process incoming webhooks',
                'steps' => [
                    'SSH into the server',
                    'Navigate to project directory: cd /var/www/api-gateway',
                    'Start Horizon: php artisan horizon',
                    'Verify status: php artisan horizon:status',
                ],
                'code_snippet' => 'php artisan horizon',
                'is_automated' => false,
            ],
            [
                'order' => 2,
                'type' => 'manual',
                'title' => 'Manual Import via Admin Panel',
                'description' => 'Use the manual import button in the admin panel to fetch recent calls',
                'steps' => [
                    'Login to Admin Panel',
                    'Navigate to Calls section',
                    'Click "Anrufe abrufen" button',
                    'Wait for import to complete',
                ],
                'is_automated' => false,
            ],
            [
                'order' => 3,
                'type' => 'script',
                'title' => 'Fix Missing API Key',
                'description' => 'Update company API key from environment configuration',
                'steps' => [
                    'Check if company has API key',
                    'Update from .env if missing',
                    'Save changes',
                ],
                'code_snippet' => "php artisan tinker\n>>> \$c = Company::first();\n>>> \$c->retell_api_key = config('services.retell.api_key');\n>>> \$c->save();",
                'is_automated' => false,
            ],
        ]);

        // Add prevention tips
        $error1->preventionTips()->createMany([
            [
                'order' => 1,
                'tip' => 'Monitor Horizon dashboard regularly at /horizon',
                'category' => 'monitoring',
            ],
            [
                'order' => 2,
                'tip' => 'Set up alerts for queue worker failures',
                'category' => 'monitoring',
            ],
            [
                'order' => 3,
                'tip' => 'Verify webhook configuration after each Retell.ai update',
                'category' => 'configuration',
            ],
        ]);

        // DB_001: Access denied for user
        $error2 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'DB_001'],
            [
                'category' => 'DB',
                'service' => 'internal',
                'title' => 'Access denied for user \'askproai_user\'@\'localhost\'',
                'description' => 'Database access is denied due to cached incorrect credentials. This typically occurs after deployment when Laravel\'s config cache contains old values.',
                'symptoms' => 'Application shows database connection error immediately after deployment',
                'stack_pattern' => 'SQLSTATE\[HY000\] \[1045\]',
                'root_causes' => [
                    'Config Cache' => 'Laravel config cache contains incorrect database credentials',
                    'ENV Files' => '.env.production file overriding correct .env values',
                    'PHP-FPM' => 'PHP-FPM process not restarted after configuration change',
                ],
                'severity' => 'critical',
                'is_active' => true,
                'auto_detectable' => true,
            ]
        );

        $error2->tags()->sync([$tags['database']->id, $tags['configuration']->id]);

        $error2->solutions()->create([
            'order' => 1,
            'type' => 'command',
            'title' => 'Clear Config Cache and Restart PHP-FPM',
            'description' => 'Remove cached configuration and restart PHP process',
            'steps' => [
                'Delete cached config file',
                'Clear all Laravel caches',
                'Recreate config cache with correct values',
                'Restart PHP-FPM service',
            ],
            'code_snippet' => "rm -f bootstrap/cache/config.php\nphp artisan config:cache\nsudo systemctl restart php8.3-fpm",
            'is_automated' => true,
            'automation_script' => 'scripts/fixes/fix-db-access.php',
        ]);

        // WEBHOOK_001: Invalid signature
        $error3 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'WEBHOOK_001'],
            [
                'category' => 'INTEGRATION',
                'service' => 'webhook',
                'title' => 'Invalid webhook signature',
                'description' => 'Webhook signature verification failed. The signature sent by the external service doesn\'t match the expected signature.',
                'symptoms' => 'Webhooks are rejected with 401 Unauthorized response',
                'stack_pattern' => 'VerifyRetellSignature middleware rejection',
                'root_causes' => [
                    'Secret Mismatch' => 'Webhook secret in .env doesn\'t match service configuration',
                    'Signature Format' => 'Incorrect signature format or calculation method',
                    'Encoding Issues' => 'Character encoding problems in signature validation',
                ],
                'severity' => 'high',
                'is_active' => true,
                'auto_detectable' => true,
            ]
        );

        $error3->tags()->sync([$tags['webhook']->id, $tags['authentication']->id]);

        // CALCOM_001: Event type not found
        $error4 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'CALCOM_001'],
            [
                'category' => 'INTEGRATION',
                'service' => 'calcom',
                'title' => 'Event type not found',
                'description' => 'Cal.com API returns 404 when trying to access an event type. This usually means the event type ID is incorrect or has been deleted.',
                'symptoms' => 'Appointment booking fails with "Event type not found" error',
                'stack_pattern' => 'CalcomV2Service::getEventType\(\) returns 404',
                'root_causes' => [
                    'Invalid ID' => 'branch.calcom_event_type_id contains non-existent ID',
                    'Deleted Event' => 'Event type was deleted in Cal.com but reference remains',
                    'Sync Issue' => 'Event types not properly synced from Cal.com',
                ],
                'severity' => 'medium',
                'is_active' => true,
                'auto_detectable' => true,
            ]
        );

        $error4->tags()->sync([$tags['api']->id]);

        // QUEUE_001: Job failed after X attempts
        $error5 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'QUEUE_001'],
            [
                'category' => 'QUEUE',
                'service' => 'internal',
                'title' => 'Job failed after X attempts',
                'description' => 'A queued job has exceeded the maximum number of retry attempts and has been marked as failed.',
                'symptoms' => 'Background jobs not completing, data not being processed',
                'stack_pattern' => 'has been attempted too many times or run too long',
                'root_causes' => [
                    'Timeout' => 'Job execution time exceeds configured timeout',
                    'Memory' => 'Job runs out of memory during processing',
                    'External Service' => 'Dependency on external service that is down',
                    'Data Issue' => 'Corrupted or invalid data causing job to fail',
                ],
                'severity' => 'medium',
                'is_active' => true,
                'auto_detectable' => false,
            ]
        );

        $error5->tags()->sync([$tags['queue']->id]);

        $error5->solutions()->createMany([
            [
                'order' => 1,
                'type' => 'config',
                'title' => 'Increase Job Timeout',
                'description' => 'Increase the timeout value for long-running jobs',
                'steps' => [
                    'Open config/horizon.php',
                    'Find the environment configuration',
                    'Increase timeout value for the specific queue',
                    'Restart Horizon',
                ],
                'code_snippet' => "'environments' => [\n    'production' => [\n        'supervisor-1' => [\n            'timeout' => 300, // 5 minutes\n        ],\n    ],\n],",
                'is_automated' => false,
            ],
            [
                'order' => 2,
                'type' => 'command',
                'title' => 'Clear Failed Jobs',
                'description' => 'Remove all failed jobs from the queue',
                'steps' => [
                    'View failed jobs to understand the issue',
                    'Clear all failed jobs',
                    'Monitor for new failures',
                ],
                'code_snippet' => "php artisan queue:failed\nphp artisan queue:flush",
                'is_automated' => false,
            ],
        ]);

        // AUTH_001: Unauthenticated
        $error6 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'AUTH_001'],
            [
                'category' => 'AUTH',
                'service' => 'internal',
                'title' => 'Unauthenticated API request',
                'description' => 'API request was made without proper authentication token or with an invalid token.',
                'symptoms' => 'API calls return 401 Unauthenticated response',
                'stack_pattern' => 'Unauthenticated',
                'root_causes' => [
                    'Missing Token' => 'Authorization header not included in request',
                    'Expired Token' => 'Authentication token has expired',
                    'Invalid Token' => 'Token is malformed or invalid',
                    'Wrong Guard' => 'Using wrong authentication guard for the route',
                ],
                'severity' => 'low',
                'is_active' => true,
                'auto_detectable' => false,
            ]
        );

        $error6->tags()->sync([$tags['authentication']->id, $tags['api']->id]);

        $error6->solutions()->create([
            'order' => 1,
            'type' => 'manual',
            'title' => 'Add Authorization Header',
            'description' => 'Include the Bearer token in the Authorization header',
            'steps' => [
                'Obtain a valid authentication token',
                'Add Authorization header to request',
                'Use format: Bearer YOUR_TOKEN',
            ],
            'code_snippet' => "'Authorization' => 'Bearer ' . \$user->createToken('api')->plainTextToken",
            'is_automated' => false,
        ]);

        // RETELL_002: Webhook signature verification failed
        $error7 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'RETELL_002'],
            [
                'category' => 'INTEGRATION',
                'service' => 'retell',
                'title' => 'Webhook signature verification failed',
                'description' => 'Retell webhook signature does not match expected value. API Key must equal Webhook Secret.',
                'symptoms' => 'Webhooks return 401 Unauthorized, calls not being processed',
                'stack_pattern' => 'VerifyRetellSignature::handle\(\) returns 401',
                'root_causes' => [
                    'Key Mismatch' => 'API Key does not equal Webhook Secret',
                    'Format Issue' => 'Signature format incorrect (should be v=timestamp,d=signature)',
                    'Encoding' => 'Character encoding issues in signature calculation',
                ],
                'severity' => 'high',
                'is_active' => true,
                'auto_detectable' => true,
            ]
        );

        $error7->tags()->sync([$tags['webhook']->id, $tags['authentication']->id]);

        $error7->solutions()->create([
            'order' => 1,
            'type' => 'script',
            'title' => 'Fix Webhook Signature Configuration',
            'description' => 'Ensure API Key equals Webhook Secret and test signature',
            'steps' => [
                'Verify RETELL_TOKEN equals RETELL_WEBHOOK_SECRET in .env',
                'Update both to the same value if different',
                'Test webhook with: php trigger-simple-webhook.php',
                'Verify format: v=timestamp,d=signature',
            ],
            'code_snippet' => "# In .env:\nRETELL_TOKEN=key_e973c8962e09d6a34b3b1cf386\nRETELL_WEBHOOK_SECRET=key_e973c8962e09d6a34b3b1cf386  # Must be same!",
            'is_automated' => true,
            'automation_script' => 'scripts/fixes/fix-webhook-signature.php',
        ]);

        // RETELL_003: Branch ID is null after webhook
        $error8 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'RETELL_003'],
            [
                'category' => 'INTEGRATION',
                'service' => 'retell',
                'title' => 'Branch ID is null after webhook',
                'description' => 'Call record created without branch_id after webhook processing. Phone number not properly mapped to branch.',
                'symptoms' => 'Calls appear in system but not assigned to any branch',
                'stack_pattern' => 'Call::create\(\) branch_id => null',
                'root_causes' => [
                    'Missing Mapping' => 'Phone number not in phone_numbers table',
                    'No Branch ID' => 'phone_numbers record exists but branch_id is null',
                    'Phone Format' => 'Phone number format mismatch (with/without country code)',
                ],
                'severity' => 'high',
                'is_active' => true,
                'auto_detectable' => true,
            ]
        );

        $error8->tags()->sync([$tags['database']->id, $tags['webhook']->id]);

        $error8->solutions()->create([
            'order' => 1,
            'type' => 'script',
            'title' => 'Fix Phone Number to Branch Mapping',
            'description' => 'Verify and fix phone number mappings in database',
            'steps' => [
                'Check phone_numbers table for the phone number',
                'Verify branch_id is set correctly',
                'Run phone resolution test script',
                'Update mappings if needed',
            ],
            'code_snippet' => "SELECT * FROM phone_numbers WHERE number LIKE '%XXX%';\nUPDATE phone_numbers SET branch_id = X WHERE number = '+49XXX';",
            'is_automated' => true,
            'automation_script' => 'scripts/fixes/fix-phone-mapping.php',
        ]);

        // RETELL_004: Wrong timezone in calls
        $error9 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'RETELL_004'],
            [
                'category' => 'INTEGRATION',
                'service' => 'retell',
                'title' => 'Wrong timezone in calls (UTC instead of Berlin)',
                'description' => 'Call timestamps show UTC time instead of Berlin time, appearing 2 hours behind actual time.',
                'symptoms' => 'Call times in admin panel show wrong time (2 hours behind)',
                'stack_pattern' => 'start_timestamp shows 2 hours behind',
                'root_causes' => [
                    'UTC Storage' => 'Retell sends timestamps in UTC',
                    'No Conversion' => 'Application not converting to local timezone',
                    'Display Issue' => 'Frontend displaying raw UTC time',
                ],
                'severity' => 'medium',
                'is_active' => false, // Already fixed
                'auto_detectable' => true,
            ]
        );

        $error9->tags()->sync([$tags['api']->id]);

        $error9->solutions()->create([
            'order' => 1,
            'type' => 'script',
            'title' => 'Fix Call Timestamps',
            'description' => 'Convert UTC timestamps to Berlin time',
            'steps' => [
                'Issue already fixed in ProcessRetellCallEndedJob',
                'For old calls, run fix script',
                'New calls automatically convert UTC → Berlin (+2h)',
            ],
            'code_snippet' => "// Already fixed in code:\n\$berlinTime = Carbon::parse(\$timestamp)\n    ->setTimezone('Europe/Berlin');",
            'is_automated' => true,
            'automation_script' => 'scripts/fixes/fix-call-timestamps.php',
        ]);

        // RETELL_005: Laravel Scheduler only loads 1 task
        $error10 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'RETELL_005'],
            [
                'category' => 'SYSTEM',
                'service' => 'internal',
                'title' => 'Laravel Scheduler only loads 1 task',
                'description' => 'Laravel scheduler only shows knowledge:watch task instead of all scheduled tasks.',
                'symptoms' => 'schedule:list shows only one task, other scheduled tasks not running',
                'stack_pattern' => 'schedule:list shows only knowledge:watch',
                'root_causes' => [
                    'Class Loading' => 'Scheduler not loading all command classes',
                    'Cache Issue' => 'Command cache corrupted',
                    'Registration' => 'Commands not properly registered',
                ],
                'severity' => 'medium',
                'is_active' => true,
                'auto_detectable' => false,
            ]
        );

        $error10->tags()->sync([$tags['queue']->id]);

        $error10->solutions()->create([
            'order' => 1,
            'type' => 'manual',
            'title' => 'Use Direct Cron Entries (Workaround)',
            'description' => 'Bypass Laravel scheduler with direct cron entries',
            'steps' => [
                'Add direct cron entries for critical tasks',
                'Verify cron jobs are running',
                'Monitor execution logs',
            ],
            'code_snippet' => "# Add to crontab:\n*/15 * * * * /usr/bin/php /var/www/api-gateway/manual-retell-import.php\n*/5 * * * * /usr/bin/php /var/www/api-gateway/cleanup-stale-calls.php",
            'is_automated' => false,
        ]);

        // DB_002: Too many connections
        $error11 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'DB_002'],
            [
                'category' => 'DB',
                'service' => 'internal',
                'title' => 'Too many connections',
                'description' => 'Database server has reached the maximum number of allowed connections.',
                'symptoms' => 'Random database connection failures, application errors',
                'stack_pattern' => 'SQLSTATE\[HY000\] \[1040\]',
                'root_causes' => [
                    'Connection Leak' => 'Connections not being properly closed',
                    'High Traffic' => 'More concurrent users than connection limit',
                    'Config Issue' => 'max_connections set too low',
                    'No Pooling' => 'Connection pooling not enabled',
                ],
                'severity' => 'high',
                'is_active' => true,
                'auto_detectable' => true,
            ]
        );

        $error11->tags()->sync([$tags['database']->id]);

        $error11->solutions()->create([
            'order' => 1,
            'type' => 'script',
            'title' => 'Fix Database Connection Limit',
            'description' => 'Increase connection limit and enable pooling',
            'steps' => [
                'Check current connections: SHOW PROCESSLIST',
                'Kill idle connections if needed',
                'Increase max_connections in my.cnf',
                'Enable connection pooling',
                'Restart MySQL service',
            ],
            'code_snippet' => "# Check connections:\nSHOW PROCESSLIST;\nSHOW VARIABLES LIKE 'max_connections';\n\n# In my.cnf:\nmax_connections = 500",
            'is_automated' => true,
            'automation_script' => 'scripts/fixes/fix-db-connections.php',
        ]);

        // PERF_001: Slow API Response
        $error12 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'PERF_001'],
            [
                'category' => 'PERFORMANCE',
                'service' => 'internal',
                'title' => 'Slow API Response > 1s',
                'description' => 'API endpoints taking longer than 1 second to respond, causing poor user experience.',
                'symptoms' => 'Slow page loads, API timeouts, user complaints',
                'stack_pattern' => 'AppointmentController@index timeout',
                'root_causes' => [
                    'N+1 Queries' => 'Missing eager loading causing multiple queries',
                    'No Caching' => 'Frequently accessed data not cached',
                    'Large Dataset' => 'Loading too much data without pagination',
                    'No Indexes' => 'Database queries on non-indexed columns',
                ],
                'severity' => 'low',
                'is_active' => true,
                'auto_detectable' => false,
            ]
        );

        $error12->tags()->sync([$tags['database']->id]);

        $error12->solutions()->create([
            'order' => 1,
            'type' => 'code',
            'title' => 'Add Eager Loading',
            'description' => 'Use with() to load relationships efficiently',
            'steps' => [
                'Identify N+1 queries in code',
                'Add eager loading with with()',
                'Test performance improvement',
            ],
            'code_snippet' => "// Before (N+1 problem):\n\$appointments = Appointment::all();\nforeach (\$appointments as \$appointment) {\n    echo \$appointment->customer->name; // N+1!\n}\n\n// After (Eager loading):\n\$appointments = Appointment::with(['customer', 'staff', 'service'])\n    ->paginate(20);",
            'is_automated' => false,
        ]);

        // PERF_002: N+1 Query detected
        $error13 = ErrorCatalog::firstOrCreate(
            ['error_code' => 'PERF_002'],
            [
                'category' => 'PERFORMANCE',
                'service' => 'internal',
                'title' => 'N+1 Query detected',
                'description' => 'Multiple database queries executed in a loop, causing severe performance degradation.',
                'symptoms' => 'Hundreds of queries for simple page loads, slow response times',
                'stack_pattern' => 'Multiple queries in loop',
                'root_causes' => [
                    'Missing Eager Load' => 'Relationships accessed without eager loading',
                    'Loop Queries' => 'Queries executed inside foreach loops',
                    'Lazy Loading' => 'Eloquent lazy loading not optimized',
                ],
                'severity' => 'medium',
                'is_active' => true,
                'auto_detectable' => true,
            ]
        );

        $error13->tags()->sync([$tags['database']->id]);

        $error13->solutions()->create([
            'order' => 1,
            'type' => 'code',
            'title' => 'Use Eager Loading',
            'description' => 'Load all required relationships upfront',
            'steps' => [
                'Identify relationships accessed in loops',
                'Add with() clause to query',
                'Consider using load() for already loaded models',
            ],
            'code_snippet' => "// Problem:\nforeach (\$branches as \$branch) {\n    \$branch->appointments; // N+1!\n}\n\n// Solution:\n\$branches = Branch::with('appointments')->get();\n\n// Or load after:\n\$branches->load('appointments');",
            'is_automated' => false,
        ]);

        // Add prevention tips for new errors
        $error7->preventionTips()->createMany([
            [
                'order' => 1,
                'tip' => 'Always keep API Key and Webhook Secret synchronized',
                'category' => 'configuration',
            ],
            [
                'order' => 2,
                'tip' => 'Test webhook signatures after any configuration change',
                'category' => 'testing',
            ],
        ]);

        $error11->preventionTips()->createMany([
            [
                'order' => 1,
                'tip' => 'Monitor database connections regularly',
                'category' => 'monitoring',
            ],
            [
                'order' => 2,
                'tip' => 'Enable connection pooling for production environments',
                'category' => 'configuration',
            ],
            [
                'order' => 3,
                'tip' => 'Set up alerts for connection limit warnings',
                'category' => 'monitoring',
            ],
        ]);

        $error12->preventionTips()->createMany([
            [
                'order' => 1,
                'tip' => 'Use Laravel Debugbar or Telescope in development',
                'category' => 'development',
            ],
            [
                'order' => 2,
                'tip' => 'Review queries before deploying new features',
                'category' => 'development',
            ],
            [
                'order' => 3,
                'tip' => 'Implement query result caching for frequently accessed data',
                'category' => 'performance',
            ],
        ]);

        $this->command->info('Error catalog seeded successfully with ' . ErrorCatalog::count() . ' errors!');
    }
}