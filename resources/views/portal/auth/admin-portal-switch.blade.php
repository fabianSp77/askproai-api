<!DOCTYPE html>
<html>
<head>
    <title>Admin Portal Switch</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .user-info {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
        }
        .user-info .label {
            font-weight: bold;
            min-width: 150px;
        }
        .logged-in {
            color: #28a745;
            font-weight: bold;
        }
        .not-logged-in {
            color: #6c757d;
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin → Portal User Switch</h1>
        
        <div class="status-box">
            <h3>Aktueller Status:</h3>
            
            @php
                $adminUser = Auth::guard('web')->user();
                $portalUser = Auth::guard('portal')->user();
            @endphp
            
            <div class="user-info">
                <span class="label">Admin (web):</span>
                @if($adminUser)
                    <span class="logged-in">✓ {{ $adminUser->email }} (ID: {{ $adminUser->id }})</span>
                @else
                    <span class="not-logged-in">✗ Nicht eingeloggt</span>
                @endif
            </div>
            
            <div class="user-info">
                <span class="label">Portal User:</span>
                @if($portalUser)
                    <span class="logged-in">✓ {{ $portalUser->email }} (ID: {{ $portalUser->id }})</span>
                @else
                    <span class="not-logged-in">✗ Nicht eingeloggt</span>
                @endif
            </div>
            
            <div class="user-info">
                <span class="label">Impersonating:</span>
                <span>{{ session('admin_impersonate_portal_user') ? 'Yes (ID: ' . session('admin_impersonate_portal_user') . ')' : 'No' }}</span>
            </div>
        </div>
        
        @if($adminUser)
            <div class="info-box">
                <strong>👤 Als Admin eingeloggt!</strong><br>
                Du kannst jetzt einen Portal-User auswählen, um das Portal aus seiner Sicht zu sehen.
            </div>
            
            <h3>Portal User auswählen:</h3>
            
            <form method="POST" action="{{ url('/business/admin-impersonate') }}">
                @csrf
                <input type="hidden" name="portal_user_id" value="22">
                <button type="submit" class="btn-primary">
                    Als Demo User einloggen (fabianspitzer@icloud.com)
                </button>
            </form>
            
            <p>oder</p>
            
            <button onclick="window.location.href='/business/login'" class="btn-success">
                Normal als Portal User einloggen
            </button>
            
            @if(session('admin_impersonate_portal_user'))
                <hr style="margin: 30px 0;">
                <form method="POST" action="{{ url('/business/admin-stop-impersonate') }}">
                    @csrf
                    <button type="submit" class="btn-danger">
                        Impersonation beenden
                    </button>
                </form>
            @endif
        @else
            <div class="info-box">
                <strong>⚠️ Nicht als Admin eingeloggt!</strong><br>
                Bitte logge dich zuerst als Admin ein, um Portal User zu impersonieren.
            </div>
            
            <button onclick="window.location.href='/admin/login'" class="btn-primary">
                Als Admin einloggen
            </button>
        @endif
        
        <hr style="margin: 30px 0;">
        
        <h3>Quick Links:</h3>
        <button onclick="window.location.href='/admin'" class="btn-primary">Admin Panel</button>
        <button onclick="window.location.href='/business/dashboard'" class="btn-success">Portal Dashboard</button>
    </div>
</body>
</html>