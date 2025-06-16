<!DOCTYPE html>
<html>
<head>
    <title>500 Error</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        pre { background: #f4f4f4; padding: 20px; overflow: auto; }
    </style>
</head>
<body>
    <h1>500 Internal Server Error</h1>
    
    @if(config('app.debug'))
        <h2>Error Details:</h2>
        <pre>{{ $exception->getMessage() }}</pre>
        
        <h3>Stack Trace:</h3>
        <pre>{{ $exception->getTraceAsString() }}</pre>
    @else
        <p>Something went wrong. Please try again later.</p>
    @endif
    
    <hr>
    <p><a href="/">Go back home</a></p>
</body>
</html>