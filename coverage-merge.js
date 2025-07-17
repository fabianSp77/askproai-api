#!/usr/bin/env node

/**
 * Script to merge PHP and JavaScript coverage reports
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Paths
const phpCoverageFile = path.join(__dirname, 'coverage/php/coverage.txt');
const jsCoverageFile = path.join(__dirname, 'coverage/vitest/coverage-summary.json');
const outputDir = path.join(__dirname, 'coverage');
const outputFile = path.join(outputDir, 'combined-coverage.json');
const htmlOutputFile = path.join(outputDir, 'index.html');

// Ensure output directory exists
if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
}

// Parse PHP coverage
function parsePHPCoverage() {
    if (!fs.existsSync(phpCoverageFile)) {
        console.warn('PHP coverage file not found');
        return null;
    }
    
    const content = fs.readFileSync(phpCoverageFile, 'utf8');
    const lines = content.split('\n');
    
    let coverage = {
        lines: { total: 0, covered: 0, pct: 0 },
        functions: { total: 0, covered: 0, pct: 0 },
        branches: { total: 0, covered: 0, pct: 0 },
        statements: { total: 0, covered: 0, pct: 0 }
    };
    
    // Parse PHP coverage summary (format may vary)
    lines.forEach(line => {
        if (line.includes('Lines:')) {
            const match = line.match(/Lines:\s+(\d+\.\d+)%\s+\((\d+)\/(\d+)\)/);
            if (match) {
                coverage.lines.pct = parseFloat(match[1]);
                coverage.lines.covered = parseInt(match[2]);
                coverage.lines.total = parseInt(match[3]);
            }
        }
        if (line.includes('Functions:')) {
            const match = line.match(/Functions:\s+(\d+\.\d+)%\s+\((\d+)\/(\d+)\)/);
            if (match) {
                coverage.functions.pct = parseFloat(match[1]);
                coverage.functions.covered = parseInt(match[2]);
                coverage.functions.total = parseInt(match[3]);
            }
        }
    });
    
    // PHP doesn't typically report branches, so we'll use lines as approximation
    coverage.branches = { ...coverage.lines };
    coverage.statements = { ...coverage.lines };
    
    return coverage;
}

// Parse JavaScript coverage
function parseJSCoverage() {
    if (!fs.existsSync(jsCoverageFile)) {
        console.warn('JavaScript coverage file not found');
        return null;
    }
    
    const content = fs.readFileSync(jsCoverageFile, 'utf8');
    const data = JSON.parse(content);
    
    return data.total;
}

// Merge coverage data
function mergeCoverage(phpCoverage, jsCoverage) {
    const combined = {
        php: phpCoverage || createEmptyCoverage(),
        javascript: jsCoverage || createEmptyCoverage(),
        total: createEmptyCoverage(),
        timestamp: new Date().toISOString(),
        thresholds: {
            lines: 80,
            functions: 80,
            branches: 80,
            statements: 80
        }
    };
    
    // Calculate combined totals
    ['lines', 'functions', 'branches', 'statements'].forEach(metric => {
        const phpData = combined.php[metric] || createEmptyMetric();
        const jsData = combined.javascript[metric] || createEmptyMetric();
        
        combined.total[metric] = {
            total: phpData.total + jsData.total,
            covered: phpData.covered + jsData.covered,
            pct: 0
        };
        
        if (combined.total[metric].total > 0) {
            combined.total[metric].pct = 
                (combined.total[metric].covered / combined.total[metric].total) * 100;
        }
    });
    
    return combined;
}

function createEmptyCoverage() {
    return {
        lines: createEmptyMetric(),
        functions: createEmptyMetric(),
        branches: createEmptyMetric(),
        statements: createEmptyMetric()
    };
}

function createEmptyMetric() {
    return { total: 0, covered: 0, pct: 0 };
}

// Generate HTML report
function generateHTMLReport(coverage) {
    const html = `
<!DOCTYPE html>
<html>
<head>
    <title>AskProAI - Combined Coverage Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1, h2 {
            margin-top: 0;
            color: #333;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .metric {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .metric h3 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 16px;
        }
        .percentage {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .good { color: #28a745; }
        .warning { color: #ffc107; }
        .danger { color: #dc3545; }
        .details {
            font-size: 14px;
            color: #6c757d;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        .section {
            margin: 40px 0;
            padding: 20px 0;
            border-top: 1px solid #e9ecef;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge.pass { background: #d4edda; color: #155724; }
        .badge.fail { background: #f8d7da; color: #721c24; }
        .timestamp {
            color: #6c757d;
            font-size: 14px;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .links a {
            display: inline-block;
            margin-right: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Combined Coverage Report</h1>
        
        <div class="section">
            <h2>Overall Coverage ${getCoverageBadge(coverage.total.lines.pct)}</h2>
            <div class="metrics">
                ${renderMetric('Lines', coverage.total.lines, coverage.thresholds.lines)}
                ${renderMetric('Functions', coverage.total.functions, coverage.thresholds.functions)}
                ${renderMetric('Branches', coverage.total.branches, coverage.thresholds.branches)}
                ${renderMetric('Statements', coverage.total.statements, coverage.thresholds.statements)}
            </div>
        </div>
        
        <div class="section">
            <h2>Coverage by Language</h2>
            <table>
                <thead>
                    <tr>
                        <th>Language</th>
                        <th>Lines</th>
                        <th>Functions</th>
                        <th>Branches</th>
                        <th>Statements</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>PHP</strong></td>
                        <td>${formatPercentage(coverage.php.lines.pct)}%</td>
                        <td>${formatPercentage(coverage.php.functions.pct)}%</td>
                        <td>${formatPercentage(coverage.php.branches.pct)}%</td>
                        <td>${formatPercentage(coverage.php.statements.pct)}%</td>
                    </tr>
                    <tr>
                        <td><strong>JavaScript</strong></td>
                        <td>${formatPercentage(coverage.javascript.lines.pct)}%</td>
                        <td>${formatPercentage(coverage.javascript.functions.pct)}%</td>
                        <td>${formatPercentage(coverage.javascript.branches.pct)}%</td>
                        <td>${formatPercentage(coverage.javascript.statements.pct)}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="links">
            <h3>Detailed Reports</h3>
            <a href="php/index.html">PHP Coverage Report</a>
            <a href="vitest/index.html">JavaScript Coverage Report</a>
            <a href="combined-coverage.json">Combined Coverage JSON</a>
        </div>
        
        <div class="timestamp">
            Generated at: ${new Date(coverage.timestamp).toLocaleString()}
        </div>
    </div>
</body>
</html>
    `;
    
    function renderMetric(name, metric, threshold) {
        const colorClass = getColorClass(metric.pct, threshold);
        return `
            <div class="metric">
                <h3>${name}</h3>
                <div class="percentage ${colorClass}">${formatPercentage(metric.pct)}%</div>
                <div class="progress-bar">
                    <div class="progress-fill ${colorClass}" style="width: ${metric.pct}%"></div>
                </div>
                <div class="details">${metric.covered} / ${metric.total}</div>
            </div>
        `;
    }
    
    function getColorClass(percentage, threshold) {
        if (percentage >= threshold) return 'good';
        if (percentage >= threshold * 0.8) return 'warning';
        return 'danger';
    }
    
    function formatPercentage(pct) {
        return pct.toFixed(2);
    }
    
    function getCoverageBadge(percentage) {
        const passed = percentage >= 80;
        return `<span class="badge ${passed ? 'pass' : 'fail'}">${passed ? 'PASS' : 'FAIL'}</span>`;
    }
    
    fs.writeFileSync(htmlOutputFile, html);
}

// Main execution
console.log('🔄 Merging coverage reports...\n');

const phpCoverage = parsePHPCoverage();
const jsCoverage = parseJSCoverage();

if (!phpCoverage && !jsCoverage) {
    console.error('❌ No coverage reports found. Run tests with coverage first.');
    process.exit(1);
}

const combinedCoverage = mergeCoverage(phpCoverage, jsCoverage);

// Save combined coverage
fs.writeFileSync(outputFile, JSON.stringify(combinedCoverage, null, 2));
console.log(`✅ Combined coverage saved to: ${outputFile}`);

// Generate HTML report
generateHTMLReport(combinedCoverage);
console.log(`✅ HTML report saved to: ${htmlOutputFile}`);

// Display summary
console.log('\n📊 Coverage Summary:');
console.log('==================');
console.log(`Lines:      ${combinedCoverage.total.lines.pct.toFixed(2)}%`);
console.log(`Functions:  ${combinedCoverage.total.functions.pct.toFixed(2)}%`);
console.log(`Branches:   ${combinedCoverage.total.branches.pct.toFixed(2)}%`);
console.log(`Statements: ${combinedCoverage.total.statements.pct.toFixed(2)}%`);

// Check thresholds
const failed = ['lines', 'functions', 'branches', 'statements'].some(metric => 
    combinedCoverage.total[metric].pct < combinedCoverage.thresholds[metric]
);

if (failed) {
    console.log('\n❌ Coverage thresholds not met!');
    process.exit(1);
} else {
    console.log('\n✅ All coverage thresholds met!');
}