<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AskProAI - Berichte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/custom.css">
</head>
<body>
    @include('layouts.nav')
    
    <div class="container mt-4">
        <h1>Anruf-Statistiken</h1>
        
        <div class="card mt-4">
            <div class="card-header">Letzte 7 Tage</div>
            <div class="card-body">
                <canvas id="dailyStats" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        new Chart(document.getElementById('dailyStats'), {
            type: 'bar',
            data: {
                labels: [@foreach($dailyStats as $stat)'{{ $stat->date }}',@endforeach],
                datasets: [{
                    label: 'Anrufe Gesamt',
                    data: [@foreach($dailyStats as $stat){{ $stat->total }},@endforeach],
                    backgroundColor: '#4e73df'
                }, {
                    label: 'Erfolgreiche Anrufe',
                    data: [@foreach($dailyStats as $stat){{ $stat->successful }},@endforeach],
                    backgroundColor: '#1cc88a'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
