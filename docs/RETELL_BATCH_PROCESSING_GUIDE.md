# Retell AI Batch Processing Guide

## Overview

The Retell AI MCP integration now includes advanced batch processing capabilities for handling large-scale call campaigns efficiently. This guide explains how to use and configure the batch processing system.

## Key Features

- **Laravel Batch Jobs**: Leverages Laravel's built-in batch job system for reliability
- **Parallel Processing**: Process multiple calls concurrently with configurable limits
- **Rate Limiting**: Built-in rate limiting to prevent API overload
- **Progress Tracking**: Real-time monitoring of campaign progress
- **Failure Handling**: Automatic retry logic with exponential backoff
- **Performance Optimization**: Chunked processing for memory efficiency

## Configuration

### Environment Variables

```env
# Enable batch processing (default: true)
RETELL_BATCH_PROCESSING_ENABLED=true

# Chunk size for each batch job
RETELL_BATCH_CHUNK_SIZE=20

# Delay between calls in milliseconds
RETELL_BATCH_CALL_DELAY_MS=500

# Maximum jobs per batch
RETELL_MAX_JOBS_PER_BATCH=100

# Queue name for batch jobs
RETELL_BATCH_QUEUE=campaigns

# Allow job failures without stopping the batch
RETELL_BATCH_ALLOW_FAILURES=true

# Job timeout in seconds
RETELL_BATCH_JOB_TIMEOUT=120

# Campaign-specific rate limits
RETELL_CAMPAIGN_CALLS_PER_MINUTE=30
RETELL_CAMPAIGN_CALLS_PER_HOUR=300
RETELL_MAX_CONCURRENT_CAMPAIGNS=3
```

### Horizon Configuration

The batch processing system requires specific queue workers configured in `config/horizon.php`:

```php
'campaigns' => [
    'connection' => 'redis',
    'queue' => ['campaigns', 'campaigns-batch'],
    'balance' => 'auto',
    'autoScalingStrategy' => 'time',
    'maxProcesses' => 10,
    'minProcesses' => 2,
    'memory' => 512,
    'tries' => 3,
    'timeout' => 300,
    'batch' => true,
],
```

## Usage

### Starting a Campaign with Batch Processing

When you start a campaign through the AI Call Center interface or API, the system automatically uses batch processing if enabled:

```php
// In your code
$bridgeServer = app(RetellAIBridgeMCPServer::class);
$result = $bridgeServer->startCampaign(['campaign_id' => $campaignId]);

// Response includes processing type
[
    'success' => true,
    'campaign_id' => 123,
    'status' => 'running',
    'processing_type' => 'batch', // or 'sequential' if disabled
]
```

### Monitoring Batch Progress

#### Via Artisan Command

```bash
# Monitor all running campaigns
php artisan campaigns:monitor-batches

# Monitor specific campaign
php artisan campaigns:monitor-batches --campaign-id=123

# Output as JSON
php artisan campaigns:monitor-batches --json
```

#### Via Dashboard

The AI Call Center dashboard displays real-time progress for all active campaigns with:
- Progress percentage
- Calls completed/failed
- Estimated time remaining
- Throughput (calls per minute)

#### Programmatically

```php
use Illuminate\Support\Facades\Bus;

$campaign = RetellAICallCampaign::find($campaignId);
$batchId = $campaign->metadata['batch_id'];
$batch = Bus::findBatch($batchId);

if ($batch) {
    echo "Progress: {$batch->progress()}%\n";
    echo "Processed: {$batch->processedJobs()}/{$batch->totalJobs}\n";
    echo "Failed: {$batch->failedJobs}\n";
}
```

## How It Works

### 1. Campaign Initialization
When a campaign starts, the system:
- Queries target customers based on campaign criteria
- Splits customers into chunks (default: 20 per chunk)
- Creates a batch job for each chunk
- Dispatches all jobs as a single batch

### 2. Batch Processing
Each batch job:
- Processes its assigned customers sequentially
- Implements rate limiting using sliding windows
- Handles failures gracefully with retry logic
- Updates campaign counters atomically

### 3. Progress Tracking
The system tracks:
- Total jobs in the batch
- Completed jobs
- Failed jobs
- Overall progress percentage
- Campaign-level statistics

### 4. Completion Handling
When a batch completes:
- Campaign status updates to 'completed'
- Final statistics are calculated
- Success rate is determined
- Optional notifications are sent

## Performance Considerations

### Optimal Configuration

For best performance, consider these settings:

```env
# For high-volume campaigns (1000+ calls)
RETELL_BATCH_CHUNK_SIZE=50
RETELL_MAX_JOBS_PER_BATCH=200
RETELL_CAMPAIGN_CALLS_PER_MINUTE=60

# For smaller campaigns (<100 calls)
RETELL_BATCH_CHUNK_SIZE=10
RETELL_MAX_JOBS_PER_BATCH=50
RETELL_CAMPAIGN_CALLS_PER_MINUTE=20
```

### Memory Usage

Each batch job processes its chunk in memory. Monitor memory usage:

```bash
# Check Horizon memory usage
php artisan horizon:list
```

### Database Optimization

Ensure these indexes exist for optimal query performance:

```sql
-- Already included in migrations
INDEX idx_customers_company_phone (company_id, phone)
INDEX idx_calls_campaign (company_id, metadata->>'$.campaign_id')
```

## Troubleshooting

### Common Issues

#### 1. Batch Jobs Not Processing
```bash
# Check if Horizon is running
php artisan horizon:status

# Check queue configuration
php artisan queue:monitor campaigns
```

#### 2. Rate Limit Exceeded
```bash
# Check current rate limit usage
php artisan tinker
>>> Cache::get('campaign_rate_limit:1')
```

#### 3. Memory Exhaustion
```bash
# Reduce chunk size
RETELL_BATCH_CHUNK_SIZE=10

# Increase worker memory in horizon.php
'memory' => 1024,
```

### Debug Mode

Enable detailed logging:

```env
RETELL_WEBHOOK_DEBUG=true
LOG_LEVEL=debug
```

Check logs:
```bash
tail -f storage/logs/laravel.log | grep -E "(campaign|batch|retell)"
```

## API Integration

### Start Campaign with Batch Processing

```bash
curl -X POST https://api.askproai.de/api/retell-mcp/campaigns/start \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "campaign_id": 123
  }'
```

### Check Batch Status

```bash
curl -X GET https://api.askproai.de/api/retell-mcp/campaigns/123/batch-status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Response:
```json
{
  "batch_id": "9a5e7d4f-3f3a-4b91-9c73-7a8b9e4f3d2a",
  "total_jobs": 50,
  "processed_jobs": 35,
  "failed_jobs": 2,
  "progress": 70,
  "throughput": "12.5 calls/minute",
  "estimated_completion": "2025-07-22T15:30:00Z"
}
```

## Best Practices

1. **Test with Small Batches**: Start with small test campaigns before running large ones
2. **Monitor Rate Limits**: Keep campaigns within Retell.ai's rate limits
3. **Use Appropriate Chunk Sizes**: Balance between memory usage and processing speed
4. **Handle Failures Gracefully**: Implement proper error handling in your campaigns
5. **Schedule During Off-Peak**: Run large campaigns during off-peak hours

## Metrics and Monitoring

Track these key metrics:

- **Campaign Success Rate**: `(completed_calls / total_calls) * 100`
- **Average Call Duration**: Monitor in Call records
- **Throughput**: Calls processed per minute
- **Queue Depth**: Pending jobs in the campaigns queue
- **Error Rate**: Failed jobs percentage

## Security Considerations

- All batch jobs run with tenant isolation
- Customer data is processed in memory only
- Failed job payloads are encrypted
- Webhook signatures are verified

## Migration from Sequential Processing

If upgrading from sequential processing:

1. No code changes required
2. Enable via environment variable
3. Monitor initial campaigns closely
4. Adjust chunk sizes based on performance

## Future Enhancements

Planned improvements:
- [ ] Dynamic chunk size adjustment
- [ ] Priority-based campaign processing  
- [ ] A/B testing support for campaigns
- [ ] Advanced analytics dashboard
- [ ] Multi-region support