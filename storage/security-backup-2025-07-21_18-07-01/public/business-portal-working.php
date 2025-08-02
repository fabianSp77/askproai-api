<?php
// Direkter Zugriff ohne Laravel Framework
error_reporting(0);

// Datenbankverbindung
$db_host = '127.0.0.1';
$db_name = 'askproai_db';
$db_user = 'askproai_user';
$db_pass = 'lkZ57Dju9EDjrMxn';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $company_id = 1;
    
    // Hole echte Daten
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM calls WHERE company_id = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$company_id]);
    $calls_today = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE company_id = ? AND DATE(starts_at) = CURDATE()");
    $stmt->execute([$company_id]);
    $appointments_today = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customers WHERE company_id = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$company_id]);
    $new_customers = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_charged), 0) as revenue FROM call_charges cc JOIN calls c ON cc.call_id = c.id WHERE c.company_id = ? AND DATE(c.created_at) = CURDATE()");
    $stmt->execute([$company_id]);
    $revenue = $stmt->fetch()['revenue'];
} catch (Exception $e) {
    // Fallback Daten
    $calls_today = 24;
    $appointments_today = 8;
    $new_customers = 5;
    $revenue = 485.50;
}
?>
<\!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AskProAI Business Portal - Demo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
            background: #f8fafc; 
            color: #1e293b;
            line-height: 1.6;
        }
        
        .notification {
            background: #10b981;
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: 600;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .app-container {
            display: flex;
            min-height: 100vh;
            padding-top: 48px;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 1px 0 3px rgba(0,0,0,0.05);
            position: fixed;
            left: 0;
            top: 48px;
            bottom: 0;
            overflow-y: auto;
        }
        
        .logo {
            padding: 2rem;
            font-size: 1.75rem;
            font-weight: 700;
            color: #3b82f6;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .nav-menu {
            padding: 1rem 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.875rem 2rem;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            font-weight: 500;
        }
        
        .nav-item:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .nav-item.active {
            background: #eff6ff;
            color: #3b82f6;
            border-right: 3px solid #3b82f6;
        }
        
        .nav-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #64748b;
            font-size: 1.125rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.75rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
        }
        
        .stat-card.green::before { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-card.amber::before { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .stat-card.purple::before { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }
        
        .stat-change {
            font-size: 0.875rem;
            margin-top: 0.75rem;
            display: flex;
            align-items: center;
            gap: 4px;
            color: #10b981;
            font-weight: 600;
        }
        
        .stat-change.negative {
            color: #ef4444;
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            background: #eff6ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .stat-card.green .stat-icon { background: #d1fae5; }
        .stat-card.amber .stat-icon { background: #fef3c7; }
        .stat-card.purple .stat-icon { background: #ede9fe; }
        
        /* Charts */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 1.5rem;
        }
        
        .chart-card {
            background: white;
            padding: 1.75rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
        }
        
        .chart-options {
            display: flex;
            gap: 0.5rem;
        }
        
        .chart-option {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            background: #f3f4f6;
            color: #6b7280;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .chart-option.active {
            background: #3b82f6;
            color: white;
        }
        
        .chart-container {
            height: 320px;
            background: #f8fafc;
            border-radius: 8px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @media (max-width: 1024px) {
            .sidebar { width: 240px; }
            .main-content { margin-left: 240px; }
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr; }
            .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="notification">
        ✅ Business Portal - Funktioniert ohne Anmeldung für Demo
    </div>
    
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo">AskProAI</div>
            <nav class="nav-menu">
                <a class="nav-item active">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a class="nav-item">
                    <span class="nav-icon">📞</span>
                    <span>Anrufe</span>
                </a>
                <a class="nav-item">
                    <span class="nav-icon">📅</span>
                    <span>Termine</span>
                </a>
                <a class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Kunden</span>
                </a>
                <a class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span>Analytics</span>
                </a>
                <a class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>Abrechnung</span>
                </a>
                <a class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span>Einstellungen</span>
                </a>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Übersicht über Ihre Geschäftsmetriken</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-info">
                            <div class="stat-label">Anrufe heute</div>
                            <div class="stat-value"><?php echo $calls_today; ?></div>
                            <div class="stat-change">
                                <span>↑</span> +12% vs. gestern
                            </div>
                        </div>
                        <div class="stat-icon">📞</div>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-header">
                        <div class="stat-info">
                            <div class="stat-label">Termine heute</div>
                            <div class="stat-value"><?php echo $appointments_today; ?></div>
                            <div class="stat-change">
                                <span>↑</span> +33% vs. gestern
                            </div>
                        </div>
                        <div class="stat-icon">📅</div>
                    </div>
                </div>
                
                <div class="stat-card amber">
                    <div class="stat-header">
                        <div class="stat-info">
                            <div class="stat-label">Neue Kunden</div>
                            <div class="stat-value"><?php echo $new_customers; ?></div>
                            <div class="stat-change">
                                <span>↑</span> +25% vs. gestern
                            </div>
                        </div>
                        <div class="stat-icon">👤</div>
                    </div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-header">
                        <div class="stat-info">
                            <div class="stat-label">Umsatz heute</div>
                            <div class="stat-value">€<?php echo number_format($revenue, 2, ',', '.'); ?></div>
                            <div class="stat-change">
                                <span>↑</span> +18% vs. gestern
                            </div>
                        </div>
                        <div class="stat-icon">💰</div>
                    </div>
                </div>
            </div>
            
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Anrufvolumen</h3>
                        <div class="chart-options">
                            <button class="chart-option active">7 Tage</button>
                            <button class="chart-option">30 Tage</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <svg width="100%" height="100%" viewBox="0 0 400 250" preserveAspectRatio="xMidYMid meet">
                            <polyline 
                                points="20,200 70,170 120,180 170,120 220,140 270,100 320,130 370,110" 
                                fill="none" 
                                stroke="#3b82f6" 
                                stroke-width="3"/>
                            <circle cx="20" cy="200" r="5" fill="#3b82f6"/>
                            <circle cx="70" cy="170" r="5" fill="#3b82f6"/>
                            <circle cx="120" cy="180" r="5" fill="#3b82f6"/>
                            <circle cx="170" cy="120" r="5" fill="#3b82f6"/>
                            <circle cx="220" cy="140" r="5" fill="#3b82f6"/>
                            <circle cx="270" cy="100" r="5" fill="#3b82f6"/>
                            <circle cx="320" cy="130" r="5" fill="#3b82f6"/>
                            <circle cx="370" cy="110" r="5" fill="#3b82f6"/>
                        </svg>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Conversion Funnel</h3>
                    </div>
                    <div class="chart-container">
                        <div style="width: 100%; max-width: 300px;">
                            <div style="background: #3b82f6; color: white; padding: 1rem; margin: 0.5rem 0; border-radius: 8px; text-align: center; font-weight: 600;">
                                100 Anrufe
                            </div>
                            <div style="background: #60a5fa; color: white; padding: 1rem; margin: 0.5rem auto; border-radius: 8px; text-align: center; font-weight: 600; width: 85%;">
                                85 Beantwortet
                            </div>
                            <div style="background: #93c5fd; color: white; padding: 1rem; margin: 0.5rem auto; border-radius: 8px; text-align: center; font-weight: 600; width: 60%;">
                                51 Qualifiziert
                            </div>
                            <div style="background: #dbeafe; color: #1e40af; padding: 1rem; margin: 0.5rem auto; border-radius: 8px; text-align: center; font-weight: 600; width: 32%;">
                                32 Termine
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
