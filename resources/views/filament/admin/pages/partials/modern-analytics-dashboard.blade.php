<!-- Ultra-Premium Analytics Dashboard - Apple-Inspired Design -->
<div class="ultra-premium-dashboard" x-data="premiumDashboard()" x-init="init()">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        :root {
            /* Ultra-Premium Color System */
            --premium-white: #ffffff;
            --premium-gray-50: #fafafa;
            --premium-gray-100: #f5f5f7;
            --premium-gray-200: #e5e5e7;
            --premium-gray-300: #d2d2d7;
            --premium-gray-400: #a1a1a6;
            --premium-gray-500: #86868b;
            --premium-gray-600: #6e6e73;
            --premium-gray-700: #48484a;
            --premium-gray-800: #1d1d1f;
            --premium-gray-900: #000000;
            
            --premium-blue: #0071e3;
            --premium-blue-light: #64d2ff;
            --premium-green: #34c759;
            --premium-yellow: #ffcc00;
            --premium-orange: #ff9500;
            --premium-red: #ff3b30;
            --premium-purple: #af52de;
            --premium-teal: #5ac8fa;
            
            /* Premium Gradients */
            --gradient-blue: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-green: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            --gradient-premium: linear-gradient(135deg, #0071e3 0%, #af52de 100%);
            --gradient-glass: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            
            /* Premium Shadows */
            --shadow-xs: 0 0 0 1px rgba(0,0,0,0.05);
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0,0,0,0.25);
            --shadow-premium: 0 2px 8px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.08);
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .ultra-premium-dashboard {
            background: linear-gradient(180deg, #fafafa 0%, #f5f5f7 50%, #ffffff 100%);
            min-height: 100vh;
            margin: -2rem;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated Background Pattern */
        .ultra-premium-dashboard::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(0,113,227,0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(175,82,222,0.03) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(100,210,255,0.03) 0%, transparent 50%);
            animation: floatGradient 20s ease infinite;
            pointer-events: none;
        }
        
        @keyframes floatGradient {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(-20px, -20px) rotate(1deg); }
            66% { transform: translate(20px, -10px) rotate(-1deg); }
        }
        
        /* Premium Glass Card */
        .premium-glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 20px;
            box-shadow: var(--shadow-premium);
            padding: 1.75rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .premium-glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255,255,255,0.5) 20%, 
                rgba(255,255,255,0.5) 80%, 
                transparent);
        }
        
        .premium-glass-card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 
                0 4px 12px rgba(0,0,0,0.08),
                0 16px 32px rgba(0,0,0,0.12);
            border-color: rgba(0,113,227,0.2);
        }
        
        /* Premium Metric Card */
        .premium-metric-card {
            background: var(--premium-white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .premium-metric-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0,113,227,0.05) 0%, transparent 70%);
            transition: all 0.5s ease;
            opacity: 0;
        }
        
        .premium-metric-card:hover::after {
            opacity: 1;
        }
        
        .premium-metric-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-xl);
        }
        
        .metric-icon-premium {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-premium);
            box-shadow: 0 4px 12px rgba(0,113,227,0.3);
            transition: all 0.3s ease;
        }
        
        .premium-metric-card:hover .metric-icon-premium {
            transform: rotate(-5deg) scale(1.1);
        }
        
        .metric-value-premium {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--premium-gray-900);
            letter-spacing: -0.02em;
            line-height: 1;
            margin-top: 1rem;
        }
        
        .metric-label-premium {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--premium-gray-500);
            margin-top: 0.5rem;
            letter-spacing: 0.01em;
        }
        
        .metric-trend {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 1rem;
            transition: all 0.2s ease;
        }
        
        .metric-trend.up {
            background: rgba(52,199,89,0.1);
            color: var(--premium-green);
        }
        
        .metric-trend.down {
            background: rgba(255,59,48,0.1);
            color: var(--premium-red);
        }
        
        .metric-trend:hover {
            transform: scale(1.05);
        }
        
        /* Premium Chart Container */
        .premium-chart-container {
            background: var(--premium-white);
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: var(--shadow-lg);
            position: relative;
        }
        
        .chart-header-premium {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title-premium {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--premium-gray-900);
            letter-spacing: -0.01em;
        }
        
        .chart-subtitle-premium {
            font-size: 0.875rem;
            color: var(--premium-gray-500);
            margin-top: 0.25rem;
        }
        
        /* Premium Buttons */
        .btn-premium {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--premium-gray-100);
            color: var(--premium-gray-700);
            position: relative;
            overflow: hidden;
        }
        
        .btn-premium::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(0,113,227,0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-premium:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: var(--premium-white);
            color: var(--premium-blue);
        }
        
        .btn-premium.active {
            background: var(--gradient-premium);
            color: var(--premium-white);
        }
        
        /* Premium Status Badge */
        .status-badge-premium {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.025em;
            text-transform: uppercase;
        }
        
        .status-badge-premium.success {
            background: rgba(52,199,89,0.1);
            color: var(--premium-green);
        }
        
        .status-badge-premium.warning {
            background: rgba(255,204,0,0.1);
            color: var(--premium-yellow);
        }
        
        .status-badge-premium.error {
            background: rgba(255,59,48,0.1);
            color: var(--premium-red);
        }
        
        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            animation: pulse-premium 2s infinite;
        }
        
        @keyframes pulse-premium {
            0% {
                box-shadow: 0 0 0 0 currentColor;
                opacity: 1;
            }
            70% {
                box-shadow: 0 0 0 6px currentColor;
                opacity: 0;
            }
            100% {
                box-shadow: 0 0 0 0 currentColor;
                opacity: 0;
            }
        }
        
        /* Premium Tables */
        .premium-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .premium-table thead {
            background: var(--premium-gray-50);
        }
        
        .premium-table th {
            padding: 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--premium-gray-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--premium-gray-200);
        }
        
        .premium-table td {
            padding: 1rem;
            font-size: 0.875rem;
            color: var(--premium-gray-700);
            border-bottom: 1px solid var(--premium-gray-100);
        }
        
        .premium-table tbody tr {
            transition: all 0.2s ease;
        }
        
        .premium-table tbody tr:hover {
            background: var(--premium-gray-50);
        }
        
        /* Premium Loading Animation */
        .loading-premium {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid var(--premium-gray-200);
            border-top-color: var(--premium-blue);
            border-radius: 50%;
            animation: spin-premium 1s linear infinite;
        }
        
        @keyframes spin-premium {
            to { transform: rotate(360deg); }
        }
        
        /* Smooth Scrollbar */
        .premium-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .premium-scrollbar::-webkit-scrollbar-track {
            background: var(--premium-gray-100);
            border-radius: 10px;
        }
        
        .premium-scrollbar::-webkit-scrollbar-thumb {
            background: var(--premium-gray-400);
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        
        .premium-scrollbar::-webkit-scrollbar-thumb:hover {
            background: var(--premium-gray-500);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .premium-glass-card {
                padding: 1.25rem;
                border-radius: 16px;
            }
            
            .metric-value-premium {
                font-size: 1.75rem;
            }
            
            .chart-title-premium {
                font-size: 1.125rem;
            }
        }
        
        /* Premium Animations */
        .fade-in-up {
            animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .slide-in-right {
            animation: slideInRight 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Chart Canvas Styling */
        canvas {
            border-radius: 12px;
        }
        .modern-analytics-wrapper {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            min-height: 100vh;
            margin: -2rem;
            padding: 2rem;
        }
        
        /* Glass Card Design */
        .modern-glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 16px;
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.05),
                0 10px 40px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .modern-glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.05),
                0 20px 60px rgba(0, 0, 0, 0.12);
        }
        
        /* Metric Cards */
        .modern-metric-card {
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .modern-metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }
        
        .modern-metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }
        
        .metric-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.2;
        }
        
        .metric-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.5rem;
            font-weight: 500;
        }
        
        .metric-change {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.75rem;
        }
        
        .metric-change.positive {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .metric-change.negative {
            background: #fee2e2;
            color: #dc2626;
        }
        
        /* Chart Containers */
        .modern-chart-container {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .chart-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .chart-action-btn {
            padding: 0.375rem 0.75rem;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #475569;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .chart-action-btn:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        
        .chart-action-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        /* Section Headers */
        .section-header {
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(180deg, #3b82f6, #8b5cf6);
            border-radius: 2px;
        }
        
        .section-subtitle {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        /* Status Indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-indicator.success {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-indicator.warning {
            background: #fef3c7;
            color: #d97706;
        }
        
        .status-indicator.error {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .status-indicator .pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        
        /* Tables */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .modern-table thead {
            background: #f8fafc;
        }
        
        .modern-table th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .modern-table td {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #1e293b;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .modern-table tbody tr:hover {
            background: #f8fafc;
        }
        
        /* Responsive Grid */
        @media (max-width: 640px) {
            .modern-metric-card {
                padding: 1rem;
            }
            
            .metric-value {
                font-size: 1.5rem;
            }
        }
    </style>
    
    <!-- Premium Dashboard Header -->
    <div class="mb-8 fade-in-up">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                    Analytics Dashboard
                </h1>
                <p class="text-gray-500 mt-2">
                    Umfassende Übersicht aller Unternehmensmetriken
                </p>
            </div>
            <div class="flex items-center gap-4">
                <div class="status-badge-premium success">
                    <span class="pulse-dot bg-green-500"></span>
                    Live
                </div>
                <button @click="refresh()" class="btn-premium">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Aktualisieren
                </button>
            </div>
        </div>
    </div>
    
    <!-- Premium Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Revenue -->
        <div class="premium-metric-card fade-in-up" style="animation-delay: 0.1s">
            <div class="metric-icon-premium">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="metric-value-premium">
                €{{ number_format($stats['revenue'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="metric-label-premium">Gesamt-Umsatz</div>
            @if(($stats['revenue'] ?? 0) > 0)
                <div class="metric-trend up">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    +12.5%
                </div>
            @endif
        </div>
        
        <!-- Total Calls -->
        <div class="modern-metric-card">
            <div class="metric-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div class="metric-value">
                {{ number_format($stats['total_calls'] ?? 0) }}
            </div>
            <div class="metric-label">Gesamt-Anrufe</div>
            <div class="text-gray-500 text-xs mt-1">
                {{ $stats['call_success_rate'] ?? 0 }}% erfolgreich
            </div>
        </div>
        
        <!-- Active Companies -->
        <div class="modern-metric-card">
            <div class="metric-icon" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div class="metric-value">
                {{ $stats['total_companies'] ?? 0 }}
            </div>
            <div class="metric-label">Aktive Unternehmen</div>
        </div>
        
        <!-- Conversion Rate -->
        <div class="modern-metric-card">
            <div class="metric-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div class="metric-value">
                {{ $stats['completion_rate'] ?? 0 }}%
            </div>
            <div class="metric-label">Abschlussrate</div>
            <div class="text-gray-500 text-xs mt-1">
                Termine zu Abschlüssen
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Revenue Trend Chart -->
        <div class="modern-glass-card">
            <div class="chart-header">
                <h3 class="chart-title">Umsatzentwicklung</h3>
                <div class="chart-actions">
                    <button class="chart-action-btn active">7 Tage</button>
                    <button class="chart-action-btn">30 Tage</button>
                    <button class="chart-action-btn">90 Tage</button>
                </div>
            </div>
            <div class="modern-chart-container" style="height: 300px;">
                <canvas id="modernRevenueChart"></canvas>
            </div>
        </div>
        
        <!-- Performance Chart -->
        <div class="modern-glass-card">
            <div class="chart-header">
                <h3 class="chart-title">Unternehmens-Performance</h3>
                <div class="chart-actions">
                    <button class="chart-action-btn">Export</button>
                </div>
            </div>
            <div class="modern-chart-container" style="height: 300px;">
                <canvas id="modernPerformanceChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Additional Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Appointments Distribution -->
        <div class="modern-glass-card">
            <div class="chart-header">
                <h3 class="chart-title">Terminverteilung</h3>
            </div>
            <div class="modern-chart-container" style="height: 250px;">
                <canvas id="modernAppointmentsChart"></canvas>
            </div>
        </div>
        
        <!-- Call Volume Heatmap -->
        <div class="modern-glass-card lg:col-span-2">
            <div class="chart-header">
                <h3 class="chart-title">Anruf-Heatmap</h3>
                <div class="status-indicator success">
                    <div class="pulse bg-green-500"></div>
                    Live
                </div>
            </div>
            <div class="p-4">
                @if(isset($heatmapData['peak_hour']))
                    <div class="text-gray-600 text-sm mb-4">
                        Hauptzeit: <span class="font-semibold">{{ sprintf('%02d:00', $heatmapData['peak_hour']) }} Uhr</span>
                    </div>
                @endif
                <div class="grid grid-cols-8 gap-1">
                    @if(isset($heatmapData['heatmap']))
                        @foreach($heatmapData['heatmap'] as $dayData)
                            <div class="text-gray-500 text-xs font-medium py-1">{{ substr($dayData['day'], 0, 2) }}</div>
                            @foreach(array_slice($dayData['data'], 8, 12) as $hourIndex => $calls)
                                <div class="w-full aspect-square rounded-md transition-all hover:scale-110 cursor-pointer" 
                                     style="background-color: {{ $calls > 0 ? 'rgba(59, 130, 246, ' . min($calls / 10, 1) . ')' : '#f1f5f9' }}"
                                     title="{{ $dayData['day'] }} {{ sprintf('%02d:00', $hourIndex + 8) }} - {{ $calls }} Anrufe">
                                </div>
                            @endforeach
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Performers & Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Performers -->
        <div class="modern-glass-card">
            <div class="section-header">
                <h3 class="section-title">Top Performer</h3>
            </div>
            
            @if(isset($topPerformers))
                <div class="space-y-6">
                    <!-- Revenue Leaders -->
                    @if(isset($topPerformers['revenue']) && count($topPerformers['revenue']) > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Umsatz-Spitzenreiter</h4>
                            @foreach($topPerformers['revenue'] as $index => $company)
                                <div class="flex items-center gap-3 p-2.5 bg-gradient-to-r from-blue-50 to-transparent rounded-lg mb-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center text-white text-sm font-bold">
                                        {{ $index + 1 }}
                                    </div>
                                    <span class="text-gray-700 text-sm font-medium">{{ $company }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    <!-- Call Volume Leaders -->
                    @if(isset($topPerformers['calls']) && count($topPerformers['calls']) > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Anruf-Volumen</h4>
                            @foreach($topPerformers['calls'] as $index => $company)
                                <div class="flex items-center gap-3 p-2.5 bg-gradient-to-r from-green-50 to-transparent rounded-lg mb-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-green-500 to-green-600 flex items-center justify-center text-white text-sm font-bold">
                                        {{ $index + 1 }}
                                    </div>
                                    <span class="text-gray-700 text-sm font-medium">{{ $company }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
        
        <!-- Activity Timeline -->
        <div class="modern-glass-card lg:col-span-2">
            <div class="section-header">
                <h3 class="section-title">Aktivitäten</h3>
                <p class="section-subtitle">Letzte Ereignisse in Echtzeit</p>
            </div>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @if(isset($activityTimeline) && count($activityTimeline) > 0)
                    @foreach($activityTimeline as $activity)
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="mt-1">
                                @if($activity['type'] === 'success')
                                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                @elseif($activity['type'] === 'warning')
                                    <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                                @else
                                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="text-gray-900 text-sm font-medium">{{ $activity['event'] }}</div>
                                <div class="text-gray-600 text-xs mt-0.5">{{ $activity['company'] }}</div>
                            </div>
                            <div class="text-gray-400 text-xs">{{ $activity['time'] }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="text-gray-500 text-center py-8">
                        Keine aktuellen Aktivitäten
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Alpine.js Data & Chart Configuration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
// Premium Dashboard Alpine Component
function premiumDashboard() {
    return {
        charts: {},
        autoRefresh: true,
        refreshInterval: 30,
        countdown: 30,
        
        init() {
            console.log('🎨 Premium Dashboard Initialized');
            this.initCharts();
            this.startAutoRefresh();
        },
        
        refresh() {
            console.log('🔄 Refreshing data...');
            // Trigger Livewire refresh (v3 uses dispatch)
            if (window.Livewire) {
                if (window.Livewire.dispatch) {
                    window.Livewire.dispatch('refresh');
                } else if (window.Livewire.emit) {
                    window.Livewire.emit('refresh');
                }
            }
            this.updateCharts();
        },
        
        startAutoRefresh() {
            if (this.autoRefresh) {
                setInterval(() => {
                    this.countdown--;
                    if (this.countdown <= 0) {
                        this.refresh();
                        this.countdown = this.refreshInterval;
                    }
                }, 1000);
            }
        },
        
        initCharts() {
            // Premium Chart Configuration
            Chart.defaults.font.family = 'Inter, -apple-system, BlinkMacSystemFont, sans-serif';
            Chart.defaults.color = '#6e6e73';
            Chart.defaults.borderColor = '#e5e5e7';
            
            // Destroy existing charts if they exist
            Object.values(this.charts).forEach(chart => {
                if (chart) chart.destroy();
            });
            
            this.createRevenueChart();
            this.createPerformanceChart();
            this.createAppointmentsChart();
        },
        
        createRevenueChart() {
            const ctx = document.getElementById('modernRevenueChart');
            if (!ctx) return;
            
            // Premium gradient background
            const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(0, 113, 227, 0.1)');
            gradient.addColorStop(1, 'rgba(0, 113, 227, 0.01)');
            
            this.charts.revenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartData['labels'] ?? ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']),
                    datasets: [{
                        label: 'Umsatz',
                        data: @json($chartData['revenue'] ?? [12000, 19000, 15000, 25000, 22000, 30000, 28000]),
                        borderColor: '#0071e3',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#0071e3',
                        pointBorderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: '#0071e3',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.9)',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: {
                                size: 14,
                                weight: '600'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return 'Umsatz: €' + context.parsed.y.toLocaleString('de-DE');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.03)',
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return '€' + value.toLocaleString('de-DE');
                                },
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    onClick: (event, activeElements) => {
                        if (activeElements.length > 0) {
                            const index = activeElements[0].index;
                            const label = this.charts.revenue.data.labels[index];
                            console.log('Clicked on:', label);
                            // Add your navigation logic here
                            // window.location.href = `/admin/analytics/day/${label}`;
                        }
                    }
                }
            });
        },
        
        createPerformanceChart() {
            const ctx = document.getElementById('modernPerformanceChart');
            if (!ctx) return;
            
            this.charts.performance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($chartData['labels'] ?? ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']),
                    datasets: [{
                        label: 'Anrufe',
                        data: @json($chartData['calls'] ?? [65, 59, 80, 81, 56, 55, 40]),
                        backgroundColor: 'rgba(0, 113, 227, 0.8)',
                        borderColor: '#0071e3',
                        borderWidth: 0,
                        borderRadius: 8,
                        barThickness: 24,
                    }, {
                        label: 'Termine',
                        data: @json($chartData['appointments'] ?? [28, 48, 40, 19, 86, 27, 90]),
                        backgroundColor: 'rgba(52, 199, 89, 0.8)',
                        borderColor: '#34c759',
                        borderWidth: 0,
                        borderRadius: 8,
                        barThickness: 24,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 13,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.9)',
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.03)',
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    onClick: (event, activeElements) => {
                        if (activeElements.length > 0) {
                            const datasetIndex = activeElements[0].datasetIndex;
                            const index = activeElements[0].index;
                            const label = this.charts.performance.data.labels[index];
                            const datasetLabel = this.charts.performance.data.datasets[datasetIndex].label;
                            console.log('Clicked on:', datasetLabel, 'at', label);
                            // Add navigation logic
                        }
                    }
                }
            });
        },
        
        createAppointmentsChart() {
            const ctx = document.getElementById('modernAppointmentsChart');
            if (!ctx) return;
            
            const companies = @json($companyComparison ?? [
                ['company' => 'Company A', 'appointments' => 45],
                ['company' => 'Company B', 'appointments' => 30],
                ['company' => 'Company C', 'appointments' => 25],
                ['company' => 'Company D', 'appointments' => 20],
                ['company' => 'Company E', 'appointments' => 15]
            ]);
            
            this.charts.appointments = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: companies.slice(0, 5).map(c => c.company),
                    datasets: [{
                        data: companies.slice(0, 5).map(c => c.appointments),
                        backgroundColor: [
                            '#0071e3',
                            '#34c759',
                            '#af52de',
                            '#ff9500',
                            '#ff3b30',
                        ],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.9)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + ' Termine';
                                }
                            }
                        }
                    },
                    cutout: '70%',
                    onClick: (event, activeElements) => {
                        if (activeElements.length > 0) {
                            const index = activeElements[0].index;
                            const company = companies[index].company;
                            console.log('Clicked on company:', company);
                            // Add navigation to company details
                        }
                    }
                }
            });
        },
        
        updateCharts() {
            // Update chart data here
            // You can fetch new data via AJAX or Livewire
            console.log('📊 Updating charts with new data...');
        }
    };
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Premium Dashboard DOM Ready');
    
    @if(isset($chartData) && !empty($chartData))
        // Legacy support for existing chart initialization
        const revenueCtx = document.getElementById('modernRevenueChart');
        if (revenueCtx) {
            new Chart(revenueCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($chartData['labels'] ?? []),
                    datasets: [{
                        label: 'Umsatz',
                        data: @json($chartData['revenue'] ?? []),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: {
                                size: 13,
                                weight: 600
                            },
                            bodyFont: {
                                size: 12
                            },
                            callbacks: {
                                label: function(context) {
                                    return 'Umsatz: €' + context.parsed.y.toLocaleString('de-DE');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(226, 232, 240, 0.5)',
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return '€' + value.toLocaleString('de-DE');
                                },
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Performance Chart
        const performanceCtx = document.getElementById('modernPerformanceChart');
        if (performanceCtx) {
            new Chart(performanceCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($chartData['labels'] ?? []),
                    datasets: [{
                        label: 'Anrufe',
                        data: @json($chartData['calls'] ?? []),
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: '#3b82f6',
                        borderWidth: 0,
                        borderRadius: 6,
                        barThickness: 20,
                    }, {
                        label: 'Termine',
                        data: @json($chartData['appointments'] ?? []),
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: '#10b981',
                        borderWidth: 0,
                        borderRadius: 6,
                        barThickness: 20,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(226, 232, 240, 0.5)',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Appointments Distribution Chart
        const appointmentsCtx = document.getElementById('modernAppointmentsChart');
        if (appointmentsCtx && window.companyComparison && window.companyComparison.length > 0) {
            const topCompanies = window.companyComparison.slice(0, 5);
            new Chart(appointmentsCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: topCompanies.map(c => c.company),
                    datasets: [{
                        data: topCompanies.map(c => c.appointments),
                        backgroundColor: [
                            '#3b82f6',
                            '#10b981',
                            '#8b5cf6',
                            '#f59e0b',
                            '#ef4444',
                        ],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 12,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    cutout: '65%'
                }
            });
        }
    @endif
    
    // Set company comparison data globally
    window.companyComparison = @json($companyComparison ?? []);
});
</script>