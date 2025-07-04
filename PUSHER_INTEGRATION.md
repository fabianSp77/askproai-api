# Pusher Integration for Live Dashboard Updates

## Overview
This implementation adds real-time updates to the AskProAI dashboard using Pusher broadcasting. When calls are created, updated, or completed, the dashboard widgets update instantly without requiring manual refresh.

## Setup Instructions

### 1. Configure Pusher Credentials
Add these environment variables to your `.env` file:

```bash
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=eu
```

### 2. Install Pusher Dependencies
```bash
composer require pusher/pusher-php-server
npm install --save laravel-echo pusher-js
```

### 3. Build Assets
```bash
npm run build
```

### 4. Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## How It Works

### Event Flow
1. **Call Created/Updated**: When a call is created or updated (e.g., via webhook), the Call model automatically dispatches events
2. **Broadcasting**: The events (`CallCreated`, `CallUpdated`) implement `ShouldBroadcast` and are sent to Pusher
3. **Channel Authorization**: Events are broadcast on private channels (e.g., `private-company.{id}`) with authorization
4. **Frontend Reception**: Dashboard widgets listen for these events via Pusher JavaScript client
5. **Widget Update**: When events are received, Livewire components refresh their data

### Fallback Mechanism
If Pusher is not configured, the system falls back to:
- Server-Sent Events (SSE) for real-time updates
- Regular polling every 5 seconds

### Key Components

#### Backend
- **Events**: `app/Events/CallCreated.php`, `CallUpdated.php`
- **Model**: `app/Models/Call.php` (dispatches events automatically)
- **Channels**: `routes/channels.php` (authorization logic)

#### Frontend
- **Pusher Integration**: `resources/js/pusher-integration.js`
- **Live Widget**: `app/Filament/Admin/Widgets/LiveCallsWidget.php`
- **Widget View**: `resources/views/filament/admin/widgets/live-calls-widget.blade.php`

### Security
- All broadcasts use private channels requiring authentication
- Users can only receive events for their own company
- CSRF protection for channel authorization

## Testing

### 1. Test Pusher Connection
```bash
php artisan tinker
>>> event(new \App\Events\CallCreated(\App\Models\Call::first()));
```

### 2. Monitor Pusher Debug Console
Visit your Pusher dashboard to see real-time event flow

### 3. Check Browser Console
Open browser console to see:
- "Connected to Pusher" message
- Event reception logs
- Any connection errors

## Troubleshooting

### No Real-time Updates
1. Check `.env` has correct Pusher credentials
2. Verify `BROADCAST_DRIVER=pusher`
3. Clear config cache: `php artisan config:clear`
4. Check browser console for errors

### Authentication Errors
1. Ensure user is logged in
2. Check CSRF token is present
3. Verify channel authorization in `routes/channels.php`

### Events Not Broadcasting
1. Ensure queue worker is running: `php artisan horizon`
2. Check Laravel logs for broadcast errors
3. Verify event implements `ShouldBroadcast`

## Performance Considerations
- Events are queued to prevent blocking
- Widget polling reduced from 2s to 5s when Pusher active
- Automatic reconnection on connection loss
- Cleanup on page navigation to prevent memory leaks