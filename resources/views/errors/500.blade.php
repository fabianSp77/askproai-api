<!DOCTYPE html>
<html>
<head>
    <title>500 Server Error</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #dc3545; }
        .error-details { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 4px; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; overflow-x: auto; }
        .back-link { display: inline-block; margin-top: 20px; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>500 - Server Error</h1>

        @if(config('app.debug'))
            <div class="error-details">
                <h3>Error Details:</h3>
                @if(isset($exception))
                    <p><strong>Message:</strong> {{ $exception->getMessage() }}</p>
                    <p><strong>File:</strong> {{ $exception->getFile() }} : {{ $exception->getLine() }}</p>

                    <h4>Stack Trace:</h4>
                    <pre>{{ $exception->getTraceAsString() }}</pre>
                @else
                    <p>No exception details available</p>
                @endif
            </div>
        @else
            <p>Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.</p>
        @endif

        <a href="/admin" class="back-link">← Zurück zum Admin Panel</a>
    </div>
</body>
</html>