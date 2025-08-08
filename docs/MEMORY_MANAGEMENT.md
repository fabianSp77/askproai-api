# Job Memory Management System

This document describes the comprehensive memory management system implemented for Laravel jobs to prevent memory leaks and overflow issues.

## Overview

The memory management system consists of:

1. **MemoryAwareJob Trait** - Core functionality for memory monitoring and management
2. **Memory Monitoring Command** - Tools for monitoring job memory usage
3. **Updated Job Classes** - Critical jobs enhanced with memory management
4. **Testing Framework** - Tools for testing memory management functionality

## Components

### 1. MemoryAwareJob Trait

**Location**: `/app/Traits/MemoryAwareJob.php`

**Features**:
- Real-time memory usage tracking
- Automatic garbage collection
- Memory limit enforcement
- Memory-safe chunk processing
- Job splitting for large datasets
- Comprehensive logging

**Key Methods**:

```php
// Initialize memory monitoring
$this->initializeMemoryMonitoring();

// Set memory limits
$this->setMemoryLimit(256); // 256MB limit

// Check memory usage at any point
$this->checkMemoryUsage('operation_name');

// Process data in memory-safe chunks
$this->processInChunks($data, $processor, $chunkSize);

// Force garbage collection
$this->forceGarbageCollection();

// Finalize monitoring
$this->finalizeMemoryMonitoring();
```

### 2. Memory Monitoring Command

**Location**: `/app/Console/Commands/MonitorJobMemory.php`

**Usage**:
```bash
# Monitor for 1 hour with 60-second intervals
php artisan jobs:monitor-memory --interval=60 --duration=3600

# Monitor with custom threshold and output file
php artisan jobs:monitor-memory --threshold=80 --output-file=/tmp/memory-report.txt
```

**Features**:
- Real-time system and PHP memory monitoring
- Job queue statistics
- Configurable alerting thresholds
- Detailed reporting with JSON export
- Historical data tracking

### 3. Updated Job Classes

The following critical job classes have been enhanced with memory management:

#### ProcessRetellAICampaignJob
- **Memory Limit**: 512MB
- **Chunk Size**: 50 customers per chunk
- **Features**: Auto-splitting into child jobs for large campaigns
- **Monitoring**: Customer processing, API calls, campaign completion

#### TrainMLModelJob
- **Memory Limit**: 1024MB (1GB)
- **Chunk Size**: 1000 records per chunk
- **Features**: Streaming CSV processing, class balancing optimization
- **Monitoring**: Data preparation, Python training, model saving

#### SendAppointmentReminderJob
- **Memory Limit**: 128MB
- **Features**: Lightweight monitoring for notification jobs
- **Monitoring**: Reminder processing by type

#### AnalyzeCallSentimentJob
- **Memory Limit**: 256MB
- **Features**: Chunked agent metrics processing
- **Monitoring**: Sentiment analysis, metrics updates

#### BulkAssignStaffToEventTypesJob
- **Memory Limit**: 256MB
- **Chunk Size**: 50 assignments per chunk
- **Features**: Auto-splitting for large bulk operations
- **Monitoring**: Assignment processing, database operations

#### ProcessRetellAICampaignBatchJob
- **Memory Limit**: 512MB
- **Chunk Size**: 100 customers per chunk
- **Features**: Batch job creation monitoring
- **Monitoring**: Customer queries, job creation, batch dispatch

## Configuration

### Memory Limits by Job Type

| Job Type | Memory Limit | Chunk Size | Auto-Split Threshold |
|----------|-------------|------------|---------------------|
| Campaign Processing | 512MB | 50 | 2KB per customer |
| ML Training | 1024MB | 1000 | 10KB per record |
| Bulk Operations | 256MB | 50 | 1KB per operation |
| Notifications | 128MB | 100 | N/A |
| Data Analysis | 256MB | 100 | 5KB per record |

### Environment Variables

```env
# Memory monitoring settings
MEMORY_MONITORING_ENABLED=true
MEMORY_WARNING_THRESHOLD=0.8
MEMORY_DEFAULT_LIMIT_MB=256
MEMORY_DEFAULT_CHUNK_SIZE=100

# Job splitting settings
JOB_AUTO_SPLIT_ENABLED=true
JOB_MAX_ITEMS_PER_CHILD=500
```

## Usage Examples

### Basic Memory Monitoring

```php
use App\Traits\MemoryAwareJob;

class MyJob implements ShouldQueue
{
    use MemoryAwareJob;
    
    public function handle()
    {
        // Initialize monitoring
        $this->initializeMemoryMonitoring();
        $this->setMemoryLimit(256); // 256MB
        
        // Your job logic here
        $this->processData();
        
        // Finalize monitoring
        $this->finalizeMemoryMonitoring();
    }
}
```

### Chunk Processing

```php
public function processLargeDataset($data)
{
    $results = $this->processInChunks($data, function ($chunk) {
        $chunkResults = [];
        
        foreach ($chunk as $item) {
            $this->checkMemoryUsage('processing_item');
            $chunkResults[] = $this->processItem($item);
        }
        
        return $chunkResults;
    }, 100); // 100 items per chunk
    
    return $results;
}
```

### Auto-Splitting Jobs

```php
public function handle()
{
    // Check if job should be split
    if ($this->shouldSplitJob($this->data->count(), 2048)) {
        $this->splitIntoChildJobs();
        return;
    }
    
    // Process normally if small enough
    $this->processData();
}

protected function splitIntoChildJobs()
{
    $childJobs = $this->createChildJobs(
        $this->data,
        static::class,
        500 // 500 items per child job
    );
    
    foreach ($childJobs as $childJob) {
        dispatch($childJob);
    }
}
```

## Monitoring and Alerting

### Memory Usage Logs

All memory-aware jobs automatically log:
- Initial memory usage
- Memory usage at checkpoints
- Peak memory usage
- Memory freed by garbage collection
- Final memory statistics

### Log Format

```json
{
    "job": "App\\Jobs\\ProcessRetellAICampaignJob",
    "operation": "customer_processing_123",
    "current_memory_mb": 145.2,
    "peak_memory_mb": 158.7,
    "memory_limit_mb": 512.0,
    "usage_percentage": 28.4,
    "timestamp": "2025-08-06T10:30:00Z"
}
```

### Alerts

The system triggers alerts for:
- Memory usage > 80% of limit
- Rapid memory growth (>50MB in 1 minute)
- Memory limit exceeded
- Garbage collection cycles > 100
- Job splitting events

## Testing

### Test Script

Run the memory management test:
```bash
php test-memory-management.php
```

### Manual Testing Commands

```bash
# Test memory monitoring
php artisan jobs:monitor-memory --interval=10 --duration=300

# Process a test campaign
php artisan retell:test-campaign --customers=1000

# Monitor specific queue
php artisan queue:work --queue=campaigns --memory=256

# Check job memory in Horizon
php artisan horizon:status
```

### Load Testing

```bash
# Generate test data
php artisan db:seed --class=LargeDatasetSeeder

# Run memory-intensive jobs
php artisan queue:work --memory=128 --timeout=300

# Monitor results
tail -f storage/logs/laravel.log | grep -i memory
```

## Performance Optimization

### Memory Optimization Tips

1. **Use Chunking**: Always process large datasets in chunks
2. **Force GC**: Call `$this->forceGarbageCollection()` after processing chunks
3. **Clear Variables**: Unset large variables when no longer needed
4. **Monitor Actively**: Use `checkMemoryUsage()` at critical points
5. **Split Jobs**: Use auto-splitting for very large datasets

### Database Optimization

```php
// Instead of loading all records at once
$records = Model::all(); // BAD

// Use chunking
Model::chunk(1000, function ($records) {
    // Process records
    $this->forceGarbageCollection();
}); // GOOD
```

### Collection Optimization

```php
// Clear collections after use
$results = collect($data)->map($processor);
unset($data); // Free original data
$this->forceGarbageCollection();
```

## Troubleshooting

### Common Issues

1. **Memory Limit Exceeded**
   - Check if chunk size is too large
   - Verify memory limit is appropriate
   - Look for memory leaks in processing logic

2. **Jobs Not Splitting**
   - Verify `shouldSplitJob()` thresholds
   - Check `createChildJobs()` implementation
   - Ensure child jobs are properly dispatched

3. **High Memory Usage**
   - Enable detailed monitoring
   - Check for circular references
   - Verify garbage collection is working

### Debug Mode

Enable debug logging:
```php
$this->disableMemoryMonitoring(); // For performance-critical sections
```

### Memory Dumps

For severe memory issues:
```bash
# Enable memory profiling
php -d memory_limit=512M artisan queue:work --memory=256
```

## Metrics and Reporting

### Key Metrics

- **Memory Efficiency**: Peak memory / Data processed
- **GC Effectiveness**: Memory freed / GC cycles
- **Job Splitting Rate**: Split jobs / Total jobs
- **Memory Alerts**: Alert count / Job count

### Monitoring Dashboard

The system provides metrics for:
- Real-time memory usage
- Job splitting statistics  
- Memory leak detection
- Performance trends

## Best Practices

1. **Always Initialize**: Call `initializeMemoryMonitoring()` at job start
2. **Set Appropriate Limits**: Match limits to job requirements
3. **Use Chunking**: Process data in appropriate chunk sizes
4. **Monitor Checkpoints**: Add checkpoints at critical operations
5. **Clean Up**: Always call `finalizeMemoryMonitoring()`
6. **Test Thoroughly**: Test with realistic data sizes
7. **Monitor Production**: Use monitoring command in production

## Conclusion

The memory management system provides comprehensive tools for preventing memory issues in Laravel jobs. By following the guidelines and using the provided tools, you can ensure your jobs run efficiently even with large datasets.

For questions or issues, check the logs and monitoring output, or run the test script to verify functionality.