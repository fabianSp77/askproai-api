<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'AskProAI') }} - Admin Portal</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Production Assets -->
    <link rel="stylesheet" href="/build/assets/app-C_r1o67p.css">
</head>
<body class="antialiased">
    <div id="admin-app"></div>
    
    <!-- Loading Spinner -->
    <div id="loading-spinner" style="
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f2f5;
    ">
        <div style="text-align: center;">
            <div style="
                width: 40px;
                height: 40px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #1890ff;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            "></div>
            <p style="color: #666;">Admin Portal wird geladen...</p>
        </div>
    </div>
    
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Hide spinner when React loads */
        #admin-app:not(:empty) ~ #loading-spinner {
            display: none !important;
        }
    </style>

    <!-- Load React Dependencies -->
    <script type="module">
        // Import the admin app
        import('/build/assets/admin-ZDKuJeHG.js').then(() => {
            console.log('Admin React App loaded');
        }).catch(err => {
            console.error('Failed to load Admin React App:', err);
            document.getElementById('loading-spinner').innerHTML = `
                <div style="text-align: center; color: #ff4d4f;">
                    <h3>Fehler beim Laden</h3>
                    <p>Das Admin Portal konnte nicht geladen werden.</p>
                    <a href="/admin" style="color: #1890ff;">Zum alten Admin Portal</a>
                </div>
            `;
        });
    </script>
</body>
</html>