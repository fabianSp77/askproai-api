#!/usr/bin/env node

/**
 * Simple Business Portal Performance Benchmark
 * Quick and focused performance testing
 */

const puppeteer = require('puppeteer');
const fs = require('fs');

async function runBenchmark() {
    console.log('🚀 Business Portal Performance Benchmark');
    console.log('==========================================\n');
    
    const baseUrl = process.env.BASE_URL || 'http://localhost:8000';
    const iterations = parseInt(process.env.ITERATIONS) || 5;
    
    const credentials = {
        email: process.env.TEST_EMAIL || 'demo@askproai.de',
        password: process.env.TEST_PASSWORD || 'password'
    };
    
    console.log(`Base URL: ${baseUrl}`);
    console.log(`Iterations: ${iterations}`);
    console.log(`Test User: ${credentials.email}\n`);
    
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const results = {
        login: [],
        dashboard: [],
        apis: []
    };
    
    try {
        // Test Login Performance
        console.log('🔐 Testing Login Performance...');
        
        for (let i = 0; i < iterations; i++) {
            const page = await browser.newPage();
            
            try {
                // Measure login page load
                const loginStart = Date.now();
                await page.goto(`${baseUrl}/business/login`, { 
                    waitUntil: 'networkidle2',
                    timeout: 15000 
                });
                const loginPageTime = Date.now() - loginStart;
                
                // Measure form submission
                const submitStart = Date.now();
                await page.type('#email', credentials.email);
                await page.type('#password', credentials.password);
                
                const [response] = await Promise.all([
                    page.waitForNavigation({ waitUntil: 'networkidle2' }),
                    page.click('button[type="submit"]')
                ]);
                
                const submitTime = Date.now() - submitStart;
                const totalLoginTime = Date.now() - loginStart;
                
                results.login.push({
                    iteration: i + 1,
                    loginPageTime,
                    submitTime,
                    totalTime: totalLoginTime,
                    success: response.status() < 400,
                    finalUrl: response.url()
                });
                
                console.log(`  Iteration ${i + 1}: ${totalLoginTime}ms (page: ${loginPageTime}ms, submit: ${submitTime}ms)`);
                
            } catch (error) {
                console.log(`  Iteration ${i + 1}: FAILED - ${error.message}`);
                results.login.push({
                    iteration: i + 1,
                    success: false,
                    error: error.message
                });
            }
            
            await page.close();
        }
        
        // Test Dashboard Performance
        console.log('\n📊 Testing Dashboard Performance...');
        
        for (let i = 0; i < iterations; i++) {
            const page = await browser.newPage();
            
            try {
                // Login first
                await page.goto(`${baseUrl}/business/login`, { waitUntil: 'networkidle2' });
                await page.type('#email', credentials.email);
                await page.type('#password', credentials.password);
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'networkidle2' }),
                    page.click('button[type="submit"]')
                ]);
                
                // Measure dashboard load
                const dashboardStart = Date.now();
                await page.goto(`${baseUrl}/business/dashboard`, { 
                    waitUntil: 'networkidle2',
                    timeout: 15000 
                });
                
                // Wait for React app to load
                await page.waitForSelector('#app', { timeout: 10000 });
                const dashboardTime = Date.now() - dashboardStart;
                
                // Measure Web Vitals
                const webVitals = await page.evaluate(() => {
                    return new Promise(resolve => {
                        const vitals = {};
                        
                        // Get paint timings
                        const paintEntries = performance.getEntriesByType('paint');
                        paintEntries.forEach(entry => {
                            if (entry.name === 'first-contentful-paint') {
                                vitals.FCP = Math.round(entry.startTime);
                            }
                        });
                        
                        // Approximate TTI
                        vitals.TTI = Math.round(performance.now());
                        
                        resolve(vitals);
                    });
                });
                
                results.dashboard.push({
                    iteration: i + 1,
                    dashboardTime,
                    webVitals,
                    success: true
                });
                
                console.log(`  Iteration ${i + 1}: ${dashboardTime}ms (FCP: ${webVitals.FCP}ms, TTI: ${webVitals.TTI}ms)`);
                
            } catch (error) {
                console.log(`  Iteration ${i + 1}: FAILED - ${error.message}`);
                results.dashboard.push({
                    iteration: i + 1,
                    success: false,
                    error: error.message
                });
            }
            
            await page.close();
        }
        
        // Test API Performance
        console.log('\n🌐 Testing API Performance...');
        
        const page = await browser.newPage();
        
        // Login once for API tests
        await page.goto(`${baseUrl}/business/login`, { waitUntil: 'networkidle2' });
        await page.type('#email', credentials.email);
        await page.type('#password', credentials.password);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2' }),
            page.click('button[type="submit"]')
        ]);
        
        const apiEndpoints = [
            '/business/api/dashboard/stats',
            '/business/api/dashboard/recent-calls',
            '/business/api/dashboard/upcoming-appointments'
        ];
        
        for (const endpoint of apiEndpoints) {
            const apiTimes = [];
            
            for (let i = 0; i < iterations; i++) {
                try {
                    const start = Date.now();
                    const response = await page.evaluate(async (url) => {
                        const resp = await fetch(url);
                        return {
                            status: resp.status,
                            ok: resp.ok,
                            size: resp.headers.get('content-length') || 0
                        };
                    }, `${baseUrl}${endpoint}`);
                    
                    const duration = Date.now() - start;
                    apiTimes.push(duration);
                    
                    if (!response.ok) {
                        console.log(`    ${endpoint} - Iteration ${i + 1}: ERROR ${response.status}`);
                    }
                    
                } catch (error) {
                    console.log(`    ${endpoint} - Iteration ${i + 1}: FAILED - ${error.message}`);
                }
            }
            
            if (apiTimes.length > 0) {
                const avgTime = apiTimes.reduce((a, b) => a + b, 0) / apiTimes.length;
                const maxTime = Math.max(...apiTimes);
                const minTime = Math.min(...apiTimes);
                
                console.log(`  ${endpoint}: avg ${Math.round(avgTime)}ms (min: ${minTime}ms, max: ${maxTime}ms)`);
                
                results.apis.push({
                    endpoint,
                    avgTime,
                    minTime,
                    maxTime,
                    allTimes: apiTimes
                });
            }
        }
        
        await page.close();
        
    } finally {
        await browser.close();
    }
    
    // Calculate and display statistics
    console.log('\n📈 PERFORMANCE SUMMARY');
    console.log('======================\n');
    
    // Login stats
    const successfulLogins = results.login.filter(r => r.success);
    if (successfulLogins.length > 0) {
        const loginTimes = successfulLogins.map(r => r.totalTime);
        const avgLogin = loginTimes.reduce((a, b) => a + b, 0) / loginTimes.length;
        const p95Login = loginTimes.sort((a, b) => a - b)[Math.floor(loginTimes.length * 0.95)];
        
        console.log('🔐 LOGIN PERFORMANCE:');
        console.log(`  Average: ${Math.round(avgLogin)}ms`);
        console.log(`  95th Percentile: ${Math.round(p95Login)}ms`);
        console.log(`  Success Rate: ${successfulLogins.length}/${results.login.length} (${Math.round(successfulLogins.length / results.login.length * 100)}%)`);
        
        // Performance rating
        if (avgLogin < 1000) console.log('  Rating: 🟢 EXCELLENT');
        else if (avgLogin < 2000) console.log('  Rating: 🟡 GOOD');
        else console.log('  Rating: 🔴 NEEDS IMPROVEMENT');
        
        console.log('');
    }
    
    // Dashboard stats
    const successfulDashboard = results.dashboard.filter(r => r.success);
    if (successfulDashboard.length > 0) {
        const dashboardTimes = successfulDashboard.map(r => r.dashboardTime);
        const avgDashboard = dashboardTimes.reduce((a, b) => a + b, 0) / dashboardTimes.length;
        const p95Dashboard = dashboardTimes.sort((a, b) => a - b)[Math.floor(dashboardTimes.length * 0.95)];
        
        // Average Web Vitals
        const fcpTimes = successfulDashboard.map(r => r.webVitals?.FCP).filter(Boolean);
        const avgFCP = fcpTimes.length > 0 ? fcpTimes.reduce((a, b) => a + b, 0) / fcpTimes.length : 0;
        
        console.log('📊 DASHBOARD PERFORMANCE:');
        console.log(`  Average Load Time: ${Math.round(avgDashboard)}ms`);
        console.log(`  95th Percentile: ${Math.round(p95Dashboard)}ms`);
        console.log(`  First Contentful Paint: ${Math.round(avgFCP)}ms`);
        console.log(`  Success Rate: ${successfulDashboard.length}/${results.dashboard.length} (${Math.round(successfulDashboard.length / results.dashboard.length * 100)}%)`);
        
        // Performance rating
        if (avgDashboard < 1500) console.log('  Rating: 🟢 EXCELLENT');
        else if (avgDashboard < 3000) console.log('  Rating: 🟡 GOOD');
        else console.log('  Rating: 🔴 NEEDS IMPROVEMENT');
        
        console.log('');
    }
    
    // API stats
    if (results.apis.length > 0) {
        console.log('🌐 API PERFORMANCE:');
        results.apis.forEach(api => {
            console.log(`  ${api.endpoint.split('/').pop()}: avg ${Math.round(api.avgTime)}ms (min: ${api.minTime}ms, max: ${api.maxTime}ms)`);
            
            if (api.avgTime < 200) console.log('    Rating: 🟢 EXCELLENT');
            else if (api.avgTime < 500) console.log('    Rating: 🟡 GOOD');
            else console.log('    Rating: 🔴 NEEDS IMPROVEMENT');
        });
        console.log('');
    }
    
    // Industry comparison
    console.log('🏭 INDUSTRY STANDARDS COMPARISON:');
    console.log('  Login < 1s: EXCELLENT | < 2s: GOOD | > 2s: POOR');
    console.log('  Dashboard < 1.5s: EXCELLENT | < 3s: GOOD | > 3s: POOR');
    console.log('  API < 200ms: EXCELLENT | < 500ms: GOOD | > 500ms: POOR');
    console.log('  FCP < 1.8s: EXCELLENT | < 3s: GOOD | > 3s: POOR');
    console.log('');
    
    // Recommendations
    console.log('💡 OPTIMIZATION RECOMMENDATIONS:');
    
    if (successfulLogins.length > 0) {
        const avgLogin = successfulLogins.map(r => r.totalTime).reduce((a, b) => a + b, 0) / successfulLogins.length;
        if (avgLogin > 2000) {
            console.log('  🔴 LOGIN: Optimize login form validation and server response time');
        }
    }
    
    if (successfulDashboard.length > 0) {
        const avgDashboard = successfulDashboard.map(r => r.dashboardTime).reduce((a, b) => a + b, 0) / successfulDashboard.length;
        if (avgDashboard > 3000) {
            console.log('  🔴 DASHBOARD: Implement code splitting and lazy loading');
        }
        
        const fcpTimes = successfulDashboard.map(r => r.webVitals?.FCP).filter(Boolean);
        const avgFCP = fcpTimes.length > 0 ? fcpTimes.reduce((a, b) => a + b, 0) / fcpTimes.length : 0;
        if (avgFCP > 3000) {
            console.log('  🔴 FCP: Optimize critical rendering path and reduce blocking resources');
        }
    }
    
    results.apis.forEach(api => {
        if (api.avgTime > 500) {
            console.log(`  🔴 API: Optimize ${api.endpoint} - implement caching or query optimization`);
        }
    });
    
    // Save detailed results
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const reportFile = `performance-report-${timestamp}.json`;
    
    const fullReport = {
        metadata: {
            timestamp: new Date().toISOString(),
            baseUrl,
            iterations,
            testUser: credentials.email
        },
        results,
        summary: {
            login: successfulLogins.length > 0 ? {
                avgTime: Math.round(successfulLogins.map(r => r.totalTime).reduce((a, b) => a + b, 0) / successfulLogins.length),
                p95Time: Math.round(successfulLogins.map(r => r.totalTime).sort((a, b) => a - b)[Math.floor(successfulLogins.length * 0.95)]),
                successRate: Math.round(successfulLogins.length / results.login.length * 100)
            } : null,
            dashboard: successfulDashboard.length > 0 ? {
                avgTime: Math.round(successfulDashboard.map(r => r.dashboardTime).reduce((a, b) => a + b, 0) / successfulDashboard.length),
                p95Time: Math.round(successfulDashboard.map(r => r.dashboardTime).sort((a, b) => a - b)[Math.floor(successfulDashboard.length * 0.95)]),
                avgFCP: Math.round(successfulDashboard.map(r => r.webVitals?.FCP).filter(Boolean).reduce((a, b) => a + b, 0) / successfulDashboard.filter(r => r.webVitals?.FCP).length || 0),
                successRate: Math.round(successfulDashboard.length / results.dashboard.length * 100)
            } : null,
            apis: results.apis
        }
    };
    
    fs.writeFileSync(reportFile, JSON.stringify(fullReport, null, 2));
    console.log(`\n📄 Detailed report saved to: ${reportFile}`);
    
    console.log('\n✅ Performance benchmark completed!');
}

// Run the benchmark
if (require.main === module) {
    runBenchmark().catch(error => {
        console.error('❌ Benchmark failed:', error);
        process.exit(1);
    });
}