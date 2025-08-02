<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit Report - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 40px;
        }
        
        h1, h2, h3 {
            color: #2c3e50;
        }
        
        h1 {
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .score-container {
            text-align: center;
            margin: 30px 0;
        }
        
        .score {
            display: inline-block;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            position: relative;
            background-color: #f0f0f0;
        }
        
        .score-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 48px;
            font-weight: bold;
        }
        
        .score-high { background-color: #27ae60; color: white; }
        .score-medium { background-color: #f39c12; color: white; }
        .score-low { background-color: #e74c3c; color: white; }
        
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .summary-item {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .summary-item h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .summary-item .value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .issues {
            margin: 30px 0;
        }
        
        .issue {
            background-color: #fff5f5;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        .issue.critical { border-left-color: #c0392b; background-color: #ffe5e5; }
        .issue.high { border-left-color: #e74c3c; background-color: #fff5f5; }
        .issue.medium { border-left-color: #f39c12; background-color: #fffaf0; }
        .issue.low { border-left-color: #95a5a6; background-color: #f8f9fa; }
        
        .issue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .severity {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
        }
        
        .severity.critical { background-color: #c0392b; }
        .severity.high { background-color: #e74c3c; }
        .severity.medium { background-color: #f39c12; }
        .severity.low { background-color: #95a5a6; }
        
        .warnings {
            background-color: #fff9e6;
            border: 1px solid #ffd966;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .warnings h3 {
            color: #f39c12;
            margin-top: 0;
        }
        
        .warnings ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .passed {
            background-color: #e8f5e9;
            border: 1px solid #81c784;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .passed h3 {
            color: #27ae60;
            margin-top: 0;
        }
        
        .recommendations {
            background-color: #e3f2fd;
            border: 1px solid #64b5f6;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .recommendations h3 {
            color: #2196f3;
            margin-top: 0;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }
        
        @media print {
            body { background-color: white; }
            .container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Security Audit Report</h1>
        
        <div class="score-container">
            <div class="score {{ $score >= 80 ? 'score-high' : ($score >= 60 ? 'score-medium' : 'score-low') }}">
                <div class="score-value">{{ $score }}</div>
            </div>
            <h2>Security Score</h2>
            <p>Generated on {{ $timestamp->format('Y-m-d H:i:s') }}</p>
        </div>
        
        <div class="summary">
            <div class="summary-item">
                <h3>Total Checks</h3>
                <div class="value">{{ count($passed) + count($issues) + count($warnings) }}</div>
            </div>
            <div class="summary-item">
                <h3>Passed</h3>
                <div class="value" style="color: #27ae60;">{{ count($passed) }}</div>
            </div>
            <div class="summary-item">
                <h3>Issues</h3>
                <div class="value" style="color: #e74c3c;">{{ count($issues) }}</div>
            </div>
            <div class="summary-item">
                <h3>Warnings</h3>
                <div class="value" style="color: #f39c12;">{{ count($warnings) }}</div>
            </div>
        </div>
        
        @if(count($issues) > 0)
        <div class="issues">
            <h2>Security Issues</h2>
            @foreach($issues as $issue)
            <div class="issue {{ $issue['severity'] }}">
                <div class="issue-header">
                    <strong>{{ $issue['message'] }}</strong>
                    <span class="severity {{ $issue['severity'] }}">{{ $issue['severity'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
        
        @if(count($warnings) > 0)
        <div class="warnings">
            <h3>Warnings</h3>
            <ul>
                @foreach($warnings as $warning)
                <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <div class="recommendations">
            <h3>Recommendations</h3>
            <ul>
                @if($score < 80)
                <li><strong>Critical:</strong> Address all critical and high severity issues immediately</li>
                @endif
                @if($score < 60)
                <li><strong>Important:</strong> Implement additional security layers and monitoring</li>
                @endif
                <li>Enable comprehensive logging for all security events</li>
                <li>Regularly update all dependencies to patch known vulnerabilities</li>
                <li>Implement automated security testing in CI/CD pipeline</li>
                <li>Conduct regular security audits (at least monthly)</li>
                <li>Train development team on secure coding practices</li>
            </ul>
        </div>
        
        @if(count($passed) > 0)
        <div class="passed">
            <h3>Passed Checks</h3>
            <ul>
                @foreach($passed as $check)
                <li>✅ {{ $check }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <div class="footer">
            <p>
                This security audit report was generated automatically by {{ config('app.name') }}<br>
                For questions or concerns, contact your security team
            </p>
        </div>
    </div>
</body>
</html>