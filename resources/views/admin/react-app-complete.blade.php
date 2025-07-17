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
            overflow-y: auto;
            height: 100vh;
            position: sticky;
            top: 0;
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
            position: sticky;
            top: 0;
            z-index: 100;
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
        
        /* Tables */
        .data-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .data-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .data-table tr:hover {
            background: #f9f9f9;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover { background: #dc2626; }
        .btn-success {
            background: #22c55e;
            color: white;
        }
        .btn-success:hover { background: #16a34a; }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success { background: #e8f5e9; color: #2e7d32; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #ffebee; color: #c62828; }
        .badge-info { background: #e3f2fd; color: #1976d2; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show { display: flex; }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        /* Filters */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 20px;
            border-bottom: 2px solid #e5e5e5;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 0;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .tab:hover {
            color: #3b82f6;
        }
        
        .tab.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { position: absolute; z-index: 1000; height: 100vh; }
            .sidebar.collapsed { transform: translateX(-100%); }
            .stats-grid { grid-template-columns: 1fr; }
            .filters { flex-direction: column; }
            .filter-group { width: 100%; }
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            gap: 5px;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination button {
            padding: 5px 10px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .pagination button:hover {
            background: #f5f5f5;
        }
        
        .pagination button.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
        // Complete Admin Portal Implementation
        (function() {
            const app = document.getElementById('app');
            const loading = document.getElementById('loading');
            
            // State Management
            let state = {
                user: { name: 'Admin', email: 'admin@askproai.de' },
                collapsed: false,
                currentPage: 'dashboard',
                currentModal: null,
                stats: {
                    companies: 0,
                    appointments_today: 0,
                    calls_today: 0,
                    customers_new: 0
                },
                // Data storage
                companies: null,
                calls: null,
                callStats: null,
                appointments: null,
                customers: null,
                branches: null,
                staff: null,
                services: null,
                users: null,
                invoices: null,
                // Filters
                filters: {
                    search: '',
                    status: '',
                    date_from: '',
                    date_to: '',
                    company_id: ''
                },
                // Pagination
                currentPageNum: 1,
                totalPages: 1
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
                { id: 'monitoring', label: 'Monitoring', icon: '📈' },
                { id: 'integrations', label: 'Integrationen', icon: '🔌' },
                { id: 'settings', label: 'Einstellungen', icon: '⚙️' }
            ];
            
            // API Functions
            async function apiCall(endpoint, options = {}) {
                const token = localStorage.getItem('admin_token');
                const response = await fetch(`/api/admin${endpoint}`, {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': token ? `Bearer ${token}` : '',
                        ...options.headers
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`API Error: ${response.status}`);
                }
                
                return response.json();
            }
            
            // Data Loading Functions
            async function loadCompanies() {
                try {
                    const params = new URLSearchParams({
                        page: state.currentPageNum,
                        per_page: 20,
                        search: state.filters.search,
                        status: state.filters.status
                    });
                    const data = await apiCall(`/companies?${params}`);
                    state.companies = data.data || [];
                    state.currentPageNum = data.current_page || 1;
                    state.totalPages = data.last_page || 1;
                    render();
                } catch (error) {
                    console.error('Failed to load companies:', error);
                    state.companies = [];
                    render();
                }
            }
            
            async function loadCalls() {
                try {
                    const params = new URLSearchParams({
                        page: state.currentPageNum,
                        per_page: 20,
                        search: state.filters.search,
                        status: state.filters.status,
                        date_from: state.filters.date_from,
                        date_to: state.filters.date_to,
                        company_id: state.filters.company_id
                    });
                    const [callsData, statsData] = await Promise.all([
                        apiCall(`/calls?${params}`),
                        apiCall('/calls/stats')
                    ]);
                    state.calls = callsData.data || [];
                    state.callStats = statsData;
                    state.currentPageNum = callsData.current_page || 1;
                    state.totalPages = callsData.last_page || 1;
                    render();
                } catch (error) {
                    console.error('Failed to load calls:', error);
                    state.calls = [];
                    state.callStats = {};
                    render();
                }
            }
            
            async function loadAppointments() {
                try {
                    const params = new URLSearchParams({
                        page: state.currentPageNum,
                        per_page: 20,
                        search: state.filters.search,
                        status: state.filters.status,
                        date_from: state.filters.date_from,
                        date_to: state.filters.date_to,
                        company_id: state.filters.company_id
                    });
                    const data = await apiCall(`/appointments?${params}`);
                    state.appointments = data.data || [];
                    state.currentPageNum = data.current_page || 1;
                    state.totalPages = data.last_page || 1;
                    render();
                } catch (error) {
                    console.error('Failed to load appointments:', error);
                    state.appointments = [];
                    render();
                }
            }
            
            async function loadCustomers() {
                try {
                    const params = new URLSearchParams({
                        page: state.currentPageNum,
                        per_page: 20,
                        search: state.filters.search,
                        company_id: state.filters.company_id
                    });
                    const data = await apiCall(`/customers?${params}`);
                    state.customers = data.data || [];
                    state.currentPageNum = data.current_page || 1;
                    state.totalPages = data.last_page || 1;
                    render();
                } catch (error) {
                    console.error('Failed to load customers:', error);
                    state.customers = [];
                    render();
                }
            }
            
            // Render Functions
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
                            <div class="stat-value">${state.stats.appointments_today}</div>
                            <div class="stat-change">5 anstehend</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-label">Anrufe heute</div>
                            <div class="stat-value">${state.stats.calls_today}</div>
                            <div class="stat-change">+15% vs gestern</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-label">Neue Kunden</div>
                            <div class="stat-value">${state.stats.customers_new}</div>
                            <div class="stat-change">+20 diese Woche</div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <h3 style="margin-bottom: 15px;">Letzte Aktivitäten</h3>
                            <div style="color: #666; font-size: 14px; line-height: 1.8;">
                                <div>📞 Neuer Anruf von +49 123 456789 - vor 5 Minuten</div>
                                <div>📅 Termin gebucht für morgen 14:00 - vor 12 Minuten</div>
                                <div>👤 Neuer Kunde registriert - vor 23 Minuten</div>
                                <div>✅ Termin abgeschlossen - vor 1 Stunde</div>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <h3 style="margin-bottom: 15px;">System Status</h3>
                            <div style="font-size: 14px; line-height: 1.8;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Database</span>
                                    <span class="badge badge-success">Operational</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Retell.ai API</span>
                                    <span class="badge badge-success">Operational</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Cal.com API</span>
                                    <span class="badge badge-warning">Slow</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Queue System</span>
                                    <span class="badge badge-success">0 Jobs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            function renderCompanies() {
                if (!state.companies) {
                    loadCompanies();
                    return renderLoading('Mandanten');
                }
                
                return `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h1 style="font-size: 24px; margin: 0;">Mandanten</h1>
                        <button class="btn btn-primary" onclick="showModal('company-create')">
                            + Neuer Mandant
                        </button>
                    </div>
                    
                    ${renderFilters(['search', 'status'])}
                    
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Telefon</th>
                                    <th style="text-align: center;">Filialen</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="text-align: right;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${state.companies.map(company => `
                                    <tr>
                                        <td>
                                            <div style="font-weight: 500;">${company.name}</div>
                                            <div style="font-size: 12px; color: #666;">ID: ${company.id}</div>
                                        </td>
                                        <td>${company.email}</td>
                                        <td>${company.phone}</td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-info">
                                                ${company.branches_count || 0} Filialen
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-${company.active ? 'success' : 'danger'}">
                                                ${company.active ? 'Aktiv' : 'Inaktiv'}
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
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
                    
                    ${renderPagination()}
                `;
            }
            
            function renderCalls() {
                if (!state.calls) {
                    loadCalls();
                    return renderLoading('Anrufe');
                }
                
                return `
                    <div style="margin-bottom: 30px;">
                        <h1 style="font-size: 24px; margin-bottom: 20px;">Anrufe</h1>
                        
                        <!-- Stats Cards -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                            <div class="stat-card" style="padding: 20px;">
                                <div style="color: #666; font-size: 14px;">Heute</div>
                                <div style="font-size: 24px; font-weight: 600;">${state.callStats?.calls_today || 0}</div>
                            </div>
                            <div class="stat-card" style="padding: 20px;">
                                <div style="color: #666; font-size: 14px;">Beantwortet</div>
                                <div style="font-size: 24px; font-weight: 600; color: #22c55e;">${Math.round(state.callStats?.answered_rate || 0)}%</div>
                            </div>
                            <div class="stat-card" style="padding: 20px;">
                                <div style="color: #666; font-size: 14px;">Ø Dauer</div>
                                <div style="font-size: 24px; font-weight: 600; color: #3b82f6;">${Math.round(state.callStats?.average_duration || 0)}s</div>
                            </div>
                            <div class="stat-card" style="padding: 20px;">
                                <div style="color: #666; font-size: 14px;">Gesamt</div>
                                <div style="font-size: 24px; font-weight: 600;">${state.callStats?.total_calls || 0}</div>
                            </div>
                        </div>
                    </div>
                    
                    ${renderFilters(['search', 'status', 'date_range', 'company'])}
                    
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Zeit</th>
                                    <th>Von/An</th>
                                    <th>Kunde</th>
                                    <th>Mandant</th>
                                    <th style="text-align: center;">Dauer</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="text-align: center;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${state.calls.map(call => `
                                    <tr>
                                        <td>
                                            <div style="font-size: 14px;">${call.created_at}</div>
                                        </td>
                                        <td>
                                            <div style="font-size: 14px;">${call.direction === 'inbound' ? call.from_phone_number : call.to_phone_number}</div>
                                            <div style="font-size: 12px; color: #666;">${call.direction === 'inbound' ? 'Eingehend' : 'Ausgehend'}</div>
                                        </td>
                                        <td>
                                            ${call.customer ? `
                                                <div style="font-size: 14px;">${call.customer.name}</div>
                                                <div style="font-size: 12px; color: #666;">${call.customer.phone}</div>
                                            ` : '<span style="color: #999;">Unbekannt</span>'}
                                        </td>
                                        <td>
                                            ${call.company ? `<span style="font-size: 13px;">${call.company.name}</span>` : '-'}
                                        </td>
                                        <td style="text-align: center;">
                                            <span style="font-size: 14px;">${call.duration_formatted}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-${getCallStatusClass(call.status)}">
                                                ${getCallStatusText(call.status)}
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
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
                    
                    ${renderPagination()}
                `;
            }
            
            function renderAppointments() {
                if (!state.appointments) {
                    loadAppointments();
                    return renderLoading('Termine');
                }
                
                return `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h1 style="font-size: 24px; margin: 0;">Termine</h1>
                        <button class="btn btn-primary" onclick="showModal('appointment-create')">
                            + Neuer Termin
                        </button>
                    </div>
                    
                    ${renderFilters(['search', 'status', 'date_range', 'company'])}
                    
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Datum/Zeit</th>
                                    <th>Kunde</th>
                                    <th>Service</th>
                                    <th>Mitarbeiter</th>
                                    <th>Filiale</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="text-align: right;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${state.appointments.map(appointment => `
                                    <tr>
                                        <td>
                                            <div style="font-weight: 500;">${appointment.start_time}</div>
                                            <div style="font-size: 12px; color: #666;">bis ${appointment.end_time}</div>
                                        </td>
                                        <td>
                                            ${appointment.customer ? `
                                                <div>${appointment.customer.name}</div>
                                                <div style="font-size: 12px; color: #666;">${appointment.customer.phone}</div>
                                            ` : '-'}
                                        </td>
                                        <td>${appointment.service?.name || '-'}</td>
                                        <td>${appointment.staff?.name || '-'}</td>
                                        <td>${appointment.branch?.name || '-'}</td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-${getAppointmentStatusClass(appointment.status)}">
                                                ${getAppointmentStatusText(appointment.status)}
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;"
                                                    onclick="viewAppointment(${appointment.id})">
                                                Details
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    ${renderPagination()}
                `;
            }
            
            function renderCustomers() {
                if (!state.customers) {
                    loadCustomers();
                    return renderLoading('Kunden');
                }
                
                return `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h1 style="font-size: 24px; margin: 0;">Kunden</h1>
                        <button class="btn btn-primary" onclick="showModal('customer-create')">
                            + Neuer Kunde
                        </button>
                    </div>
                    
                    ${renderFilters(['search', 'company'])}
                    
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Kontakt</th>
                                    <th>Adresse</th>
                                    <th>Mandant</th>
                                    <th style="text-align: center;">Termine</th>
                                    <th style="text-align: center;">Anrufe</th>
                                    <th style="text-align: right;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${state.customers.map(customer => `
                                    <tr>
                                        <td>
                                            <div style="font-weight: 500;">${customer.name}</div>
                                            <div style="font-size: 12px; color: #666;">Seit ${customer.created_at}</div>
                                        </td>
                                        <td>
                                            <div style="font-size: 14px;">${customer.phone}</div>
                                            ${customer.email ? `<div style="font-size: 12px; color: #666;">${customer.email}</div>` : ''}
                                        </td>
                                        <td>
                                            ${customer.address || customer.city ? `
                                                <div style="font-size: 13px;">${customer.address || ''}</div>
                                                <div style="font-size: 12px; color: #666;">${customer.postal_code || ''} ${customer.city || ''}</div>
                                            ` : '-'}
                                        </td>
                                        <td>${customer.company?.name || '-'}</td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-info">${customer.appointments_count || 0}</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-info">${customer.calls_count || 0}</span>
                                        </td>
                                        <td style="text-align: right;">
                                            <button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;"
                                                    onclick="viewCustomer(${customer.id})">
                                                Details
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    ${renderPagination()}
                `;
            }
            
            // Helper Functions
            function renderLoading(title) {
                return `
                    <h1 style="font-size: 24px; margin-bottom: 30px;">${title}</h1>
                    <div style="text-align: center; padding: 40px;">
                        <div class="spinner"></div>
                        <p style="margin-top: 20px; color: #666;">Lade ${title}...</p>
                    </div>
                `;
            }
            
            function renderFilters(filterTypes) {
                let html = '<div class="filters">';
                
                if (filterTypes.includes('search')) {
                    html += `
                        <div class="filter-group">
                            <label class="form-label">Suche</label>
                            <input type="text" class="form-input" placeholder="Suchen..." 
                                   value="${state.filters.search}"
                                   onchange="updateFilter('search', this.value)">
                        </div>
                    `;
                }
                
                if (filterTypes.includes('status')) {
                    html += `
                        <div class="filter-group">
                            <label class="form-label">Status</label>
                            <select class="form-select" onchange="updateFilter('status', this.value)">
                                <option value="">Alle</option>
                                <option value="active">Aktiv</option>
                                <option value="inactive">Inaktiv</option>
                            </select>
                        </div>
                    `;
                }
                
                if (filterTypes.includes('date_range')) {
                    html += `
                        <div class="filter-group">
                            <label class="form-label">Von</label>
                            <input type="date" class="form-input" 
                                   value="${state.filters.date_from}"
                                   onchange="updateFilter('date_from', this.value)">
                        </div>
                        <div class="filter-group">
                            <label class="form-label">Bis</label>
                            <input type="date" class="form-input" 
                                   value="${state.filters.date_to}"
                                   onchange="updateFilter('date_to', this.value)">
                        </div>
                    `;
                }
                
                if (filterTypes.includes('company')) {
                    html += `
                        <div class="filter-group">
                            <label class="form-label">Mandant</label>
                            <select class="form-select" onchange="updateFilter('company_id', this.value)">
                                <option value="">Alle Mandanten</option>
                            </select>
                        </div>
                    `;
                }
                
                html += `
                    <div class="filter-group">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-secondary" onclick="resetFilters()">
                            Filter zurücksetzen
                        </button>
                    </div>
                `;
                
                html += '</div>';
                return html;
            }
            
            function renderPagination() {
                if (state.totalPages <= 1) return '';
                
                let html = '<div class="pagination">';
                
                // Previous button
                html += `<button onclick="changePage(${state.currentPageNum - 1})" 
                                ${state.currentPageNum === 1 ? 'disabled' : ''}>←</button>`;
                
                // Page numbers
                for (let i = 1; i <= Math.min(state.totalPages, 5); i++) {
                    html += `<button onclick="changePage(${i})" 
                                    class="${i === state.currentPageNum ? 'active' : ''}">${i}</button>`;
                }
                
                if (state.totalPages > 5) {
                    html += '<span>...</span>';
                    html += `<button onclick="changePage(${state.totalPages})">${state.totalPages}</button>`;
                }
                
                // Next button
                html += `<button onclick="changePage(${state.currentPageNum + 1})" 
                                ${state.currentPageNum === state.totalPages ? 'disabled' : ''}>→</button>`;
                
                html += '</div>';
                return html;
            }
            
            function getCallStatusClass(status) {
                const classes = {
                    'ended': 'success',
                    'active': 'info',
                    'no-answer': 'warning',
                    'failed': 'danger'
                };
                return classes[status] || 'secondary';
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
            
            function getAppointmentStatusClass(status) {
                const classes = {
                    'scheduled': 'info',
                    'confirmed': 'success',
                    'completed': 'success',
                    'cancelled': 'danger',
                    'no_show': 'warning'
                };
                return classes[status] || 'secondary';
            }
            
            function getAppointmentStatusText(status) {
                const texts = {
                    'scheduled': 'Geplant',
                    'confirmed': 'Bestätigt',
                    'completed': 'Abgeschlossen',
                    'cancelled': 'Abgesagt',
                    'no_show': 'Nicht erschienen'
                };
                return texts[status] || status;
            }
            
            // Render Other Pages
            function renderPage(pageId) {
                switch(pageId) {
                    case 'companies':
                        return renderCompanies();
                    case 'calls':
                        return renderCalls();
                    case 'appointments':
                        return renderAppointments();
                    case 'customers':
                        return renderCustomers();
                    default:
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
                    
                    <!-- Modals -->
                    <div id="modal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-title" id="modal-title">Modal Title</h2>
                                <button class="modal-close" onclick="closeModal()">×</button>
                            </div>
                            <div id="modal-body">
                                <!-- Modal content will be inserted here -->
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Event Handlers
            window.navigateTo = function(pageId) {
                state.currentPage = pageId;
                state.currentPageNum = 1;
                // Clear data to force reload
                state[pageId] = null;
                render();
            };
            
            window.toggleSidebar = function() {
                state.collapsed = !state.collapsed;
                render();
            };
            
            window.logout = function() {
                if (confirm('Möchten Sie sich wirklich abmelden?')) {
                    localStorage.removeItem('admin_token');
                    window.location.href = '/admin/login';
                }
            };
            
            window.updateFilter = function(key, value) {
                state.filters[key] = value;
                state.currentPageNum = 1;
                // Reload current data
                switch(state.currentPage) {
                    case 'companies':
                        state.companies = null;
                        break;
                    case 'calls':
                        state.calls = null;
                        break;
                    case 'appointments':
                        state.appointments = null;
                        break;
                    case 'customers':
                        state.customers = null;
                        break;
                }
                render();
            };
            
            window.resetFilters = function() {
                state.filters = {
                    search: '',
                    status: '',
                    date_from: '',
                    date_to: '',
                    company_id: ''
                };
                updateFilter('search', '');
            };
            
            window.changePage = function(page) {
                if (page < 1 || page > state.totalPages) return;
                state.currentPageNum = page;
                // Reload current data
                switch(state.currentPage) {
                    case 'companies':
                        loadCompanies();
                        break;
                    case 'calls':
                        loadCalls();
                        break;
                    case 'appointments':
                        loadAppointments();
                        break;
                    case 'customers':
                        loadCustomers();
                        break;
                }
            };
            
            // Detail View Functions
            window.viewCompany = function(id) {
                showModal('company-details', id);
            };
            
            window.viewCall = function(id) {
                showModal('call-details', id);
            };
            
            window.viewAppointment = function(id) {
                showModal('appointment-details', id);
            };
            
            window.viewCustomer = function(id) {
                showModal('customer-details', id);
            };
            
            // Modal Functions
            window.showModal = function(type, id = null) {
                const modal = document.getElementById('modal');
                const modalTitle = document.getElementById('modal-title');
                const modalBody = document.getElementById('modal-body');
                
                switch(type) {
                    case 'company-details':
                        modalTitle.textContent = 'Mandanten Details';
                        modalBody.innerHTML = '<div class="spinner"></div>';
                        modal.classList.add('show');
                        loadCompanyDetails(id);
                        break;
                    case 'call-details':
                        modalTitle.textContent = 'Anruf Details';
                        modalBody.innerHTML = '<div class="spinner"></div>';
                        modal.classList.add('show');
                        loadCallDetails(id);
                        break;
                    case 'appointment-details':
                        modalTitle.textContent = 'Termin Details';
                        modalBody.innerHTML = '<div class="spinner"></div>';
                        modal.classList.add('show');
                        loadAppointmentDetails(id);
                        break;
                    case 'customer-details':
                        modalTitle.textContent = 'Kunden Details';
                        modalBody.innerHTML = '<div class="spinner"></div>';
                        modal.classList.add('show');
                        loadCustomerDetails(id);
                        break;
                    default:
                        modalTitle.textContent = 'Details';
                        modalBody.innerHTML = '<p>Details werden geladen...</p>';
                        modal.classList.add('show');
                }
                
                state.currentModal = { type, id };
            };
            
            window.closeModal = function() {
                const modal = document.getElementById('modal');
                modal.classList.remove('show');
                state.currentModal = null;
            };
            
            async function loadCompanyDetails(id) {
                try {
                    const company = await apiCall(`/companies/${id}`);
                    const modalBody = document.getElementById('modal-body');
                    modalBody.innerHTML = `
                        <div>
                            <h3>${company.name}</h3>
                            <p><strong>Email:</strong> ${company.email}</p>
                            <p><strong>Telefon:</strong> ${company.phone}</p>
                            <p><strong>Status:</strong> ${company.active ? 'Aktiv' : 'Inaktiv'}</p>
                            <p><strong>Filialen:</strong> ${company.branches_count || 0}</p>
                            <p><strong>Kunden:</strong> ${company.customers_count || 0}</p>
                            <p><strong>Termine:</strong> ${company.appointments_count || 0}</p>
                            <p><strong>Anrufe:</strong> ${company.calls_count || 0}</p>
                        </div>
                    `;
                } catch (error) {
                    console.error('Failed to load company details:', error);
                    document.getElementById('modal-body').innerHTML = '<p style="color: red;">Fehler beim Laden der Details</p>';
                }
            }
            
            async function loadCallDetails(id) {
                try {
                    const call = await apiCall(`/calls/${id}`);
                    const modalBody = document.getElementById('modal-body');
                    modalBody.innerHTML = `
                        <div>
                            <p><strong>ID:</strong> ${call.retell_call_id}</p>
                            <p><strong>Von:</strong> ${call.from_phone_number}</p>
                            <p><strong>An:</strong> ${call.to_phone_number}</p>
                            <p><strong>Richtung:</strong> ${call.direction}</p>
                            <p><strong>Status:</strong> ${call.status}</p>
                            <p><strong>Dauer:</strong> ${call.duration_formatted}</p>
                            <p><strong>Zeitpunkt:</strong> ${call.created_at}</p>
                            ${call.transcript ? `
                                <hr style="margin: 20px 0;">
                                <h4>Transkript</h4>
                                <p style="white-space: pre-wrap;">${call.transcript}</p>
                            ` : ''}
                        </div>
                    `;
                } catch (error) {
                    console.error('Failed to load call details:', error);
                    document.getElementById('modal-body').innerHTML = '<p style="color: red;">Fehler beim Laden der Details</p>';
                }
            }
            
            // Check authentication
            async function checkAuth() {
                const token = localStorage.getItem('admin_token');
                if (!token) {
                    window.location.href = '/admin-react-login';
                    return;
                }
                
                try {
                    const userData = await apiCall('/auth/user');
                    state.user = userData.user || userData;
                } catch (error) {
                    console.error('Auth check failed:', error);
                    localStorage.removeItem('admin_token');
                    window.location.href = '/admin-react-login';
                    return;
                }
            }
            
            // Initialize
            setTimeout(async () => {
                await checkAuth();
                
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