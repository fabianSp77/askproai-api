@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Backup System Monitor</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Backup Monitor</li>
    </ol>

    <!-- System Status Card -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header bg-{{ $status['color'] == 'green' ? 'success' : ($status['color'] == 'yellow' ? 'warning' : 'danger') }} text-white">
                    <i class="fas fa-shield-alt me-1"></i>
                    System Status: {{ ucfirst($status['level']) }}
                </div>
                <div class="card-body">
                    @if(count($status['messages']) > 0)
                        <div class="alert alert-{{ $status['color'] == 'green' ? 'success' : ($status['color'] == 'yellow' ? 'warning' : 'danger') }}">
                            <ul class="mb-0">
                                @foreach($status['messages'] as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-success"><i class="fas fa-check-circle"></i> All systems operational</p>
                    @endif
                    <small class="text-muted">Last updated: <span id="last-update">{{ $lastUpdate }}</span></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h4>{{ $metrics['totalBackups'] }}</h4>
                    Total Backups
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <small>Since {{ $metrics['oldestBackup'] ?? 'N/A' }}</small>
                    <div class="small text-white"><i class="fas fa-database"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h4>{{ $metrics['totalSize'] }}</h4>
                    Total Size
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <small>Avg: {{ $metrics['averageSize'] }}</small>
                    <div class="small text-white"><i class="fas fa-hdd"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h4>{{ $metrics['backupRate'] }}</h4>
                    Backups/Day
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <small>Average Rate</small>
                    <div class="small text-white"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h4 id="disk-usage">--</h4>
                    Disk Usage
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <small>Current Usage</small>
                    <div class="small text-white"><i class="fas fa-percentage"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Backups Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Recent Backups
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>File</th>
                            <th>Size</th>
                            <th>Age</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $backup)
                        <tr>
                            <td>
                                <span class="badge bg-{{ $backup['type'] == 'database' ? 'primary' : 'secondary' }}">
                                    {{ ucfirst($backup['type']) }}
                                </span>
                            </td>
                            <td><code>{{ $backup['file'] }}</code></td>
                            <td>{{ $backup['size'] }}</td>
                            <td>
                                <span class="text-{{ strpos($backup['age'], 'days') !== false && intval($backup['age']) > 1 ? 'warning' : 'success' }}">
                                    {{ $backup['age'] }}
                                </span>
                            </td>
                            <td>{{ $backup['time'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Issues -->
    @if(count($issues) > 0)
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-exclamation-triangle me-1"></i>
            Recent Issues & Resolutions
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Issue</th>
                            <th>Action</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($issues as $issue)
                        <tr>
                            <td><small>{{ $issue['age'] }}</small></td>
                            <td>{{ $issue['issue'] }}</td>
                            <td>{{ $issue['action'] }}</td>
                            <td>
                                <span class="badge bg-{{ $issue['result'] == 'success' ? 'success' : 'warning' }}">
                                    {{ $issue['result'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tools me-1"></i>
                    Manual Actions
                </div>
                <div class="card-body">
                    <button class="btn btn-primary" onclick="runBackup()">
                        <i class="fas fa-play"></i> Run Backup Now
                    </button>
                    <button class="btn btn-info" onclick="runValidation()">
                        <i class="fas fa-check"></i> Validate Backups
                    </button>
                    <button class="btn btn-warning" onclick="runHealing()">
                        <i class="fas fa-medkit"></i> Run Self-Healing
                    </button>
                    <button class="btn btn-success" onclick="runTests()">
                        <i class="fas fa-vial"></i> Run Tests
                    </button>
                    <button class="btn btn-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 30 seconds
setInterval(refreshDashboard, 30000);

function refreshDashboard() {
    fetch('/admin/backup-monitor/status')
        .then(response => response.json())
        .then(data => {
            // Update status
            document.getElementById('last-update').textContent = new Date().toLocaleString();
            document.getElementById('disk-usage').textContent = data.diskUsage + '%';
            
            // Update status indicator if needed
            if (data.status.level !== '{{ $status['level'] }}') {
                location.reload(); // Reload page if status changed
            }
        });
}

function runBackup() {
    if (confirm('Start backup process now?')) {
        fetch('/admin/backup-monitor/run-backup', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Backup started');
                setTimeout(refreshDashboard, 5000);
            });
    }
}

function runValidation() {
    if (confirm('Run backup validation?')) {
        fetch('/admin/backup-monitor/validate', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Validation complete');
                refreshDashboard();
            });
    }
}

function runHealing() {
    if (confirm('Run self-healing diagnostics?')) {
        fetch('/admin/backup-monitor/heal', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Self-healing complete');
                refreshDashboard();
            });
    }
}

function runTests() {
    if (confirm('Run backup system tests?')) {
        fetch('/admin/backup-monitor/test', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Tests complete');
                refreshDashboard();
            });
    }
}

// Initial load
refreshDashboard();
</script>
@endsection