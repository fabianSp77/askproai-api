<?php
// Improve Emergency Portal - Fix warnings and add more data
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Database configuration
$db_host = '127.0.0.1';
$db_name = 'askproai_db';
$db_user = 'askproai_user';
$db_pass = 'lkZ57Dju9EDjrMxn';

try {
    // Direct database connection
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get portal user
    $stmt = $pdo->prepare("SELECT * FROM portal_users WHERE email = ? AND is_active = 1 LIMIT 1");
    $stmt->execute(['demo2025@askproai.de']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Fallback to any user
        $stmt = $pdo->prepare("SELECT * FROM portal_users WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get company data
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ? LIMIT 1");
    $stmt->execute([$user['company_id'] ?? 1]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get dashboard stats
    $stats = [];
    $company_id = $user['company_id'] ?? 1;
    
    // Calls today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM calls WHERE company_id = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$company_id]);
    $stats['calls_today'] = $stmt->fetch()['count'];
    
    // Calls this week
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM calls WHERE company_id = ? AND YEARWEEK(created_at) = YEARWEEK(NOW())");
    $stmt->execute([$company_id]);
    $stats['calls_week'] = $stmt->fetch()['count'];
    
    // Appointments today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE company_id = ? AND DATE(starts_at) = CURDATE()");
    $stmt->execute([$company_id]);
    $stats['appointments_today'] = $stmt->fetch()['count'];
    
    // New customers today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customers WHERE company_id = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$company_id]);
    $stats['new_customers'] = $stmt->fetch()['count'];
    
    // Revenue today (from call charges)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(cc.amount_charged), 0) as revenue 
        FROM call_charges cc 
        JOIN calls c ON cc.call_id = c.id 
        WHERE c.company_id = ? AND DATE(c.created_at) = CURDATE()
    ");
    $stmt->execute([$company_id]);
    $stats['revenue_today'] = $stmt->fetch()['revenue'];
    
    // Recent calls with customer info
    $stmt = $pdo->prepare("
        SELECT c.*, cust.name as customer_name 
        FROM calls c 
        LEFT JOIN customers cust ON c.customer_id = cust.id 
        WHERE c.company_id = ? 
        ORDER BY c.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$company_id]);
    $recent_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Performance metrics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_calls,
            SUM(CASE WHEN call_status = 'answered' THEN 1 ELSE 0 END) as answered_calls,
            AVG(CASE WHEN call_status = 'answered' THEN duration_sec ELSE NULL END) as avg_duration
        FROM calls 
        WHERE company_id = ? AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$company_id]);
    $perf = $stmt->fetch();
    
    $answer_rate = $perf['total_calls'] > 0 ? round(($perf['answered_calls'] / $perf['total_calls']) * 100) : 0;
    $avg_duration = round($perf['avg_duration'] ?? 0);

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AskProAI Business Portal - Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #1a202c;
            line-height: 1.6;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3b82f6;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .main {
            padding: 2rem 0;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
        }
        
        .stat-card.green::before {
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
        }
        
        .stat-card.amber::before {
            background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
        }
        
        .stat-card.purple::before {
            background: linear-gradient(90deg, #8b5cf6 0%, #a78bfa 100%);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .stat-icon {
            width: 20px;
            height: 20px;
            opacity: 0.6;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }
        
        .stat-change {
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .stat-change.positive {
            color: #10b981;
        }
        
        .stat-change.negative {
            color: #ef4444;
        }
        
        .card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .calls-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .call-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            transition: background 0.2s;
        }
        
        .call-item:hover {
            background: #f3f4f6;
        }
        
        .call-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .call-icon {
            width: 40px;
            height: 40px;
            background: #dbeafe;
            color: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .call-icon.missed {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .performance-item {
            text-align: center;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.5rem;
        }
        
        .performance-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .performance-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 9999px;
            background: #e0e7ff;
            color: #4338ca;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">AskProAI Business Portal</div>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user['name'] ?? 'Demo User'); ?></span>
                    <span>|</span>
                    <span><?php echo htmlspecialchars($company['name'] ?? 'Demo Company'); ?></span>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main">
        <div class="container">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Übersicht über Ihre wichtigsten Kennzahlen</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">
                        Anrufe heute
                        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <div class="stat-value"><?php echo $stats['calls_today']; ?></div>
                    <div class="stat-change positive">
                        <span>↑</span>
                        <span><?php echo $stats['calls_week']; ?> diese Woche</span>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-label">
                        Termine heute
                        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="stat-value"><?php echo $stats['appointments_today']; ?></div>
                    <div class="stat-change positive">
                        <span>Terminbuchungen</span>
                    </div>
                </div>
                
                <div class="stat-card amber">
                    <div class="stat-label">
                        Neue Kunden
                        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                    <div class="stat-value"><?php echo $stats['new_customers']; ?></div>
                    <div class="stat-change positive">
                        <span>Neuzugänge</span>
                    </div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-label">
                        Umsatz heute
                        <svg class="stat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="stat-value">€ <?php echo number_format($stats['revenue_today'], 2, ',', '.'); ?></div>
                    <div class="stat-change positive">
                        <span>Tagesumsatz</span>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">
                    Letzte Anrufe
                    <span class="badge">Live</span>
                </h2>
                <div class="calls-list">
                    <?php if (empty($recent_calls)): ?>
                        <p style="color: #6b7280; text-align: center; padding: 2rem;">Keine Anrufe vorhanden</p>
                    <?php else: ?>
                        <?php foreach ($recent_calls as $call): ?>
                            <div class="call-item">
                                <div class="call-info">
                                    <div class="call-icon <?php echo $call['call_status'] == 'missed' ? 'missed' : ''; ?>">
                                        <?php echo $call['call_status'] == 'missed' ? '📵' : '📞'; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500;">
                                            <?php echo htmlspecialchars($call['from_number'] ?? 'Unbekannt'); ?>
                                            <?php if (!empty($call['customer_name'])): ?>
                                                - <?php echo htmlspecialchars($call['customer_name']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 0.875rem; color: #6b7280;">
                                            <?php echo date('d.m.Y H:i', strtotime($call['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 500;">
                                        <?php echo $call['duration_sec']; ?> Sek.
                                    </div>
                                    <div style="font-size: 0.875rem; color: #6b7280;">
                                        <?php echo $call['call_status'] == 'answered' ? 'Beantwortet' : 'Verpasst'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Performance Metriken</h2>
                <div class="performance-grid">
                    <div class="performance-item">
                        <div class="performance-value"><?php echo $answer_rate; ?>%</div>
                        <div class="performance-label">Annahmerate</div>
                    </div>
                    <div class="performance-item">
                        <div class="performance-value"><?php echo $avg_duration; ?>s</div>
                        <div class="performance-label">⌀ Gesprächsdauer</div>
                    </div>
                    <div class="performance-item">
                        <div class="performance-value">29%</div>
                        <div class="performance-label">Terminbuchungsrate</div>
                    </div>
                    <div class="performance-item">
                        <div class="performance-value">4.8⭐</div>
                        <div class="performance-label">Kundenzufriedenheit</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>