<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cost Alerts - System Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">

@section('title', 'Cost Tracking Alerts')

@section('head')
<style>
    .alert-card {
        transition: all 0.2s ease;
    }
    .alert-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .severity-critical {
        border-left: 4px solid #ef4444;
        background: linear-gradient(90deg, #fef2f2 0%, #ffffff 100%);
    }
    .severity-warning {
        border-left: 4px solid #f59e0b;
        background: linear-gradient(90deg, #fffbeb 0%, #ffffff 100%);
    }
    .severity-info {
        border-left: 4px solid #3b82f6;
        background: linear-gradient(90deg, #eff6ff 0%, #ffffff 100%);
    }
    .metric-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #e2e8f0;
    }
    .loading-skeleton {
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .chart-container {
        position: relative;
        height: 300px;
    }
</style>
@endsection

@section('content')
<div class="view" x-data="costAlertsApp()" x-init="initialize()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Cost Tracking Alerts</h1>
            <p class="text-gray-600 mt-1">Monitor and manage cost-related alerts for your companies</p>
        </div>
        
        <div class="flex items-center space-x-3">
            <button @click="refreshData()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
            
            <div class="relative">
                <select x-model="filters.company_id" @change="loadAlerts()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">All Companies</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="text-sm text-gray-500">
                Last updated: <span x-text="lastUpdate"></span>
            </div>
        </div>
    </div>

    <!-- Real-time Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="metric-card rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Critical Alerts</dt>
                        <dd class="text-lg font-semibold text-gray-900" x-text="dashboardData.alert_summary?.critical || 0"></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="metric-card rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Warning Alerts</dt>
                        <dd class="text-lg font-semibold text-gray-900" x-text="dashboardData.alert_summary?.warning || 0"></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="metric-card rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Alerts</dt>
                        <dd class="text-lg font-semibold text-gray-900" x-text="dashboardData.alert_summary?.pending || 0"></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="metric-card rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Companies</dt>
                        <dd class="text-lg font-semibold text-gray-900" x-text="realTimeMetrics?.total_companies || 0"></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Summary Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Alerts by Type</h3>
            <div class="chart-container">
                <canvas id="alertTypeChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Alerts by Severity</h3>
            <div class="chart-container">
                <canvas id="alertSeverityChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Recent Alerts</h3>
                
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="relative">
                        <input x-model="filters.search" @input.debounce.500ms="loadAlerts()" 
                               type="text" placeholder="Search alerts..." 
                               class="w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <svg class="absolute right-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    
                    <!-- Severity Filter -->
                    <select x-model="filters.severity" @change="loadAlerts()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">All Severities</option>
                        <option value="critical">Critical</option>
                        <option value="warning">Warning</option>
                        <option value="info">Info</option>
                    </select>
                    
                    <!-- Status Filter -->
                    <select x-model="filters.status" @change="loadAlerts()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="sent">Sent</option>
                        <option value="acknowledged">Acknowledged</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Alerts List -->
        <div class="divide-y divide-gray-200" x-show="!loading">
            <template x-for="alert in alerts" :key="alert.id">
                <div class="alert-card p-6 hover:bg-gray-50" :class="getSeverityClass(alert.severity)">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <span x-html="getSeverityBadge(alert.severity)"></span>
                                <span x-html="getTypeBadge(alert.alert_type)"></span>
                                <span x-html="getStatusBadge(alert.status)"></span>
                                <span class="text-sm text-gray-500" x-text="formatDate(alert.created_at)"></span>
                            </div>
                            
                            <h4 class="text-lg font-medium text-gray-900 mb-1" x-text="alert.title"></h4>
                            <p class="text-gray-600 mb-3" x-text="alert.message"></p>
                            
                            <div class="flex items-center space-x-6 text-sm text-gray-500">
                                <span><strong>Company:</strong> <span x-text="alert.company?.name || 'N/A'"></span></span>
                                <template x-if="alert.threshold_value">
                                    <span><strong>Threshold:</strong> <span x-text="formatValue(alert.threshold_value, alert.alert_type)"></span></span>
                                </template>
                                <template x-if="alert.current_value">
                                    <span><strong>Current:</strong> <span x-text="formatValue(alert.current_value, alert.alert_type)"></span></span>
                                </template>
                            </div>
                            
                            <!-- Alert-specific data -->
                            <template x-if="alert.data && (alert.alert_type === 'low_balance' || alert.alert_type === 'zero_balance')">
                                <div class="mt-3 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Balance: <span x-text="formatCurrency(alert.data.balance)"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                        
                        <div class="flex items-center space-x-3 ml-6">
                            <template x-if="alert.status !== 'acknowledged'">
                                <button @click="acknowledgeAlert(alert.id)" 
                                        class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Acknowledge
                                </button>
                            </template>
                            
                            <button @click="showAlertDetails(alert)" 
                                    class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Details
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            
            <!-- Empty state -->
            <template x-if="!loading && alerts.length === 0">
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No alerts found</h3>
                    <p class="mt-1 text-sm text-gray-500">No cost tracking alerts match your current filters.</p>
                </div>
            </template>
        </div>

        <!-- Loading state -->
        <div x-show="loading" class="divide-y divide-gray-200">
            <template x-for="i in 5" :key="i">
                <div class="p-6">
                    <div class="animate-pulse flex space-x-4">
                        <div class="flex-1 space-y-2">
                            <div class="loading-skeleton h-4 rounded w-3/4"></div>
                            <div class="loading-skeleton h-4 rounded w-1/2"></div>
                            <div class="loading-skeleton h-3 rounded w-1/4"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Pagination -->
        <div x-show="pagination && pagination.last_page > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button @click="previousPage()" :disabled="pagination.current_page <= 1" 
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                        Previous
                    </button>
                    <button @click="nextPage()" :disabled="pagination.current_page >= pagination.last_page"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span x-text="(pagination.current_page - 1) * pagination.per_page + 1"></span> to 
                            <span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> of 
                            <span x-text="pagination.total"></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <!-- Pagination buttons would go here -->
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alpine.js Component -->
<script>
function costAlertsApp() {
    return {
        loading: false,
        alerts: [],
        dashboardData: {},
        realTimeMetrics: {},
        pagination: null,
        lastUpdate: '',
        filters: {
            company_id: '{{ $filters['company_id'] ?? '' }}',
            severity: '{{ $filters['severity'] ?? '' }}',
            status: '{{ $filters['status'] ?? '' }}',
            alert_type: '{{ $filters['alert_type'] ?? '' }}',
            search: ''
        },
        
        async initialize() {
            await this.loadDashboardData();
            await this.loadAlerts();
            this.startAutoRefresh();
        },
        
        async loadDashboardData() {
            try {
                const response = await fetch('/telescope/cost-alerts/data?' + new URLSearchParams(this.filters));
                const result = await response.json();
                
                if (result.success) {
                    this.dashboardData = result.data;
                    this.realTimeMetrics = result.data.real_time_metrics;
                    this.updateCharts();
                }
                
                this.lastUpdate = new Date().toLocaleTimeString();
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
        },
        
        async loadAlerts(page = 1) {
            this.loading = true;
            
            try {
                const params = { ...this.filters, page, per_page: 20 };
                const response = await fetch('/telescope/cost-alerts/alerts?' + new URLSearchParams(params));
                const result = await response.json();
                
                if (result.success) {
                    this.alerts = result.data;
                    this.pagination = result.pagination;
                }
            } catch (error) {
                console.error('Failed to load alerts:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async refreshData() {
            this.loading = true;
            await this.loadDashboardData();
            await this.loadAlerts();
            this.loading = false;
        },
        
        async acknowledgeAlert(alertId) {
            try {
                const response = await fetch(`/telescope/cost-alerts/${alertId}/acknowledge`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update the alert in the list
                    const alertIndex = this.alerts.findIndex(a => a.id === alertId);
                    if (alertIndex !== -1) {
                        this.alerts[alertIndex].status = 'acknowledged';
                        this.alerts[alertIndex].acknowledged_at = result.data.acknowledged_at;
                    }
                    
                    // Refresh dashboard data
                    await this.loadDashboardData();
                } else {
                    alert('Failed to acknowledge alert: ' + result.message);
                }
            } catch (error) {
                console.error('Failed to acknowledge alert:', error);
                alert('Failed to acknowledge alert. Please try again.');
            }
        },
        
        showAlertDetails(alert) {
            // Create a modal or detailed view - for now, log the alert
            console.log('Alert details:', alert);
            
            // You could implement a modal here to show detailed alert information
            alert(`Alert Details:\n\nID: ${alert.id}\nType: ${alert.alert_type}\nSeverity: ${alert.severity}\nCompany: ${alert.company?.name || 'N/A'}\nCreated: ${this.formatDate(alert.created_at)}\nStatus: ${alert.status}`);
        },
        
        startAutoRefresh() {
            // Refresh data every 60 seconds
            setInterval(() => {
                if (!this.loading) {
                    this.loadDashboardData();
                }
            }, 60000);
        },
        
        updateCharts() {
            // Update alert type chart
            this.updateAlertTypeChart();
            
            // Update severity chart
            this.updateAlertSeverityChart();
        },
        
        updateAlertTypeChart() {
            const ctx = document.getElementById('alertTypeChart');
            if (!ctx) return;
            
            const data = this.dashboardData.alert_types || {};
            
            // Simple bar chart implementation or use Chart.js
            // For now, just log the data
            console.log('Alert types data:', data);
        },
        
        updateAlertSeverityChart() {
            const ctx = document.getElementById('alertSeverityChart');
            if (!ctx) return;
            
            const data = this.dashboardData.alert_summary || {};
            
            // Simple pie chart implementation or use Chart.js
            // For now, just log the data
            console.log('Alert severity data:', data);
        },
        
        getSeverityClass(severity) {
            return `severity-${severity}`;
        },
        
        getSeverityBadge(severity) {
            const badges = {
                critical: '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">🚨 Critical</span>',
                warning: '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">⚠️ Warning</span>',
                info: '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">📊 Info</span>'
            };
            return badges[severity] || badges.info;
        },
        
        getTypeBadge(type) {
            const label = type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">${label}</span>`;
        },
        
        getStatusBadge(status) {
            const badges = {
                pending: '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Pending</span>',
                sent: '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Sent</span>',
                acknowledged: '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Acknowledged</span>',
                failed: '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Failed</span>'
            };
            return badges[status] || badges.pending;
        },
        
        formatDate(date) {
            return new Date(date).toLocaleString();
        },
        
        formatValue(value, type) {
            if (type === 'low_balance' || type === 'zero_balance' || type === 'budget_exceeded') {
                return value + '%';
            }
            return value;
        },
        
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return 'N/A';
            return '€' + parseFloat(amount).toFixed(2);
        },
        
        nextPage() {
            if (this.pagination && this.pagination.current_page < this.pagination.last_page) {
                this.loadAlerts(this.pagination.current_page + 1);
            }
        },
        
        previousPage() {
            if (this.pagination && this.pagination.current_page > 1) {
                this.loadAlerts(this.pagination.current_page - 1);
            }
        }
    };
}
</script>
@endsection