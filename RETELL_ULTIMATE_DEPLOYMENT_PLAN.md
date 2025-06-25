# Retell Ultimate Control Center - Deployment Plan

## Executive Summary
Dieser Plan beschreibt einen sicheren, schrittweisen Rollout der Retell Ultimate Control Center Features mit minimalen Risiken für das bestehende System.

**Deployment Zeitplan**: 4 Wochen (1 Woche pro Phase)
**Rollback-Zeit**: Max. 15 Minuten für jede Phase
**Erwartete Downtime**: 0 (Zero-Downtime Deployment)

## 1. Pre-Deployment Checklist

### 1.1 Backup-Strategie

#### Vollständiges System-Backup
```bash
#!/bin/bash
# pre-deployment-backup.sh

BACKUP_DIR="/var/www/api-gateway/storage/backups/deployment-$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR

echo "Starting pre-deployment backup..."

# 1. Database Backup
echo "Backing up database..."
mysqldump -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db | gzip > $BACKUP_DIR/database_full.sql.gz

# 2. Code Backup
echo "Backing up application code..."
tar -czf $BACKUP_DIR/app_code.tar.gz \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='node_modules' \
  --exclude='vendor' \
  /var/www/api-gateway/

# 3. Environment Configuration
echo "Backing up configuration..."
cp /var/www/api-gateway/.env $BACKUP_DIR/.env.backup
cp -r /var/www/api-gateway/config $BACKUP_DIR/config_backup

# 4. Retell Agent Configurations
echo "Backing up Retell agent configurations..."
php artisan retell:backup-agents --output=$BACKUP_DIR/retell_agents.json

# 5. Create backup manifest
cat > $BACKUP_DIR/manifest.json << EOF
{
  "created_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "version": "$(git rev-parse HEAD)",
  "database_size": "$(du -h $BACKUP_DIR/database_full.sql.gz | cut -f1)",
  "deployment_phase": "pre-deployment"
}
EOF

echo "Backup completed: $BACKUP_DIR"
```

#### Incremental Backup vor jeder Phase
```bash
#!/bin/bash
# phase-backup.sh

PHASE=$1
BACKUP_DIR="/var/www/api-gateway/storage/backups/phase-$PHASE-$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR

# Quick database backup (nur geänderte Tabellen)
mysqldump -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db \
  retell_agents \
  retell_agent_configurations \
  retell_custom_functions \
  retell_knowledge_bases \
  --single-transaction \
  --quick | gzip > $BACKUP_DIR/phase_$PHASE.sql.gz

echo "Phase $PHASE backup completed: $BACKUP_DIR"
```

### 1.2 Test-Umgebung Setup

#### Staging Environment Setup
```bash
#!/bin/bash
# setup-staging.sh

# 1. Clone production database to staging
echo "Creating staging database..."
mysql -u root -p'V9LGz2tdR5gpDQz' -e "CREATE DATABASE IF NOT EXISTS askproai_staging;"
mysqldump -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db | \
  mysql -u root -p'V9LGz2tdR5gpDQz' askproai_staging

# 2. Setup staging environment
cd /var/www/api-gateway-staging
cp .env.production .env.staging
sed -i 's/DB_DATABASE=askproai_db/DB_DATABASE=askproai_staging/' .env.staging
sed -i 's/APP_ENV=production/APP_ENV=staging/' .env.staging
sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env.staging

# 3. Deploy code to staging
git pull origin feature/retell-ultimate-control-center
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan cache:clear
php artisan config:cache

echo "Staging environment ready!"
```

### 1.3 Rollback-Plan

#### Automatisches Rollback Script
```bash
#!/bin/bash
# rollback.sh

BACKUP_PATH=$1
if [ -z "$BACKUP_PATH" ]; then
  echo "Usage: ./rollback.sh <backup_path>"
  exit 1
fi

echo "STARTING EMERGENCY ROLLBACK from $BACKUP_PATH"

# 1. Stop all queues
php artisan down
supervisorctl stop horizon

# 2. Restore database
echo "Restoring database..."
gunzip < $BACKUP_PATH/database_full.sql.gz | mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

# 3. Restore code (if needed)
if [ -f "$BACKUP_PATH/app_code.tar.gz" ]; then
  echo "Restoring application code..."
  tar -xzf $BACKUP_PATH/app_code.tar.gz -C /
fi

# 4. Restore environment
cp $BACKUP_PATH/.env.backup /var/www/api-gateway/.env

# 5. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 6. Restart services
supervisorctl start horizon
php artisan up

echo "ROLLBACK COMPLETED!"
```

### 1.4 Monitoring Setup

#### Deployment Monitoring Dashboard
```php
<?php
// app/Console/Commands/DeploymentMonitor.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RetellAgent;
use App\Models\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DeploymentMonitor extends Command
{
    protected $signature = 'deployment:monitor {--interval=60}';
    protected $description = 'Monitor deployment health metrics';

    public function handle()
    {
        $interval = $this->option('interval');
        
        while (true) {
            $metrics = [
                'timestamp' => now()->toIso8601String(),
                'api_health' => $this->checkApiHealth(),
                'database_health' => $this->checkDatabaseHealth(),
                'retell_sync_status' => $this->checkRetellSync(),
                'error_rate' => $this->calculateErrorRate(),
                'response_time' => $this->measureResponseTime(),
                'queue_depth' => $this->getQueueDepth(),
                'memory_usage' => memory_get_usage(true),
                'active_calls' => Call::where('status', 'active')->count(),
            ];
            
            // Store metrics
            Cache::put('deployment_metrics', $metrics, 3600);
            
            // Alert if thresholds exceeded
            $this->checkAlertThresholds($metrics);
            
            $this->info(json_encode($metrics, JSON_PRETTY_PRINT));
            
            sleep($interval);
        }
    }
    
    private function checkApiHealth()
    {
        try {
            $response = app(\App\Services\RetellV2Service::class)->listAgents();
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy: ' . $e->getMessage();
        }
    }
    
    private function checkDatabaseHealth()
    {
        try {
            DB::select('SELECT 1');
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }
    
    private function checkRetellSync()
    {
        $outOfSync = RetellAgent::where('sync_status', '!=', 'synced')
            ->where('updated_at', '<', now()->subMinutes(5))
            ->count();
            
        return $outOfSync === 0 ? 'synced' : "out_of_sync: $outOfSync agents";
    }
    
    private function calculateErrorRate()
    {
        $totalCalls = Call::where('created_at', '>=', now()->subHour())->count();
        $failedCalls = Call::where('created_at', '>=', now()->subHour())
            ->where('status', 'failed')
            ->count();
            
        return $totalCalls > 0 ? round(($failedCalls / $totalCalls) * 100, 2) : 0;
    }
    
    private function measureResponseTime()
    {
        $start = microtime(true);
        
        try {
            DB::select('SELECT 1');
            $dbTime = (microtime(true) - $start) * 1000;
            
            return [
                'database_ms' => round($dbTime, 2),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    private function getQueueDepth()
    {
        return [
            'default' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
        ];
    }
    
    private function checkAlertThresholds($metrics)
    {
        // Error rate > 5%
        if ($metrics['error_rate'] > 5) {
            $this->alert("HIGH ERROR RATE: {$metrics['error_rate']}%");
        }
        
        // Database unhealthy
        if ($metrics['database_health'] !== 'healthy') {
            $this->alert("DATABASE UNHEALTHY: {$metrics['database_health']}");
        }
        
        // API unhealthy
        if ($metrics['api_health'] !== 'healthy') {
            $this->alert("RETELL API UNHEALTHY: {$metrics['api_health']}");
        }
        
        // High queue depth
        if ($metrics['queue_depth']['default'] > 1000) {
            $this->alert("HIGH QUEUE DEPTH: {$metrics['queue_depth']['default']}");
        }
    }
    
    private function alert($message)
    {
        $this->error("[ALERT] $message");
        
        // Send to monitoring system
        logger()->critical($message, [
            'deployment_phase' => config('app.deployment_phase'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

## 2. Phased Rollout Strategy

### Phase 1: Basic Features (Week 1)
**Features**: Agent Update, Dynamic Variables, Basic UI

#### Deployment Steps
```bash
#!/bin/bash
# deploy-phase1.sh

echo "Starting Phase 1 Deployment..."

# 1. Backup
./phase-backup.sh 1

# 2. Enable feature flags
php artisan tinker --execute="
    config(['features.retell_ultimate.agent_update' => true]);
    config(['features.retell_ultimate.dynamic_variables' => true]);
    config(['features.retell_ultimate.advanced_features' => false]);
"

# 3. Deploy code
git checkout feature/retell-ultimate-phase1
composer install --no-dev --optimize-autoloader
npm run build

# 4. Run migrations
php artisan migrate --force --path=database/migrations/phase1/

# 5. Clear caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 6. Start monitoring
php artisan deployment:monitor --interval=30 &

echo "Phase 1 deployed! Monitor for 24 hours before proceeding."
```

#### Phase 1 Tests
```php
<?php
// tests/Feature/Deployment/Phase1Test.php

namespace Tests\Feature\Deployment;

use Tests\TestCase;
use App\Models\RetellAgent;
use App\Models\Company;

class Phase1Test extends TestCase
{
    public function test_agent_update_functionality()
    {
        $company = Company::factory()->create();
        $agent = RetellAgent::factory()->create(['company_id' => $company->id]);
        
        $response = $this->actingAs($company->users->first())
            ->put("/api/retell/agents/{$agent->id}", [
                'agent_name' => 'Updated Agent',
                'pronunciation_guide' => ['test' => 'test pronunciation'],
            ]);
        
        $response->assertOk();
        $this->assertDatabaseHas('retell_agents', [
            'id' => $agent->id,
            'agent_name' => 'Updated Agent',
        ]);
    }
    
    public function test_dynamic_variables_rendering()
    {
        $company = Company::factory()->create([
            'dynamic_variables' => [
                'company_name' => 'Test Company',
                'opening_hours' => '9-17 Uhr',
            ]
        ]);
        
        $agent = RetellAgent::factory()->create([
            'company_id' => $company->id,
            'prompt' => 'Welcome to {{company_name}}, we are open {{opening_hours}}',
        ]);
        
        $processedPrompt = $agent->getProcessedPrompt();
        
        $this->assertStringContainsString('Test Company', $processedPrompt);
        $this->assertStringContainsString('9-17 Uhr', $processedPrompt);
    }
}
```

### Phase 2: Customer Recognition (Week 2)
**Features**: Existing Customer Detection, Call History (ohne VIP Features)

#### Deployment Steps
```bash
#!/bin/bash
# deploy-phase2.sh

echo "Starting Phase 2 Deployment..."

# 1. Verify Phase 1 stability
PHASE1_ERRORS=$(php artisan deployment:check-phase 1)
if [ "$PHASE1_ERRORS" != "0" ]; then
  echo "Phase 1 has errors. Aborting Phase 2."
  exit 1
fi

# 2. Backup
./phase-backup.sh 2

# 3. Enable additional features
php artisan tinker --execute="
    config(['features.retell_ultimate.customer_recognition' => true]);
    config(['features.retell_ultimate.call_history' => true]);
    config(['features.retell_ultimate.vip_features' => false]);
"

# 4. Deploy Phase 2 code
git checkout feature/retell-ultimate-phase2
composer install --no-dev --optimize-autoloader
npm run build

# 5. Run migrations
php artisan migrate --force --path=database/migrations/phase2/

# 6. Warm up caches
php artisan customers:cache-frequent --limit=1000

echo "Phase 2 deployed!"
```

### Phase 3: Multi-Booking (Week 3)
**Features**: Mehrfachbuchungen (max. 3), Erweiterte Validierung

#### Deployment Steps
```bash
#!/bin/bash
# deploy-phase3.sh

echo "Starting Phase 3 Deployment..."

# 1. Backup
./phase-backup.sh 3

# 2. Enable multi-booking with limits
php artisan tinker --execute="
    config(['features.retell_ultimate.multi_booking' => true]);
    config(['features.retell_ultimate.max_bookings_per_call' => 3]);
"

# 3. Deploy Phase 3
git checkout feature/retell-ultimate-phase3
composer install --no-dev --optimize-autoloader
npm run build

# 4. Run migrations
php artisan migrate --force --path=database/migrations/phase3/

# 5. Update booking limits
php artisan retell:update-booking-limits --max=3

echo "Phase 3 deployed with booking limit of 3!"
```

### Phase 4: Full Features (Week 4)
**Features**: Alle Features, VIP Support, Knowledge Base, unbegrenzte Buchungen

#### Deployment Steps
```bash
#!/bin/bash
# deploy-phase4.sh

echo "Starting Phase 4 (Final) Deployment..."

# 1. Final backup
./pre-deployment-backup.sh

# 2. Enable all features
php artisan tinker --execute="
    config(['features.retell_ultimate.all_features' => true]);
    config(['features.retell_ultimate.max_bookings_per_call' => null]);
"

# 3. Deploy final version
git checkout main
git merge feature/retell-ultimate-control-center
composer install --no-dev --optimize-autoloader
npm run build

# 4. Run all remaining migrations
php artisan migrate --force

# 5. Full cache refresh
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Verify deployment
php artisan deployment:verify --phase=4

echo "Full Retell Ultimate Control Center deployed!"
```

## 3. Testing Protocol

### Unit Tests
```bash
#!/bin/bash
# run-unit-tests.sh

# Core service tests
php artisan test --testsuite=Unit --filter=RetellV2Service
php artisan test --testsuite=Unit --filter=CustomerRecognitionService
php artisan test --testsuite=Unit --filter=MultiBookingService

# Agent configuration tests  
php artisan test --testsuite=Unit --filter=AgentConfigurationManager
php artisan test --testsuite=Unit --filter=DynamicVariableProcessor
```

### Integration Tests
```bash
#!/bin/bash
# run-integration-tests.sh

# API endpoint tests
php artisan test --testsuite=Feature --filter=RetellAgentApiTest
php artisan test --testsuite=Feature --filter=CustomerRecognitionApiTest
php artisan test --testsuite=Feature --filter=MultiBookingApiTest

# Webhook processing tests
php artisan test --testsuite=Feature --filter=RetellWebhookTest
```

### End-to-End Tests
```php
<?php
// tests/E2E/RetellUltimateFlowTest.php

namespace Tests\E2E;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Customer;
use App\Models\RetellAgent;

class RetellUltimateFlowTest extends TestCase
{
    public function test_complete_booking_flow_with_recognition()
    {
        // Setup
        $company = Company::factory()->create();
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'phone' => '+4915912345678',
            'is_vip' => true,
        ]);
        
        $agent = RetellAgent::factory()->create([
            'company_id' => $company->id,
            'features' => [
                'customer_recognition' => true,
                'multi_booking' => true,
            ]
        ]);
        
        // Simulate incoming call
        $response = $this->postJson('/api/retell/webhook', [
            'event' => 'call_started',
            'call' => [
                'id' => 'call_123',
                'agent_id' => $agent->retell_agent_id,
                'from_number' => '+4915912345678',
            ]
        ]);
        
        $response->assertOk();
        
        // Verify customer was recognized
        $this->assertDatabaseHas('calls', [
            'call_id' => 'call_123',
            'customer_id' => $customer->id,
            'is_vip_caller' => true,
        ]);
    }
    
    public function test_multi_booking_flow()
    {
        $company = Company::factory()->create();
        $agent = RetellAgent::factory()->create([
            'company_id' => $company->id,
            'features' => ['multi_booking' => true]
        ]);
        
        // Simulate call with multiple bookings
        $response = $this->postJson('/api/retell/webhook', [
            'event' => 'call_ended',
            'call' => [
                'id' => 'call_456',
                'agent_id' => $agent->retell_agent_id,
                'custom_analysis_data' => [
                    'bookings' => [
                        ['date' => '2025-07-01', 'service' => 'Haircut'],
                        ['date' => '2025-07-08', 'service' => 'Haircut'],
                        ['date' => '2025-07-15', 'service' => 'Haircut'],
                    ]
                ]
            ]
        ]);
        
        $response->assertOk();
        
        // Verify 3 appointments created
        $this->assertEquals(3, 
            \App\Models\Appointment::where('call_id', 'call_456')->count()
        );
    }
}
```

### Performance Tests
```bash
#!/bin/bash
# run-performance-tests.sh

# Load test for agent updates
echo "Testing agent update performance..."
ab -n 1000 -c 10 -T 'application/json' \
   -H 'Authorization: Bearer TOKEN' \
   -p agent_update.json \
   https://api.askproai.de/api/retell/agents/123

# Load test for customer recognition
echo "Testing customer recognition performance..."
ab -n 5000 -c 50 -T 'application/json' \
   -H 'Authorization: Bearer TOKEN' \
   -p call_webhook.json \
   https://api.askproai.de/api/retell/webhook

# Database query performance
php artisan tinker --execute="
    \$start = microtime(true);
    \App\Services\CustomerRecognitionService::findByPhone('+4915912345678');
    echo 'Recognition time: ' . (microtime(true) - \$start) . ' seconds';
"
```

## 4. Monitoring & Alerting

### Key Metrics Dashboard
```yaml
# grafana-dashboard.yml
apiVersion: 1
providers:
  - name: 'Retell Ultimate Deployment'
    folder: 'Deployment'
    type: file
    options:
      path: /var/lib/grafana/dashboards

dashboards:
  - title: "Retell Ultimate Deployment Monitor"
    panels:
      - title: "API Error Rate"
        targets:
          - expr: 'rate(retell_api_errors_total[5m])'
        alert:
          condition: "last() > 0.05"
          message: "API error rate > 5%"
      
      - title: "Agent Sync Status"
        targets:
          - expr: 'retell_agents_out_of_sync'
        alert:
          condition: "last() > 10"
          message: "More than 10 agents out of sync"
      
      - title: "Customer Recognition Performance"
        targets:
          - expr: 'histogram_quantile(0.95, retell_customer_recognition_duration_seconds)'
        alert:
          condition: "last() > 1"
          message: "Customer recognition taking > 1s"
      
      - title: "Multi-Booking Success Rate"
        targets:
          - expr: 'rate(retell_multi_booking_success[5m]) / rate(retell_multi_booking_total[5m])'
        alert:
          condition: "last() < 0.95"
          message: "Multi-booking success rate < 95%"
```

### Alert Configuration
```php
<?php
// config/monitoring.php

return [
    'alerts' => [
        'deployment' => [
            'channels' => ['slack', 'email', 'sms'],
            'thresholds' => [
                'error_rate' => 5, // percent
                'response_time' => 1000, // ms
                'sync_delay' => 300, // seconds
                'memory_usage' => 80, // percent
                'queue_depth' => 1000, // jobs
            ],
            'escalation' => [
                'level_1' => ['slack'], // immediate
                'level_2' => ['slack', 'email'], // after 5 min
                'level_3' => ['slack', 'email', 'sms'], // after 15 min
            ],
        ],
    ],
];
```

### Real-time Monitoring Script
```bash
#!/bin/bash
# monitor-deployment.sh

# Terminal dashboard for deployment monitoring
watch -n 5 'echo "=== RETELL ULTIMATE DEPLOYMENT MONITOR ==="
echo
echo "Time: $(date)"
echo
echo "=== API Health ==="
curl -s http://localhost/api/health | jq .
echo
echo "=== Database Status ==="
mysql -u askproai_user -p"lkZ57Dju9EDjrMxn" -e "
SELECT 
  COUNT(*) as total_agents,
  SUM(CASE WHEN sync_status = \"synced\" THEN 1 ELSE 0 END) as synced,
  SUM(CASE WHEN sync_status != \"synced\" THEN 1 ELSE 0 END) as out_of_sync
FROM retell_agents;" askproai_db
echo
echo "=== Recent Errors ==="
tail -n 10 /var/www/api-gateway/storage/logs/laravel.log | grep ERROR
echo
echo "=== Queue Status ==="
php artisan queue:monitor
echo
echo "=== Memory Usage ==="
free -h'
```

## 5. Rollback Procedures

### Immediate Rollback (< 5 minutes)
```bash
#!/bin/bash
# immediate-rollback.sh

echo "INITIATING IMMEDIATE ROLLBACK!"

# 1. Disable features instantly
php artisan tinker --execute="
    config(['features.retell_ultimate.all_features' => false]);
    Cache::flush();
"

# 2. Revert to previous code
git checkout HEAD~1
composer install --no-dev
php artisan optimize:clear

# 3. Notify team
curl -X POST https://hooks.slack.com/services/YOUR/WEBHOOK/URL \
  -H 'Content-Type: application/json' \
  -d '{"text":"🚨 ROLLBACK INITIATED: Retell Ultimate deployment reverted"}'

echo "Immediate rollback completed!"
```

### Database Rollback
```bash
#!/bin/bash
# database-rollback.sh

PHASE=$1
BACKUP_DIR="/var/www/api-gateway/storage/backups"

# Find latest backup for phase
LATEST_BACKUP=$(ls -t $BACKUP_DIR/phase-$PHASE-*/phase_$PHASE.sql.gz | head -1)

if [ -z "$LATEST_BACKUP" ]; then
  echo "No backup found for phase $PHASE"
  exit 1
fi

echo "Rolling back database to: $LATEST_BACKUP"

# Restore specific tables
gunzip < $LATEST_BACKUP | mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

# Clear caches
php artisan cache:clear
redis-cli FLUSHALL

echo "Database rollback completed!"
```

### Agent Configuration Rollback
```php
<?php
// app/Console/Commands/RollbackAgentConfigurations.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RetellAgent;
use Illuminate\Support\Facades\Storage;

class RollbackAgentConfigurations extends Command
{
    protected $signature = 'retell:rollback-agents {backup-file}';
    protected $description = 'Rollback Retell agent configurations from backup';

    public function handle()
    {
        $backupFile = $this->argument('backup-file');
        
        if (!file_exists($backupFile)) {
            $this->error("Backup file not found: $backupFile");
            return 1;
        }
        
        $this->info("Starting agent configuration rollback...");
        
        $backup = json_decode(file_get_contents($backupFile), true);
        $rolledBack = 0;
        
        foreach ($backup['agents'] as $agentData) {
            $agent = RetellAgent::find($agentData['id']);
            
            if (!$agent) {
                $this->warn("Agent {$agentData['id']} not found, skipping...");
                continue;
            }
            
            // Restore configuration
            $agent->update([
                'agent_name' => $agentData['agent_name'],
                'voice_id' => $agentData['voice_id'],
                'language' => $agentData['language'],
                'prompt' => $agentData['prompt'],
                'response_engine' => $agentData['response_engine'],
                'llm_websocket_url' => $agentData['llm_websocket_url'],
                'custom_functions' => $agentData['custom_functions'],
                'pronunciation_guide' => $agentData['pronunciation_guide'],
                'features' => $agentData['features'],
            ]);
            
            // Sync to Retell
            try {
                app(\App\Services\RetellV2Service::class)->updateAgent(
                    $agent->retell_agent_id,
                    $agent->toRetellPayload()
                );
                
                $rolledBack++;
                $this->info("Rolled back agent: {$agent->agent_name}");
            } catch (\Exception $e) {
                $this->error("Failed to sync agent {$agent->id}: " . $e->getMessage());
            }
        }
        
        $this->info("Rollback completed! Rolled back $rolledBack agents.");
        
        return 0;
    }
}
```

### Communication Plan Template
```markdown
# Rollback Communication Template

## Internal Team Notification

**Subject**: 🚨 Deployment Rollback - Retell Ultimate Control Center

**Team**: Engineering, Support, Management

**Message**:
We have initiated a rollback of the Retell Ultimate Control Center deployment due to [ISSUE].

**Current Status**: 
- Rollback initiated at: [TIME]
- Affected features: [FEATURES]
- Expected completion: [ETA]

**Impact**:
- [List any customer impact]
- [List any data impact]

**Next Steps**:
1. Engineering team investigating root cause
2. Support team monitoring customer tickets
3. Status update in 30 minutes

---

## Customer Communication (if needed)

**Subject**: Temporary Service Adjustment

**Message**:
Dear valued customer,

We are currently performing maintenance on our advanced calling features to ensure the best possible service quality. 

**What's affected**:
- Some advanced features may be temporarily unavailable
- Basic calling and booking functionality remains fully operational

**Duration**: Approximately [TIME] 

We apologize for any inconvenience and appreciate your patience.

Best regards,
The AskProAI Team
```

## 6. Post-Deployment Checklist

### Verification Script
```bash
#!/bin/bash
# post-deployment-verify.sh

echo "Running post-deployment verification..."

# 1. Feature flags
echo "Checking feature flags..."
php artisan tinker --execute="
    \$features = config('features.retell_ultimate');
    foreach (\$features as \$key => \$value) {
        echo \"\$key: \" . (\$value ? 'enabled' : 'disabled') . PHP_EOL;
    }
"

# 2. API endpoints
echo -e "\nTesting API endpoints..."
endpoints=(
    "/api/retell/agents"
    "/api/retell/agents/sync-status"
    "/api/customers/recognize"
    "/api/health"
)

for endpoint in "${endpoints[@]}"; do
    response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost$endpoint)
    echo "$endpoint: $response"
done

# 3. Database integrity
echo -e "\nChecking database integrity..."
php artisan db:check-integrity

# 4. Queue processing
echo -e "\nChecking queue health..."
php artisan horizon:status

# 5. Generate report
php artisan deployment:report --phase=current > deployment_report_$(date +%Y%m%d_%H%M%S).txt

echo -e "\nVerification complete! Check deployment_report_*.txt for details."
```

### Success Criteria
- ✅ Error rate < 1%
- ✅ All API endpoints responding < 500ms
- ✅ No sync failures in last hour
- ✅ Queue depth < 100 jobs
- ✅ All automated tests passing
- ✅ No critical alerts in monitoring

## 7. Lessons Learned Documentation

Nach jeder Phase sollte ein "Lessons Learned" Dokument erstellt werden:

```markdown
# Phase [X] Lessons Learned

**Deployment Date**: [DATE]
**Duration**: [TIME]
**Team**: [MEMBERS]

## What Went Well
- [Success points]

## What Could Be Improved  
- [Improvement areas]

## Issues Encountered
- [Issue]: [Resolution]

## Recommendations for Next Phase
- [Specific recommendations]

## Metrics
- Deployment time: [TIME]
- Rollback required: [YES/NO]
- Customer impact: [NONE/MINOR/MAJOR]
- Support tickets: [COUNT]
```

Dieser umfassende Deployment-Plan stellt sicher, dass die Retell Ultimate Control Center Features sicher und kontrolliert ausgerollt werden können, mit minimalen Risiken und maximaler Transparenz.