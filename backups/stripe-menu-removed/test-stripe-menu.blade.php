<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Menu Test - AskProAI</title>
    @vite(['resources/css/app.css', 'resources/css/stripe-menu.css', 'resources/js/stripe-menu.js'])
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .test-content {
            padding: 100px 20px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .feature-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }
        .feature-card h3 {
            margin-top: 0;
            color: #1e293b;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-active {
            background: #10b981;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .keyboard-shortcut {
            background: #334155;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    {{-- Include Stripe Menu --}}
    @include('stripe-menu-standalone')
    
    {{-- Include Mobile Bottom Navigation --}}
    @include('components.mobile-bottom-nav')
    
    <div class="test-content">
        <h1 style="font-size: 32px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
            🎨 Stripe Menu Test Page
        </h1>
        <p style="color: #64748b; font-size: 18px;">
            <span class="status-indicator status-active"></span>
            Menu System Active - Test all features below
        </p>
        
        <div class="feature-grid">
            <div class="feature-card">
                <h3>🖥️ Desktop Navigation</h3>
                <p>Hover over menu items to see the mega menu dropdown with advanced hover intent detection.</p>
                <small style="color: #94a3b8;">✅ Only shows working Filament resources</small>
            </div>
            
            <div class="feature-card">
                <h3>🎯 Active Link Highlighting</h3>
                <p>Current page links are highlighted with indigo color and borders.</p>
                <small style="color: #94a3b8;">✅ NEW: Real-time active state detection</small>
            </div>
            
            <div class="feature-card">
                <h3>📱 Mobile Bottom Navigation</h3>
                <p>Fixed bottom nav with 4 primary actions for mobile users.</p>
                <small style="color: #94a3b8;">✅ NEW: iOS-style bottom navigation</small>
            </div>
            
            <div class="feature-card">
                <h3>🔗 Smart Redirects</h3>
                <p>Profile → User Edit, Settings → Integrations, Help → Dedicated Page</p>
                <small style="color: #94a3b8;">✅ NEW: Functional redirect routes</small>
            </div>
            
            <div class="feature-card">
                <h3>⌨️ Enhanced Keyboard Navigation</h3>
                <p>Alt+H (Dashboard), Alt+C (Customers), Alt+P (Calls), Alt+A (Appointments)</p>
                <small style="color: #94a3b8;">✅ NEW: Quick keyboard shortcuts</small>
            </div>
            
            <div class="feature-card">
                <h3>✅ 100% Working Links</h3>
                <p>All navigation items now lead to existing Filament resources.</p>
                <small style="color: #94a3b8;">✅ FIXED: No more 404 errors</small>
            </div>
        </div>
        
        <div style="margin-top: 40px; background: #1e293b; color: white; padding: 20px; border-radius: 8px;">
            <h3 style="margin-top: 0;">📊 Navigation Data Structure</h3>
            <pre style="background: #0f172a; padding: 15px; border-radius: 4px; overflow-x: auto;">
@php
    $navigationService = app(\App\Services\NavigationService::class);
    $navigation = $navigationService->getNavigation();
    echo json_encode($navigation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
@endphp
            </pre>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px;">
            <h3 style="margin-top: 0; color: #92400e;">⚠️ Testing Notes</h3>
            <ul style="color: #78350f; margin-bottom: 0;">
                <li>This is a standalone test page outside of Filament admin</li>
                <li>Menu should appear at the top of the page</li>
                <li>All JavaScript features should be functional</li>
                <li>Check browser console for any errors</li>
                <li>Resize window to test responsive behavior</li>
            </ul>
        </div>
    </div>
</body>
</html>