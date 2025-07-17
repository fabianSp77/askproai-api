# Business Portal Troubleshooting Guide

## Overview

This guide provides solutions to common issues encountered in the Business Portal, based on real fixes implemented during development. Each solution includes diagnostic steps, root causes, and verified fixes.

## Quick Diagnostics

```bash
# Run comprehensive health check
php artisan portal:health-check

# Check specific components
php artisan portal:check auth
php artisan portal:check api
php artisan portal:check database
php artisan portal:check sessions
```

## Common Issues & Solutions

### 1. Authentication & Login Issues

#### Problem: Cannot Login to Portal (419 Session Expired)

**Symptoms:**
- Error 419 "Page Expired" on login
- CSRF token mismatch errors
- Login redirects back to login page

**Root Cause:**
- Session configuration mismatch
- CSRF token not being sent/validated correctly
- Cookie domain issues

**Solution:**
```php
// 1. Check session configuration
// config/session.php
'portal' => [
    'driver' => env('SESSION_DRIVER', 'database'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => 'portal_sessions',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => true,
    'cookie' => 'portal_session',
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',
],

// 2. Clear all caches
php artisan optimize:clear
php artisan config:cache

// 3. Check CSRF middleware
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'api/webhook/*',  // Only webhook endpoints
    // DO NOT exclude portal routes!
];

// 4. Verify in frontend
// Include CSRF token in all requests
axios.defaults.headers.common['X-CSRF-TOKEN'] = 
    document.querySelector('meta[name="csrf-token"]').content;
```

**Debug Commands:**
```bash
# Check session status
php debug-business-portal-session.php

# Test login directly
php test-portal-login.php

# Clear sessions
php artisan session:clear
```

#### Problem: 401 Unauthorized After Login

**Symptoms:**
- Login succeeds but API calls return 401
- Token appears valid but not accepted
- Works in Postman but not in browser

**Root Cause:**
- Token not being stored/sent correctly
- Authorization header missing
- API middleware configuration issue

**Solution:**
```javascript
// 1. Ensure token is stored after login
const login = async (credentials) => {
    const response = await api.post('/auth/login', credentials);
    const { token, user } = response.data;
    
    // Store token
    localStorage.setItem('portal_token', token);
    localStorage.setItem('portal_user', JSON.stringify(user));
    
    // Set default header
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    
    return response.data;
};

// 2. Add request interceptor
api.interceptors.request.use(
    config => {
        const token = localStorage.getItem('portal_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    error => Promise.reject(error)
);

// 3. Add response interceptor for 401s
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            // Clear storage and redirect to login
            localStorage.removeItem('portal_token');
            localStorage.removeItem('portal_user');
            window.location.href = '/portal/login';
        }
        return Promise.reject(error);
    }
);
```

**Backend Fix:**
```php
// Ensure API routes use correct auth
// routes/api.php
Route::prefix('v2/portal')->middleware(['api', 'auth:sanctum'])->group(function () {
    // Portal API routes
});

// Check token validation
// app/Http/Middleware/Authenticate.php
protected function authenticate($request, array $guards)
{
    if ($this->auth->guard('sanctum')->check()) {
        return $this->auth->shouldUse('sanctum');
    }
    
    $this->unauthenticated($request, $guards);
}
```

### 2. API & Data Issues

#### Problem: Empty Dashboard / No Data Showing

**Symptoms:**
- Dashboard loads but shows no data
- API returns 200 but empty results
- Data exists in database but not displayed

**Root Cause:**
- Tenant scope filtering out data
- Missing company_id in queries
- Incorrect date filtering

**Solution:**
```php
// 1. Check tenant scope
// app/Models/Call.php
protected static function booted()
{
    // For portal queries, ensure company_id is set
    static::addGlobalScope(new TenantScope);
}

// 2. In API controllers, ensure company context
public function index(Request $request)
{
    $companyId = auth()->user()->company_id;
    
    $calls = Call::where('company_id', $companyId)
        ->when($request->date_from, function ($query, $date) {
            $query->where('created_at', '>=', $date);
        })
        ->paginate(20);
        
    return response()->json($calls);
}

// 3. Debug query
\DB::enableQueryLog();
$result = Call::where('company_id', 1)->get();
dd(\DB::getQueryLog());
```

**Frontend Debug:**
```javascript
// Add debug logging
const fetchDashboardData = async () => {
    console.log('Fetching dashboard data...');
    
    try {
        const response = await api.get('/portal/dashboard');
        console.log('Dashboard response:', response.data);
        
        if (!response.data || Object.keys(response.data).length === 0) {
            console.error('Empty dashboard data received');
        }
        
        setDashboardData(response.data);
    } catch (error) {
        console.error('Dashboard fetch error:', error.response || error);
    }
};
```

#### Problem: Call Details Not Loading

**Symptoms:**
- Click on call shows loading spinner forever
- 404 error for `/api/v2/portal/calls/{id}`
- Call exists in database

**Root Cause:**
- Route parameter mismatch
- Missing route definition
- Tenant scope blocking access

**Solution:**
```php
// 1. Ensure route exists
// routes/api.php
Route::get('/calls/{call}', [CallController::class, 'show']);

// 2. Controller method
public function show(Call $call)
{
    // Check ownership
    if ($call->company_id !== auth()->user()->company_id) {
        abort(403, 'Unauthorized');
    }
    
    // Load relationships
    $call->load(['customer', 'appointments', 'staff']);
    
    return new CallResource($call);
}

// 3. If using UUID/custom ID
public function show($id)
{
    $call = Call::where('uuid', $id)
        ->where('company_id', auth()->user()->company_id)
        ->firstOrFail();
        
    return new CallResource($call);
}
```

### 3. Session & Cookie Issues

#### Problem: Admin Can't Access Business Portal

**Symptoms:**
- Admin logged in but portal shows "Unauthorized"
- Session conflicts between admin and portal
- Redirected to wrong login page

**Root Cause:**
- Shared session cookie names
- Auth guard conflicts
- Middleware ordering issues

**Solution:**
```php
// 1. Separate session configurations
// config/session.php
return [
    'connections' => [
        'admin' => [
            'driver' => 'database',
            'table' => 'admin_sessions',
            'cookie' => 'admin_session',
        ],
        'portal' => [
            'driver' => 'database', 
            'table' => 'portal_sessions',
            'cookie' => 'portal_session',
        ],
    ],
];

// 2. Create middleware for admin portal access
// app/Http/Middleware/AdminPortalAccess.php
public function handle($request, Closure $next)
{
    if (Auth::guard('admin')->check()) {
        $admin = Auth::guard('admin')->user();
        
        // Create temporary portal session
        $portalUser = PortalUser::where('email', $admin->email)->first();
        
        if (!$portalUser) {
            // Create shadow portal user
            $portalUser = PortalUser::create([
                'name' => $admin->name,
                'email' => $admin->email,
                'company_id' => $request->company_id ?? 1,
                'is_admin_shadow' => true,
            ]);
        }
        
        Auth::guard('portal')->login($portalUser);
    }
    
    return $next($request);
}

// 3. Add bypass route for admins
Route::get('/admin/portal-access/{company}', function ($companyId) {
    if (!Auth::guard('admin')->check()) {
        return redirect('/admin/login');
    }
    
    session(['admin_accessing_portal' => true]);
    session(['portal_company_id' => $companyId]);
    
    return redirect('/portal/dashboard');
})->middleware('auth:admin');
```

### 4. Performance Issues

#### Problem: Slow Dashboard Loading

**Symptoms:**
- Dashboard takes 5+ seconds to load
- Multiple API calls timing out
- Browser becomes unresponsive

**Root Cause:**
- N+1 query problems
- Missing database indexes
- Too much data being loaded

**Solution:**
```php
// 1. Optimize queries with eager loading
// Before
$calls = Call::where('company_id', $companyId)->get();
foreach ($calls as $call) {
    $customer = $call->customer; // N+1 problem!
}

// After
$calls = Call::with(['customer', 'appointments', 'staff'])
    ->where('company_id', $companyId)
    ->limit(100) // Add pagination
    ->get();

// 2. Add database indexes
// Create migration
Schema::table('calls', function (Blueprint $table) {
    $table->index(['company_id', 'created_at']);
    $table->index(['company_id', 'status']);
});

// 3. Implement caching
public function getDashboardStats($companyId)
{
    return Cache::remember("dashboard_stats_{$companyId}", 300, function () use ($companyId) {
        return [
            'total_calls' => Call::where('company_id', $companyId)->count(),
            'total_appointments' => Appointment::where('company_id', $companyId)->count(),
            // ... other stats
        ];
    });
}

// 4. Use query optimization
$stats = DB::table('calls')
    ->select(
        DB::raw('COUNT(*) as total_calls'),
        DB::raw('COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_calls'),
        DB::raw('AVG(duration) as avg_duration')
    )
    ->where('company_id', $companyId)
    ->where('created_at', '>=', now()->subDays(30))
    ->first();
```

**Frontend Optimization:**
```javascript
// 1. Implement lazy loading
const LazyDashboard = React.lazy(() => import('./Dashboard'));

// 2. Use React.memo for expensive components
const CallList = React.memo(({ calls }) => {
    // Component code
}, (prevProps, nextProps) => {
    return prevProps.calls.length === nextProps.calls.length;
});

// 3. Debounce search/filter operations
const debouncedSearch = useMemo(
    () => debounce((value) => {
        searchCalls(value);
    }, 300),
    []
);

// 4. Virtual scrolling for large lists
import { FixedSizeList } from 'react-window';

const VirtualCallList = ({ calls }) => (
    <FixedSizeList
        height={600}
        itemCount={calls.length}
        itemSize={80}
        width="100%"
    >
        {({ index, style }) => (
            <div style={style}>
                <CallItem call={calls[index]} />
            </div>
        )}
    </FixedSizeList>
);
```

### 5. File & Asset Issues

#### Problem: React App Not Loading (Blank Page)

**Symptoms:**
- Portal shows blank white page
- Console shows 404 for JS/CSS files
- "Unexpected token <" errors

**Root Cause:**
- Asset URLs incorrect
- Build files not generated
- Vite configuration issues

**Solution:**
```bash
# 1. Rebuild assets
npm run build

# 2. Check Vite config
// vite.config.js
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/app-react.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],
    build: {
        manifest: true,
        outDir: 'public/build',
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['react', 'react-dom', 'react-router-dom'],
                    ui: ['antd', '@ant-design/icons'],
                },
            },
        },
    },
});

// 3. Check Blade template
// resources/views/portal/layouts/app.blade.php
@viteReactRefresh
@vite(['resources/css/app.css', 'resources/js/app-react.jsx'])

// 4. Clear Laravel caches
php artisan optimize:clear
php artisan view:clear

// 5. Check nginx configuration
location ~ \.(js|css|png|jpg|gif|svg|ico|woff|woff2|ttf|eot)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### 6. Data Display Issues

#### Problem: Dates Showing Wrong Timezone

**Symptoms:**
- Times off by several hours
- "Yesterday" showing for today's calls
- Timezone confusion

**Root Cause:**
- Server in UTC, display in local time
- Moment.js timezone issues
- Database storing wrong timezone

**Solution:**
```php
// 1. Ensure consistent timezone in Laravel
// config/app.php
'timezone' => 'Europe/Berlin',

// 2. In models, use timezone casting
protected $casts = [
    'created_at' => 'datetime:Y-m-d H:i:s',
    'scheduled_at' => 'datetime:Y-m-d H:i:s',
];

// 3. API response formatting
public function toArray($request)
{
    return [
        'id' => $this->id,
        'created_at' => $this->created_at->timezone('Europe/Berlin')->format('Y-m-d H:i:s'),
        'created_at_human' => $this->created_at->diffForHumans(),
        // ...
    ];
}
```

**Frontend Fix:**
```javascript
// 1. Use dayjs with timezone
import dayjs from 'dayjs';
import timezone from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';

dayjs.extend(utc);
dayjs.extend(timezone);

// 2. Set default timezone
dayjs.tz.setDefault('Europe/Berlin');

// 3. Format dates consistently
const formatDate = (date) => {
    return dayjs(date).tz('Europe/Berlin').format('DD.MM.YYYY HH:mm');
};

// 4. Relative time
const relativeTime = (date) => {
    return dayjs(date).fromNow();
};
```

### 7. WebSocket Issues (When Enabled)

#### Problem: Real-time Updates Not Working

**Symptoms:**
- No live updates on dashboard
- WebSocket connection failing
- Console errors about Echo/Pusher

**Root Cause:**
- WebSocket server not running
- CORS issues
- Authentication failing

**Solution:**
```javascript
// 1. Configure Echo correctly
// resources/js/bootstrap.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('portal_token')}`,
        },
    },
});

// 2. Subscribe to channels
const subscribeToUpdates = (companyId) => {
    Echo.channel(`company.${companyId}`)
        .listen('CallReceived', (e) => {
            console.log('New call:', e.call);
            // Update state
        })
        .listen('AppointmentUpdated', (e) => {
            console.log('Appointment updated:', e.appointment);
            // Update state
        });
};

// 3. Handle connection errors
Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('WebSocket error:', err);
});
```

**Backend Broadcasting:**
```php
// 1. Configure broadcasting
// config/broadcasting.php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'encrypted' => true,
        'host' => '127.0.0.1',
        'port' => 6001,
        'scheme' => 'http',
    ],
],

// 2. Broadcast events
event(new CallReceived($call));

// 3. Authorize channels
Broadcast::channel('company.{companyId}', function ($user, $companyId) {
    return $user->company_id === (int) $companyId;
});
```

## Diagnostic Scripts

### Comprehensive Health Check

```php
// portal-health-check.php
<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Business Portal Health Check\n";
echo "===========================\n\n";

// 1. Database Connection
try {
    DB::connection()->getPdo();
    echo "✅ Database connection: OK\n";
} catch (Exception $e) {
    echo "❌ Database connection: FAILED - " . $e->getMessage() . "\n";
}

// 2. Redis Connection
try {
    Redis::ping();
    echo "✅ Redis connection: OK\n";
} catch (Exception $e) {
    echo "❌ Redis connection: FAILED - " . $e->getMessage() . "\n";
}

// 3. Session Configuration
$sessionConfig = config('session');
echo "✅ Session driver: " . $sessionConfig['driver'] . "\n";
echo "✅ Session lifetime: " . $sessionConfig['lifetime'] . " minutes\n";

// 4. Portal Users
$portalUsers = \App\Models\PortalUser::count();
echo "✅ Portal users: " . $portalUsers . "\n";

// 5. Recent Activity
$recentCalls = \App\Models\Call::where('created_at', '>', now()->subDay())->count();
echo "✅ Calls (last 24h): " . $recentCalls . "\n";

// 6. Queue Status
try {
    $horizonStatus = Artisan::call('horizon:status');
    echo "✅ Horizon status: " . ($horizonStatus === 0 ? 'RUNNING' : 'STOPPED') . "\n";
} catch (Exception $e) {
    echo "❌ Horizon status: UNKNOWN\n";
}

// 7. File Permissions
$directories = [
    'storage/app',
    'storage/logs',
    'bootstrap/cache',
    'public/build',
];

foreach ($directories as $dir) {
    if (is_writable(base_path($dir))) {
        echo "✅ {$dir}: Writable\n";
    } else {
        echo "❌ {$dir}: Not writable\n";
    }
}

echo "\nHealth check complete.\n";
```

### Session Debugger

```php
// debug-portal-session.php
<?php
session_start();

echo "Portal Session Debug\n";
echo "===================\n\n";

echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n\n";

echo "Session Data:\n";
print_r($_SESSION);

echo "\n\nCookies:\n";
print_r($_COOKIE);

echo "\n\nAuth Check:\n";
$user = Auth::guard('portal')->user();
if ($user) {
    echo "✅ Authenticated as: " . $user->email . "\n";
    echo "Company ID: " . $user->company_id . "\n";
} else {
    echo "❌ Not authenticated\n";
}
```

## Performance Monitoring

### Query Performance

```sql
-- Find slow queries
SELECT 
    query_time,
    lock_time,
    rows_sent,
    rows_examined,
    sql_text
FROM mysql.slow_log
WHERE query_time > 1
ORDER BY query_time DESC
LIMIT 20;

-- Check index usage
SELECT 
    table_schema,
    table_name,
    index_name,
    cardinality
FROM information_schema.statistics
WHERE table_schema = 'askproai_db'
ORDER BY cardinality DESC;

-- Table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
    table_rows
FROM information_schema.tables
WHERE table_schema = 'askproai_db'
ORDER BY size_mb DESC;
```

### API Performance

```php
// Add to AppServiceProvider
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

public function boot()
{
    if (config('app.debug')) {
        DB::listen(function ($query) {
            if ($query->time > 100) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time
                ]);
            }
        });
    }
}
```

## Emergency Fixes

### Reset Everything

```bash
# Nuclear option - use with caution!
php artisan down

# Clear everything
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
rm -rf bootstrap/cache/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

# Rebuild
composer dump-autoload
php artisan config:cache
php artisan route:cache
npm run build

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo systemctl restart redis
php artisan horizon:terminate
php artisan up
```

### Create Emergency Admin Access

```php
// emergency-portal-access.php
<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

$email = 'emergency@admin.com';
$password = 'EmergencyAccess2024!';

$user = PortalUser::firstOrCreate(
    ['email' => $email],
    [
        'name' => 'Emergency Admin',
        'password' => Hash::make($password),
        'company_id' => 1,
        'is_super_admin' => true,
        'email_verified_at' => now(),
    ]
);

echo "Emergency access created:\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";
echo "URL: " . config('app.url') . "/portal/login\n";
```

## Monitoring & Alerts

### Set Up Monitoring

```bash
# 1. Install monitoring script
cat > /usr/local/bin/monitor-portal.sh << 'EOF'
#!/bin/bash

# Check if portal is responding
response=$(curl -s -o /dev/null -w "%{http_code}" https://portal.askproai.de/api/health)

if [ $response -ne 200 ]; then
    echo "Portal health check failed with status: $response"
    # Send alert (email, Slack, etc.)
fi

# Check queue processing
failed_jobs=$(mysql -u root -p'password' -e "SELECT COUNT(*) FROM failed_jobs" askproai_db | tail -1)

if [ $failed_jobs -gt 10 ]; then
    echo "High number of failed jobs: $failed_jobs"
    # Send alert
fi

# Check disk space
disk_usage=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')

if [ $disk_usage -gt 80 ]; then
    echo "Disk usage critical: ${disk_usage}%"
    # Send alert
fi
EOF

chmod +x /usr/local/bin/monitor-portal.sh

# 2. Add to crontab
echo "*/5 * * * * /usr/local/bin/monitor-portal.sh" | crontab -
```

### Application Monitoring

```php
// app/Console/Commands/MonitorPortal.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Models\PortalUser;
use Carbon\Carbon;

class MonitorPortal extends Command
{
    protected $signature = 'portal:monitor';
    protected $description = 'Monitor portal health and send alerts';
    
    public function handle()
    {
        $issues = [];
        
        // Check for stale data
        $lastCall = Call::latest()->first();
        if ($lastCall && $lastCall->created_at < now()->subHours(24)) {
            $issues[] = 'No new calls in 24 hours';
        }
        
        // Check active sessions
        $activeSessions = DB::table('portal_sessions')
            ->where('last_activity', '>', now()->subMinutes(30)->timestamp)
            ->count();
            
        if ($activeSessions === 0) {
            $issues[] = 'No active portal sessions';
        }
        
        // Check failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 10) {
            $issues[] = "High failed job count: {$failedJobs}";
        }
        
        // Send alerts if issues found
        if (!empty($issues)) {
            // Send to Slack, email, etc.
            Log::error('Portal monitoring issues', $issues);
        }
        
        $this->info('Monitoring complete. Issues found: ' . count($issues));
    }
}
```

## Best Practices for Troubleshooting

1. **Always Check Logs First**
   ```bash
   tail -f storage/logs/laravel.log
   tail -f storage/logs/api-*.log
   tail -f /var/log/nginx/error.log
   ```

2. **Use Debug Mode Temporarily**
   ```env
   APP_DEBUG=true
   APP_ENV=local
   ```

3. **Test in Isolation**
   - Test API endpoints with Postman
   - Test database queries with Tinker
   - Test frontend components in Storybook

4. **Document Solutions**
   - Create fix files with clear names
   - Include problem description
   - Document what worked and why

5. **Version Control Debugging**
   ```bash
   # Find when issue started
   git bisect start
   git bisect bad HEAD
   git bisect good <last-known-good-commit>
   ```

---

*For more detailed documentation, see the [main guide](./BUSINESS_PORTAL_COMPLETE_DOCUMENTATION.md)*