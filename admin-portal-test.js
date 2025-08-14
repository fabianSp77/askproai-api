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
        
        // Check if login form exists
        await page.waitForSelector('form', { timeout: 10000 });
        const emailField = await page.$('input[type="email"], input[name="email"], input[name="data.email"]');
        const passwordField = await page.$('input[type="password"], input[name="password"], input[name="data.password"]');
        const loginButton = await page.$('button[type="submit"], button:contains("Anmelden")');
        
        if (emailField && passwordField && loginButton) {
            console.log('✅ Login form found');
            
            // Enter credentials
            await page.type('input[type="email"], input[name="email"], input[name="data.email"]', 'admin@askproai.de');
            await page.type('input[type="password"], input[name="password"], input[name="data.password"]', 'password');
            
            await page.screenshot({ path: `${screenshotDir}/02-login-filled.png`, fullPage: true });
            testResults.screenshots.push('02-login-filled.png');
            
            console.log('🔐 Submitting login form...');
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle2' }),
                page.click('button[type="submit"]')
            ]);
            
            const currentUrl = page.url();
            testResults.loginTest = {
                success: currentUrl.includes('/admin') && !currentUrl.includes('/login'),
                redirectedTo: currentUrl,
                hasLoginForm: true
            };
            
            console.log(`Current URL after login: ${currentUrl}`);
            
            if (testResults.loginTest.success) {
                console.log('✅ Login successful');
                
                // Take dashboard screenshot
                await page.screenshot({ path: `${screenshotDir}/03-dashboard.png`, fullPage: true });
                testResults.screenshots.push('03-dashboard.png');
                
                console.log('📊 Step 2: DASHBOARD TEST');
                
                // Test dashboard widgets and KPIs
                const widgets = await page.$$('[class*="widget"], [class*="card"], [class*="stat"]');
                const tables = await page.$$('table');
                const charts = await page.$$('[class*="chart"], canvas');
                
                testResults.dashboardTest = {
                    widgetCount: widgets.length,
                    tableCount: tables.length,
                    chartCount: charts.length,
                    hasContent: widgets.length > 0 || tables.length > 0
                };
                
                console.log(`Found ${widgets.length} widgets, ${tables.length} tables, ${charts.length} charts`);
                
                console.log('🧭 Step 3: NAVIGATION TEST');
                
                // Find all navigation links
                const navLinks = await page.$$eval('nav a, aside a, [class*="nav"] a', links => 
                    links.map(link => ({
                        text: link.textContent.trim(),
                        href: link.href,
                        visible: link.offsetWidth > 0 && link.offsetHeight > 0
                    })).filter(link => link.text && link.visible)
                );
                
                console.log(`Found ${navLinks.length} navigation links:`);
                navLinks.forEach(link => console.log(`  - ${link.text}: ${link.href}`));
                
                testResults.navigationTest = {
                    totalLinks: navLinks.length,
                    links: navLinks,
                    testedPages: {}
                };
                
                console.log('🔍 Step 4: TESTING NAVIGATION PAGES');
                
                for (let i = 0; i < Math.min(navLinks.length, 10); i++) {
                    const link = navLinks[i];
                    try {
                        console.log(`Testing page: ${link.text}`);
                        
                        await page.goto(link.href, { waitUntil: 'networkidle2', timeout: 10000 });
                        
                        const pageTitle = await page.title();
                        const hasTable = await page.$('table') !== null;
                        const hasForm = await page.$('form') !== null;
                        const hasError = await page.$('[class*="error"], .alert-danger') !== null;
                        
                        const screenshotName = `04-page-${i+1}-${link.text.replace(/[^a-zA-Z0-9]/g, '-').toLowerCase()}.png`;
                        await page.screenshot({ path: `${screenshotDir}/${screenshotName}`, fullPage: true });
                        testResults.screenshots.push(screenshotName);
                        
                        testResults.navigationTest.testedPages[link.text] = {
                            url: link.href,
                            title: pageTitle,
                            hasTable,
                            hasForm,
                            hasError,
                            accessible: true
                        };
                        
                        console.log(`  ✅ ${link.text}: ${hasTable ? 'Table' : ''} ${hasForm ? 'Form' : ''} ${hasError ? 'ERROR!' : ''}`);
                        
                        // Test specific functionality based on page type
                        if (hasTable) {
                            // Test table sorting
                            const sortableHeaders = await page.$$('th[class*="sortable"], th[onclick], th[data-sort]');
                            if (sortableHeaders.length > 0) {
                                try {
                                    await sortableHeaders[0].click();
                                    await page.waitForTimeout(1000);
                                    console.log('    ✅ Table sorting works');
                                } catch (e) {
                                    console.log('    ⚠️ Table sorting failed:', e.message);
                                    testResults.uiIssues.push(`${link.text}: Table sorting not working`);
                                }
                            }
                            
                            // Test pagination
                            const paginationLinks = await page.$$('[class*="pagination"] a, .paginator a');
                            if (paginationLinks.length > 0) {
                                console.log('    ✅ Pagination found');
                            }
                        }
                        
                        if (hasForm) {
                            // Test form fields
                            const inputs = await page.$$('input, select, textarea');
                            const submitButtons = await page.$$('button[type="submit"], input[type="submit"]');
                            console.log(`    Found ${inputs.length} form fields and ${submitButtons.length} submit buttons`);
                        }
                        
                    } catch (error) {
                        console.log(`  ❌ Failed to test ${link.text}: ${error.message}`);
                        testResults.navigationTest.testedPages[link.text] = {
                            url: link.href,
                            accessible: false,
                            error: error.message
                        };
                        testResults.errors.push(`${link.text}: ${error.message}`);
                    }
                    
                    await page.waitForTimeout(1000);
                }
                
                console.log('🧪 Step 5: FUNCTIONAL TESTS');
                
                // Test search functionality if available
                const searchInputs = await page.$$('input[type="search"], input[placeholder*="search" i], input[name*="search" i]');
                if (searchInputs.length > 0) {
                    try {
                        await searchInputs[0].type('test');
                        await page.keyboard.press('Enter');
                        await page.waitForTimeout(2000);
                        console.log('  ✅ Search functionality works');
                        testResults.functionalityTest.search = true;
                    } catch (e) {
                        console.log('  ⚠️ Search functionality failed:', e.message);
                        testResults.functionalityTest.search = false;
                    }
                }
                
                // Test filter functionality
                const filterSelects = await page.$$('select[name*="filter"], select[class*="filter"]');
                if (filterSelects.length > 0) {
                    try {
                        await filterSelects[0].select(await filterSelects[0].$eval('option:nth-child(2)', el => el.value));
                        await page.waitForTimeout(2000);
                        console.log('  ✅ Filter functionality works');
                        testResults.functionalityTest.filter = true;
                    } catch (e) {
                        console.log('  ⚠️ Filter functionality failed:', e.message);
                        testResults.functionalityTest.filter = false;
                    }
                }
                
                console.log('🎨 Step 6: UI ISSUES DETECTION');
                
                // Check for JavaScript errors
                const jsErrors = [];
                page.on('pageerror', error => {
                    jsErrors.push(error.message);
                    console.log('JS Error:', error.message);
                });
                
                // Check for broken images
                const brokenImages = await page.$$eval('img', imgs => 
                    imgs.filter(img => !img.complete || img.naturalWidth === 0).length
                );
                
                if (brokenImages > 0) {
                    testResults.uiIssues.push(`${brokenImages} broken images found`);
                }
                
                // Final screenshot
                await page.screenshot({ path: `${screenshotDir}/99-final-state.png`, fullPage: true });
                testResults.screenshots.push('99-final-state.png');
                
            } else {
                console.log('❌ Login failed');
                testResults.errors.push('Login failed - possibly incorrect credentials or captcha');
            }
        } else {
            console.log('❌ Login form not found');
            testResults.loginTest = {
                success: false,
                hasLoginForm: false,
                error: 'Login form not found'
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
        testDuration: 'Complete',
        ...testResults
    };
    
    fs.writeFileSync(`${screenshotDir}/test-report.json`, JSON.stringify(report, null, 2));
    
    console.log('\n' + '='.repeat(80));
    console.log('📊 ASKPROAI ADMIN PORTAL - COMPLETE TEST REPORT');
    console.log('='.repeat(80));
    console.log(`🔐 Login Test: ${testResults.loginTest?.success ? '✅ SUCCESS' : '❌ FAILED'}`);
    console.log(`📊 Dashboard: ${testResults.dashboardTest?.hasContent ? '✅ HAS CONTENT' : '⚠️ EMPTY'}`);
    console.log(`🧭 Navigation: ${testResults.navigationTest?.totalLinks || 0} links found`);
    console.log(`📄 Pages Tested: ${Object.keys(testResults.navigationTest?.testedPages || {}).length}`);
    console.log(`🎯 Functional Tests: ${Object.keys(testResults.functionalityTest).length} features tested`);
    console.log(`⚠️ UI Issues: ${testResults.uiIssues.length} issues found`);
    console.log(`❌ Errors: ${testResults.errors.length} errors encountered`);
    console.log(`📸 Screenshots: ${testResults.screenshots.length} images captured`);
    console.log('='.repeat(80));
    
    if (testResults.uiIssues.length > 0) {
        console.log('\n🚨 UI ISSUES DETECTED:');
        testResults.uiIssues.forEach(issue => console.log(`  - ${issue}`));
    }
    
    if (testResults.errors.length > 0) {
        console.log('\n❌ ERRORS ENCOUNTERED:');
        testResults.errors.forEach(error => console.log(`  - ${error}`));
    }
    
    console.log(`\n📁 All screenshots saved to: ${screenshotDir}/`);
    console.log(`📄 Detailed report: ${screenshotDir}/test-report.json`);
    console.log('\n🎉 Admin Portal Testing Complete!');
}

runAdminPortalTest().catch(console.error);