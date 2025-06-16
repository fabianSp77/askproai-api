<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Simple Login Form</h1>
    <form method="POST" action="{{ route('simple-login.submit') }}">
        @csrf
        <div>
            <label>Email:</label>
            <input type="email" name="email" value="admin@example.com" required>
        </div>
        <div>
            <label>Password:</label>
            <input type="password" name="password" value="password" required>
        </div>
        <button type="submit">Login</button>
    </form>
    
    @if(session('error'))
        <p style="color: red;">{{ session('error') }}</p>
    @endif
</body>
</html>