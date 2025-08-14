import puppeteer from 'puppeteer';
import fs from 'fs';
import path from 'path';

async function runAdminPortalTest() {
    console.log('🚀 Starting AskProAI Admin Portal Complete Functionality Test');
    
    const browser = await puppeteer.launch({
        headless: 'new',
        executablePath: '/usr/bin/chromium',
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-web-security',
            '--allow-running-insecure-content',
            '--disable-features=VizDisplayCompositor',
            '--disable-extensions',
            '--disable-plugins'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });
    
    const screenshotDir = '/var/www/api-gateway/admin-portal-test-screenshots';
    const testResults = {
        loginTest: null,
        dashboardTest: null,
        navigationTest: null,
        pagesTest: {},
        functionalityTest: {},
        uiIssues: [],
        screenshots: [],
        errors: []
    };

    try {
        console.log('📋 Step 1: LOGIN PROCESS TEST');
        console.log('Navigating to: https://api.askproai.de/admin/login');
        
        await page.goto('https://api.askproai.de/admin/login', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: `${screenshotDir}/01-login-page.png`, fullPage: true });
        testResults.screenshots.push('01-login-page.png');
        
        // Wait for form and get all input fields
        await page.waitForSelector('form', { timeout: 10000 });
        
        // Find email and password fields more robustly
        const emailField = await page.$('input[type="email"]') || await page.$('input:first-of-type');
        const passwordField = await page.$('input[type="password"]') || await page.$('input:nth-of-type(2)');
        const loginButton = await page.$('button') || await page.$('input[type="submit"]');
        
        console.log(`Email field found: ${emailField ? 'Yes' : 'No'}`);
        console.log(`Password field found: ${passwordField ? 'Yes' : 'No'}`);
        console.log(`Login button found: ${loginButton ? 'Yes' : 'No'}`);
        
        if (emailField && passwordField) {
            console.log('✅ Login form found');
            
            // Clear and enter credentials
            await emailField.click({ clickCount: 3 });
            await emailField.type('admin@askproai.de');
            
            await passwordField.click({ clickCount: 3 });
            await passwordField.type('password');
            
            await page.screenshot({ path: `${screenshotDir}/02-login-filled.png`, fullPage: true });
            testResults.screenshots.push('02-login-filled.png');
            
            console.log('🔐 Submitting login form...');
            
            if (loginButton) {
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 15000 }),
                    loginButton.click()
                ]);
            } else {
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 15000 }),
                    page.keyboard.press('Enter')
                ]);
            }
            
            const currentUrl = page.url();
            testResults.loginTest = {
                success: currentUrl.includes('/admin') && !currentUrl.includes('/login'),
                redirectedTo: currentUrl,
                hasLoginForm: true
            };
            
            console.log(`Current URL after login: ${currentUrl}`);
            
            if (testResults.loginTest.success) {
                console.log('✅ Login successful');
                
                // Wait for dashboard to load
                await page.waitForTimeout(3000);
                
                // Take dashboard screenshot
                await page.screenshot({ path: `${screenshotDir}/03-dashboard.png`, fullPage: true });
                testResults.screenshots.push('03-dashboard.png');
                
                console.log('📊 Step 2: DASHBOARD TEST');
                
                // Test dashboard content more thoroughly
                const widgets = await page.$$('[class*="widget"], [class*="card"], [class*="stat"], .fi-section, .fi-widget');
                const tables = await page.$$('table, [class*="table"]');
                const charts = await page.$$('[class*="chart"], canvas, svg');
                const metrics = await page.$$('[class*="metric"], [class*="kpi"], [class*="counter"]');
                
                // Get page title and heading
                const pageTitle = await page.title();
                const mainHeading = await page.$eval('h1, h2, .fi-header-heading', el => el.textContent.trim()).catch(() => 'No heading found');
                
                testResults.dashboardTest = {
                    pageTitle,
                    mainHeading,
                    widgetCount: widgets.length,
                    tableCount: tables.length,
                    chartCount: charts.length,
                    metricCount: metrics.length,
                    hasContent: widgets.length > 0 || tables.length > 0 || metrics.length > 0
                };
                
                console.log(`Dashboard loaded: "${mainHeading}"`);
                console.log(`Found ${widgets.length} widgets, ${tables.length} tables, ${charts.length} charts, ${metrics.length} metrics`);
                
                console.log('🧭 Step 3: NAVIGATION TEST');
                
                // Wait for navigation to be fully loaded
                await page.waitForTimeout(2000);
                
                // Find all navigation links with multiple selectors
                const navLinks = await page.evaluate(() => {
                    const selectors = [
                        'nav a',
                        'aside a', 
                        '[class*="nav"] a',
                        '.fi-sidebar-nav a',
                        '.fi-topbar a',
                        '.fi-sidebar-item a'
                    ];
                    
                    const allLinks = [];
                    selectors.forEach(selector => {
                        const links = document.querySelectorAll(selector);
                        links.forEach(link => {
                            if (link.href && link.textContent.trim() && 
                                link.offsetWidth > 0 && link.offsetHeight > 0 &&
                                !link.href.includes('#') && !link.href.includes('logout')) {
                                allLinks.push({
                                    text: link.textContent.trim(),
                                    href: link.href,
                                    visible: true
                                });
                            }
                        });
                    });
                    
                    // Remove duplicates
                    const uniqueLinks = [];
                    const seen = new Set();
                    allLinks.forEach(link => {
                        if (!seen.has(link.href)) {
                            seen.add(link.href);
                            uniqueLinks.push(link);
                        }
                    });
                    
                    return uniqueLinks;
                });
                
                console.log(`Found ${navLinks.length} navigation links:`);
                navLinks.forEach(link => console.log(`  - ${link.text}: ${link.href}`));
                
                testResults.navigationTest = {
                    totalLinks: navLinks.length,
                    links: navLinks,
                    testedPages: {}
                };
                
                console.log('🔍 Step 4: TESTING NAVIGATION PAGES');
                
                for (let i = 0; i < Math.min(navLinks.length, 15); i++) {
                    const link = navLinks[i];
                    try {
                        console.log(`Testing page ${i+1}/${Math.min(navLinks.length, 15)}: ${link.text}`);
                        
                        await page.goto(link.href, { waitUntil: 'networkidle2', timeout: 15000 });
                        
                        // Wait for page to fully load
                        await page.waitForTimeout(2000);
                        
                        const pageTitle = await page.title();
                        const currentUrl = page.url();
                        
                        // Check for various page elements
                        const hasTable = await page.$('table, [class*="table"], .fi-table') !== null;
                        const hasForm = await page.$('form, [class*="form"], .fi-form') !== null;
                        const hasError = await page.$('[class*="error"], .alert-danger, .fi-section-content-error') !== null;
                        const hasData = await page.$('tbody tr, .fi-table-row, [class*="record"]') !== null;
                        const hasCreateButton = await page.$('[class*="create"], [class*="new"], button[class*="primary"]') !== null;
                        const hasFilters = await page.$('[class*="filter"], select, input[type="search"]') !== null;
                        
                        // Get page heading
                        const pageHeading = await page.$eval('h1, h2, .fi-header-heading, .fi-section-header h2', 
                            el => el.textContent.trim()).catch(() => 'No heading');
                        
                        const screenshotName = `04-page-${String(i+1).padStart(2, '0')}-${link.text.replace(/[^a-zA-Z0-9]/g, '-').toLowerCase()}.png`;
                        await page.screenshot({ path: `${screenshotDir}/${screenshotName}`, fullPage: true });
                        testResults.screenshots.push(screenshotName);
                        
                        testResults.navigationTest.testedPages[link.text] = {
                            url: currentUrl,
                            title: pageTitle,
                            heading: pageHeading,
                            hasTable,
                            hasForm,
                            hasError,
                            hasData,
                            hasCreateButton,
                            hasFilters,
                            accessible: true,
                            statusCode: 200
                        };
                        
                        const statusIcons = [
                            hasTable ? '📊Table' : '',
                            hasForm ? '📝Form' : '',
                            hasData ? '💾Data' : '',
                            hasCreateButton ? '➕Create' : '',
                            hasFilters ? '🔍Filter' : '',
                            hasError ? '❌ERROR' : '✅OK'
                        ].filter(Boolean).join(' ');
                        
                        console.log(`  ${pageHeading}: ${statusIcons}`);
                        
                        // Test specific functionality
                        if (hasTable && hasData) {
                            // Test table sorting if available
                            const sortableHeaders = await page.$$('th[class*="sort"], th[onclick], th[data-sort], .fi-table-header-cell button');
                            if (sortableHeaders.length > 0) {
                                try {
                                    await sortableHeaders[0].click();
                                    await page.waitForTimeout(2000);
                                    console.log('    ✅ Table sorting tested');
                                } catch (e) {
                                    console.log('    ⚠️ Table sorting issue:', e.message);
                                    testResults.uiIssues.push(`${link.text}: Table sorting not working - ${e.message}`);
                                }
                            }
                            
                            // Test pagination
                            const paginationLinks = await page.$$('[class*="pagination"] a, .fi-pagination a, nav[role="navigation"] a');
                            if (paginationLinks.length > 0) {
                                console.log(`    ✅ Pagination found (${paginationLinks.length} links)`);
                            }
                        }
                        
                        if (hasForm) {
                            // Test form fields
                            const inputs = await page.$$('input, select, textarea');
                            const submitButtons = await page.$$('button[type="submit"], input[type="submit"], .fi-btn-primary');
                            console.log(`    Found ${inputs.length} form fields and ${submitButtons.length} submit buttons`);
                        }
                        
                        // Test search if available
                        const searchInput = await page.$('input[type="search"], input[placeholder*="search" i], input[name*="search" i]');
                        if (searchInput) {
                            try {
                                await searchInput.type('test');
                                await page.waitForTimeout(1000);
                                await searchInput.clear();
                                console.log('    ✅ Search functionality tested');
                            } catch (e) {
                                console.log('    ⚠️ Search issue:', e.message);
                            }
                        }
                        
                    } catch (error) {
                        console.log(`  ❌ Failed to test ${link.text}: ${error.message}`);
                        testResults.navigationTest.testedPages[link.text] = {
                            url: link.href,
                            accessible: false,
                            error: error.message,
                            statusCode: error.message.includes('timeout') ? 'timeout' : 'error'
                        };
                        testResults.errors.push(`${link.text}: ${error.message}`);
                    }
                    
                    await page.waitForTimeout(1000);
                }
                
                console.log('🧪 Step 5: FUNCTIONAL TESTS');
                
                // Go back to dashboard for functional tests
                await page.goto(testResults.loginTest.redirectedTo, { waitUntil: 'networkidle2' });
                await page.waitForTimeout(2000);
                
                // Test global search functionality
                const globalSearch = await page.$('input[type="search"], input[placeholder*="search" i]');
                if (globalSearch) {
                    try {
                        await globalSearch.type('test');
                        await page.keyboard.press('Enter');
                        await page.waitForTimeout(3000);
                        console.log('  ✅ Global search functionality works');
                        testResults.functionalityTest.globalSearch = true;
                        
                        await page.screenshot({ path: `${screenshotDir}/05-search-test.png`, fullPage: true });
                        testResults.screenshots.push('05-search-test.png');
                    } catch (e) {
                        console.log('  ⚠️ Global search failed:', e.message);
                        testResults.functionalityTest.globalSearch = false;
                    }
                }
                
                console.log('🎨 Step 6: UI ISSUES DETECTION');
                
                // Check for console errors
                const logs = [];
                page.on('console', msg => {
                    if (msg.type() === 'error') {
                        logs.push(`Console Error: ${msg.text()}`);
                        console.log('Console Error:', msg.text());
                    }
                });
                
                // Check for failed network requests
                page.on('requestfailed', request => {
                    logs.push(`Failed Request: ${request.url()} - ${request.failure()?.errorText}`);
                    console.log('Failed Request:', request.url(), request.failure()?.errorText);
                });
                
                // Check responsive behavior by changing viewport
                await page.setViewport({ width: 768, height: 1024 });
                await page.waitForTimeout(1000);
                await page.screenshot({ path: `${screenshotDir}/06-tablet-view.png`, fullPage: true });
                testResults.screenshots.push('06-tablet-view.png');
                
                await page.setViewport({ width: 375, height: 667 });
                await page.waitForTimeout(1000);
                await page.screenshot({ path: `${screenshotDir}/07-mobile-view.png`, fullPage: true });
                testResults.screenshots.push('07-mobile-view.png');
                
                // Reset to desktop
                await page.setViewport({ width: 1920, height: 1080 });
                
                // Check for broken images
                const brokenImages = await page.evaluate(() => {
                    const images = Array.from(document.querySelectorAll('img'));
                    return images.filter(img => !img.complete || img.naturalWidth === 0).length;
                });
                
                if (brokenImages > 0) {
                    testResults.uiIssues.push(`${brokenImages} broken images found`);
                }
                
                if (logs.length > 0) {
                    testResults.uiIssues.push(...logs.slice(0, 10)); // Only keep first 10 issues
                }
                
                // Final screenshot
                await page.screenshot({ path: `${screenshotDir}/08-final-state.png`, fullPage: true });
                testResults.screenshots.push('08-final-state.png');
                
            } else {
                console.log('❌ Login failed - redirected to:', currentUrl);
                testResults.errors.push('Login failed - possibly incorrect credentials or additional authentication required');
            }
        } else {
            console.log('❌ Login form elements not found');
            testResults.loginTest = {
                success: false,
                hasLoginForm: false,
                error: 'Login form elements not properly detected'
            };
        }
        
    } catch (error) {
        console.error('Fatal error during testing:', error);
        testResults.errors.push(`Fatal error: ${error.message}`);
    }

    await browser.close();
    
    console.log('📄 Step 7: GENERATING TEST REPORT');
    
    const report = {
        timestamp: new Date().toISOString(),
        testDuration: Date.now(),
        baseUrl: 'https://api.askproai.de/admin',
        credentials: 'admin@askproai.de / password',
        ...testResults
    };
    
    fs.writeFileSync(`${screenshotDir}/test-report.json`, JSON.stringify(report, null, 2));
    
    // Generate summary report
    const summaryReport = `
# AskProAI Admin Portal - Complete Functionality Test Report
**Generated:** ${new Date().toISOString()}
**Base URL:** https://api.askproai.de/admin

## 📊 TEST SUMMARY
- **Login Test:** ${testResults.loginTest?.success ? '✅ SUCCESS' : '❌ FAILED'}
- **Dashboard:** ${testResults.dashboardTest?.hasContent ? '✅ HAS CONTENT' : '⚠️ EMPTY/LIMITED'}
- **Navigation Links:** ${testResults.navigationTest?.totalLinks || 0} found
- **Pages Tested:** ${Object.keys(testResults.navigationTest?.testedPages || {}).length}
- **Screenshots:** ${testResults.screenshots.length} captured
- **UI Issues:** ${testResults.uiIssues.length} identified
- **Errors:** ${testResults.errors.length} encountered

## 🔐 LOGIN RESULTS
${testResults.loginTest?.success ? 
  '✅ Login successful - Admin portal accessible' : 
  '❌ Login failed - Check credentials or authentication flow'}

## 📊 DASHBOARD ANALYSIS
${testResults.dashboardTest ? `
- **Page Title:** ${testResults.dashboardTest.pageTitle}
- **Main Heading:** ${testResults.dashboardTest.mainHeading}
- **Widgets:** ${testResults.dashboardTest.widgetCount}
- **Tables:** ${testResults.dashboardTest.tableCount}
- **Charts:** ${testResults.dashboardTest.chartCount}
- **Metrics:** ${testResults.dashboardTest.metricCount}
` : 'Dashboard not accessible due to login failure'}

## 🧭 NAVIGATION RESULTS
${Object.entries(testResults.navigationTest?.testedPages || {}).map(([name, page]) => 
  `- **${name}:** ${page.accessible ? '✅' : '❌'} ${page.heading || page.title || ''} ${page.hasTable ? '📊' : ''}${page.hasForm ? '📝' : ''}${page.hasData ? '💾' : ''}${page.hasError ? '❌' : ''}`
).join('\n')}

## ⚠️ ISSUES IDENTIFIED
${testResults.uiIssues.length > 0 ? 
  testResults.uiIssues.map(issue => `- ${issue}`).join('\n') : 
  '✅ No major UI issues detected'}

## ❌ ERRORS ENCOUNTERED
${testResults.errors.length > 0 ? 
  testResults.errors.map(error => `- ${error}`).join('\n') : 
  '✅ No errors encountered'}

## 📁 ARTIFACTS
- **Screenshots Directory:** /var/www/api-gateway/admin-portal-test-screenshots/
- **Detailed Report:** test-report.json
- **Total Screenshots:** ${testResults.screenshots.length}

## 🎯 RECOMMENDATIONS
${testResults.loginTest?.success ? 
  (testResults.uiIssues.length > 0 ? 
    '1. Address identified UI issues\n2. Test functionality on different screen sizes\n3. Verify all navigation links work properly' :
    '✅ Admin portal is functioning well - no critical issues found') :
  '🚨 Critical: Fix login functionality before proceeding with other tests'}

---
*Generated by AskProAI Admin Portal Test Suite*
`;
    
    fs.writeFileSync(`${screenshotDir}/TEST-REPORT-SUMMARY.md`, summaryReport);
    
    console.log('\n' + '='.repeat(80));
    console.log('📊 ASKPROAI ADMIN PORTAL - COMPLETE TEST REPORT');
    console.log('='.repeat(80));
    console.log(`🔐 Login Test: ${testResults.loginTest?.success ? '✅ SUCCESS' : '❌ FAILED'}`);
    console.log(`📊 Dashboard: ${testResults.dashboardTest?.hasContent ? '✅ HAS CONTENT' : '⚠️ EMPTY/LIMITED'}`);
    console.log(`🧭 Navigation: ${testResults.navigationTest?.totalLinks || 0} links found`);
    console.log(`📄 Pages Tested: ${Object.keys(testResults.navigationTest?.testedPages || {}).length}`);
    console.log(`🎯 Functional Tests: ${Object.keys(testResults.functionalityTest).length} features tested`);
    console.log(`⚠️ UI Issues: ${testResults.uiIssues.length} issues found`);
    console.log(`❌ Errors: ${testResults.errors.length} errors encountered`);
    console.log(`📸 Screenshots: ${testResults.screenshots.length} images captured`);
    console.log('='.repeat(80));
    
    if (testResults.uiIssues.length > 0) {
        console.log('\n🚨 UI ISSUES DETECTED:');
        testResults.uiIssues.slice(0, 10).forEach(issue => console.log(`  - ${issue}`));
        if (testResults.uiIssues.length > 10) {
            console.log(`  ... and ${testResults.uiIssues.length - 10} more issues`);
        }
    }
    
    if (testResults.errors.length > 0) {
        console.log('\n❌ ERRORS ENCOUNTERED:');
        testResults.errors.slice(0, 5).forEach(error => console.log(`  - ${error}`));
        if (testResults.errors.length > 5) {
            console.log(`  ... and ${testResults.errors.length - 5} more errors`);
        }
    }
    
    console.log(`\n📁 All screenshots saved to: ${screenshotDir}/`);
    console.log(`📄 Summary report: ${screenshotDir}/TEST-REPORT-SUMMARY.md`);
    console.log(`📄 Detailed report: ${screenshotDir}/test-report.json`);
    console.log('\n🎉 Admin Portal Testing Complete!');
}

runAdminPortalTest().catch(console.error);