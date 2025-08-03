<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h1>Basic Dashboard</h1>
    @if(Auth::guard('portal')->check())
        <p>Authenticated as: {{ Auth::guard('portal')->user()->email }}</p>
        <form method="POST" action="{{ route('business.logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    @else
        <p>Not authenticated</p>
        <a href="{{ route('business.login') }}">Login</a>
    @endif
</body>
</html>