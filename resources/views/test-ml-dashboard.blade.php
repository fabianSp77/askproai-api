<!DOCTYPE html>
<html>
<head>
    <title>ML Dashboard Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { 
            background: #f0f0f0; 
            padding: 20px; 
            border-radius: 8px;
            text-align: center;
        }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .stat-label { color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <h1>ML Dashboard (Test Version)</h1>
    
    <div class="stats">
        <div class="stat-box">
            <div class="stat-value">{{ number_format($stats['total_calls']) }}</div>
            <div class="stat-label">Total Calls</div>
        </div>
        
        <div class="stat-box">
            <div class="stat-value">{{ number_format($stats['with_transcript']) }}</div>
            <div class="stat-label">With Transcript</div>
        </div>
        
        <div class="stat-box">
            <div class="stat-value">{{ number_format($stats['analyzed']) }}</div>
            <div class="stat-label">Analyzed</div>
        </div>
    </div>
    
    <p>This is a simple test page outside of Filament to verify the data is accessible.</p>
</body>
</html>