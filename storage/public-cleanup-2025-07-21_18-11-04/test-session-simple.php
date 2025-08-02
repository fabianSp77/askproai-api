<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Start session
session_start();

// Check if we have a test value
if (isset($_GET['set'])) {
    $_SESSION['test'] = $_GET['set'];
    $message = "Session value set to: " . $_GET['set'];
} elseif (isset($_GET['check'])) {
    $message = "Session value is: " . ($_SESSION['test'] ?? 'NOT SET');
} else {
    $message = "Use ?set=value to set a session value, or ?check to check it";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Test</title>
</head>
<body>
    <h1>Simple Session Test</h1>
    <p><?php echo $message; ?></p>
    
    <h2>Session Info:</h2>
    <pre>
Session ID: <?php echo session_id(); ?>
Session Name: <?php echo session_name(); ?>
Session Cookie Params: <?php print_r(session_get_cookie_params()); ?>
    </pre>
    
    <h2>Cookies:</h2>
    <pre><?php print_r($_COOKIE); ?></pre>
    
    <h2>Laravel Session Config:</h2>
    <pre>
Driver: <?php echo config('session.driver'); ?>
Cookie Name: <?php echo config('session.cookie'); ?>
Domain: <?php echo config('session.domain'); ?>
Secure: <?php echo config('session.secure') ? 'true' : 'false'; ?>
Same Site: <?php echo config('session.same_site'); ?>
    </pre>
    
    <p>
        <a href="?set=test123">Set Session Value</a> |
        <a href="?check">Check Session Value</a>
    </p>
</body>
</html>