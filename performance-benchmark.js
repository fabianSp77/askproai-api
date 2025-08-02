/**
 * Business Portal Performance Benchmark Tool
 * Measures comprehensive performance metrics for login and dashboard
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

class PerformanceBenchmark {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || 'https://api.askproai.de';
        this.iterations = options.iterations || 10;
        this.credentials = options.credentials || {
            email: 'demo@askproai.de',
            password: 'password'
        };
        this.results = {
            login: [],
            dashboard: [],
            api: [],
            resources: []
        };
        this.browser = null;
        this.page = null;
    }

    async initialize() {
        this.browser = await puppeteer.launch({
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-web-security',
                '--disable-features=site-per-process'
            ]
        });
    }

    async createNewPage() {
        if (this.page) {
            await this.page.close();
        }
        
        this.page = await this.browser.newPage();
        
        // Enable request/response monitoring
        await this.page.setCacheEnabled(false);
        await this.page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        // Set viewport for consistent measurements
        await this.page.setViewport({ width: 1920, height: 1080 });
        
        return this.page;
    }

    async measurePageLoad(url, label) {
        const page = await this.createNewPage();
        const startTime = Date.now();
        
        // Track network requests
        const requests = [];
        const responses = [];
        
        page.on('request', request => {
            requests.push({
                url: request.url(),
                method: request.method(),
                resourceType: request.resourceType(),
                timestamp: Date.now()
            });
        });
        
        page.on('response', response => {
            responses.push({
                url: response.url(),
                status: response.status(),
                size: response.headers()['content-length'] || 0,
                timestamp: Date.now()
            });
        });

        // Navigate and measure performance
        try {
            const response = await page.goto(url, { 
                waitUntil: 'networkidle0',
                timeout: 30000 
            });
            
            const loadTime = Date.now() - startTime;
            
            // Get Web Vitals
            const webVitals = await page.evaluate(() => {
                return new Promise((resolve) => {
                    const vitals = {};
                    
                    // First Contentful Paint
                    new PerformanceObserver((list) => {
                        for (const entry of list.getEntries()) {
                            if (entry.name === 'first-contentful-paint') {
                                vitals.FCP = entry.startTime;
                            }
                            if (entry.name === 'largest-contentful-paint') {
                                vitals.LCP = entry.startTime;
                            }
                        }
                    }).observe({ entryTypes: ['paint', 'largest-contentful-paint'] });
                    
                    // Time to Interactive (approximation)
                    setTimeout(() => {
                        vitals.TTI = performance.now();
                        
                        // Layout shift
                        vitals.CLS = 0; // Would need layout-shift observer
                        
                        resolve(vitals);
                    }, 100);
                });
            });
            
            // Get resource loading times
            const resourceTiming = await page.evaluate(() => {
                const resources = [];
                const entries = performance.getEntriesByType('resource');
                
                for (const entry of entries) {
                    resources.push({
                        name: entry.name,
                        type: entry.initiatorType,
                        size: entry.transferSize || 0,
                        duration: entry.responseEnd - entry.requestStart,
                        loadTime: entry.responseEnd - entry.fetchStart
                    });
                }
                
                return resources;
            });

            return {
                label,
                url,
                success: true,
                pageLoadTime: loadTime,
                responseStatus: response.status(),
                webVitals,
                resourceTiming,
                requestCount: requests.length,
                totalTransferSize: responses.reduce((sum, r) => sum + parseInt(r.size || 0), 0),
                timestamp: new Date().toISOString()
            };
            
        } catch (error) {
            return {
                label,
                url,
                success: false,
                error: error.message,
                timestamp: new Date().toISOString()
            };
        }
    }

    async measureLoginFlow() {
        console.log('🔐 Benchmarking login flow...');
        
        for (let i = 0; i < this.iterations; i++) {
            console.log(`  Iteration ${i + 1}/${this.iterations}`);
            
            const page = await this.createNewPage();
            const loginUrl = `${this.baseUrl}/business/login`;
            
            try {
                // 1. Measure login page load
                const loginPageStart = Date.now();
                await page.goto(loginUrl, { waitUntil: 'networkidle0' });
                const loginPageTime = Date.now() - loginPageStart;
                
                // 2. Measure form submission
                const formSubmitStart = Date.now();
                
                await page.type('#email', this.credentials.email);
                await page.type('#password', this.credentials.password);
                
                // Submit form and wait for navigation
                const [response] = await Promise.all([
                    page.waitForNavigation({ waitUntil: 'networkidle0' }),
                    page.click('button[type="submit"]')
                ]);
                
                const formSubmitTime = Date.now() - formSubmitStart;
                
                this.results.login.push({
                    iteration: i + 1,
                    loginPageLoadTime: loginPageTime,
                    formSubmissionTime: formSubmitTime,
                    totalLoginTime: loginPageTime + formSubmitTime,
                    success: response.status() < 400,
                    redirectUrl: response.url(),
                    timestamp: new Date().toISOString()
                });
                
            } catch (error) {
                this.results.login.push({
                    iteration: i + 1,
                    success: false,
                    error: error.message,
                    timestamp: new Date().toISOString()
                });
            }
        }
    }

    async measureDashboardLoad() {
        console.log('📊 Benchmarking dashboard load...');
        
        for (let i = 0; i < this.iterations; i++) {
            console.log(`  Iteration ${i + 1}/${this.iterations}`);
            
            const page = await this.createNewPage();
            
            try {
                // Login first
                await page.goto(`${this.baseUrl}/business/login`, { waitUntil: 'networkidle0' });
                await page.type('#email', this.credentials.email);
                await page.type('#password', this.credentials.password);
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'networkidle0' }),
                    page.click('button[type="submit"]')
                ]);
                
                // Now measure dashboard load
                const dashboardStart = Date.now();
                await page.goto(`${this.baseUrl}/business/dashboard`, { waitUntil: 'networkidle0' });
                const dashboardTime = Date.now() - dashboardStart;
                
                // Wait for React to hydrate
                await page.waitForSelector('#app', { timeout: 10000 });
                const hydrationTime = Date.now() - dashboardStart;
                
                // Measure API calls
                const apiStart = Date.now();
                const apiResponses = await this.measureDashboardApis(page);
                const apiTime = Date.now() - apiStart;
                
                this.results.dashboard.push({
                    iteration: i + 1,
                    dashboardLoadTime: dashboardTime,
                    hydrationTime: hydrationTime,
                    apiResponseTime: apiTime,
                    totalTime: hydrationTime + apiTime,
                    apiResponses,
                    timestamp: new Date().toISOString()
                });
                
            } catch (error) {
                this.results.dashboard.push({
                    iteration: i + 1,
                    success: false,
                    error: error.message,
                    timestamp: new Date().toISOString()
                });
            }
        }
    }

    async measureDashboardApis(page) {
        const apiUrls = [
            '/business/api/dashboard/stats',
            '/business/api/dashboard/recent-calls',
            '/business/api/dashboard/upcoming-appointments'
        ];
        
        const responses = [];
        
        for (const url of apiUrls) {
            try {
                const start = Date.now();
                const response = await page.evaluate(async (apiUrl) => {
                    const resp = await fetch(apiUrl);
                    return {
                        status: resp.status,
                        size: resp.headers.get('content-length') || 0,
                        data: await resp.json()
                    };
                }, url);
                
                const duration = Date.now() - start;
                
                responses.push({
                    url,
                    duration,
                    status: response.status,
                    size: response.size,
                    success: response.status < 400
                });
                
            } catch (error) {
                responses.push({
                    url,
                    success: false,
                    error: error.message
                });
            }
        }
        
        return responses;
    }

    async measureResourceLoading() {
        console.log('📦 Benchmarking resource loading...');
        
        const page = await this.createNewPage();
        
        // Track all resource loads
        const resources = [];
        
        page.on('response', response => {
            const url = response.url();
            const contentType = response.headers()['content-type'] || '';
            
            let type = 'other';
            if (contentType.includes('javascript')) type = 'js';
            else if (contentType.includes('css')) type = 'css';
            else if (contentType.includes('image')) type = 'image';
            else if (contentType.includes('font')) type = 'font';
            
            resources.push({
                url,
                type,
                status: response.status(),
                size: parseInt(response.headers()['content-length'] || 0),
                timing: response.request().timing()
            });
        });
        
        // Load the dashboard with all resources
        await page.goto(`${this.baseUrl}/business/login`, { waitUntil: 'networkidle0' });
        await page.type('#email', this.credentials.email);
        await page.type('#password', this.credentials.password);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]')
        ]);
        
        await page.goto(`${this.baseUrl}/business/dashboard`, { waitUntil: 'networkidle0' });
        
        // Categorize resources
        const resourceSummary = {
            js: resources.filter(r => r.type === 'js'),
            css: resources.filter(r => r.type === 'css'),
            images: resources.filter(r => r.type === 'image'),
            fonts: resources.filter(r => r.type === 'font'),
            other: resources.filter(r => r.type === 'other')
        };
        
        this.results.resources = {
            total: resources.length,
            totalSize: resources.reduce((sum, r) => sum + r.size, 0),
            breakdown: Object.keys(resourceSummary).map(type => ({
                type,
                count: resourceSummary[type].length,
                totalSize: resourceSummary[type].reduce((sum, r) => sum + r.size, 0),
                avgSize: resourceSummary[type].reduce((sum, r) => sum + r.size, 0) / resourceSummary[type].length || 0
            })),
            resources
        };
    }

    calculateStatistics(data, key) {
        const values = data.filter(item => item.success !== false).map(item => item[key]).filter(v => v);
        
        if (values.length === 0) return null;
        
        values.sort((a, b) => a - b);
        
        return {
            count: values.length,
            min: values[0],
            max: values[values.length - 1],
            avg: values.reduce((sum, val) => sum + val, 0) / values.length,
            median: values[Math.floor(values.length / 2)],
            p95: values[Math.floor(values.length * 0.95)],
            p99: values[Math.floor(values.length * 0.99)]
        };
    }

    generateReport() {
        const report = {
            metadata: {
                baseUrl: this.baseUrl,
                iterations: this.iterations,
                timestamp: new Date().toISOString(),
                userAgent: 'Performance Benchmark Tool'
            },
            
            summary: {
                login: {
                    pageLoadTime: this.calculateStatistics(this.results.login, 'loginPageLoadTime'),
                    formSubmissionTime: this.calculateStatistics(this.results.login, 'formSubmissionTime'),
                    totalLoginTime: this.calculateStatistics(this.results.login, 'totalLoginTime')
                },
                
                dashboard: {
                    loadTime: this.calculateStatistics(this.results.dashboard, 'dashboardLoadTime'),
                    hydrationTime: this.calculateStatistics(this.results.dashboard, 'hydrationTime'),
                    apiResponseTime: this.calculateStatistics(this.results.dashboard, 'apiResponseTime'),
                    totalTime: this.calculateStatistics(this.results.dashboard, 'totalTime')
                },
                
                resources: this.results.resources
            },
            
            industryComparison: this.compareWithIndustryStandards(),
            
            recommendations: this.generateRecommendations(),
            
            rawData: this.results
        };
        
        return report;
    }

    compareWithIndustryStandards() {
        const standards = {
            pageLoad: { good: 1000, poor: 3000 }, // ms
            apiResponse: { good: 200, poor: 1000 }, // ms
            FCP: { good: 1800, poor: 3000 }, // ms
            LCP: { good: 2500, poor: 4000 }, // ms
            TTI: { good: 3800, poor: 7300 }, // ms
            bundleSize: { good: 200000, poor: 1000000 } // bytes
        };
        
        const summary = this.generateReport().summary;
        const comparison = {};
        
        if (summary.login.totalLoginTime) {
            comparison.loginPerformance = this.categorizePerformance(
                summary.login.totalLoginTime.avg, 
                standards.pageLoad
            );
        }
        
        if (summary.dashboard.totalTime) {
            comparison.dashboardPerformance = this.categorizePerformance(
                summary.dashboard.totalTime.avg, 
                standards.pageLoad
            );
        }
        
        if (summary.dashboard.apiResponseTime) {
            comparison.apiPerformance = this.categorizePerformance(
                summary.dashboard.apiResponseTime.avg, 
                standards.apiResponse
            );
        }
        
        return comparison;
    }

    categorizePerformance(value, standard) {
        if (value <= standard.good) return 'EXCELLENT';
        if (value <= standard.poor) return 'GOOD';
        return 'NEEDS_IMPROVEMENT';
    }

    generateRecommendations() {
        const recommendations = [];
        const summary = this.generateReport().summary;
        
        // Login performance recommendations
        if (summary.login.totalLoginTime && summary.login.totalLoginTime.avg > 2000) {
            recommendations.push({
                category: 'Login Performance',
                priority: 'HIGH',
                issue: 'Login process is slow',
                suggestion: 'Optimize login form validation and reduce server response time',
                impact: 'Users may abandon login process'
            });
        }
        
        // Dashboard loading recommendations
        if (summary.dashboard.hydrationTime && summary.dashboard.hydrationTime.avg > 3000) {
            recommendations.push({
                category: 'Dashboard Loading',
                priority: 'HIGH',
                issue: 'React hydration is slow',
                suggestion: 'Implement code splitting and lazy loading for dashboard components',
                impact: 'Poor user experience with loading states'
            });
        }
        
        // API response recommendations
        if (summary.dashboard.apiResponseTime && summary.dashboard.apiResponseTime.avg > 500) {
            recommendations.push({
                category: 'API Performance',
                priority: 'MEDIUM',
                issue: 'Dashboard API calls are slow',
                suggestion: 'Implement caching, optimize database queries, or use pagination',
                impact: 'Dashboard appears slow to load content'
            });
        }
        
        // Resource loading recommendations
        if (summary.resources && summary.resources.totalSize > 1000000) {
            recommendations.push({
                category: 'Resource Optimization',
                priority: 'MEDIUM',
                issue: 'Large bundle size',
                suggestion: 'Enable compression, optimize images, implement tree shaking',
                impact: 'Slow initial page loads, especially on mobile'
            });
        }
        
        return recommendations;
    }

    async run() {
        console.log('🚀 Starting Business Portal Performance Benchmark');
        console.log(`Base URL: ${this.baseUrl}`);
        console.log(`Iterations: ${this.iterations}`);
        console.log('');
        
        await this.initialize();
        
        try {
            await this.measureLoginFlow();
            await this.measureDashboardLoad();
            await this.measureResourceLoading();
            
            const report = this.generateReport();
            
            // Save report to file
            const reportPath = path.join(__dirname, `performance-report-${Date.now()}.json`);
            fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));
            
            console.log('');
            console.log('📊 PERFORMANCE BENCHMARK RESULTS');
            console.log('=====================================');
            
            // Login Performance
            if (report.summary.login.totalLoginTime) {
                console.log('🔐 LOGIN PERFORMANCE:');
                console.log(`  Average Total Login Time: ${Math.round(report.summary.login.totalLoginTime.avg)}ms`);
                console.log(`  95th Percentile: ${Math.round(report.summary.login.totalLoginTime.p95)}ms`);
                console.log(`  Page Load: ${Math.round(report.summary.login.pageLoadTime?.avg || 0)}ms`);
                console.log(`  Form Submit: ${Math.round(report.summary.login.formSubmissionTime?.avg || 0)}ms`);
                console.log('');
            }
            
            // Dashboard Performance
            if (report.summary.dashboard.totalTime) {
                console.log('📊 DASHBOARD PERFORMANCE:');
                console.log(`  Average Total Time: ${Math.round(report.summary.dashboard.totalTime.avg)}ms`);
                console.log(`  95th Percentile: ${Math.round(report.summary.dashboard.totalTime.p95)}ms`);
                console.log(`  Initial Load: ${Math.round(report.summary.dashboard.loadTime?.avg || 0)}ms`);
                console.log(`  React Hydration: ${Math.round(report.summary.dashboard.hydrationTime?.avg || 0)}ms`);
                console.log(`  API Calls: ${Math.round(report.summary.dashboard.apiResponseTime?.avg || 0)}ms`);
                console.log('');
            }
            
            // Resource Performance
            if (report.summary.resources) {
                console.log('📦 RESOURCE LOADING:');
                console.log(`  Total Resources: ${report.summary.resources.total}`);
                console.log(`  Total Size: ${Math.round(report.summary.resources.totalSize / 1024)}KB`);
                report.summary.resources.breakdown.forEach(resource => {
                    console.log(`  ${resource.type.toUpperCase()}: ${resource.count} files, ${Math.round(resource.totalSize / 1024)}KB`);
                });
                console.log('');
            }
            
            // Industry Comparison
            console.log('🏭 INDUSTRY COMPARISON:');
            Object.entries(report.industryComparison).forEach(([key, value]) => {
                const status = value === 'EXCELLENT' ? '🟢' : value === 'GOOD' ? '🟡' : '🔴';
                console.log(`  ${key}: ${status} ${value}`);
            });
            console.log('');
            
            // Recommendations
            if (report.recommendations.length > 0) {
                console.log('💡 PERFORMANCE RECOMMENDATIONS:');
                report.recommendations.forEach((rec, index) => {
                    const priority = rec.priority === 'HIGH' ? '🔴' : '🟡';
                    console.log(`  ${index + 1}. ${priority} ${rec.category}: ${rec.suggestion}`);
                });
                console.log('');
            }
            
            console.log(`📄 Full report saved to: ${reportPath}`);
            
            return report;
            
        } finally {
            if (this.browser) {
                await this.browser.close();
            }
        }
    }
}

// CLI Usage
if (require.main === module) {
    const args = process.argv.slice(2);
    const options = {};
    
    // Parse command line arguments
    for (let i = 0; i < args.length; i += 2) {
        const key = args[i].replace('--', '');
        const value = args[i + 1];
        
        if (key === 'iterations') options.iterations = parseInt(value);
        if (key === 'url') options.baseUrl = value;
        if (key === 'email') options.credentials = { ...options.credentials, email: value };
        if (key === 'password') options.credentials = { ...options.credentials, password: value };
    }
    
    const benchmark = new PerformanceBenchmark(options);
    
    benchmark.run().then(() => {
        console.log('✅ Benchmark completed successfully');
        process.exit(0);
    }).catch(error => {
        console.error('❌ Benchmark failed:', error);
        process.exit(1);
    });
}

module.exports = PerformanceBenchmark;