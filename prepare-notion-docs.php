<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create notion-ready directory if it doesn't exist
$notionDir = base_path('docs/notion-ready');
if (!is_dir($notionDir)) {
    mkdir($notionDir, 0755, true);
}

// Documentation mapping (source => destination)
$documentMapping = [
    // Cal.com Integration
    'CALCOM_INTEGRATION_GUIDE.md' => [
        'source' => 'docs/CALCOM_V2_API_DOCUMENTATION.md',
        'extract' => 'sections',
        'sections' => ['Overview', 'Key Features', 'Architecture']
    ],
    'CALCOM_WEBHOOK_SETUP.md' => [
        'source' => 'docs/CALCOM_V2_API_DOCUMENTATION.md',
        'extract' => 'sections',
        'sections' => ['Webhook Endpoints', 'Webhook Processing', 'Webhook Security']
    ],
    'CALCOM_ERROR_HANDLING.md' => [
        'source' => 'docs/CALCOM_V2_API_DOCUMENTATION.md',
        'extract' => 'sections',
        'sections' => ['Error Handling', 'Circuit Breaker', 'Retry Logic']
    ],
    'CALCOM_BOOKING_FLOW.md' => [
        'source' => 'ENHANCED_BOOKING_SERVICE.md',
        'extract' => 'full'
    ],
    'CALCOM_MONITORING.md' => [
        'source' => 'docs/CALCOM_V2_API_DOCUMENTATION.md',
        'extract' => 'sections',
        'sections' => ['Health Monitoring', 'Metrics', 'Logging']
    ],
    'CALCOM_TROUBLESHOOTING.md' => [
        'source' => 'TROUBLESHOOTING_GUIDE.md',
        'extract' => 'sections',
        'sections' => ['Cal.com', 'Calendar', 'Booking']
    ],
    
    // Email System
    'EMAIL_SYSTEM_COMPLETE.md' => [
        'source' => 'EMAIL_SYSTEM_DOCUMENTATION.md',
        'extract' => 'full'
    ],
    'EMAIL_CONFIGURATION.md' => [
        'content' => "# Email Configuration Guide\n\n## Environment Variables\n\n```env\nMAIL_MAILER=smtp\nMAIL_HOST=smtp.udag.de\nMAIL_PORT=587\nMAIL_USERNAME=your-username\nMAIL_PASSWORD=your-password\nMAIL_ENCRYPTION=tls\nMAIL_FROM_ADDRESS=noreply@askproai.de\nMAIL_FROM_NAME=\"AskProAI\"\n```\n\n## Queue Configuration\n\nEmails are sent via queue for better performance:\n\n```php\n// config/queue.php\n'mail' => [\n    'driver' => 'redis',\n    'connection' => 'default',\n    'queue' => 'mail',\n    'retry_after' => 90,\n    'block_for' => null,\n],\n```\n\n## Testing Email\n\n```bash\n# Test email configuration\nphp artisan tinker\n>>> Mail::raw('Test email', function(\$message) {\n>>>     \$message->to('test@example.com')->subject('Test');\n>>> });\n```"
    ],
    'EMAIL_TEMPLATES.md' => [
        'content' => "# Email Templates\n\n## Available Templates\n\n### 1. Call Summary Email\n- Location: `resources/views/emails/call-summary.blade.php`\n- Used for: Sending call summaries after AI phone calls\n- Variables: `\$call`, `\$company`, `\$customer`\n\n### 2. Appointment Confirmation\n- Location: `resources/views/emails/appointment-confirmation.blade.php`\n- Used for: Confirming new appointments\n- Variables: `\$appointment`, `\$customer`, `\$service`\n\n### 3. Appointment Reminder\n- Location: `resources/views/emails/appointment-reminder.blade.php`\n- Used for: Reminding customers of upcoming appointments\n- Variables: `\$appointment`, `\$customer`\n\n## Template Customization\n\n```php\n// Example template structure\n@component('mail::message')\n# Hello {{ \$customer->name }}\n\nYour appointment details:\n- Date: {{ \$appointment->formatted_date }}\n- Time: {{ \$appointment->formatted_time }}\n- Service: {{ \$appointment->service->name }}\n\n@component('mail::button', ['url' => \$url])\nView Appointment\n@endcomponent\n\nThanks,<br>\n{{ config('app.name') }}\n@endcomponent\n```"
    ],
    'EMAIL_TROUBLESHOOTING.md' => [
        'content' => "# Email Troubleshooting Guide\n\n## Common Issues\n\n### 1. Emails Not Sending\n\n**Check Queue Worker**\n```bash\n# Check if Horizon is running\nphp artisan horizon:status\n\n# Start Horizon\nphp artisan horizon\n\n# Check failed jobs\nphp artisan queue:failed\n```\n\n**Check Mail Configuration**\n```bash\n# Test mail config\nphp artisan tinker\n>>> config('mail')\n```\n\n### 2. Email Delays\n\n**Monitor Queue**\n```bash\n# Check queue size\nphp artisan queue:monitor mail\n\n# Process mail queue specifically\nphp artisan queue:work --queue=mail\n```\n\n### 3. Template Errors\n\n**Debug Templates**\n```php\n// Add to controller\nreturn view('emails.template', \$data);\n```\n\n**Clear View Cache**\n```bash\nphp artisan view:clear\n```"
    ],
    
    // Infrastructure
    'INFRASTRUCTURE_ARCHITECTURE.md' => [
        'content' => "# Infrastructure Architecture\n\n## Server Setup\n\n- **Provider**: Netcup\n- **Server**: VPS 2000 G10\n- **OS**: Ubuntu 22.04 LTS\n- **Web Server**: Nginx + PHP-FPM 8.3\n- **Database**: MariaDB 10.6\n- **Cache**: Redis 7.0\n- **Queue**: Laravel Horizon\n\n## Directory Structure\n\n```\n/var/www/api-gateway/          # Main application\n├── app/                       # Application code\n├── config/                    # Configuration files\n├── database/                  # Migrations and seeds\n├── public/                    # Web root\n├── resources/                 # Views and assets\n├── routes/                    # Application routes\n├── storage/                   # Application storage\n└── vendor/                    # Composer dependencies\n```\n\n## Network Architecture\n\n```\n[Internet]\n    |\n[Cloudflare]\n    |\n[Nginx]\n    |\n[PHP-FPM]\n    |\n[Laravel App]\n    |     |\n[Redis] [MariaDB]\n```\n\n## Security Layers\n\n1. **Cloudflare**: DDoS protection, WAF\n2. **Firewall**: UFW with strict rules\n3. **SSL**: Let's Encrypt certificates\n4. **Application**: Laravel security middleware"
    ],
    'SERVER_CONFIGURATION.md' => [
        'content' => "# Server Configuration\n\n## Nginx Configuration\n\n```nginx\nserver {\n    listen 80;\n    listen [::]:80;\n    server_name api.askproai.de;\n    return 301 https://\$server_name\$request_uri;\n}\n\nserver {\n    listen 443 ssl http2;\n    listen [::]:443 ssl http2;\n    server_name api.askproai.de;\n    root /var/www/api-gateway/public;\n\n    ssl_certificate /etc/letsencrypt/live/api.askproai.de/fullchain.pem;\n    ssl_certificate_key /etc/letsencrypt/live/api.askproai.de/privkey.pem;\n\n    index index.php;\n\n    location / {\n        try_files \$uri \$uri/ /index.php?\$query_string;\n    }\n\n    location ~ \\.php$ {\n        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;\n        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;\n        include fastcgi_params;\n    }\n}\n```\n\n## PHP Configuration\n\n```ini\n; /etc/php/8.3/fpm/php.ini\nmemory_limit = 512M\nmax_execution_time = 300\nupload_max_filesize = 50M\npost_max_size = 50M\n```\n\n## System Services\n\n```bash\n# Key services\nsystemctl status nginx\nsystemctl status php8.3-fpm\nsystemctl status mariadb\nsystemctl status redis\n```"
    ],
    'SECURITY_HARDENING.md' => [
        'content' => "# Security Hardening Guide\n\n## Firewall Configuration\n\n```bash\n# UFW Rules\nufw default deny incoming\nufw default allow outgoing\nufw allow 22/tcp     # SSH\nufw allow 80/tcp     # HTTP\nufw allow 443/tcp    # HTTPS\nufw enable\n```\n\n## SSH Hardening\n\n```bash\n# /etc/ssh/sshd_config\nPermitRootLogin no\nPasswordAuthentication no\nPubkeyAuthentication yes\nPort 22222  # Non-standard port\n```\n\n## Application Security\n\n### Environment Variables\n```bash\n# Secure .env file\nchmod 600 .env\nchown www-data:www-data .env\n```\n\n### Directory Permissions\n```bash\n# Set proper permissions\nchown -R www-data:www-data /var/www/api-gateway\nfind /var/www/api-gateway -type f -exec chmod 644 {} \\;\nfind /var/www/api-gateway -type d -exec chmod 755 {} \\;\nchmod -R 775 storage bootstrap/cache\n```\n\n## Monitoring\n\n### Fail2ban\n```bash\n# Install and configure\napt install fail2ban\ncp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local\n# Configure for SSH, Nginx\n```\n\n### Log Monitoring\n```bash\n# Key logs to monitor\n/var/log/nginx/error.log\n/var/log/php8.3-fpm.log\n/var/www/api-gateway/storage/logs/laravel.log\n```"
    ],
    
    // Queue & Horizon
    'QUEUE_HORIZON_GUIDE.md' => [
        'content' => "# Queue & Horizon Complete Guide\n\n## Overview\n\nLaravel Horizon provides a dashboard and code-driven configuration for Redis queues.\n\n## Installation & Setup\n\n```bash\n# Install Horizon\ncomposer require laravel/horizon\nphp artisan horizon:install\n\n# Publish assets\nphp artisan horizon:publish\n```\n\n## Configuration\n\n```php\n// config/horizon.php\n'environments' => [\n    'production' => [\n        'supervisor-1' => [\n            'connection' => 'redis',\n            'queue' => ['high', 'default', 'low'],\n            'balance' => 'auto',\n            'maxProcesses' => 10,\n            'tries' => 3,\n            'nice' => 0,\n        ],\n    ],\n],\n```\n\n## Queue Priorities\n\n1. **High Priority**: Webhooks, API callbacks\n2. **Default**: Email, notifications\n3. **Low Priority**: Reports, maintenance\n\n## Running Horizon\n\n```bash\n# Development\nphp artisan horizon\n\n# Production (via Supervisor)\nsupervisorctl start horizon\n```"
    ],
    'QUEUE_CONFIGURATION.md' => [
        'content' => "# Queue Configuration\n\n## Redis Configuration\n\n```php\n// config/database.php\n'redis' => [\n    'client' => env('REDIS_CLIENT', 'phpredis'),\n    'default' => [\n        'url' => env('REDIS_URL'),\n        'host' => env('REDIS_HOST', '127.0.0.1'),\n        'password' => env('REDIS_PASSWORD', null),\n        'port' => env('REDIS_PORT', '6379'),\n        'database' => env('REDIS_DB', '0'),\n    ],\n],\n```\n\n## Queue Drivers\n\n```php\n// config/queue.php\n'default' => env('QUEUE_CONNECTION', 'redis'),\n\n'connections' => [\n    'redis' => [\n        'driver' => 'redis',\n        'connection' => 'default',\n        'queue' => env('REDIS_QUEUE', 'default'),\n        'retry_after' => 90,\n        'block_for' => null,\n        'after_commit' => false,\n    ],\n],\n```\n\n## Job Configuration\n\n```php\n// Example Job\nclass ProcessWebhook implements ShouldQueue\n{\n    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;\n    \n    public \$tries = 3;\n    public \$maxExceptions = 3;\n    public \$timeout = 120;\n    public \$backoff = [10, 30, 60];\n    \n    public function handle()\n    {\n        // Job logic\n    }\n}\n```"
    ],
    'HORIZON_MONITORING.md' => [
        'content' => "# Horizon Monitoring\n\n## Dashboard Access\n\nAccess Horizon dashboard at: `/horizon`\n\n## Metrics Available\n\n1. **Job Metrics**\n   - Jobs per minute\n   - Job runtime\n   - Failed jobs\n\n2. **Queue Metrics**\n   - Queue length\n   - Wait time\n   - Throughput\n\n3. **Worker Metrics**\n   - Active workers\n   - Memory usage\n   - CPU usage\n\n## Monitoring Commands\n\n```bash\n# Check status\nphp artisan horizon:status\n\n# List failed jobs\nphp artisan queue:failed\n\n# Monitor in real-time\nphp artisan horizon:snapshot\n```\n\n## Alerts Configuration\n\n```php\n// config/horizon.php\n'waits' => [\n    'redis:default' => 60,  // Alert if job waits > 60 seconds\n],\n\n'trim' => [\n    'recent' => 60,\n    'pending' => 60,\n    'completed' => 60,\n    'recent_failed' => 10080,\n    'failed' => 10080,\n    'monitored' => 10080,\n],\n```\n\n## Supervisor Configuration\n\n```ini\n[program:horizon]\nprocess_name=%(program_name)s\ncommand=php /var/www/api-gateway/artisan horizon\nautostart=true\nautorestart=true\nuser=www-data\nredirect_stderr=true\nstdout_logfile=/var/www/api-gateway/storage/logs/horizon.log\nstopwaitsecs=3600\n```"
    ]
];

// Process each document
foreach ($documentMapping as $filename => $config) {
    $destPath = $notionDir . '/' . $filename;
    
    if (isset($config['content'])) {
        // Direct content
        file_put_contents($destPath, $config['content']);
        echo "Created: $filename\n";
    } elseif (isset($config['source'])) {
        $sourcePath = base_path($config['source']);
        if (!file_exists($sourcePath)) {
            $sourcePath = base_path('docs/' . $config['source']);
        }
        
        if (file_exists($sourcePath)) {
            if ($config['extract'] === 'full') {
                copy($sourcePath, $destPath);
                echo "Copied: $filename from {$config['source']}\n";
            } elseif ($config['extract'] === 'sections' && isset($config['sections'])) {
                // Extract specific sections (simplified)
                $content = file_get_contents($sourcePath);
                $extractedContent = "# " . str_replace('_', ' ', str_replace('.md', '', $filename)) . "\n\n";
                $extractedContent .= "Extracted from: {$config['source']}\n\n";
                
                foreach ($config['sections'] as $section) {
                    if (stripos($content, $section) !== false) {
                        $extractedContent .= "## $section\n\n";
                        $extractedContent .= "See full documentation in {$config['source']}\n\n";
                    }
                }
                
                file_put_contents($destPath, $extractedContent);
                echo "Extracted sections for: $filename\n";
            }
        } else {
            echo "Source not found for $filename: {$config['source']}\n";
        }
    }
}

echo "\nPreparation complete! Check docs/notion-ready/ directory.\n";