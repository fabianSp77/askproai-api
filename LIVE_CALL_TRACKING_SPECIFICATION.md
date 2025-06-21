# Live Call Tracking Specification

## Overview
Implement real-time call tracking using Retell's `call_started` webhook event to display active calls across the AskProAI platform.

## Webhook Events

### 1. `call_started`
- **Purpose**: Track when a call begins
- **Data Available**:
  - `call_id`: Unique identifier
  - `agent_id`: Which AI agent is handling the call
  - `from_number`: Caller's phone number
  - `to_number`: Called number (identifies branch/company)
  - `start_timestamp`: When call started
  - `call_type`: inbound/outbound

### 2. `call_ended`
- **Purpose**: Track when call completes
- **Additional Data**:
  - `end_timestamp`: When call ended
  - `duration_ms`: Call duration
  - `transcript`: Full conversation transcript
  - `call_analysis`: AI-extracted data (name, email, appointment details)
  - `recording_url`: Audio recording
  - `cost`: Call cost

### 3. `call_analyzed`
- **Purpose**: Receive post-call analysis
- **Data**: Enhanced analysis results

## Implementation Plan

### Database Schema
```sql
-- Add to calls table
ALTER TABLE calls ADD COLUMN is_active BOOLEAN DEFAULT false;
ALTER TABLE calls ADD COLUMN started_at TIMESTAMP NULL;
ALTER TABLE calls ADD INDEX idx_active_calls (is_active, company_id, started_at);

-- New table for real-time tracking
CREATE TABLE active_calls (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    call_id VARCHAR(255) UNIQUE NOT NULL,
    company_id BIGINT NOT NULL,
    branch_id BIGINT NULL,
    agent_id VARCHAR(255),
    from_number VARCHAR(50),
    to_number VARCHAR(50),
    started_at TIMESTAMP NOT NULL,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    metadata JSON,
    INDEX idx_company_active (company_id, started_at),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);
```

### Live Call Service
```php
namespace App\Services;

class LiveCallTrackingService
{
    public function handleCallStarted(array $callData): void
    {
        // 1. Identify company/branch from to_number
        $branch = $this->phoneNumberResolver->resolveBranch($callData['to_number']);
        
        // 2. Create active call record
        ActiveCall::create([
            'call_id' => $callData['call_id'],
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'agent_id' => $callData['agent_id'],
            'from_number' => $callData['from_number'],
            'to_number' => $callData['to_number'],
            'started_at' => Carbon::createFromTimestampMs($callData['start_timestamp']),
            'metadata' => $callData
        ]);
        
        // 3. Broadcast event for real-time updates
        broadcast(new CallStarted($branch->company_id, $callData));
    }
    
    public function handleCallEnded(array $callData): void
    {
        // 1. Remove from active calls
        ActiveCall::where('call_id', $callData['call_id'])->delete();
        
        // 2. Update calls table
        Call::where('call_id', $callData['call_id'])
            ->update(['is_active' => false]);
        
        // 3. Broadcast event
        broadcast(new CallEnded($callData));
    }
    
    public function getActiveCalls(?int $companyId = null): Collection
    {
        $query = ActiveCall::with(['branch', 'company']);
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return $query->orderBy('started_at', 'desc')->get();
    }
}
```

### Display Locations

#### 1. Admin Dashboard Widget
```php
// app/Filament/Admin/Widgets/LiveCallsWidget.php
class LiveCallsWidget extends Widget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';
    
    public function getActiveCalls(): Collection
    {
        return app(LiveCallTrackingService::class)
            ->getActiveCalls(auth()->user()->company_id);
    }
}
```

#### 2. System Cockpit Page
- Add real-time call counter
- Show list of active calls with duration
- Click to view call details

#### 3. Branch Overview
- Show active calls per branch
- Visual indicator (pulsing dot) for branches with active calls

#### 4. Top Navigation Bar
- Global active call counter
- Dropdown with current calls
- Real-time updates via WebSocket

### Real-time Updates

#### Backend Broadcasting
```php
// app/Events/CallStarted.php
class CallStarted implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return [
            new PrivateChannel('company.' . $this->companyId),
            new Channel('global-calls') // For super admin
        ];
    }
}
```

#### Frontend Integration
```javascript
// resources/js/live-calls.js
Echo.private(`company.${companyId}`)
    .listen('CallStarted', (e) => {
        // Update call counter
        updateCallCounter(e.callData);
        
        // Show notification
        showCallNotification(e.callData);
        
        // Update widgets
        Livewire.emit('callStarted', e.callData);
    })
    .listen('CallEnded', (e) => {
        // Remove from active calls
        removeActiveCall(e.callId);
        
        // Update counter
        decrementCallCounter();
    });
```

### Monitoring Features

#### Call Analytics
- Average call duration by hour/day
- Peak call times
- Concurrent call capacity
- Agent utilization

#### Alerts
- Too many concurrent calls
- Long call duration (>X minutes)
- Failed call handling
- No agent available

### API Endpoints

```php
// routes/api.php
Route::prefix('calls')->group(function () {
    Route::get('/active', [CallController::class, 'getActiveCalls']);
    Route::get('/active/count', [CallController::class, 'getActiveCallCount']);
    Route::get('/active/{callId}', [CallController::class, 'getActiveCallDetails']);
    Route::post('/webhook/started', [RetellWebhookController::class, 'handleCallStarted']);
});
```

### Performance Considerations

1. **Caching**: Cache active call count (1-second TTL)
2. **Database**: Use Redis for active call storage
3. **Broadcasting**: Throttle updates to max 1/second per client
4. **Cleanup**: Cron job to remove stale active calls (>1 hour)

### Security

1. **Webhook Verification**: Validate Retell signature
2. **Access Control**: Users only see their company's calls
3. **PII Protection**: Mask phone numbers in logs
4. **Rate Limiting**: Limit webhook requests

## Implementation Priority

1. **Phase 1**: Basic tracking
   - Store active calls
   - Display count in dashboard
   
2. **Phase 2**: Real-time updates
   - WebSocket broadcasting
   - Live dashboard widget
   
3. **Phase 3**: Advanced features
   - Call analytics
   - Monitoring & alerts
   - Historical trends