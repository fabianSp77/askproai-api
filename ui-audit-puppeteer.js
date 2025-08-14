import puppeteer from 'puppeteer';
import fs from 'fs';
import path from 'path';

const BASE_URL = 'https://api.askproai.de';
const CREDENTIALS = {
    email: 'fabian@askproai.de',
    password: 'password'
};

// Create screenshots directory
const screenshotDir = './ui-audit-screenshots';
if (!fs.existsSync(screenshotDir)) {
    fs.mkdirSync(screenshotDir, { recursive: true });
}

const viewports = [
    { name: 'desktop', width: 1920, height: 1080 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'mobile', width: 375, height: 667 }
];

async function auditUI() {
    console.log('🚀 Starting UI Audit for AskProAI Admin Panel...');
    
    const browser = await puppeteer.launch({
        headless: false,
        executablePath: '/usr/bin/chromium',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--single-process',
            '--disable-gpu'
        ]
    });

    const page = await browser.newPage();
    
    // Enable console monitoring
    page.on('console', msg => console.log('CONSOLE:', msg.text()));
    page.on('pageerror', err => console.log('PAGE ERROR:', err.message));
    
    const auditReport = {
        timestamp: new Date().toISOString(),
        issues: [],
        screenshots: [],
        consoleErrors: [],
        cssIssues: []
    };

    try {
        // Test each viewport
        for (const viewport of viewports) {
            console.log(`\n📱 Testing ${viewport.name} viewport (${viewport.width}x${viewport.height})`);
            
            await page.setViewport({
                width: viewport.width,
                height: viewport.height,
                deviceScaleFactor: 1
            });

            // Step 1: Navigate to emergency login
            console.log('📋 Step 1: Loading emergency login page...');
            await page.goto(BASE_URL + '/emergency-login.php', { 
                waitUntil: 'networkidle2',
                timeout: 30000 
            });
            
            // Wait for page to fully load
            await page.waitForTimeout(2000);
            
            // Take screenshot of login page
            const loginScreenshot = `${screenshotDir}/01-login-${viewport.name}.png`;
            await page.screenshot({ 
                path: loginScreenshot, 
                fullPage: true 
            });
            auditReport.screenshots.push(loginScreenshot);
            console.log(`✅ Screenshot saved: ${loginScreenshot}`);

            // Step 2: Login
            console.log('🔐 Step 2: Attempting login...');
            
            // Fill login form
            await page.waitForSelector('input[name="email"], input[type="email"]', { timeout: 10000 });
            await page.type('input[name="email"], input[type="email"]', CREDENTIALS.email);
            await page.type('input[name="password"], input[type="password"]', CREDENTIALS.password);
            
            // Submit form
            await page.click('button[type="submit"], input[type="submit"]');
            
            // Wait for redirect
            await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 });
            
            const currentUrl = page.url();
            console.log(`✅ Login successful, current URL: ${currentUrl}`);

            // Step 3: Take screenshot of dashboard
            console.log('📸 Step 3: Taking dashboard screenshot...');
            await page.waitForTimeout(3000); // Let everything load
            
            const dashboardScreenshot = `${screenshotDir}/02-dashboard-${viewport.name}.png`;
            await page.screenshot({ 
                path: dashboardScreenshot, 
                fullPage: true 
            });
            auditReport.screenshots.push(dashboardScreenshot);
            console.log(`✅ Screenshot saved: ${dashboardScreenshot}`);

            // Step 4: Analyze navigation structure
            console.log('🔍 Step 4: Analyzing navigation structure...');
            
            const navigationAnalysis = await page.evaluate(() => {
                const analysis = {
                    navigationElements: [],
                    overlappingIssues: [],
                    clickabilityIssues: [],
                    positioningProblems: []
                };
                
                // Find navigation elements
                const navSelectors = [
                    'nav', 
                    '.navigation', 
                    '.sidebar', 
                    '.menu', 
                    '[role="navigation"]',
                    '.nav-menu',
                    '.admin-nav'
                ];
                
                navSelectors.forEach(selector => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach((el, index) => {
                        const rect = el.getBoundingClientRect();
                        const styles = window.getComputedStyle(el);
                        
                        analysis.navigationElements.push({
                            selector: selector + '[' + index + ']',
                            position: {
                                x: rect.x,
                                y: rect.y,
                                width: rect.width,
                                height: rect.height
                            },
                            styles: {
                                position: styles.position,
                                zIndex: styles.zIndex,
                                display: styles.display,
                                visibility: styles.visibility,
                                overflow: styles.overflow,
                                pointerEvents: styles.pointerEvents
                            },
                            classes: el.className,
                            text: el.textContent ? el.textContent.substring(0, 100) : ''
                        });
                    });
                });
                
                // Check clickability
                const clickableElements = document.querySelectorAll('a, button, [role="button"]');
                clickableElements.forEach(el => {
                    const styles = window.getComputedStyle(el);
                    const rect = el.getBoundingClientRect();
                    
                    if (styles.pointerEvents === 'none' || 
                        styles.visibility === 'hidden' ||
                        styles.display === 'none' ||
                        rect.width === 0 || rect.height === 0) {
                        
                        analysis.clickabilityIssues.push({
                            element: el.tagName + '.' + el.className,
                            text: el.textContent ? el.textContent.substring(0, 50) : '',
                            issue: 'Not clickable',
                            styles: {
                                pointerEvents: styles.pointerEvents,
                                visibility: styles.visibility,
                                display: styles.display
                            }
                        });
                    }
                });
                
                return analysis;
            });
            
            console.log(`🔍 Found ${navigationAnalysis.navigationElements.length} navigation elements`);
            console.log(`⚠️ Found ${navigationAnalysis.overlappingIssues.length} overlapping issues`);
            console.log(`🚫 Found ${navigationAnalysis.clickabilityIssues.length} clickability issues`);

            // Store viewport-specific analysis
            auditReport.issues.push({
                type: 'Navigation Analysis',
                viewport: viewport.name,
                navigation: navigationAnalysis
            });
        }

        // Generate final report
        console.log('\n📋 Generating audit report...');
        const reportPath = `${screenshotDir}/ui-audit-report.json`;
        fs.writeFileSync(reportPath, JSON.stringify(auditReport, null, 2));
        console.log(`✅ Report saved: ${reportPath}`);

    } catch (error) {
        console.error('❌ Audit failed:', error);
        auditReport.issues.push({
            type: 'Fatal Error',
            error: error.message,
            stack: error.stack
        });
    } finally {
        await browser.close();
    }

    return auditReport;
}

// Run the audit
auditUI().then(report => {
    console.log('\n🎉 UI Audit Complete!');
    console.log(`📸 Screenshots saved in: ${screenshotDir}`);
    console.log(`📋 Total issues found: ${report.issues.length}`);
    console.log(`🖼️ Total screenshots: ${report.screenshots.length}`);
}).catch(console.error);
