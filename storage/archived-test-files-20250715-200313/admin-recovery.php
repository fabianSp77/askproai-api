<?php
session_start();

// Force admin authentication
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_user_id'] = 6;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Recovery Panel</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #1f2937;
            margin-top: 0;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #60a5fa;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .action-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 5px;
        }
        .button:hover {
            background: #2563eb;
        }
        .button.secondary {
            background: #6b7280;
        }
        .button.secondary:hover {
            background: #4b5563;
        }
        iframe {
            width: 100%;
            height: 600px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔧 Admin Recovery Panel</h1>
            
            <div class="alert alert-warning">
                <strong>Problem erkannt:</strong> Das normale Admin Panel zeigt einen weißen/grauen Bildschirm. 
                Dies ist ein temporäres Recovery Panel.
            </div>
            
            <div class="alert alert-info">
                <strong>Status:</strong> Sie sind eingeloggt als Admin User #6
            </div>
        </div>
        
        <div class="card">
            <h2>Schnellzugriff</h2>
            <div class="grid">
                <div class="action-card">
                    <h3>📊 Datenbank</h3>
                    <a href="/admin/companies" class="button" target="_blank">Firmen</a>
                    <a href="/admin/calls" class="button" target="_blank">Anrufe</a>
                </div>
                
                <div class="action-card">
                    <h3>👥 Benutzer</h3>
                    <a href="/admin/users" class="button" target="_blank">Admin Users</a>
                    <a href="/admin/portal-users" class="button" target="_blank">Portal Users</a>
                </div>
                
                <div class="action-card">
                    <h3>🔧 System</h3>
                    <a href="/horizon" class="button secondary" target="_blank">Laravel Horizon</a>
                    <a href="/admin/settings" class="button secondary" target="_blank">Einstellungen</a>
                </div>
                
                <div class="action-card">
                    <h3>🚀 Alternativen</h3>
                    <a href="/admin-simple.php" class="button secondary">Simple Admin</a>
                    <a href="/admin" class="button secondary">Normal Admin (Problem)</a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>Debug Information</h2>
            <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>Laravel Version:</strong> 11.x</p>
            
            <h3>Mögliche Lösungen:</h3>
            <ol>
                <li>Browser Cache leeren (Strg+Shift+F5)</li>
                <li>In einem anderen Browser versuchen</li>
                <li>Cookies für api.askproai.de löschen</li>
                <li><a href="/admin?_debug=1">Admin mit Debug Mode</a> versuchen</li>
            </ol>
        </div>
        
        <div class="card">
            <h2>Admin Panel Test (iFrame)</h2>
            <p>Versuche das Admin Panel in einem iFrame zu laden:</p>
            <iframe src="/admin" id="admin-frame"></iframe>
            
            <script>
                // Monitor iframe loading
                const iframe = document.getElementById('admin-frame');
                iframe.onload = function() {
                    try {
                        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        console.log('iFrame loaded, body length:', iframeDoc.body.innerHTML.length);
                    } catch (e) {
                        console.log('Cannot access iframe content:', e.message);
                    }
                };
            </script>
        </div>
    </div>
</body>
</html>