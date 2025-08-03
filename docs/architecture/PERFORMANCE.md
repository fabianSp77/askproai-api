## Performance Considerations

### 🎯 Performance Benchmarks & Targets

#### Response Time Targets:
- **API Endpoints**: < 200ms (p95)
- **Webhook Processing**: < 500ms 
- **Admin Dashboard**: < 1s page load
- **Database Queries**: < 100ms per query
- **Queue Job Processing**: < 30s per job

#### Resource Limits:
- **Memory Usage**: < 512MB per request
- **CPU Usage**: < 80% sustained
- **Database Connections**: < 100 concurrent
- **Redis Memory**: < 2GB
- **Disk I/O**: < 100 MB/s

#### Throughput Targets:
- **API Requests**: 1000 req/min
- **Webhook Events**: 500/min
- **Concurrent Users**: 100
- **Calls per Hour**: 1000
- **Appointments per Day**: 5000

### 🚨 Performance Monitoring:
```bash
# Check current performance
php artisan performance:analyze

# Slow query log
tail -f storage/logs/slow-queries.log

# Real-time metrics
php artisan horizon:metrics
```

### Caching Strategy
- Event types cached for 5 minutes
- Company settings cached indefinitely (clear on update)
- API responses cached for 1 minute
- Clear cache after configuration changes

### Query Optimization
- **MUST** use eager loading for relationships
- **MUST** implement query scopes for common filters
- **MUST** add indexes for: company_id, created_at, phone_number
- **AVOID** whereRaw() - use query builder instead
- **LIMIT** results to 50 per page max

### Queue Configuration
- Horizon configured for multiple queues
- High priority: webhooks (timeout: 60s)
- Default: general processing (timeout: 300s)
- Low priority: maintenance tasks (timeout: 900s)

## Business Logic & Workflow
