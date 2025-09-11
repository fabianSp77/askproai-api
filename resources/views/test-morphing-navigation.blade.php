<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Morphing Navigation Test - State of the Art</title>
    
    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- Morphing Navigation CSS --}}
    <style>
        @php
        $cssPath = resource_path('css/morphing-navigation.css');
        if (file_exists($cssPath) && is_readable($cssPath)) {
            echo file_get_contents($cssPath);
        } else {
            echo '/* Error: Could not load morphing-navigation.css */';
            \Log::error('Failed to load morphing navigation CSS', ['path' => $cssPath]);
        }
        @endphp
    </style>
    
    {{-- Screen reader only class --}}
    <style>
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
        
        /* Demo page styles */
        body {
            padding-top: var(--morph-nav-height);
            font-family: system-ui, -apple-system, sans-serif;
        }
        
        .demo-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 48px 24px;
        }
        
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }
        
        .demo-card {
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .demo-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .demo-card p {
            color: #666;
            line-height: 1.6;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 24px 0;
        }
        
        .feature-list li {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .feature-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }
        
        .test-section {
            margin-top: 48px;
            padding: 32px;
            background: #f9f9f9;
            border-radius: 12px;
        }
        
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        
        .test-button {
            padding: 12px 24px;
            background: white;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .test-button:hover {
            border-color: #667eea;
            background: #f7f8ff;
        }
        
        @media (prefers-color-scheme: dark) {
            body {
                background: #111;
                color: #fff;
            }
            
            .demo-card {
                background: #1a1a1a;
                border-color: #333;
            }
            
            .demo-card p {
                color: #a0a0a0;
            }
            
            .test-section {
                background: #1a1a1a;
            }
            
            .test-button {
                background: #222;
                border-color: #333;
                color: #fff;
            }
            
            .test-button:hover {
                border-color: #667eea;
                background: #2a2a3a;
            }
        }
    </style>
</head>
<body>
    {{-- Include Morphing Navigation Component --}}
    @include('components.morphing-navigation')
    
    {{-- Morphing Navigation JavaScript --}}
    <script>
        @php
        $jsPath = resource_path('js/morphing-navigation.js');
        if (file_exists($jsPath) && is_readable($jsPath)) {
            echo file_get_contents($jsPath);
        } else {
            echo '// Error: Could not load morphing-navigation.js';
            echo '\nconsole.error("Failed to load morphing navigation JS");';
            \Log::error('Failed to load morphing navigation JS', ['path' => $jsPath]);
        }
        @endphp
    </script>
    
    {{-- Morphing Navigation Initialization Guards --}}
    <script>
        @php
        $initPath = resource_path('js/morphing-navigation-init.js');
        if (file_exists($initPath) && is_readable($initPath)) {
            echo file_get_contents($initPath);
        } else {
            echo '// Initialization guards not loaded';
        }
        @endphp
    </script>
    
    {{-- Demo Content --}}
    <div class="demo-container">
        <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 16px;">
            State-of-the-Art Morphing Navigation
        </h1>
        <p style="font-size: 1.25rem; color: #666; margin-bottom: 32px;">
            Stripe-inspired navigation system with morphing animations, perfect responsiveness, and accessibility.
        </p>
        
        {{-- Feature Highlights --}}
        <ul class="feature-list">
            <li>
                <span class="feature-icon">✨</span>
                <div>
                    <strong>Morphing Animations</strong> - 
                    Container dynamically adapts size and position using GPU-accelerated transforms
                </div>
            </li>
            <li>
                <span class="feature-icon">📱</span>
                <div>
                    <strong>Perfect Responsive</strong> - 
                    Mobile-first design with touch gestures and edge swipe support
                </div>
            </li>
            <li>
                <span class="feature-icon">⚡</span>
                <div>
                    <strong>60fps Performance</strong> - 
                    Only CSS transforms used for animations, no layout recalculations
                </div>
            </li>
            <li>
                <span class="feature-icon">♿</span>
                <div>
                    <strong>Full Accessibility</strong> - 
                    ARIA labels, keyboard navigation, screen reader announcements
                </div>
            </li>
            <li>
                <span class="feature-icon">🔍</span>
                <div>
                    <strong>Command Palette</strong> - 
                    Press <kbd style="padding: 2px 6px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px;">⌘K</kbd> 
                    to open search
                </div>
            </li>
            <li>
                <span class="feature-icon">🌙</span>
                <div>
                    <strong>Dark Mode Support</strong> - 
                    Automatically adapts to system theme preference
                </div>
            </li>
        </ul>
        
        {{-- Test Controls --}}
        <div class="test-section">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 16px;">
                Interactive Test Controls
            </h2>
            <p style="color: #666; margin-bottom: 24px;">
                Test various navigation features and responsive behaviors:
            </p>
            
            <div class="test-grid">
                <button class="test-button" onclick="MorphingNavigation.openSearch()">
                    Open Search (⌘K)
                </button>
                <button class="test-button" onclick="MorphingNavigation.closeAll()">
                    Close All Menus
                </button>
                <button class="test-button" onclick="document.documentElement.classList.toggle('dark')">
                    Toggle Dark Mode
                </button>
                <button class="test-button" onclick="testMobileView()">
                    Test Mobile View
                </button>
                <button class="test-button" onclick="testTabletView()">
                    Test Tablet View
                </button>
                <button class="test-button" onclick="testDesktopView()">
                    Test Desktop View
                </button>
            </div>
        </div>
        
        {{-- Demo Cards --}}
        <div class="demo-grid">
            <div class="demo-card">
                <h3>Hover Intent Detection</h3>
                <p>
                    The navigation uses a 200ms hover intent delay to prevent accidental 
                    triggering when moving the mouse across the navigation bar.
                </p>
            </div>
            <div class="demo-card">
                <h3>Touch Gestures</h3>
                <p>
                    On mobile devices, swipe from the left edge to open the menu, 
                    or swipe right to close it. The hamburger menu also works as expected.
                </p>
            </div>
            <div class="demo-card">
                <h3>Keyboard Navigation</h3>
                <p>
                    Full keyboard support with Tab, Arrow keys, Home/End, and Escape. 
                    Try navigating with just your keyboard!
                </p>
            </div>
            <div class="demo-card">
                <h3>Screen Reader Support</h3>
                <p>
                    All interactive elements have proper ARIA labels and live regions 
                    announce state changes to assistive technologies.
                </p>
            </div>
            <div class="demo-card">
                <h3>Performance Optimized</h3>
                <p>
                    Using transform and opacity for animations ensures smooth 60fps 
                    performance by leveraging GPU acceleration.
                </p>
            </div>
            <div class="demo-card">
                <h3>Responsive Breakpoints</h3>
                <p>
                    The navigation automatically adapts at 1024px (desktop), 768px (tablet), 
                    and 375px (mobile) breakpoints.
                </p>
            </div>
        </div>
        
        {{-- Responsive Test Info --}}
        <div style="margin-top: 48px; padding: 24px; background: #f0f7ff; border-radius: 12px; border: 1px solid #c7e2ff;">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 12px; color: #0066cc;">
                Current Viewport Info
            </h3>
            <div id="viewport-info" style="font-family: monospace; color: #555;">
                <span id="viewport-width">--</span> × <span id="viewport-height">--</span> | 
                <span id="viewport-mode">--</span> Mode | 
                <span id="viewport-orientation">--</span>
            </div>
        </div>
    </div>
    
    {{-- Test Scripts --}}
    <script>
        // Viewport info updater
        function updateViewportInfo() {
            const width = window.innerWidth;
            const height = window.innerHeight;
            let mode = 'Desktop';
            
            if (width < 768) {
                mode = 'Mobile';
            } else if (width < 1024) {
                mode = 'Tablet';
            }
            
            const orientation = width > height ? 'Landscape' : 'Portrait';
            
            document.getElementById('viewport-width').textContent = width + 'px';
            document.getElementById('viewport-height').textContent = height + 'px';
            document.getElementById('viewport-mode').textContent = mode;
            document.getElementById('viewport-orientation').textContent = orientation;
        }
        
        // Update on load and resize
        updateViewportInfo();
        window.addEventListener('resize', updateViewportInfo);
        
        // Test view functions
        function testMobileView() {
            // Create iframe for testing
            const iframe = document.createElement('iframe');
            iframe.src = window.location.href;
            iframe.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 375px;
                height: 667px;
                border: 16px solid #333;
                border-radius: 36px;
                box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
                z-index: 10000;
                background: white;
            `;
            
            // Add close button
            const closeBtn = document.createElement('button');
            closeBtn.textContent = '✕ Close Preview';
            closeBtn.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10001;
                padding: 12px 24px;
                background: #333;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
            `;
            closeBtn.onclick = () => {
                iframe.remove();
                closeBtn.remove();
                overlay.remove();
            };
            
            // Add overlay
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.8);
                z-index: 9999;
            `;
            
            document.body.appendChild(overlay);
            document.body.appendChild(iframe);
            document.body.appendChild(closeBtn);
        }
        
        function testTabletView() {
            const iframe = document.createElement('iframe');
            iframe.src = window.location.href;
            iframe.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 768px;
                height: 1024px;
                max-height: 90vh;
                border: 24px solid #555;
                border-radius: 24px;
                box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
                z-index: 10000;
                background: white;
            `;
            
            const closeBtn = document.createElement('button');
            closeBtn.textContent = '✕ Close Preview';
            closeBtn.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10001;
                padding: 12px 24px;
                background: #333;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
            `;
            closeBtn.onclick = () => {
                iframe.remove();
                closeBtn.remove();
                overlay.remove();
            };
            
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.8);
                z-index: 9999;
            `;
            
            document.body.appendChild(overlay);
            document.body.appendChild(iframe);
            document.body.appendChild(closeBtn);
        }
        
        function testDesktopView() {
            // Reset to full viewport
            alert('Desktop view is the current view. Resize your browser window to test different desktop sizes.');
        }
        
        // Log navigation events for testing
        window.addEventListener('command-palette:open', () => {
            console.log('Command palette opened');
        });
    </script>
</body>
</html>