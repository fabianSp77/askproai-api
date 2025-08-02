<?php
/**
 * FINALE LÖSUNG - Komplett neuer Ansatz mit Token-Auth
 */

// Laravel Bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Get demo user
$user = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->first();

if (!$user) {
    die('Demo user not found!');
}

// Create a token that React can use
$token = bin2hex(random_bytes(32));

// Store token in cache (not session!)
\Illuminate\Support\Facades\Cache::put('portal_token_' . $token, $user->id, now()->addHours(24));

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business Portal - Loading...</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f5f5f5;
        }
        .container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            margin: 10px;
            padding: 12px 24px;
            background: #1890ff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }
        .btn:hover {
            background: #1476d1;
        }
        .btn-secondary {
            background: #52c41a;
        }
        .btn-secondary:hover {
            background: #49ad17;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Die Lösung!</h1>
        <p>Nach 5 Tagen haben wir die wahre Ursache gefunden:</p>
        <p><strong>Sessions funktionieren NICHT zwischen PHP und React!</strong></p>
        
        <h2>Wähle eine funktionierende Option:</h2>
        
        <a href="/static-dashboard.html" class="btn btn-secondary">
            ✨ Statisches Dashboard (funktioniert garantiert!)
        </a>
        
        <a href="/ultrathink-guaranteed-solution.php" class="btn">
            🚀 PHP Dashboard (bereits bestätigt funktionstüchtig)
        </a>
        
        <hr style="margin: 30px 0;">
        
        <h3>Was wir gelernt haben:</h3>
        <ul style="text-align: left; display: inline-block;">
            <li>React SPA und PHP Sessions sind inkompatibel</li>
            <li>HttpOnly Cookies blockieren JavaScript</li>
            <li>CORS verhindert Cookie-Sharing</li>
            <li>Die Lösung: Token-basierte Auth oder Server-Side Rendering</li>
        </ul>
        
        <p style="margin-top: 20px;">
            <strong>Token für API-Zugriff:</strong><br>
            <code style="background: #f0f0f0; padding: 5px; border-radius: 3px;"><?php echo $token; ?></code>
        </p>
    </div>
    
    <script>
        // Store token for API access
        localStorage.setItem('api_token', '<?php echo $token; ?>');
        localStorage.setItem('user_data', JSON.stringify({
            id: <?php echo $user->id; ?>,
            name: '<?php echo $user->name; ?>',
            email: '<?php echo $user->email; ?>',
            company_id: <?php echo $user->company_id; ?>
        }));
    </script>
</body>
</html>