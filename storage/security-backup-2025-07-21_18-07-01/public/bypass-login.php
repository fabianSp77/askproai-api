<?php
// Direct bypass ohne Laravel Framework
session_start();
$_SESSION['_token'] = 'bypass-token';

// Redirect to business portal with session
header('Location: /business');
exit;