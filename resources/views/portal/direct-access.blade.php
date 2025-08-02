<\!DOCTYPE html>
<html>
<head>
    <title>Business Portal - Direct Access</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f5; }
        .header { background: #1e40af; color: white; padding: 1rem 2rem; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-value { font-size: 2rem; font-weight: bold; color: #1e40af; }
        .stat-label { color: #666; font-size: 0.9rem; margin-top: 0.5rem; }
        .success-banner { background: #10b981; color: white; padding: 1rem; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div class="success-banner">✅ DIRECT ACCESS - NO AUTHENTICATION REQUIRED</div>
    <div class="header">
        <h1>AskProAI Business Portal</h1>
    </div>
    <div class="container">
        <h2>Dashboard</h2>
        <div class="stats-grid" id="stats">
            <div class="stat-card">
                <div class="stat-value">Loading...</div>
                <div class="stat-label">Anrufe heute</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">Loading...</div>
                <div class="stat-label">Termine heute</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">Loading...</div>
                <div class="stat-label">Neue Kunden</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">Loading...</div>
                <div class="stat-label">Umsatz heute</div>
            </div>
        </div>
    </div>
    <script>
        // Load data without any authentication
        fetch("/api/business-direct/data")
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const stats = data.stats;
                    document.querySelectorAll(".stat-value")[0].textContent = stats.calls_today;
                    document.querySelectorAll(".stat-value")[1].textContent = stats.appointments_today;
                    document.querySelectorAll(".stat-value")[2].textContent = stats.new_customers;
                    document.querySelectorAll(".stat-value")[3].textContent = "€" + stats.revenue_today;
                }
            })
            .catch(err => {
                document.querySelectorAll(".stat-value").forEach(el => {
                    el.textContent = "Error";
                });
            });
    </script>
</body>
</html>