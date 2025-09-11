@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <div class="alert alert-info">Anrufe heute: <span id="todayCalls">-</span> | Gesamt: <span id="totalCalls">-</span></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Letzte Anrufe</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Zeit</th><th>Nummer</th><th>Status</th></tr></thead>
                            <tbody id="callsList"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Anrufstatistik</div>
                <div class="card-body">
                    <canvas id="callsChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiNGEwMzg1ZWM2ZTM5NTY3MDgxYWI4NWRkNGJjYjQ1ZjkyOTRjYjllOTY3Zjg5MmZjZWNjZmMwNzNiMjE1MjNhMmM0ZTQ1ZGMxYTNjZDIzYjMiLCJpYXQiOjE3NDIwMzg2NDIuNjI5ODQsIm5iZiI6MTc0MjAzODY0Mi42Mjk4NDMsImV4cCI6MTc3MzU3NDY0Mi42MjYyNjMsInN1YiI6IjEiLCJzY29wZXMiOltdfQ.oszaa-ycNtw4C7kwE6XG114Op4HY9tImfIhZws66hN5em4vsrjC37zdA3y8s3y7uz5NLNAbljVXd4YB3fDdRi_nPL4NujvnFCTyPoRN_-oXt2Ii-Z0VFVClmXvnfqb3HCuUMZSxg5l7I1yCKBszZa3JUhK9q8hYHi6smxPAVyCYXmmjrRmMHUTNbqrCGfAzsKSyNZjG_wHD7IZoXsPNst0d9Re3vDCV7Up0JyExJ7Lw-Pvl4v6Kpozv8nFqDAMkcMMTjRh_V1QQiZrkhMA4P9ZDRhwjvObJ9Wd78_AMLbRypI6wusXLAG3p9ZPA4GPixwLWViWt6lASyrcMLGBDH4CCdHuDo2vv7NuDkXRbn4nIi5QHr33tyLLmx41UB80yYytFeOa_KgMlsKCDavmyG8byS7Y035foQk-6y1HQSZsUV084o-ItyVJX3_tXVZgEWx2eoNQt9PQyKA6AIMriAnZhSzXmz0SKiMxfQLm59AH30dr-pZNlc3nbcy2hb-aJsHMMdXEV0anjAdTfukUw5Z3YM4RCZOx1oAhIU36D2pDlro8ju3RA3Bq93d6lu9zuVBvHL1t06NUy_DI-5n3k9VrIUSSNC_X2k8bXOO8SqUQiSbVd2aNpzSu7XPeVrnL-G_OawaUV5SyXhbLK51Q8VBcCw8GfhYWXzHmlWZVC1f5I';
        
        async function fetchData() {
            try {
                const response = await fetch('/api/dashboard', {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
                const data = await response.json();
                updateDashboard(data);
            } catch (error) {
                console.error('Fehler:', error);
            }
        }

        function updateDashboard(data) {
            document.getElementById('todayCalls').textContent = data.stats?.today_calls || 0;
            document.getElementById('totalCalls').textContent = data.stats?.total_calls || 0;
            
            const tbody = document.getElementById('callsList');
            tbody.innerHTML = '';
            
            if (data.recent_calls?.length) {
                data.recent_calls.slice(0, 5).forEach(call => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${new Date(call.start_time).toLocaleTimeString('de-DE', {hour: '2-digit', minute:'2-digit'})}</td>
                        <td>${call.caller_number || 'Unbekannt'}</td>
                        <td>${call.end_time ? '<span class="badge bg-success">Abgeschlossen</span>' : '<span class="badge bg-warning">Offen</span>'}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center">Keine Anrufe</td></tr>';
            }
            
            createChart();
        }

        function createChart() {
            const ctx = document.getElementById('callsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                    datasets: [{
                        label: 'Anrufe',
                        data: [12, 19, 13, 15, 20, 8, 5],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)'
                    }]
                },
                options: {responsive: true}
            });
        }

        document.addEventListener('DOMContentLoaded', fetchData);
    </script>
@endsection
