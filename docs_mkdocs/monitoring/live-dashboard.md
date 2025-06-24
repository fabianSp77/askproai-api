# Live System Dashboard

<div data-live-docs>
    <script src="/js/docs-live-data.js"></script>
</div>

## System Overview

### 📊 Key Metrics

<div class="metrics-grid">
    <div class="metric-card">
        <h4>Total Appointments</h4>
        <p class="metric-value" id="total-appointments">Loading...</p>
        <p class="metric-sub">Today: <span id="today-appointments">-</span></p>
    </div>
    
    <div class="metric-card">
        <h4>Active Customers</h4>
        <p class="metric-value" id="active-customers">Loading...</p>
        <p class="metric-sub">New Today: <span id="new-customers-today">-</span></p>
    </div>
    
    <div class="metric-card">
        <h4>Phone Calls</h4>
        <p class="metric-value" id="today-calls">Loading...</p>
        <p class="metric-sub">Avg Duration: <span id="avg-call-duration">-</span></p>
    </div>
    
    <div class="metric-card">
        <h4>System Health</h4>
        <p class="metric-value" id="system-health-status">Loading...</p>
        <p class="metric-sub">Uptime: <span id="system-uptime">-</span></p>
    </div>
</div>

## 🚀 Performance Metrics

<div id="performance-table">
    <p>Loading performance data...</p>
</div>

## 🔄 Booking Workflow Status

<div class="workflow-overview">
    <p>Success Rate: <strong id="booking-success-rate">-</strong></p>
    <p>Average Processing Time: <strong id="booking-avg-time">-</strong></p>
    <p>Bookings Today: <strong id="bookings-today">-</strong></p>
</div>

<div id="workflow-steps">
    <p>Loading workflow steps...</p>
</div>

## 🏥 Service Health

<div id="health-checks">
    <p>Loading health checks...</p>
</div>

## 📈 Queue Status

<div class="queue-metrics">
    <p>Queue Size: <strong id="queue-size">-</strong></p>
    <p>Failed Jobs: <strong id="failed-jobs">-</strong></p>
</div>

---

<style>
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.metric-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
}

.metric-card h4 {
    margin: 0 0 0.5rem 0;
    color: #495057;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-value {
    font-size: 2rem;
    font-weight: bold;
    color: #212529;
    margin: 0.5rem 0;
}

.metric-sub {
    font-size: 0.875rem;
    color: #6c757d;
}

.workflow-overview {
    background: #e7f3ff;
    border-left: 4px solid #0066cc;
    padding: 1rem;
    margin: 1rem 0;
}

.queue-metrics {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 1rem;
    margin: 1rem 0;
}
</style>

!!! info "Live Data"
    This dashboard displays real-time data from the AskProAI system. Data refreshes automatically every 30-60 seconds.

!!! tip "API Access"
    You can access the raw data via these endpoints:
    - `/api/docs-data/metrics` - System metrics
    - `/api/docs-data/performance` - Performance data
    - `/api/docs-data/workflows` - Workflow statistics
    - `/api/docs-data/health` - Health checks