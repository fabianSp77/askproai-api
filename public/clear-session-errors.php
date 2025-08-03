<?php
// Clear session errors
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Clear any error messages
session()->forget('errors');
session()->forget('_old_input');
session()->forget('_flash');
session()->save();

// Redirect to login page
header('Location: /business/login');
exit;