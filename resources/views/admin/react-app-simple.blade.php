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
    
    <!-- Styles -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; }
        
        .container { min-height: 100vh; display: flex; }
        
        /* Sidebar */
        .sidebar { 
            width: 250px; 
            background: #1a1a1a; 
            color: white; 
            padding: 20px;
            transition: width 0.3s;
        }
        .sidebar.collapsed { width: 60px; }
        .sidebar h1 { font-size: 20px; margin-bottom: 30px; }
        .sidebar.collapsed h1 { display: none; }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            margin-bottom: 5px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .nav-item:hover { background: rgba(255,255,255,0.1); }
        .nav-item.active { background: #3b82f6; }
        .nav-item span { white-space: nowrap; }
        .sidebar.collapsed .nav-item span { display: none; }
        
        /* Main Content */
        .main { flex: 1; display: flex; flex-direction: column; }
        
        .header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content { 
            flex: 1; 
            padding: 30px; 
            overflow-y: auto;
        }
        
        /* Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-label { 
            color: #666; 
            font-size: 14px; 
            margin-bottom: 8px;
        }
        .stat-value { 
            font-size: 32px; 
            font-weight: 600; 
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        .stat-change { 
            font-size: 13px; 
            color: #22c55e;
        }
        .stat-change.negative { color: #ef4444; }
        
        /* Info Box */
        .info-box {
            background: #dbeafe;
            border: 1px solid #60a5fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .info-box h3 {
            color: #1e40af;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .info-box p { color: #1e40af; margin-bottom: 10px; }
        .info-box ul { list-style: none; }
        .info-box li { 
            color: #2563eb; 
            padding: 5px 0;
            font-size: 14px;
        }
        
        /* Button */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover { background: #2563eb; }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover { background: #dc2626; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { position: absolute; z-index: 1000; height: 100vh; }
            .sidebar.collapsed { transform: translateX(-100%); }
            .stats-grid { grid-template-columns: 1fr; }
        }
        
        /* Loading */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="app" class="container">
        <!-- Loading State -->
        <div class="loading" id="loading">
            <div>
                <div class="spinner"></div>
                <p style="margin-top: 20px; color: #666;">Admin Portal wird geladen...</p>
            </div>
        </div>
    </div>

    <script>
        // Simple React-like Admin Portal
        (function() {
            const app = document.getElementById('app');
            const loading = document.getElementById('loading');
            
            // State
            let state = {
                user: { name: 'Admin', email: 'admin@askproai.de' },
                collapsed: false,
                currentPage: 'dashboard',
                stats: {
                    companies: 12,
                    appointments: 24,
                    calls: 89,
                    customers: 145
                }
            };
            
            // Menu Items
            const menuItems = [
                { id: 'dashboard', label: 'Dashboard', icon: '📊' },
                { id: 'companies', label: 'Mandanten', icon: '🏢' },
                { id: 'calls', label: 'Anrufe', icon: '📞' },
                { id: 'appointments', label: 'Termine', icon: '📅' },
                { id: 'customers', label: 'Kunden', icon: '👤' },
                { id: 'branches', label: 'Filialen', icon: '🏪' },
                { id: 'staff', label: 'Mitarbeiter', icon: '👥' },
                { id: 'services', label: 'Dienstleistungen', icon: '💼' },
                { id: 'users', label: 'Benutzer', icon: '🔐' },
                { id: 'invoices', label: 'Rechnungen', icon: '📄' },
                { id: 'settings', label: 'Einstellungen', icon: '⚙️' }
            ];
            
            // Render Dashboard
            function renderDashboard() {
                return `
                    <h1 style="font-size: 24px; margin-bottom: 30px;">Dashboard</h1>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Aktive Mandanten</div>
                            <div class="stat-value">${state.stats.companies}</div>
                            <div class="stat-change">+2 diese Woche</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-label">Termine heute</div>
                            <div class="stat-value">${state.stats.appointments}</div>
                            <div class="stat-change">5 anstehend</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-label">Anrufe heute</div>
                            <div class="stat-value">${state.stats.calls}</div>
                            <div class="stat-change">+15% vs gestern</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-label">Kunden gesamt</div>
                            <div class="stat-value">${state.stats.customers}</div>
                            <div class="stat-change">+20 diese Woche</div>
                        </div>
                    </div>
                    
                    <div class="info-box">
                        <h3>🎉 Willkommen im neuen React Admin Portal!</h3>
                        <p>Dies ist das neue Admin Portal - keine Session-Konflikte mehr!</p>
                        <ul>
                            <li>✅ Keine 419 Session Errors</li>
                            <li>✅ Einheitliche Technologie mit Business Portal</li>
                            <li>✅ Schnellere Performance</li>
                            <li>✅ Modernes, responsives Design</li>
                        </ul>
                    </div>
                    
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 15px;">Letzte Aktivitäten</h3>
                        <div style="color: #666; font-size: 14px; line-height: 1.8;">
                            <div>📞 Neuer Anruf von +49 123 456789 - vor 5 Minuten</div>
                            <div>📅 Termin gebucht für morgen 14:00 - vor 12 Minuten</div>
                            <div>👤 Neuer Kunde registriert - vor 23 Minuten</div>
                            <div>✅ Termin abgeschlossen - vor 1 Stunde</div>
                        </div>
                    </div>
                `;
            }
            
            // Render Companies Page
            function renderCompanies() {
                if (!state.companies) {
                    // Load companies on first visit
                    loadCompanies();
                    return `
                        <h1 style="font-size: 24px; margin-bottom: 30px;">Mandanten</h1>
                        <div style="text-align: center; padding: 40px;">
                            <div class="spinner"></div>
                            <p style="margin-top: 20px; color: #666;">Lade Mandanten...</p>
                        </div>
                    `;
                }
                
                return `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h1 style="font-size: 24px; margin: 0;">Mandanten</h1>
                        <button class="btn btn-primary" onclick="alert('Neuen Mandanten erstellen - wird implementiert')">
                            + Neuer Mandant
                        </button>
                    </div>
                    
                    <div style="background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f5f5f5; border-bottom: 1px solid #e5e5e5;">
                                    <th style="padding: 12px; text-align: left; font-weight: 600;">Name</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600;">Email</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600;">Telefon</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600;">Filialen</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600;">Status</th>
                                    <th style="padding: 12px; text-align: right; font-weight: 600;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${state.companies.map(company => `
                                    <tr style="border-bottom: 1px solid #f0f0f0;">
                                        <td style="padding: 12px;">
                                            <div style="font-weight: 500;">${company.name}</div>
                                            <div style="font-size: 12px; color: #666;">ID: ${company.id}</div>
                                        </td>
                                        <td style="padding: 12px; color: #666;">${company.email}</td>
                                        <td style="padding: 12px; color: #666;">${company.phone}</td>
                                        <td style="padding: 12px; text-align: center;">
                                            <span style="background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                                ${company.branches_count || 0} Filialen
                                            </span>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <span style="background: ${company.active ? '#e8f5e9' : '#ffebee'}; 
                                                       color: ${company.active ? '#2e7d32' : '#c62828'}; 
                                                       padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                                ${company.active ? 'Aktiv' : 'Inaktiv'}
                                            </span>
                                        </td>
                                        <td style="padding: 12px; text-align: right;">
                                            <button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;"
                                                    onclick="viewCompany(${company.id})">
                                                Details
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            // Render Calls Page
            function renderCalls() {
                if (!state.calls) {
                    loadCalls();
                    return `
                        <h1 style="font-size: 24px; margin-bottom: 30px;">Anrufe</h1>
                        <div style="text-align: center; padding: 40px;">
                            <div class="spinner"></div>
                            <p style="margin-top: 20px; color: #666;">Lade Anrufe...</p>
                        </div>
                    `;
                }
                
                return `
                    <div style="margin-bottom: 30px;">
                        <h1 style="font-size: 24px; margin-bottom: 20px;">Anrufe</h1>
                        
                        <!-- Stats Cards -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <div style="color: #666; font-size: 14px;">Heute</div>
                                <div style="font-size: 24px; font-weight: 600; color: #1a1a1a;">${state.callStats?.calls_today || 0}</div>
                            </div>
                            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <div style="color: #666; font-size: 14px;">Beantwortet</div>
                                <div style="font-size: 24px; font-weight: 600; color: #22c55e;">${Math.round(state.callStats?.answered_rate || 0)}%</div>
                            </div>
                            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <div style="color: #666; font-size: 14px;">Ø Dauer</div>
                                <div style="font-size: 24px; font-weight: 600; color: #3b82f6;">${Math.round(state.callStats?.average_duration || 0)}s</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f5f5f5; border-bottom: 1px solid #e5e5e5;">
                                    <th style="padding: 12px; text-align: left; font-weight: 600;">Zeit</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600;">Von/An</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600;">Kunde</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600;">Dauer</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600;">Status</th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${state.calls.map(call => `
                                    <tr style="border-bottom: 1px solid #f0f0f0;">
                                        <td style="padding: 12px;">
                                            <div style="font-size: 14px;">${call.created_at}</div>
                                        </td>
                                        <td style="padding: 12px;">
                                            <div style="font-size: 14px;">${call.direction === 'inbound' ? call.from_phone_number : call.to_phone_number}</div>
                                            <div style="font-size: 12px; color: #666;">${call.direction === 'inbound' ? 'Eingehend' : 'Ausgehend'}</div>
                                        </td>
                                        <td style="padding: 12px;">
                                            ${call.customer ? `
                                                <div style="font-size: 14px;">${call.customer.name}</div>
                                                <div style="font-size: 12px; color: #666;">${call.customer.phone}</div>
                                            ` : '<span style="color: #999;">Unbekannt</span>'}
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <span style="font-size: 14px;">${call.duration_formatted}</span>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <span style="background: ${getCallStatusColor(call.status)}; 
                                                       color: white; 
                                                       padding: 4px 8px; 
                                                       border-radius: 4px; 
                                                       font-size: 12px;">
                                                ${getCallStatusText(call.status)}
                                            </span>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;"
                                                    onclick="viewCall(${call.id})">
                                                Details
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            // Helper functions
            function getCallStatusColor(status) {
                const colors = {
                    'ended': '#22c55e',
                    'active': '#3b82f6',
                    'no-answer': '#f59e0b',
                    'failed': '#ef4444'
                };
                return colors[status] || '#6b7280';
            }
            
            function getCallStatusText(status) {
                const texts = {
                    'ended': 'Beendet',
                    'active': 'Aktiv',
                    'no-answer': 'Nicht beantwortet',
                    'failed': 'Fehlgeschlagen'
                };
                return texts[status] || status;
            }
            
            // Render Other Pages
            function renderPage(pageId) {
                if (pageId === 'companies') {
                    return renderCompanies();
                }
                if (pageId === 'calls') {
                    return renderCalls();
                }
                
                const page = menuItems.find(item => item.id === pageId);
                return `
                    <h1 style="font-size: 24px; margin-bottom: 30px;">${page.label}</h1>
                    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <p style="color: #666;">Diese Seite wird noch implementiert...</p>
                        <p style="color: #999; font-size: 14px; margin-top: 10px;">
                            Hier werden alle ${page.label} verwaltet.
                        </p>
                    </div>
                `;
            }
            
            // Render App
            function render() {
                app.innerHTML = `
                    <!-- Sidebar -->
                    <aside class="sidebar ${state.collapsed ? 'collapsed' : ''}">
                        <h1>AskProAI Admin</h1>
                        <nav>
                            ${menuItems.map(item => `
                                <div class="nav-item ${state.currentPage === item.id ? 'active' : ''}" 
                                     onclick="navigateTo('${item.id}')">
                                    <span style="font-size: 20px;">${item.icon}</span>
                                    <span>${item.label}</span>
                                </div>
                            `).join('')}
                        </nav>
                    </aside>
                    
                    <!-- Main Content -->
                    <div class="main">
                        <header class="header">
                            <div>
                                <button class="btn btn-primary" onclick="toggleSidebar()">☰</button>
                                <span style="margin-left: 20px; font-size: 18px; font-weight: 600;">
                                    ${menuItems.find(item => item.id === state.currentPage).label}
                                </span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <span>${state.user.name}</span>
                                <button class="btn btn-danger" onclick="logout()">Abmelden</button>
                            </div>
                        </header>
                        
                        <main class="content">
                            ${state.currentPage === 'dashboard' ? renderDashboard() : renderPage(state.currentPage)}
                        </main>
                    </div>
                `;
            }
            
            // Navigation
            window.navigateTo = function(pageId) {
                state.currentPage = pageId;
                render();
            };
            
            // Toggle Sidebar
            window.toggleSidebar = function() {
                state.collapsed = !state.collapsed;
                render();
            };
            
            // Logout
            window.logout = function() {
                if (confirm('Möchten Sie sich wirklich abmelden?')) {
                    localStorage.removeItem('admin_token');
                    window.location.href = '/admin/login';
                }
            };
            
            // API Functions
            async function apiCall(endpoint, options = {}) {
                const token = localStorage.getItem('admin_token');
                const response = await fetch(`/api/admin${endpoint}`, {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': token ? `Bearer ${token}` : '',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        ...options.headers
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`API Error: ${response.status}`);
                }
                
                return response.json();
            }
            
            // Load Companies
            async function loadCompanies() {
                try {
                    const data = await apiCall('/companies');
                    state.companies = data.data || [];
                    render();
                } catch (error) {
                    console.error('Failed to load companies:', error);
                    // Use mock data as fallback
                    state.companies = [
                        { id: 1, name: 'Musterfirma GmbH', email: 'info@musterfirma.de', phone: '+49 123 456789', active: true, branches_count: 3 },
                        { id: 2, name: 'Beispiel AG', email: 'kontakt@beispiel.de', phone: '+49 987 654321', active: true, branches_count: 2 },
                        { id: 3, name: 'Demo Company', email: 'demo@company.de', phone: '+49 111 222333', active: false, branches_count: 1 }
                    ];
                    render();
                }
            }
            
            // Load Calls
            async function loadCalls() {
                try {
                    const [callsData, statsData] = await Promise.all([
                        apiCall('/calls'),
                        apiCall('/calls/stats')
                    ]);
                    state.calls = callsData.data || [];
                    state.callStats = statsData;
                    render();
                } catch (error) {
                    console.error('Failed to load calls:', error);
                    // Use mock data as fallback
                    state.calls = [
                        { 
                            id: 1, 
                            created_at: '10.01.2025 14:30', 
                            from_phone_number: '+49 123 456789',
                            to_phone_number: '+49 30 12345678',
                            direction: 'inbound',
                            duration_formatted: '02:45',
                            status: 'ended',
                            customer: { name: 'Max Mustermann', phone: '+49 123 456789' }
                        },
                        { 
                            id: 2, 
                            created_at: '10.01.2025 13:15', 
                            from_phone_number: '+49 987 654321',
                            to_phone_number: '+49 30 12345678',
                            direction: 'inbound',
                            duration_formatted: '01:20',
                            status: 'ended',
                            customer: null
                        }
                    ];
                    state.callStats = {
                        calls_today: 89,
                        answered_rate: 92,
                        average_duration: 165
                    };
                    render();
                }
            }
            
            // View Details Functions
            window.viewCompany = function(companyId) {
                alert(`Company Details für ID ${companyId} - wird implementiert`);
            };
            
            window.viewCall = function(callId) {
                alert(`Call Details für ID ${callId} - wird implementiert`);
            };
            
            // Initialize
            setTimeout(() => {
                loading.style.display = 'none';
                render();
                
                // Load dashboard stats
                if (state.currentPage === 'dashboard') {
                    apiCall('/dashboard/stats?simple=true')
                        .then(data => {
                            state.stats = data;
                            render();
                        })
                        .catch(console.error);
                }
                
                // Update stats periodically
                setInterval(() => {
                    if (state.currentPage === 'dashboard') {
                        apiCall('/dashboard/stats?simple=true')
                            .then(data => {
                                state.stats = data;
                                render();
                            })
                            .catch(console.error);
                    }
                }, 30000); // Every 30 seconds
            }, 500);
        })();
    </script>
</body>
</html>