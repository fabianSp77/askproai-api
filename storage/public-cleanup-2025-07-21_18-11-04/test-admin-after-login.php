<?php
// Test admin functionality after login
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// Login as demo user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();
if (!$user) {
    die("Demo user not found\n");
}

Auth::login($user);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Test After Login</title>
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
</head>
<body>
    <h1>Admin Portal Test - After Login</h1>
    
    <div style="background: #f0f0f0; padding: 20px; margin: 20px 0;">
        <h2>Login Status</h2>
        <p>Logged in as: <?php echo Auth::user()->email ?? 'Not logged in'; ?></p>
        <p>User ID: <?php echo Auth::user()->id ?? 'N/A'; ?></p>
        <p>Company ID: <?php echo Auth::user()->company_id ?? 'N/A'; ?></p>
        <p>CSRF Token: <?php echo substr(csrf_token(), 0, 20); ?>...</p>
    </div>

    <div style="background: #e0f0ff; padding: 20px; margin: 20px 0;">
        <h2>Test Links</h2>
        <ul>
            <li><a href="/admin" target="_blank">Admin Dashboard</a></li>
            <li><a href="/admin/calls" target="_blank">Calls</a></li>
            <li><a href="/admin/appointments" target="_blank">Appointments</a></li>
            <li><a href="/admin/customers" target="_blank">Customers</a></li>
        </ul>
    </div>

    <div style="background: #ffe0e0; padding: 20px; margin: 20px 0;">
        <h2>API Tests</h2>
        <button onclick="testAPI('/api/v2/calls')">Test Calls API</button>
        <button onclick="testAPI('/api/v2/appointments')">Test Appointments API</button>
        <button onclick="testLivewire()">Test Livewire</button>
        <div id="api-results"></div>
    </div>

    <script>
        // Set up axios with CSRF token
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

        function testAPI(endpoint) {
            const resultsDiv = document.getElementById('api-results');
            resultsDiv.innerHTML = 'Testing ' + endpoint + '...';
            
            axios.get(endpoint)
                .then(response => {
                    resultsDiv.innerHTML = '<pre>Success: ' + JSON.stringify(response.data, null, 2) + '</pre>';
                })
                .catch(error => {
                    resultsDiv.innerHTML = '<pre style="color: red;">Error: ' + error.response.status + ' - ' + error.response.statusText + '\n' + JSON.stringify(error.response.data, null, 2) + '</pre>';
                });
        }

        function testLivewire() {
            const resultsDiv = document.getElementById('api-results');
            resultsDiv.innerHTML = 'Testing Livewire...';
            
            // Simulate a Livewire request
            axios.post('/livewire/message/app.filament.admin.resources.call-resource.pages.list-calls', {
                fingerprint: {
                    id: 'test123',
                    name: 'app.filament.admin.resources.call-resource.pages.list-calls',
                    path: 'admin/calls',
                    method: 'GET'
                },
                serverMemo: {
                    data: {},
                    dataMeta: {},
                    htmlHash: 'test'
                },
                updates: []
            })
            .then(response => {
                resultsDiv.innerHTML = '<pre>Livewire Success: ' + JSON.stringify(response.data, null, 2) + '</pre>';
            })
            .catch(error => {
                resultsDiv.innerHTML = '<pre style="color: red;">Livewire Error: ' + error.response.status + ' - ' + error.response.statusText + '\n' + JSON.stringify(error.response.data, null, 2) + '</pre>';
            });
        }
    </script>

    <div style="background: #f0f0f0; padding: 20px; margin: 20px 0;">
        <h2>Session Debug</h2>
        <pre><?php
        echo "Session ID: " . session()->getId() . "\n";
        echo "Session Driver: " . config('session.driver') . "\n";
        echo "Session Cookie: " . config('session.cookie') . "\n";
        echo "All session data:\n";
        print_r(session()->all());
        ?></pre>
    </div>
</body>
</html>